<?php
/**
 * Hugo Search Index Builder
 * Populates SQLite database from Hugo-generated search data
 */

class SearchIndexBuilder {
    private $dbPath;
    private $jsonPath;

    public function __construct($dbPath = 'search.db', $jsonPath = 'public/search-data/index.json') {
        $this->dbPath = $dbPath;
        $this->jsonPath = $jsonPath;
    }

    public function build() {
        try {
            echo "Building search index...\n";

            // Create/connect to SQLite database
            $pdo = new PDO("sqlite:" . $this->dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create search table
            $this->createTable($pdo);

            // Load and process search data
            $this->loadSearchData($pdo);

            echo "Search index built successfully!\n";
            echo "Database: " . $this->dbPath . "\n";
            echo "Records: " . $this->getRecordCount($pdo) . "\n";

        } catch (Exception $e) {
            echo "Error building search index: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function createTable($pdo) {
        // Drop existing tables if they exist
        $pdo->exec("DROP TABLE IF EXISTS search_fts");
        $pdo->exec("DROP TABLE IF EXISTS search_content");

        // Create main content table (for metadata)
        $pdo->exec("
            CREATE TABLE search_content (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                url TEXT NOT NULL,
                content TEXT,
                summary TEXT,
                date TEXT,
                section TEXT,
                tags TEXT,
                categories TEXT
            )
        ");

        // Create FTS5 virtual table with porter tokenizer for stemming
        // This enables advanced search features with minimal size overhead
        $pdo->exec("
            CREATE VIRTUAL TABLE search_fts USING fts5(
                title,
                content,
                summary,
                tags,
                categories,
                content='search_content',
                content_rowid='rowid',
                tokenize='porter'
            )
        ");

        // Create triggers to keep FTS in sync with content table
        $pdo->exec("
            CREATE TRIGGER search_content_ai AFTER INSERT ON search_content BEGIN
                INSERT INTO search_fts(rowid, title, content, summary, tags, categories)
                VALUES (new.rowid, new.title, new.content, new.summary, new.tags, new.categories);
            END
        ");

        $pdo->exec("
            CREATE TRIGGER search_content_ad AFTER DELETE ON search_content BEGIN
                DELETE FROM search_fts WHERE rowid = old.rowid;
            END
        ");

        $pdo->exec("
            CREATE TRIGGER search_content_au AFTER UPDATE ON search_content BEGIN
                UPDATE search_fts
                SET title = new.title,
                    content = new.content,
                    summary = new.summary,
                    tags = new.tags,
                    categories = new.categories
                WHERE rowid = new.rowid;
            END
        ");

        // Create indexes on frequently queried metadata columns
        $pdo->exec("CREATE INDEX idx_section ON search_content(section)");
        $pdo->exec("CREATE INDEX idx_date ON search_content(date)");

        // Composite index for queries that filter by section AND date together
        // This is more efficient than using two separate indexes
        $pdo->exec("CREATE INDEX idx_section_date ON search_content(section, date)");

        echo "Database tables created with FTS5 support.\n";
    }

    private function loadSearchData($pdo) {
        if (!file_exists($this->jsonPath)) {
            throw new Exception("Search data file not found: " . $this->jsonPath);
        }

        $jsonData = file_get_contents($this->jsonPath);
        if ($jsonData === false) {
            throw new Exception("Failed to read search data file: " . $this->jsonPath);
        }

        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg() . " at position " . json_last_error());
        }

        if (!is_array($data)) {
            throw new Exception("Expected JSON array, got " . gettype($data));
        }

        $stmt = $pdo->prepare("
            INSERT INTO search_content
            (id, title, url, content, summary, date, section, tags, categories)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;
        $errors = 0;
        foreach ($data as $index => $item) {
            try {
                // Sanitize and process tags and categories
                $itemTags = $item['tags'] ?? null;
                $itemCategories = $item['categories'] ?? null;

                // Ensure tags and categories are properly encoded as JSON arrays
                $tags = $this->sanitizeJsonField($itemTags);
                $categories = $this->sanitizeJsonField($itemCategories);

                // Sanitize text fields to remove invalid UTF-8 and control characters
                $id = $this->sanitizeText($item['id'] ?? '');
                $title = $this->sanitizeText($item['title'] ?? '');
                $url = $item['href'] ?? $item['url'] ?? '';  // Hugo uses 'href', fallback to 'url'
                $content = $this->sanitizeText($item['content'] ?? '');
                $summary = $this->sanitizeText($item['summary'] ?? '');
                $date = $this->sanitizeText($item['date'] ?? '');
                $section = $this->sanitizeText($item['section'] ?? '');

                // Validate date format if provided
                if (!empty($date) && !$this->isValidDate($date)) {
                    echo "Warning: Invalid date format '$date' for item '$title', using empty date\n";
                    $date = '';
                }

                $stmt->execute([
                    $id,
                    $title,
                    $url,
                    $content,
                    $summary,
                    $date,
                    $section,
                    $tags,
                    $categories
                ]);
                $count++;
            } catch (Exception $e) {
                $errors++;
                $itemTitle = $item['title'] ?? 'Unknown';
                echo "Warning: Failed to insert item #$index ('$itemTitle'): " . $e->getMessage() . "\n";
                // Continue processing other items
            }
        }

        if ($errors > 0) {
            echo "WARNING: $errors items failed to import. Check the warnings above.\n";
        }

        // Optimize FTS5 index for better compression and performance
        $pdo->exec("INSERT INTO search_fts(search_fts) VALUES('optimize')");

        // Run ANALYZE to update query planner statistics
        // This helps SQLite choose the most efficient query execution plans
        $pdo->exec("ANALYZE");

        // Run VACUUM to reclaim unused space and defragment the database
        // This reduces file size and improves I/O performance
        $pdo->exec("VACUUM");

        echo "Loaded $count records successfully.\n";
        echo "Database optimized (ANALYZE + VACUUM completed).\n";

        // Verify database integrity
        $this->verifyDatabaseIntegrity($pdo);
    }

    /**
     * Sanitize text fields to remove invalid UTF-8, control characters, and null bytes
     */
    private function sanitizeText($text) {
        if (!is_string($text)) {
            return '';
        }

        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Remove or replace control characters (except newlines, tabs, carriage returns)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Ensure valid UTF-8 encoding
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to convert from other encodings to UTF-8
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        // Final cleanup: remove any remaining invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return $text;
    }

    /**
     * Sanitize and normalize JSON fields (tags, categories)
     */
    private function sanitizeJsonField($value) {
        if ($value === null) {
            return '[]';
        }

        // If it's already an array, encode it
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // If it's a string, check if it's already valid JSON
        if (is_string($value)) {
            // Try to decode it to validate
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // It's valid JSON, re-encode to ensure consistent format
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            // If it's a non-empty string but not valid JSON, treat as single item
            if (!empty($value)) {
                return json_encode([$value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        // Default to empty array
        return '[]';
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    private function isValidDate($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        // Validate it's an actual date
        $parts = explode('-', $date);
        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    /**
     * Verify database integrity after build
     */
    private function verifyDatabaseIntegrity($pdo) {
        echo "Verifying database integrity...\n";

        // Check for corrupted data
        $stmt = $pdo->query("PRAGMA integrity_check");
        $result = $stmt->fetchColumn();
        if ($result !== 'ok') {
            throw new Exception("Database integrity check failed: $result");
        }

        // Test JSON decoding on all tags and categories
        $stmt = $pdo->query("SELECT id, title, tags, categories FROM search_content");
        $invalidCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tagsDecoded = json_decode($row['tags'], true);
            $categoriesDecoded = json_decode($row['categories'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $invalidCount++;
                echo "Warning: Invalid JSON in record '{$row['title']}' (ID: {$row['id']})\n";
            }
        }

        if ($invalidCount > 0) {
            echo "WARNING: Found $invalidCount records with invalid JSON data.\n";
        } else {
            echo "Database integrity verified: All records have valid JSON.\n";
        }
    }

    private function getRecordCount($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM search_content");
        return $stmt->fetchColumn();
    }
}

// Run the builder
$builder = new SearchIndexBuilder(
    isset($argv[1]) ? $argv[1] : 'search.db',
    isset($argv[2]) ? $argv[2] : 'public/search-data/index.json'
);

$builder->build();
?>
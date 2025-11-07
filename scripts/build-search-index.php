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
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }

        $stmt = $pdo->prepare("
            INSERT INTO search_content
            (id, title, url, content, summary, date, section, tags, categories)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;
        foreach ($data as $item) {
            // Process tags and categories - check if they exist first
            $itemTags = $item['tags'] ?? null;
            $itemCategories = $item['categories'] ?? null;

            $tags = is_array($itemTags) ? json_encode($itemTags) : (is_string($itemTags) ? $itemTags : '');
            $categories = is_array($itemCategories) ? json_encode($itemCategories) : (is_string($itemCategories) ? $itemCategories : '');

            $stmt->execute([
                $item['id'] ?? '',
                $item['title'] ?? '',
                $item['href'] ?? $item['url'] ?? '',  // Hugo uses 'href', fallback to 'url'
                $item['content'] ?? '',
                $item['summary'] ?? '',
                $item['date'] ?? '',
                $item['section'] ?? '',
                $tags,
                $categories
            ]);
            $count++;
        }

        // Optimize FTS5 index for better compression and performance
        $pdo->exec("INSERT INTO search_fts(search_fts) VALUES('optimize')");

        // Run ANALYZE to update query planner statistics
        // This helps SQLite choose the most efficient query execution plans
        $pdo->exec("ANALYZE");

        // Run VACUUM to reclaim unused space and defragment the database
        // This reduces file size and improves I/O performance
        $pdo->exec("VACUUM");

        echo "Loaded $count records.\n";
        echo "Database optimized (ANALYZE + VACUUM completed).\n";
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
<?php
/**
 * Hugo Search API
 * Fast search endpoint using SQLite database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

class SearchAPI {
    private $dbPath;
    private $resultsPerPage = 20;
    private $maxResults = 100;

    public function __construct($dbPath = '../search.db') {
        $this->dbPath = $dbPath;
    }

    public function search() {
        try {
            $query = trim($_GET['q'] ?? '');
            $page = max(1, intval($_GET['page'] ?? 1));
            $section = trim($_GET['section'] ?? '');
            $sort = trim($_GET['sort'] ?? 'relevance');
            $limit = min($this->resultsPerPage, intval($_GET['limit'] ?? $this->resultsPerPage));

            if (empty($query) || strlen($query) < 2) {
                $this->sendResponse([
                    'results' => [],
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $limit,
                    'query' => $query
                ]);
                return;
            }

            $pdo = new PDO("sqlite:" . $this->dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $results = $this->performSearch($pdo, $query, $page, $limit, $section, $sort);
            $this->sendResponse($results);

        } catch (Exception $e) {
            $this->sendError('Search error: ' . $e->getMessage());
        }
    }

    private function performSearch($pdo, $query, $page, $limit, $section, $sort = 'relevance') {
        // Parse the query for special operators
        $parsedQuery = $this->parseQuery($query);

        // Build FTS5 query
        $ftsQuery = $this->buildFTS5Query($parsedQuery);

        if (empty($ftsQuery)) {
            return [
                'results' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $limit,
                'query' => $query
            ];
        }

        $params = [];

        // Base SQL using FTS5 with BM25 ranking
        $sql = "
            SELECT
                c.id, c.title, c.url, c.summary, c.date, c.section, c.tags, c.categories,
                bm25(f.search_fts) as relevance,
                snippet(f.search_fts, 1, '<mark>', '</mark>', '...', 32) as content_snippet
            FROM search_content c
            JOIN search_fts f ON c.rowid = f.rowid
            WHERE f.search_fts MATCH :fts_query
        ";
        $params['fts_query'] = $ftsQuery;

        // Add section filter
        if (!empty($section)) {
            $sql .= " AND c.section = :section";
            $params['section'] = $section;
        }

        // Add date range filters
        if (!empty($parsedQuery['after'])) {
            $sql .= " AND c.date >= :after";
            $params['after'] = $parsedQuery['after'];
        }
        if (!empty($parsedQuery['before'])) {
            $sql .= " AND c.date <= :before";
            $params['before'] = $parsedQuery['before'];
        }

        // Count total results (build separate count query to avoid bm25 issues)
        $countSql = "
            SELECT COUNT(*)
            FROM search_content c
            JOIN search_fts f ON c.rowid = f.rowid
            WHERE f.search_fts MATCH :fts_query
        ";

        if (!empty($section)) {
            $countSql .= " AND c.section = :section";
        }
        if (!empty($parsedQuery['after'])) {
            $countSql .= " AND c.date >= :after";
        }
        if (!empty($parsedQuery['before'])) {
            $countSql .= " AND c.date <= :before";
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = intval($countStmt->fetchColumn());

        // Build ORDER BY clause based on sort parameter
        $validSorts = ['relevance', 'date_desc', 'date_asc'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'relevance';
        }

        switch ($sort) {
            case 'date_desc':
                $sql .= " ORDER BY c.date DESC, bm25(f.search_fts)";
                break;
            case 'date_asc':
                $sql .= " ORDER BY c.date ASC, bm25(f.search_fts)";
                break;
            case 'relevance':
            default:
                // Order by relevance with title boost and recency
                $sql .= " ORDER BY
                            CASE
                                WHEN c.title LIKE :title_boost THEN 1
                                ELSE 2
                            END,
                            bm25(f.search_fts),
                            c.date DESC";
                $params['title_boost'] = !empty($parsedQuery['terms']) ? "%{$parsedQuery['terms'][0]}%" : "%";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $params['limit'] = min($limit, $this->maxResults);
        $params['offset'] = ($page - 1) * $limit;

        $stmt = $pdo->prepare($sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process results
        foreach ($results as &$result) {
            // Safely decode tags and categories with error handling
            $result['tags'] = $this->safeJsonDecode($result['tags'] ?? '[]', $result['title']);
            $result['categories'] = $this->safeJsonDecode($result['categories'] ?? '[]', $result['title']);

            // Highlight search terms in title and summary
            $result['title_highlighted'] = $this->highlightTerms($result['title'], $parsedQuery['terms']);
            $result['summary_highlighted'] = $this->highlightTerms($result['summary'], $parsedQuery['terms']);

            // Add relevance score (normalized)
            $result['relevance_score'] = round(abs($result['relevance']), 2);
        }

        return [
            'results' => $results,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $limit,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
            'query' => $query,
            'parsed_query' => $parsedQuery,
            'fts_query' => $ftsQuery
        ];
    }

    private function parseQuery($query) {
        $result = [
            'terms' => [],
            'phrases' => [],
            'field_searches' => [],
            'after' => null,
            'before' => null,
            'operators' => []
        ];

        // Extract quoted phrases
        if (preg_match_all('/"([^"]+)"/', $query, $matches)) {
            $result['phrases'] = $matches[1];
            $query = preg_replace('/"[^"]+"/', '', $query);
        }

        // Extract date filters: after:YYYY-MM-DD or before:YYYY-MM-DD
        if (preg_match('/after:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            if ($this->isValidDate($matches[1])) {
                $result['after'] = $matches[1];
            }
            $query = str_replace($matches[0], '', $query);
        }
        if (preg_match('/before:(\d{4}-\d{2}-\d{2})/', $query, $matches)) {
            if ($this->isValidDate($matches[1])) {
                $result['before'] = $matches[1];
            }
            $query = str_replace($matches[0], '', $query);
        }

        // Extract field-specific searches: field:term
        if (preg_match_all('/(title|tags|categories|content|summary):(\S+)/', $query, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result['field_searches'][] = [
                    'field' => $match[1],
                    'term' => $match[2]
                ];
                $query = str_replace($match[0], '', $query);
            }
        }

        // Extract remaining terms and operators
        $words = preg_split('/\s+/', trim($query));
        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            if (in_array(strtoupper($word), ['AND', 'OR', 'NOT'])) {
                $result['operators'][] = strtoupper($word);
            } elseif (strlen($word) >= 2) {
                $result['terms'][] = $word;
            }
        }

        return $result;
    }

    private function buildFTS5Query($parsed) {
        $parts = [];

        // Add field-specific searches
        foreach ($parsed['field_searches'] as $fs) {
            $parts[] = "{$fs['field']}:" . $this->escapeFTS5($fs['term']);
        }

        // Add phrases (exact match)
        foreach ($parsed['phrases'] as $phrase) {
            $parts[] = '"' . str_replace('"', '""', $phrase) . '"';
        }

        // Add regular terms
        if (!empty($parsed['terms'])) {
            // Check for explicit operators in the query
            $hasExplicitOr = in_array('OR', $parsed['operators']);

            if ($hasExplicitOr) {
                // If OR is present, use it between terms
                $termParts = [];
                foreach ($parsed['terms'] as $term) {
                    $termParts[] = $this->escapeFTS5($term);
                }
                $parts[] = '(' . implode(' OR ', $termParts) . ')';
            } else {
                // Default: AND between terms
                foreach ($parsed['terms'] as $term) {
                    $parts[] = $this->escapeFTS5($term);
                }
            }
        }

        if (empty($parts)) {
            return '';
        }

        // Combine all parts with AND
        return implode(' AND ', $parts);
    }

    private function escapeFTS5($term) {
        // Check if term explicitly ends with * (user wants wildcard search)
        $userWildcard = substr($term, -1) === '*';
        if ($userWildcard) {
            $term = substr($term, 0, -1); // Remove the * temporarily
        }

        // Check if term contains FTS5 special characters that need quoting
        // FTS5 special chars: " ( ) [ ] : + - ^ * AND OR NOT
        $needsQuoting = preg_match('/["\(\)\[\]:+\-\^\*]|^(AND|OR|NOT)$/i', $term);

        if ($needsQuoting) {
            // Escape internal double quotes and wrap in quotes
            $term = '"' . str_replace('"', '""', $term) . '"';

            // If user explicitly added wildcard, we can't use it with quoted terms
            // So we return the quoted term without wildcard
            return $term;
        }

        // For regular terms without special characters
        // Escape double quotes just in case
        $term = str_replace('"', '""', $term);

        // Add wildcard for prefix matching (fuzzy search)
        if ($userWildcard) {
            return $term . '*'; // User's explicit wildcard
        } else {
            return $term . '*'; // Auto-add wildcard for fuzzy matching
        }
    }

    private function highlightTerms($text, $terms) {
        if (empty($text) || empty($terms)) {
            return $text;
        }

        // Split HTML into tags and text parts
        $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as &$part) {
            // Only highlight text parts (not HTML tags)
            if (!preg_match('/^<[^>]+>$/', $part)) {
                foreach ($terms as $term) {
                    $part = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $part);
                }
            }
        }

        return implode('', $parts);
    }

    public function getSections() {
        try {
            $pdo = new PDO("sqlite:" . $this->dbPath);
            $stmt = $pdo->query("SELECT DISTINCT section FROM search_content WHERE section != '' ORDER BY section");
            $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->sendResponse(['sections' => $sections]);
        } catch (Exception $e) {
            $this->sendError('Error getting sections: ' . $e->getMessage());
        }
    }

    /**
     * Safely decode JSON with error handling and logging
     */
    private function safeJsonDecode($jsonString, $recordTitle = 'Unknown') {
        if (empty($jsonString)) {
            return [];
        }

        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the error but don't break the search
            error_log("JSON decode error in record '$recordTitle': " . json_last_error_msg() . " - Data: " . substr($jsonString, 0, 100));
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Validate date format (YYYY-MM-DD) and ensure it's a real date
     */
    private function isValidDate($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);
        if (count($parts) !== 3) {
            return false;
        }

        // Validate it's an actual date (e.g., not 2024-02-30 or 2024-13-01)
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    private function sendResponse($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON encoding fails, send a clear error
            $this->sendError('Failed to encode response: ' . json_last_error_msg(), 500);
        }

        echo $json;
        exit;
    }

    private function sendError($message, $code = 500) {
        http_response_code($code);
        $json = json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback if even error encoding fails
            echo '{"error":"Critical error: Unable to encode error message"}';
        } else {
            echo $json;
        }
        exit;
    }
}

// Handle the request
$api = new SearchAPI();

$action = $_GET['action'] ?? 'search';

switch ($action) {
    case 'sections':
        $api->getSections();
        break;
    default:
        $api->search();
        break;
}
?>
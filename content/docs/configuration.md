---
title: "Configuration"
date: 2025-10-06
draft: false
weight: 3
url: /docs/configuration/
---

# Configuration

Customize Hugo SQLite Search to match your site's needs.

## Hugo Configuration

### Required: JSON Output

Enable JSON output in your `hugo.yaml`:

```yaml
outputs:
  home:
    - HTML
    - RSS
    - JSON

outputFormats:
  JSON:
    baseName: index
    isPlainText: true
    mediaType: application/json
    notAlternative: true
```

### JSON Template Configuration

The search data template (`layouts/_default/search-data.json`) controls what content gets indexed.

#### Default Template

```go-template
{{- $pages := where site.RegularPages "Type" "!=" "page" -}}
{{- $pages = where $pages "Draft" "!=" true -}}
{{- $pages = where $pages "Params.search" "!=" false -}}
[
{{- range $index, $page := $pages -}}
  {{- if $index }},{{ end }}
  {
    "id": {{ $page.File.UniqueID | jsonify }},
    "title": {{ $page.Title | jsonify }},
    "url": {{ $page.Permalink | jsonify }},
    "content": {{ $page.Plain | jsonify }},
    "summary": {{ $page.Summary | jsonify }},
    "date": {{ $page.Date.Format "2006-01-02" | jsonify }},
    "section": {{ $page.Section | jsonify }},
    "tags": {{ $page.Params.tags | jsonify }},
    "categories": {{ $page.Params.categories | jsonify }}
  }
{{- end -}}
]
```

#### Exclude Pages from Search

Add to page front matter:

```yaml
---
title: "Private Page"
search: false
---
```

#### Include Only Specific Sections

```go-template
{{- $pages := where site.RegularPages "Section" "in" (slice "posts" "docs") -}}
```

#### Limit Content Length

For very long pages, truncate content:

```go-template
"content": {{ (truncate 5000 $page.Plain) | jsonify }},
```

#### Add Custom Fields

Include additional metadata:

```go-template
{
  "id": {{ $page.File.UniqueID | jsonify }},
  "title": {{ $page.Title | jsonify }},
  "url": {{ $page.Permalink | jsonify }},
  "content": {{ $page.Plain | jsonify }},
  "summary": {{ $page.Summary | jsonify }},
  "date": {{ $page.Date.Format "2006-01-02" | jsonify }},
  "section": {{ $page.Section | jsonify }},
  "tags": {{ $page.Params.tags | jsonify }},
  "categories": {{ $page.Params.categories | jsonify }},
  "author": {{ $page.Params.author | jsonify }},
  "featured": {{ $page.Params.featured | jsonify }}
}
```

## PHP Index Builder Configuration

Modify `scripts/build-search-index.php` to customize indexing behavior.

### Database Schema

The script creates two tables:

**search_content** (main table):
```sql
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
```

**search_fts** (FTS5 virtual table):
```sql
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
```

### Tokenizer Options

Change the tokenizer by modifying the FTS5 table creation:

**Porter Stemmer (default)**:
```php
tokenize='porter'
```

**Unicode61 (case-insensitive, no stemming)**:
```php
tokenize='unicode61'
```

**Trigram (for CJK languages)**:
```php
tokenize='trigram'
```

### Custom Fields in Index

To index additional fields, modify the table creation and insert statements:

```php
// Add custom field to table
$pdo->exec("
    CREATE TABLE search_content (
        ...
        author TEXT,
        featured INTEGER
    )
");

// Add to FTS5 table
$pdo->exec("
    CREATE VIRTUAL TABLE search_fts USING fts5(
        title,
        content,
        summary,
        tags,
        categories,
        author,
        ...
    )
");

// Update insert statement
$stmt->execute([
    $item['id'] ?? '',
    $item['title'] ?? '',
    ...
    $item['author'] ?? '',
    $item['featured'] ? 1 : 0
]);
```

## PHP Search API Configuration

Customize `static/api/search.php` to modify search behavior.

### Results Per Page

```php
class SearchAPI {
    private $resultsPerPage = 20;  // Default results per page
    private $maxResults = 100;     // Maximum allowed
```

### Field Weighting

FTS5 doesn't directly support field weighting, but you can boost results by modifying the ranking:

```php
// In the ORDER BY clause, boost title matches
$sql .= " ORDER BY
    CASE
        WHEN c.title LIKE :title_boost THEN 1
        ELSE 2
    END,
    bm25(f.search_fts),
    c.date DESC";
```

### Custom Sorting Options

Add new sort options:

```php
$validSorts = ['relevance', 'date_desc', 'date_asc', 'title_asc'];

// Add to switch statement
case 'title_asc':
    $sql .= " ORDER BY c.title ASC, bm25(f.search_fts)";
    break;
```

### Database Path

Change the default database location:

```php
public function __construct($dbPath = '../search.db') {
    $this->dbPath = $dbPath;
}
```

### CORS Configuration

Modify CORS headers for specific domains:

```php
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
```

## Search Page Configuration

Customize `layouts/page/search.html` for your needs.

### API Endpoint

Change the API URL:

```javascript
class HugoSearch {
    constructor() {
        this.apiUrl = '/api/search.php';  // Change this
        ...
    }
}
```

### Default Results Per Page

```javascript
async performSearch(page = 1) {
    const params = new URLSearchParams({
        q: query,
        page: page.toString(),
        limit: '20'  // Change default limit
    });
}
```

### Debounce Timing

Adjust search-as-you-type delay:

```javascript
this.searchTimeout = setTimeout(() => {
    if (this.searchInput.value.length >= 2) {
        this.performSearch();
    }
}, 300);  // 300ms delay - adjust as needed
```

### Minimum Query Length

```javascript
if (!query || query.length < 2) {  // Change minimum length
    this.searchResults.innerHTML = '<div class="no-results">Please enter at least 2 characters to search.</div>';
    return;
}
```

### Styling

Customize the embedded CSS in `search.html` or extract to a separate stylesheet:

```html
<style>
.search-container {
    max-width: 800px;  /* Adjust width */
    margin: 0 auto;
    padding: 20px;
}

#search-input {
    font-size: 16px;  /* Adjust size */
    padding: 12px;
}

mark {
    background-color: #ffeb3b;  /* Change highlight color */
}
</style>
```

## Build Script Configuration

Customize `build.sh` for your workflow.

### Output Directory

Change Hugo's output directory:

```bash
# Build to custom directory
hugo --minify --destination custom-public

SEARCH_DATA_FILE="custom-public/index.json"
```

### Skip Minification

```bash
# Build without minification (faster development builds)
hugo

# Or with specific minify options
hugo --minify --minifyOptions css=true,js=false
```

### Custom Database Path

```bash
# Create database in custom location
php scripts/build-search-index.php /var/db/search.db "$SEARCH_DATA_FILE"
```

### Post-Build Actions

Add custom actions after build:

```bash
echo "✅ Search database created"

# Example: Copy to multiple locations
cp public/search.db backup/search-$(date +%Y%m%d).db

# Example: Generate search statistics
sqlite3 public/search.db "SELECT COUNT(*) as total_pages FROM search_content;"
sqlite3 public/search.db "SELECT section, COUNT(*) FROM search_content GROUP BY section;"

# Example: Validate database
sqlite3 public/search.db "PRAGMA integrity_check;"
```

## Advanced Configuration

### Multi-Language Support

For multi-language sites, create separate indexes per language:

**Hugo config**:
```yaml
outputs:
  home:
    - HTML
    - RSS
    - JSON

languages:
  en:
    outputs:
      home: [HTML, RSS, JSON]
  es:
    outputs:
      home: [HTML, RSS, JSON]
```

**Build script**:
```bash
# Build English index
php scripts/build-search-index.php public/en/search.db public/en/index.json

# Build Spanish index
php scripts/build-search-index.php public/es/search.db public/es/index.json
```

### Performance Tuning

#### Optimize Database

```bash
# After building, optimize database
sqlite3 public/search.db "VACUUM;"
sqlite3 public/search.db "ANALYZE;"
```

#### Index Only Recent Content

Limit indexing to recent pages:

```go-template
{{- $pages := where site.RegularPages "Date" "ge" (now.AddDate -2 0 0) -}}
```

#### Reduce Content Size

Strip HTML and limit content:

```go-template
"content": {{ (truncate 2000 (plainify $page.Content)) | jsonify }},
```

### Security Configuration

#### Rate Limiting (Nginx)

```nginx
limit_req_zone $binary_remote_addr zone=search:10m rate=10r/s;

location /api/search.php {
    limit_req zone=search burst=20 nodelay;
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    include fastcgi_params;
}
```

#### Input Validation

The PHP API already validates inputs, but you can add stricter limits:

```php
// In search() method
$query = trim($_GET['q'] ?? '');
if (strlen($query) > 200) {  // Add maximum length
    $this->sendError('Query too long', 400);
    return;
}
```

## Example Configurations

### Personal Blog

```yaml
# hugo.yaml
outputs:
  home: [HTML, RSS, JSON]

params:
  search:
    sections: [posts]  # Only index posts
    limit: 10
```

```go-template
{{/* layouts/_default/search-data.json */}}
{{- $pages := where site.RegularPages "Section" "posts" -}}
{{- $pages = where $pages "Draft" "!=" true -}}
[...]
```

### Documentation Site

```yaml
# hugo.yaml - index all docs
outputs:
  home: [HTML, RSS, JSON]
  section: [HTML, RSS]
```

```php
// search.php - Higher results per page
private $resultsPerPage = 50;
```

### Large Content Site

```bash
# build.sh - Optimize after build
php scripts/build-search-index.php public/search.db "$SEARCH_DATA_FILE"

echo "Optimizing database..."
sqlite3 public/search.db "VACUUM;"
sqlite3 public/search.db "ANALYZE;"
sqlite3 public/search.db "PRAGMA optimize;"
```

These configurations give you complete control over how Hugo SQLite Search works on your site.

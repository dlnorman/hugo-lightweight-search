---
title: "Examples"
date: 2025-10-06
draft: false
weight: 4
url: /docs/examples/
---

# Examples

Real-world examples of implementing Hugo SQLite Search in various scenarios. All examples are based on the actual implementation from this project.

## Hugo Integration Examples

### 1. Complete Working Search Page

Create a search page with full-text search capabilities. First, create a content file at `content/search.md`:

```markdown
---
title: "Search"
layout: "search"
---
```

Then create the search page template at `layouts/page/search.html`:

```html
{{ define "main" }}
<div class="search-container">
    <h1>{{ .Title }}</h1>

    <div class="search-form">
        <input type="text" id="search-input" placeholder="Search..." autocomplete="off">
        <select id="section-filter">
            <option value="">All sections</option>
        </select>
        <select id="sort-order">
            <option value="relevance">Sort by: Relevance</option>
            <option value="date_desc">Sort by: Newest first</option>
            <option value="date_asc">Sort by: Oldest first</option>
        </select>
        <button id="search-button">Search</button>
    </div>

    <div id="search-loading" class="loading" style="display: none;">
        Searching...
    </div>

    <div id="search-results"></div>
    <div id="search-pagination"></div>
</div>

<script>
class HugoSearch {
    constructor() {
        this.apiUrl = '/api/search.php';
        this.currentPage = 1;
        this.currentQuery = '';

        this.initializeElements();
        this.bindEvents();
        this.loadSections();
        this.checkUrlParams();
    }

    initializeElements() {
        this.searchInput = document.getElementById('search-input');
        this.sectionFilter = document.getElementById('section-filter');
        this.sortOrder = document.getElementById('sort-order');
        this.searchButton = document.getElementById('search-button');
        this.searchLoading = document.getElementById('search-loading');
        this.searchResults = document.getElementById('search-results');
        this.searchPagination = document.getElementById('search-pagination');
    }

    bindEvents() {
        this.searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                this.performSearch();
            }
        });

        this.searchButton.addEventListener('click', () => {
            this.performSearch();
        });

        this.sectionFilter.addEventListener('change', () => {
            this.performSearch();
        });

        this.sortOrder.addEventListener('change', () => {
            this.performSearch();
        });
    }

    async performSearch(page = 1) {
        const query = this.searchInput.value.trim();
        const section = this.sectionFilter.value;
        const sort = this.sortOrder.value;

        if (!query || query.length < 2) {
            this.searchResults.innerHTML = '<div class="no-results">Please enter at least 2 characters to search.</div>';
            return;
        }

        this.showLoading();

        try {
            const params = new URLSearchParams({
                q: query,
                page: page.toString()
            });

            if (section) params.append('section', section);
            if (sort && sort !== 'relevance') params.append('sort', sort);

            const response = await fetch(`${this.apiUrl}?${params}`);
            const data = await response.json();

            if (data.error) throw new Error(data.error);

            this.displayResults(data);
        } catch (error) {
            console.error('Search error:', error);
            this.searchResults.innerHTML = `<div class="no-results">Search error: ${error.message}</div>`;
        } finally {
            this.hideLoading();
        }
    }

    displayResults(data) {
        if (data.results.length === 0) {
            this.searchResults.innerHTML = '<div class="no-results">No results found.</div>';
            return;
        }

        const stats = `<div class="search-stats">Found ${data.total} results (page ${data.page} of ${data.total_pages})</div>`;
        const resultsHtml = data.results.map(result => this.renderResult(result)).join('');

        this.searchResults.innerHTML = stats + resultsHtml;
        this.renderPagination(data);
    }

    renderResult(result) {
        const tags = Array.isArray(result.tags) ? result.tags : [];
        const tagsHtml = tags.length > 0 ?
            `<div class="result-tags">${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}</div>` : '';

        return `
            <div class="search-result">
                <div class="result-title">
                    <a href="${result.url}">${result.title_highlighted || result.title}</a>
                </div>
                <div class="result-meta">
                    ${result.section ? result.section + ' • ' : ''}${result.date}
                </div>
                <div class="result-summary">
                    ${result.summary_highlighted || result.content_snippet || result.summary || ''}
                </div>
                ${tagsHtml}
            </div>
        `;
    }

    showLoading() {
        this.searchLoading.style.display = 'block';
        this.searchResults.innerHTML = '';
    }

    hideLoading() {
        this.searchLoading.style.display = 'none';
    }

    async loadSections() {
        try {
            const response = await fetch(`${this.apiUrl}?action=sections`);
            const data = await response.json();

            if (data.sections) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section.charAt(0).toUpperCase() + section.slice(1);
                    this.sectionFilter.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading sections:', error);
        }
    }

    checkUrlParams() {
        const params = new URLSearchParams(window.location.search);
        const query = params.get('q');

        if (query) {
            this.searchInput.value = query;
            this.performSearch();
        }
    }
}

// Initialize search when page loads
let search;
document.addEventListener('DOMContentLoaded', () => {
    search = new HugoSearch();
});
</script>
{{ end }}
```

### 2. Adding Search to Site Navigation

Update your Hugo config (`hugo.yaml`) to add search to the main menu:

```yaml
menu:
  main:
    - identifier: search
      name: Search
      url: /search/
      weight: 50
```

### 3. Customizing the Search UI

Add custom CSS to style your search interface. Add this to your site's custom CSS:

```css
.search-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

#search-input {
    flex: 1;
    min-width: 300px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.search-result {
    border-bottom: 1px solid #eee;
    padding: 20px 0;
}

.result-title a {
    color: #1a73e8;
    text-decoration: none;
    font-size: 20px;
}

.result-title a:hover {
    text-decoration: underline;
}

.result-meta {
    color: #666;
    font-size: 14px;
    margin: 5px 0 10px 0;
}

mark {
    background-color: #ffeb3b;
    padding: 0 2px;
    border-radius: 2px;
}
```

### 4. Excluding Specific Pages from Search

Exclude pages from the search index by setting front matter:

```yaml
---
title: "Private Page"
search: false
---
```

Or modify the search data template at `layouts/_default/search-data.json`:

```go-html-template
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

## JavaScript Client Examples

### 1. Basic Search Implementation

Simple search with vanilla JavaScript:

```javascript
async function search(query) {
    const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);
    const data = await response.json();

    return data.results;
}

// Usage
search('hugo').then(results => {
    console.log(`Found ${results.length} results`);
    results.forEach(result => {
        console.log(`- ${result.title}: ${result.url}`);
    });
});
```

### 2. Advanced Search with Filters

Search with section filtering and custom sorting:

```javascript
async function advancedSearch(query, options = {}) {
    const params = new URLSearchParams({
        q: query,
        page: options.page || 1,
        limit: options.limit || 20
    });

    if (options.section) {
        params.append('section', options.section);
    }

    if (options.sort) {
        params.append('sort', options.sort);
    }

    const response = await fetch(`/api/search.php?${params}`);
    const data = await response.json();

    if (data.error) {
        throw new Error(data.error);
    }

    return data;
}

// Usage examples
advancedSearch('hugo', {
    section: 'docs',
    sort: 'date_desc',
    page: 1
}).then(data => {
    console.log(`Found ${data.total} results in docs section`);
});
```

### 3. Autocomplete/Search-as-You-Type

Implement live search with debouncing:

```javascript
class LiveSearch {
    constructor(inputElement, resultsElement, apiUrl = '/api/search.php') {
        this.input = inputElement;
        this.results = resultsElement;
        this.apiUrl = apiUrl;
        this.debounceTimer = null;

        this.input.addEventListener('input', (e) => this.handleInput(e));
    }

    handleInput(e) {
        clearTimeout(this.debounceTimer);

        const query = e.target.value.trim();

        if (query.length < 2) {
            this.results.innerHTML = '';
            return;
        }

        // Debounce: wait 300ms after user stops typing
        this.debounceTimer = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }

    async performSearch(query) {
        try {
            const response = await fetch(`${this.apiUrl}?q=${encodeURIComponent(query)}&limit=5`);
            const data = await response.json();

            this.displayResults(data.results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displayResults(results) {
        if (results.length === 0) {
            this.results.innerHTML = '<div class="no-results">No results found</div>';
            return;
        }

        this.results.innerHTML = results.map(result => `
            <a href="${result.url}" class="autocomplete-item">
                <div class="title">${result.title_highlighted || result.title}</div>
                <div class="section">${result.section}</div>
            </a>
        `).join('');
    }
}

// Initialize
const searchInput = document.getElementById('search-input');
const searchResults = document.getElementById('search-results');
const liveSearch = new LiveSearch(searchInput, searchResults);
```

### 4. Result Highlighting and Snippets

The API automatically provides highlighted snippets. Display them:

```javascript
function renderSearchResult(result) {
    return `
        <div class="search-result">
            <h3>
                <a href="${result.url}">
                    ${result.title_highlighted || result.title}
                </a>
            </h3>
            <div class="result-snippet">
                ${result.content_snippet || result.summary_highlighted || result.summary}
            </div>
            <div class="result-meta">
                <span class="section">${result.section}</span>
                <span class="date">${result.date}</span>
                <span class="score">Relevance: ${result.relevance_score}</span>
            </div>
            ${result.tags && result.tags.length > 0 ? `
                <div class="tags">
                    ${result.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                </div>
            ` : ''}
        </div>
    `;
}
```

### 5. Pagination Implementation

Complete pagination with page numbers:

```javascript
function renderPagination(data) {
    const { page, total_pages } = data;

    if (total_pages <= 1) return '';

    let html = '<div class="pagination">';

    // Previous button
    if (page > 1) {
        html += `<button onclick="goToPage(${page - 1})">Previous</button>`;
    }

    // Page numbers
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(total_pages, page + 2);

    if (startPage > 1) {
        html += `<button onclick="goToPage(1)">1</button>`;
        if (startPage > 2) html += '<span>...</span>';
    }

    for (let i = startPage; i <= endPage; i++) {
        const active = i === page ? ' class="active"' : '';
        html += `<button${active} onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) html += '<span>...</span>';
        html += `<button onclick="goToPage(${total_pages})">${total_pages}</button>`;
    }

    // Next button
    if (page < total_pages) {
        html += `<button onclick="goToPage(${page + 1})">Next</button>`;
    }

    html += '</div>';
    return html;
}

function goToPage(page) {
    const query = document.getElementById('search-input').value;
    performSearch(query, page);
}
```

## PHP API Usage Examples

### 1. Direct API Calls with curl

Test the search API from the command line:

```bash
# Basic search
curl "http://localhost/api/search.php?q=hugo"

# Search with filters
curl "http://localhost/api/search.php?q=search&section=docs&sort=date_desc&page=1"

# Get available sections
curl "http://localhost/api/search.php?action=sections"

# Advanced query syntax
curl "http://localhost/api/search.php?q=title:configuration+after:2024-01-01"

# Phrase search
curl "http://localhost/api/search.php?q=\"search+engine\""
```

### 2. Fetch API Examples

Modern JavaScript fetch requests:

```javascript
// Basic search
fetch('/api/search.php?q=hugo')
    .then(response => response.json())
    .then(data => {
        console.log('Results:', data.results);
        console.log('Total:', data.total);
    });

// Search with all options
const searchParams = {
    q: 'sqlite search',
    section: 'docs',
    sort: 'relevance',
    page: 1,
    limit: 10
};

const queryString = new URLSearchParams(searchParams).toString();

fetch(`/api/search.php?${queryString}`)
    .then(response => response.json())
    .then(data => {
        console.log(`Found ${data.total} results`);
        console.log(`Page ${data.page} of ${data.total_pages}`);

        data.results.forEach(result => {
            console.log(`${result.title} - ${result.url}`);
        });
    })
    .catch(error => console.error('Error:', error));

// Get sections
fetch('/api/search.php?action=sections')
    .then(response => response.json())
    .then(data => {
        console.log('Available sections:', data.sections);
    });
```

### 3. Advanced Query Syntax Examples

The search API supports powerful query syntax:

```javascript
// Field-specific searches
fetch('/api/search.php?q=title:hugo')  // Search in title only
fetch('/api/search.php?q=tags:tutorial')  // Search in tags
fetch('/api/search.php?q=categories:development')  // Search in categories

// Date range filters
fetch('/api/search.php?q=hugo after:2024-01-01')  // Posts after date
fetch('/api/search.php?q=tutorial before:2024-12-31')  // Posts before date
fetch('/api/search.php?q=search after:2024-01-01 before:2024-06-30')  // Date range

// Boolean operators
fetch('/api/search.php?q=hugo OR jekyll')  // Either term
fetch('/api/search.php?q=search engine')  // Both terms (AND is default)

// Phrase search
fetch('/api/search.php?q="static site generator"')  // Exact phrase

// Complex queries
fetch('/api/search.php?q=title:configuration category:hugo after:2024-01-01')
```

### 4. Error Handling

Implement robust error handling:

```javascript
async function searchWithErrorHandling(query) {
    try {
        const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);

        // Check HTTP status
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        // Check for API errors
        if (data.error) {
            throw new Error(data.error);
        }

        // Validate response structure
        if (!data.results || !Array.isArray(data.results)) {
            throw new Error('Invalid response format');
        }

        return data;

    } catch (error) {
        console.error('Search failed:', error);

        // Display user-friendly error message
        if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
            return { error: 'Unable to connect to search service. Please check your internet connection.' };
        } else if (error.message.includes('timeout')) {
            return { error: 'Search request timed out. Please try again.' };
        } else {
            return { error: `Search error: ${error.message}` };
        }
    }
}
```

## Advanced Use Cases

### 1. Multi-language Search

Configure Hugo for multi-language support:

```yaml
# hugo.yaml
languages:
  en:
    languageName: English
    weight: 1
  es:
    languageName: Español
    weight: 2
```

Create language-specific search data templates:

```go-html-template
{{- /* layouts/_default/search-data.json */ -}}
{{- $pages := where site.RegularPages "Type" "!=" "page" -}}
{{- $pages = where $pages "Draft" "!=" true -}}
{{- $pages = where $pages "Lang" site.Language.Lang -}}
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
    "language": {{ $page.Lang | jsonify }},
    "tags": {{ $page.Params.tags | jsonify }},
    "categories": {{ $page.Params.categories | jsonify }}
  }
{{- end -}}
]
```

### 2. Section-Specific Search Pages

Create dedicated search pages for specific sections:

```markdown
---
title: "Search Documentation"
layout: "search"
params:
  searchSection: "docs"
---
```

Modify the search JavaScript to filter by section:

```javascript
constructor() {
    this.apiUrl = '/api/search.php';
    // Get section from page params if set
    this.defaultSection = document.querySelector('meta[name="search-section"]')?.content || '';
    // ...
}

async performSearch(page = 1) {
    const section = this.defaultSection || this.sectionFilter.value;

    const params = new URLSearchParams({
        q: query,
        page: page.toString()
    });

    if (section) {
        params.append('section', section);
    }
    // ...
}
```

### 3. Search Analytics/Logging

Track search queries for analytics:

```php
<?php
// Add to search.php before performing search

// Log search query
function logSearch($query, $results_count) {
    $log_file = '../logs/search.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_entry = sprintf(
        "[%s] IP: %s | Query: %s | Results: %d | UA: %s\n",
        $timestamp,
        $ip,
        $query,
        $results_count,
        $user_agent
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Call after search
$results = $this->performSearch($pdo, $query, $page, $limit, $section, $sort);
logSearch($query, $results['total']);
?>
```

JavaScript tracking:

```javascript
// Track searches with analytics
async function performSearch(query) {
    const data = await search(query);

    // Track with Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'search', {
            'search_term': query,
            'results_count': data.total
        });
    }

    // Track with custom analytics
    fetch('/api/analytics.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            event: 'search',
            query: query,
            results: data.total,
            timestamp: new Date().toISOString()
        })
    });

    return data;
}
```

### 4. Custom Ranking Algorithms

Modify the search API to implement custom ranking:

```php
// In search.php, modify the ORDER BY clause
switch ($sort) {
    case 'custom_rank':
        $sql .= " ORDER BY
                    CASE
                        -- Boost exact title matches
                        WHEN c.title = :exact_match THEN 1
                        -- Boost recent posts in certain sections
                        WHEN c.section = 'blog' AND c.date > DATE('now', '-30 days') THEN 2
                        -- Boost posts with specific tags
                        WHEN c.tags LIKE '%featured%' THEN 3
                        ELSE 4
                    END,
                    bm25(f.search_fts),
                    c.date DESC";
        $params['exact_match'] = $parsedQuery['terms'][0] ?? '';
        break;
}
```

## Deployment Examples

### 1. GitHub Actions CI/CD

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy Hugo Site with Search

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
    - name: Checkout code
      uses: actions/checkout@v3

    - name: Setup Hugo
      uses: peaceiris/actions-hugo@v2
      with:
        hugo-version: 'latest'
        extended: true

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, sqlite3

    - name: Build Hugo site
      run: hugo --minify

    - name: Build search index
      run: |
        php scripts/build-search-index.php public/search.db public/search-data/index.json

    - name: Deploy to hosting
      run: |
        # Copy public/ directory to your hosting
        rsync -avz --delete public/ user@yourserver.com:/var/www/html/
```

### 2. Netlify Deployment

Since Netlify doesn't support PHP, use a serverless function alternative or static search.

Create `netlify.toml`:

```toml
[build]
  command = "hugo --minify && npm run build-search"
  publish = "public"

[[redirects]]
  from = "/api/search.php"
  to = "/.netlify/functions/search"
  status = 200
```

Create a Netlify function at `netlify/functions/search.js`:

```javascript
// Note: This requires porting the PHP search logic to Node.js
// or using a different search solution for serverless deployments

const Database = require('better-sqlite3');

exports.handler = async (event) => {
    const query = event.queryStringParameters.q;
    const page = parseInt(event.queryStringParameters.page || '1');

    const db = new Database('./public/search.db', { readonly: true });

    // Implement search logic similar to PHP version
    const stmt = db.prepare(`
        SELECT * FROM search_content c
        JOIN search_fts f ON c.rowid = f.rowid
        WHERE f.search_fts MATCH ?
        LIMIT 20 OFFSET ?
    `);

    const results = stmt.all(query + '*', (page - 1) * 20);

    return {
        statusCode: 200,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ results })
    };
};
```

### 3. Traditional Hosting Deployment

Deploy to traditional PHP hosting:

```bash
#!/bin/bash
# deploy.sh

# Build Hugo site
hugo --minify

# Build search index
php scripts/build-search-index.php public/search.db public/search-data/index.json

# Set permissions
chmod 644 public/search.db
chmod 755 public/api

# Deploy via FTP/SFTP
lftp -e "mirror -R public/ /public_html/; quit" -u username,password ftp.yourhost.com

# Or via rsync
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    public/ user@yourserver.com:/var/www/html/

echo "Deployment complete!"
```

### 4. Docker Deployment

Create `Dockerfile`:

```dockerfile
FROM php:8.1-apache

# Install required PHP extensions
RUN apt-get update && \
    apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo pdo_sqlite

# Copy Hugo built site
COPY public/ /var/www/html/

# Copy search database
COPY public/search.db /var/www/html/search.db

# Set permissions
RUN chown -R www-data:www-data /var/www/html/ && \
    chmod 644 /var/www/html/search.db

# Enable Apache modules
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
```

Build and run:

```bash
# Build the site
hugo --minify

# Build search index
php scripts/build-search-index.php public/search.db public/search-data/index.json

# Build Docker image
docker build -t hugo-search-site .

# Run container
docker run -d -p 8080:80 hugo-search-site
```

With docker-compose (`docker-compose.yml`):

```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./public:/var/www/html
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html
```

## Testing Examples

### Test Search Functionality

```bash
# Test basic search
curl -s "http://localhost/api/search.php?q=hugo" | jq '.results[] | {title, url}'

# Test with filters
curl -s "http://localhost/api/search.php?q=search&section=docs" | jq '.total'

# Test pagination
curl -s "http://localhost/api/search.php?q=hugo&page=2&limit=5" | jq '.page, .total_pages'

# Test sections endpoint
curl -s "http://localhost/api/search.php?action=sections" | jq '.sections'

# Test advanced queries
curl -s "http://localhost/api/search.php?q=title:configuration" | jq '.results | length'
curl -s "http://localhost/api/search.php?q=after:2024-01-01" | jq '.results | length'
```

## Performance Optimization Examples

### 1. Enable Database Caching

Add caching headers in PHP:

```php
// In search.php
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
header('ETag: ' . md5($query . $section . $page));
```

### 2. Implement Result Caching

```php
class SearchCache {
    private $cacheDir = '../cache/';
    private $ttl = 300; // 5 minutes

    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.json';

        if (file_exists($file) && (time() - filemtime($file)) < $this->ttl) {
            return json_decode(file_get_contents($file), true);
        }

        return null;
    }

    public function set($key, $data) {
        $file = $this->cacheDir . md5($key) . '.json';
        file_put_contents($file, json_encode($data));
    }
}

// Usage in search.php
$cache = new SearchCache();
$cacheKey = $query . $section . $page . $sort;

if ($cached = $cache->get($cacheKey)) {
    $this->sendResponse($cached);
    return;
}

$results = $this->performSearch($pdo, $query, $page, $limit, $section, $sort);
$cache->set($cacheKey, $results);
```

## More Resources

- Check our [API Reference](/docs/api-reference/) for complete API documentation
- See [Configuration](/docs/configuration/) for all available options
- Visit the [Getting Started](/docs/getting-started/) guide for installation instructions

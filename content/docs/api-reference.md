---
title: "API Reference"
date: 2025-10-06
draft: false
weight: 2
url: /docs/api-reference/
---

# API Reference

Complete reference for the Hugo Lightweight Search PHP API.

## Endpoint

```
GET /api/search.php
```

Base URL: `https://yourdomain.com/api/search.php`

## Authentication

The API is publicly accessible and does not require authentication. CORS is enabled to allow cross-origin requests.

## Query Parameters

### Search Action (Default)

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `q` | string | Yes | - | Search query (minimum 2 characters) |
| `page` | integer | No | 1 | Page number for pagination (1-indexed) |
| `section` | string | No | - | Filter results by section (e.g., "blog", "docs") |
| `sort` | string | No | relevance | Sort order: `relevance`, `date_desc`, or `date_asc` |
| `limit` | integer | No | 20 | Results per page (maximum 100) |

### Sections Action

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | Yes | Set to `sections` to retrieve available sections |

## Advanced Query Syntax

The search API supports advanced query operators for precise searching:

### Exact Phrase Search

Use double quotes to search for exact phrases:

```
"machine learning"
```

### Field-Specific Searches

Search within specific fields using `field:term` syntax:

- **title:term** - Search in title only
- **content:term** - Search in content only
- **tags:term** - Search in tags
- **categories:term** - Search in categories
- **summary:term** - Search in summary

**Examples:**
```
title:tutorial
tags:javascript
content:"error handling"
```

### Date Filters

Filter results by date range:

- **after:YYYY-MM-DD** - Results published on or after this date
- **before:YYYY-MM-DD** - Results published on or before this date

**Examples:**
```
after:2024-01-01
before:2024-12-31
after:2024-01-01 before:2024-12-31
```

### Boolean Operators

- **AND** (default) - All terms must be present
- **OR** - Any term can be present
- **NOT** - Currently recognized but not implemented

**Examples:**
```
javascript OR python
react AND hooks
```

### Wildcard and Fuzzy Matching

- Automatic prefix matching: Terms automatically get a wildcard suffix for fuzzy matching
- Explicit wildcards: Use `*` at the end of a term for prefix search

**Examples:**
```
java*    (matches java, javascript, javadoc)
react    (automatically searches for react*)
```

### Combining Query Operators

You can combine multiple operators in a single query:

```
title:"getting started" after:2024-01-01 tags:tutorial
"docker compose" OR kubernetes content:deployment
```

## Response Format

### Successful Search Response

```json
{
  "results": [
    {
      "id": "1",
      "title": "Getting Started with Hugo",
      "title_highlighted": "Getting Started with <mark>Hugo</mark>",
      "url": "/blog/getting-started-with-hugo/",
      "summary": "Learn how to build static sites with Hugo",
      "summary_highlighted": "Learn how to build static sites with <mark>Hugo</mark>",
      "content_snippet": "...quick start guide for <mark>Hugo</mark> static site...",
      "date": "2024-03-15",
      "section": "blog",
      "tags": ["hugo", "tutorial", "static-site"],
      "categories": ["web-development"],
      "relevance": -2.45,
      "relevance_score": 2.45
    }
  ],
  "total": 42,
  "page": 1,
  "per_page": 20,
  "total_pages": 3,
  "query": "hugo tutorial",
  "parsed_query": {
    "terms": ["hugo", "tutorial"],
    "phrases": [],
    "field_searches": [],
    "after": null,
    "before": null,
    "operators": []
  },
  "fts_query": "hugo* AND tutorial*"
}
```

### Sections Response

```json
{
  "sections": [
    "blog",
    "docs",
    "tutorials"
  ]
}
```

### Error Response

```json
{
  "error": "Search error: Database connection failed"
}
```

HTTP status code will be 500 for errors.

## Result Object Fields

Each search result contains:

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier for the content |
| `title` | string | Original title |
| `title_highlighted` | string | Title with search terms wrapped in `<mark>` tags |
| `url` | string | Relative URL to the content |
| `summary` | string | Original summary/description |
| `summary_highlighted` | string | Summary with search terms highlighted |
| `content_snippet` | string | Excerpt from content with search terms highlighted |
| `date` | string | Publication date (YYYY-MM-DD format) |
| `section` | string | Content section (e.g., "blog", "docs") |
| `tags` | array | Array of tag strings |
| `categories` | array | Array of category strings |
| `relevance` | number | Raw BM25 relevance score (negative value) |
| `relevance_score` | number | Normalized relevance score (positive, rounded to 2 decimals) |

## Sorting Options

### relevance (default)

Results are sorted by BM25 relevance score with intelligent boosting:

1. Documents with the search term in the title are ranked higher
2. More relevant matches (based on BM25 algorithm) appear first
3. Recent content gets slight preference when relevance is equal

### date_desc

Results sorted by publication date, newest first. Relevance is used as a secondary sort key.

### date_asc

Results sorted by publication date, oldest first. Relevance is used as a secondary sort key.

## Pagination

The API uses offset-based pagination:

- **page**: Current page number (1-indexed)
- **per_page**: Number of results per page (max 100)
- **total**: Total number of matching results
- **total_pages**: Total number of pages available

To fetch the next page:
```
?q=search&page=2
```

## Example Requests

### Basic Search

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=hugo"
```

**JavaScript (Fetch):**
```javascript
fetch('https://yourdomain.com/api/search.php?q=hugo')
  .then(response => response.json())
  .then(data => {
    console.log(`Found ${data.total} results`);
    data.results.forEach(result => {
      console.log(result.title, result.url);
    });
  });
```

**JavaScript (Async/Await):**
```javascript
async function search(query) {
  const response = await fetch(
    `https://yourdomain.com/api/search.php?q=${encodeURIComponent(query)}`
  );
  const data = await response.json();
  return data;
}

const results = await search('hugo tutorial');
```

### Search with Pagination

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=javascript&page=2&limit=10"
```

**JavaScript:**
```javascript
const params = new URLSearchParams({
  q: 'javascript',
  page: 2,
  limit: 10
});

const response = await fetch(`/api/search.php?${params}`);
const data = await response.json();
```

### Filter by Section

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=tutorial&section=blog"
```

**JavaScript:**
```javascript
const response = await fetch('/api/search.php?q=tutorial&section=blog');
const data = await response.json();
```

### Sort by Date

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=news&sort=date_desc"
```

**JavaScript:**
```javascript
const response = await fetch('/api/search.php?q=news&sort=date_desc');
const data = await response.json();
```

### Advanced Query with Multiple Operators

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=title:docker%20after:2024-01-01%20tags:tutorial"
```

**JavaScript:**
```javascript
const query = 'title:docker after:2024-01-01 tags:tutorial';
const response = await fetch(
  `/api/search.php?q=${encodeURIComponent(query)}`
);
const data = await response.json();
```

### Exact Phrase Search

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?q=\"getting%20started\""
```

**JavaScript:**
```javascript
const response = await fetch(
  '/api/search.php?q=' + encodeURIComponent('"getting started"')
);
const data = await response.json();
```

### Get Available Sections

**cURL:**
```bash
curl "https://yourdomain.com/api/search.php?action=sections"
```

**JavaScript:**
```javascript
const response = await fetch('/api/search.php?action=sections');
const { sections } = await response.json();
console.log('Available sections:', sections);
```

## Complete Search UI Example

Here's a complete example of building a search interface:

```javascript
class SearchUI {
  constructor(apiUrl) {
    this.apiUrl = apiUrl;
    this.currentPage = 1;
    this.currentQuery = '';
  }

  async search(query, options = {}) {
    const params = new URLSearchParams({
      q: query,
      page: options.page || this.currentPage,
      limit: options.limit || 20,
      sort: options.sort || 'relevance'
    });

    if (options.section) {
      params.append('section', options.section);
    }

    try {
      const response = await fetch(`${this.apiUrl}?${params}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();

      if (data.error) {
        throw new Error(data.error);
      }

      return data;
    } catch (error) {
      console.error('Search failed:', error);
      throw error;
    }
  }

  async getSections() {
    const response = await fetch(`${this.apiUrl}?action=sections`);
    const data = await response.json();
    return data.sections;
  }

  renderResults(data) {
    const resultsContainer = document.getElementById('search-results');
    const html = data.results.map(result => `
      <article class="search-result">
        <h3>
          <a href="${result.url}">${result.title_highlighted}</a>
        </h3>
        <div class="metadata">
          <span class="date">${result.date}</span>
          <span class="section">${result.section}</span>
          <span class="score">Score: ${result.relevance_score}</span>
        </div>
        <p class="summary">${result.summary_highlighted}</p>
        ${result.content_snippet ?
          `<p class="snippet">${result.content_snippet}</p>` : ''
        }
        ${result.tags.length ?
          `<div class="tags">${result.tags.map(tag =>
            `<span class="tag">${tag}</span>`
          ).join('')}</div>` : ''
        }
      </article>
    `).join('');

    resultsContainer.innerHTML = html;

    // Render pagination
    this.renderPagination(data);
  }

  renderPagination(data) {
    const pagination = document.getElementById('pagination');
    const pages = [];

    for (let i = 1; i <= data.total_pages; i++) {
      const isActive = i === data.page ? 'active' : '';
      pages.push(`
        <button class="${isActive}" data-page="${i}">
          ${i}
        </button>
      `);
    }

    pagination.innerHTML = `
      <div class="pagination-info">
        Showing ${(data.page - 1) * data.per_page + 1}-${Math.min(data.page * data.per_page, data.total)}
        of ${data.total} results
      </div>
      <div class="pagination-buttons">
        ${pages.join('')}
      </div>
    `;

    // Add click handlers
    pagination.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', () => {
        this.currentPage = parseInt(btn.dataset.page);
        this.search(this.currentQuery, { page: this.currentPage })
          .then(data => this.renderResults(data));
      });
    });
  }
}

// Usage
const searchUI = new SearchUI('/api/search.php');

document.getElementById('search-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const query = document.getElementById('search-input').value;
  const section = document.getElementById('section-filter').value;
  const sort = document.getElementById('sort-order').value;

  searchUI.currentQuery = query;
  searchUI.currentPage = 1;

  const results = await searchUI.search(query, { section, sort });
  searchUI.renderResults(results);
});
```

## Rate Limiting

The API does not currently implement rate limiting. For production use, consider implementing rate limiting at the web server level (e.g., using nginx `limit_req` module).

## Performance Tips

1. **Use specific field searches** when possible (e.g., `title:term`) to narrow results
2. **Limit results per page** to reduce response size and improve load times
3. **Cache section listings** since they change infrequently
4. **Implement client-side debouncing** for search-as-you-type features
5. **Use pagination** rather than fetching all results at once

## Database Structure

The API uses SQLite with FTS5 (Full-Text Search 5) for high-performance searches. The database contains:

- **search_content** - Main content table with metadata
- **search_fts** - FTS5 virtual table for full-text indexing

Searches use BM25 ranking algorithm for relevance scoring.

## Error Handling

The API returns JSON error responses with appropriate HTTP status codes:

- **200 OK** - Successful request
- **500 Internal Server Error** - Database errors or server issues

Always check for the `error` field in the response:

```javascript
const response = await fetch('/api/search.php?q=test');
const data = await response.json();

if (data.error) {
  console.error('Search error:', data.error);
  // Handle error appropriately
} else {
  // Process results
}
```

---
title: "Examples"
date: 2025-10-06
draft: false
weight: 4
url: /docs/examples/
---

# Examples

Real-world examples of implementing Hugo SQLite Search in various scenarios. All source code is available in the [GitHub repository](https://github.com/dlnorman/hugo-lightweight-search).

## Hugo Integration Examples

### 1. Complete Working Search Page

The repository includes a fully functional search page implementation:

- **Content file**: `content/pages/search.md` defines the search page
- **Template**: `layouts/page/search.html` contains the complete search UI and JavaScript client
- **Live demo**: [Try it here](/search/)

To use this in your site, simply copy these files from the repository to your Hugo site.

The search page includes:
- Search input with Enter key support
- Section filtering dropdown (dynamically populated)
- Sort options (relevance, date ascending/descending)
- Result highlighting with `<mark>` tags
- Pagination controls
- URL parameter support (e.g., `/search/?q=hugo`)
- Loading states and error handling

View the complete implementation in `layouts/page/search.html` in the repository.

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

The search page template (`layouts/page/search.html`) includes embedded CSS that you can customize. The default styles provide:
- Responsive layout with max-width container
- Flexbox-based search form
- Highlighted search terms with `<mark>` tags
- Clean result cards with metadata

To customize:
1. Edit the `<style>` section in `layouts/page/search.html`
2. Or extract the styles to your theme's CSS files
3. Match your site's existing design system

View the default CSS in the repository's `layouts/page/search.html` file.

### 4. Excluding Specific Pages from Search

To exclude pages from the search index, add this to the page's front matter:

```yaml
---
title: "Private Page"
search: false
---
```

The default search data template (`layouts/_default/search-data.json` in the repository) already filters out:
- Pages with `search: false` in front matter
- Draft pages
- Pages with type "page"

See the template in the repository for the complete filtering logic, which you can customize.

## JavaScript Client Examples

The repository includes a complete JavaScript implementation in `layouts/page/search.html`. Here are key patterns you can extract:

### 1. Basic Search Implementation

The repository shows how to:
- Make API requests with fetch()
- Handle query parameters with URLSearchParams
- Process and display results
- Handle errors gracefully

See the `HugoSearch` class in `layouts/page/search.html` for the complete implementation.

### 2. Advanced Features

The reference implementation demonstrates:

**Section Filtering**: The `loadSections()` method shows how to:
- Fetch available sections from the API
- Dynamically populate filter dropdowns
- Apply section filters to search queries

**Sort Options**: See how to implement multiple sort orders:
- Relevance (default BM25 ranking)
- Date descending (newest first)
- Date ascending (oldest first)

**Pagination**: The `renderPagination()` method demonstrates:
- Building page number controls
- Handling page navigation
- Showing result counts

All of these are implemented in `layouts/page/search.html` in the repository.

### 3. Search-as-You-Type

To implement live search:
- Add input event listener with debouncing (300ms recommended)
- Check minimum query length (2 characters)
- Limit results (e.g., 5 for autocomplete)
- Clear timeout on new input

The repository's implementation uses a 300ms debounce to balance responsiveness with API load.

### 4. Result Highlighting

The API automatically provides highlighted results in these fields:
- `title_highlighted` - Title with `<mark>` tags around matches
- `summary_highlighted` - Summary with highlighted matches
- `content_snippet` - Content excerpt with highlights

Simply use these fields in your HTML to display highlighted results. See `renderResult()` in the repository for an example.

### 5. Custom Implementations

For custom search interfaces, refer to the repository's implementation as a starting point:
- **Modal/overlay search**: Adapt the search form for modal dialogs
- **Inline search**: Embed search in page headers or sidebars
- **Mobile-optimized**: The default CSS is responsive, but you can customize further
- **Keyboard navigation**: Add arrow key support for results

## API Usage Examples

### 1. Testing with curl

Test the API from the command line:

```bash
# Basic search
curl "http://localhost/api/search.php?q=hugo"

# Search with filters
curl "http://localhost/api/search.php?q=search&section=docs&sort=date_desc"

# Get available sections
curl "http://localhost/api/search.php?action=sections"

# Advanced query syntax
curl "http://localhost/api/search.php?q=title:configuration+after:2024-01-01"
```

### 2. JavaScript Integration

The repository demonstrates fetch API usage patterns. Key concepts:
- Build query strings with URLSearchParams
- Handle async/await for cleaner code
- Parse JSON responses
- Handle errors with try/catch

See the API Reference documentation for complete parameter details and response formats.

### 3. Advanced Query Syntax

The API supports sophisticated query syntax. Examples:

**Field-specific searches:**
- `title:hugo` - Search in title only
- `tags:tutorial` - Search in tags
- `categories:development` - Search in categories

**Date filters:**
- `after:2024-01-01` - Posts after date
- `before:2024-12-31` - Posts before date
- `after:2024-01-01 before:2024-06-30` - Date range

**Boolean operators:**
- `hugo OR jekyll` - Either term
- `search engine` - Both terms (AND is default)

**Phrase search:**
- `"static site generator"` - Exact phrase

**Complex queries:**
- `title:configuration tags:hugo after:2024-01-01`

See the API Reference for complete query syntax documentation.

## Advanced Use Cases

### 1. Multi-language Search

For multi-language Hugo sites:
1. Filter pages by language in the search data template
2. Add a `language` field to the JSON output
3. Create separate search databases per language
4. Build each language's index: `php scripts/build-search-index.php public/en/search.db public/en/index.json`

See the Configuration documentation for template customization examples.

### 2. Section-Specific Search Pages

To create dedicated search pages for specific sections:
1. Create a search page with front matter specifying the section
2. Modify the JavaScript to read the section from page metadata
3. Automatically filter searches to that section

This is useful for documentation sites where you want separate search for docs vs. blog posts.

### 3. Search Analytics

Track search queries by:
- **Server-side**: Add logging to `static/api/search.php` to write queries to a log file
- **Client-side**: Use Google Analytics events or custom analytics endpoints
- **Data to track**: Query terms, result counts, timestamps, user agents

This helps identify popular topics and improve content.

### 4. Custom Ranking

Customize relevance by modifying `static/api/search.php`:
- Boost exact title matches
- Prioritize recent content
- Favor specific sections or tags
- Adjust BM25 weights

The repository's PHP code includes SQL ORDER BY clauses you can customize.

## Deployment Examples

### 1. GitHub Actions CI/CD

The repository can be deployed automatically with GitHub Actions:

**Key steps:**
1. Setup Hugo and PHP with required extensions
2. Build Hugo site: `hugo --minify`
3. Build search index: `php scripts/build-search-index.php public/search.db public/index.json`
4. Deploy the `public/` directory to your hosting

See the Getting Started guide for a complete GitHub Actions workflow example.

### 2. Traditional PHP Hosting

For shared hosting or VPS with PHP:

**Deployment process:**
1. Build locally with `./build.sh`
2. Upload `public/` directory via SFTP/FTP or rsync
3. Ensure proper permissions (644 for .db, 755 for .php files)
4. Verify PHP has SQLite3 extension enabled

Most traditional hosts (cPanel, Plesk, etc.) support this out of the box.

### 3. Docker Deployment

For containerized deployment:

**Requirements:**
- Base image: `php:8.1-apache` or `php:8.1-fpm`
- PHP extensions: `pdo`, `pdo_sqlite`
- Copy `public/` directory to web root
- Set proper permissions

The repository includes deployment script examples you can adapt.

### 4. Serverless Notes

**Important:** This solution uses PHP and SQLite, which work best with traditional or container hosting. For serverless platforms (Netlify, Vercel):
- You'll need to port the PHP code to serverless functions (Node.js/Python)
- Or use an alternative JavaScript-based search solution
- The SQLite database can work with serverless if using read-only mode

## Testing

Test your search implementation:

```bash
# Basic search
curl "http://localhost/api/search.php?q=hugo" | jq '.total'

# With filters
curl "http://localhost/api/search.php?q=search&section=docs" | jq '.results[] | .title'

# Advanced queries
curl "http://localhost/api/search.php?q=title:configuration"
curl "http://localhost/api/search.php?q=after:2024-01-01"

# Get sections
curl "http://localhost/api/search.php?action=sections" | jq '.sections'
```

## Performance Optimization

**Caching strategies:**
- Add HTTP cache headers to the PHP API (5-10 minute cache)
- Implement file-based result caching for common queries
- Use CDN for the search page assets
- Enable PHP opcache on your server

**Database optimization:**
- Run `VACUUM` and `ANALYZE` after building the index
- Keep content truncated to reasonable lengths
- Index only necessary fields

**Client-side:**
- Debounce search-as-you-type (300ms+)
- Limit initial result counts
- Implement virtual scrolling for many results

See the Configuration documentation for specific optimization settings.

## More Resources

- **[API Reference](/docs/api-reference/)** - Complete API documentation with all parameters and response formats
- **[Configuration](/docs/configuration/)** - Customization options for templates, tokenizers, and behavior
- **[Getting Started](/docs/getting-started/)** - Step-by-step installation guide
- **[GitHub Repository](https://github.com/dlnorman/hugo-lightweight-search)** - View all source code and contribute

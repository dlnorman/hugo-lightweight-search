# Hugo SQLite Search

A blazing-fast, self-hosted search engine for Hugo static sites using SQLite FTS5, PHP, and JavaScript. No external dependencies, no tracking, complete control.

🔍 **Live Demo**: [View Documentation](https://sandbox.darcynorman.net/hugo-lightweight-search/)

## Features

- **⚡ Lightning Fast**: Sub-50ms query times with BM25 ranking
- **🔧 Easy Setup**: Install in under 10 minutes
- **📦 Self-Hosted**: Complete data ownership, no external services
- **🎯 Advanced Queries**: Field searches, date filters, phrases, Boolean operators
- **🌐 Portable**: Single SQLite database file
- **📱 Lightweight**: Typical index: 50-200 KB per 100 pages
- **🔍 Smart Search**: Porter stemming for word variants
- **💪 Scalable**: Handles 1000+ pages with ease

## Quick Start

```bash
# 1. Clone this repository
git clone https://github.com/dlnorman/hugo-lightweight-search.git
cd hugo-lightweight-search

# 2. Copy files to your Hugo site (see Installation below)

# 3. Configure Hugo for JSON output (see Installation below)

# 4. Build your site with search
./build.sh

# 5. Test locally
cd public && php -S localhost:8080
# Visit http://localhost:8080/search/
```

## Requirements

- **Hugo** - Any recent version
- **PHP 7.4+** - With SQLite3 extension
- **Web Server** - Apache, Nginx, or PHP built-in server

```bash
# Verify your setup
hugo version
php -v
php -m | grep sqlite3
```

## Installation

### 1. Clone or Download

Get the source code:

```bash
# Clone the repository
git clone https://github.com/dlnorman/hugo-lightweight-search.git
cd hugo-lightweight-search

# Or download and extract the ZIP
# Then copy the required files to your Hugo site
```

### 2. Copy Files to Your Hugo Site

Copy these files from the repository to your Hugo site:

```bash
# Required files:
cp -r layouts/ /path/to/your-hugo-site/
cp -r scripts/ /path/to/your-hugo-site/
cp -r static/api/ /path/to/your-hugo-site/static/
cp content/pages/search.md /path/to/your-hugo-site/content/
cp build.sh /path/to/your-hugo-site/
```

### 3. Configure Hugo

Add to your `hugo.yaml` or `config.toml`:

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

menu:
  main:
    - name: Search
      url: /search/
      weight: 50
```

### 4. Build

```bash
chmod +x build.sh
./build.sh
```

This will:
1. Build your Hugo site
2. Generate `public/index.json` with searchable content
3. Create `public/search.db` SQLite database with FTS5 index
4. Set proper permissions

### 5. Deploy

Upload the `public/` directory to your web server. The search database and API go along with your static HTML.

## Usage

### Basic Search

Visit `/search/` on your site and try these queries:

```
hugo tutorial          # Find both words (AND logic)
"static site"          # Exact phrase
title:configuration    # Search in titles only
after:2024-01-01       # Posts after date
hugo OR jekyll         # Either term
```

### API Usage

```javascript
fetch('/api/search.php?q=hugo')
  .then(res => res.json())
  .then(data => {
    console.log(`Found ${data.total} results`);
    data.results.forEach(result => {
      console.log(result.title, result.url);
    });
  });
```

### Advanced Query Syntax

- **Phrases**: `"exact phrase"`
- **Field searches**: `title:term`, `tags:tutorial`, `categories:web`
- **Date filters**: `after:2024-01-01`, `before:2024-12-31`
- **Boolean**: `OR` for alternatives (AND is default)
- **Fuzzy**: Automatic prefix matching

## How It Works

### Build Time

1. **Hugo generates JSON** (`layouts/_default/search-data.json`):
   ```json
   [
     {
       "id": "123",
       "title": "Getting Started",
       "url": "/posts/getting-started/",
       "content": "Full post content...",
       "summary": "Brief description",
       "date": "2024-01-01",
       "section": "posts",
       "tags": ["tutorial", "beginner"],
       "categories": ["guides"]
     }
   ]
   ```

2. **PHP script creates SQLite database** (`scripts/build-search-index.php`):
   - Creates `search_content` table for metadata
   - Creates `search_fts` FTS5 virtual table with Porter stemmer
   - Populates with JSON data
   - Optimizes for compression and speed

### Runtime

3. **PHP API handles searches** (`static/api/search.php`):
   - Parses query syntax (phrases, fields, dates, operators)
   - Executes FTS5 MATCH query with BM25 ranking
   - Returns JSON with results, highlights, pagination

4. **JavaScript frontend** (`layouts/page/search.html`):
   - Provides search UI
   - Calls API with fetch()
   - Displays results with highlighting
   - Manages pagination and filters

## Performance

Typical performance metrics:

| Pages | Index Size | Build Time | Query Time |
|-------|------------|------------|------------|
| 100   | ~75 KB     | ~0.2s      | <10ms      |
| 500   | ~350 KB    | ~1.5s      | <15ms      |
| 1,000 | ~700 KB    | ~3s        | <25ms      |
| 5,000 | ~3.5 MB    | ~15s       | <50ms      |

## Documentation

Full documentation is available at the [demo site](https://sandbox.darcynorman.net/hugo-lightweight-search/):

- **[Getting Started](https://sandbox.darcynorman.net/hugo-lightweight-search/docs/getting-started/)** - Detailed installation guide
- **[API Reference](https://sandbox.darcynorman.net/hugo-lightweight-search/docs/api-reference/)** - Complete API documentation
- **[Configuration](https://sandbox.darcynorman.net/hugo-lightweight-search/docs/configuration/)** - Customization options
- **[Examples](https://sandbox.darcynorman.net/hugo-lightweight-search/docs/examples/)** - Real-world implementations

## Customization

### Exclude Pages from Search

Add to page front matter:

```yaml
---
title: "Private Page"
search: false
---
```

### Change Tokenizer

Edit `scripts/build-search-index.php`:

```php
// For multilingual support
tokenize='unicode61 remove_diacritics 1'

// For CJK languages
tokenize='trigram'
```

### Customize Search UI

Edit `layouts/page/search.html` to match your theme's design.

### Adjust Results Per Page

Edit `static/api/search.php`:

```php
private $resultsPerPage = 20;  // Change default
private $maxResults = 100;     // Change maximum
```

## Deployment Examples

### Traditional Hosting

```bash
./build.sh
rsync -avz public/ user@server:/var/www/html/
```

### GitHub Actions

```yaml
- name: Build Hugo and Search
  run: |
    hugo --minify
    php scripts/build-search-index.php public/search.db public/index.json
```

### Docker

```dockerfile
FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_sqlite
COPY public/ /var/www/html/
```

See [Examples documentation](/docs/examples/) for complete deployment guides.

## Troubleshooting

**Search returns no results:**
```bash
# Check database exists
ls -lh public/search.db

# Check database content
sqlite3 public/search.db "SELECT COUNT(*) FROM search_content;"

# Test API directly
curl "http://localhost:8080/api/search.php?q=test"
```

**JSON not generated:**
- Verify Hugo config has JSON output format
- Check template exists at `layouts/_default/search-data.json`
- Run `hugo --verbose` to see errors

**Database not created:**
- Verify PHP has SQLite3: `php -m | grep sqlite3`
- Check build script output for errors
- Run manually: `php scripts/build-search-index.php public/search.db public/index.json`

See [Troubleshooting](/docs/getting-started/#troubleshooting) for more solutions.

## Technology Stack

- **Hugo** - Static site generator
- **SQLite FTS5** - Full-text search engine with BM25 ranking
- **PHP** - Search API backend
- **JavaScript** - Search UI (vanilla, no frameworks)
- **Porter Stemmer** - Word variant matching

## Why This Stack?

- **No external services** - Everything runs on your server
- **No build complexity** - Simple PHP script, no Node.js build process
- **No large JavaScript bundles** - Index stays server-side
- **Works everywhere** - PHP and SQLite are available on almost all hosting
- **Easy to understand** - Straightforward code, easy to customize

## Contributing

Contributions are welcome! This is a reference implementation that can be improved:

- Bug fixes and improvements
- Documentation enhancements
- Additional examples
- Tokenizer configurations
- Performance optimizations

## License

MIT License - free for commercial and personal use.

## Credits

Built for the Hugo community. Inspired by the need for simple, self-hosted search that respects user privacy.

## Support

- 📖 [Documentation](/docs/)
- 💬 [GitHub Issues](https://github.com/)
- 🐦 [Twitter](https://twitter.com/)

---

**Ready to add search to your Hugo site?** Follow the [Getting Started guide](/docs/getting-started/) and you'll be searching in 10 minutes.

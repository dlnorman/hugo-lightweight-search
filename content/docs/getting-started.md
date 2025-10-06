---
title: "Getting Started"
date: 2025-10-06
draft: false
weight: 1
url: /docs/getting-started/
---

# Getting Started

Get Hugo Lightweight Search up and running on your site in under 10 minutes. This guide covers the complete setup of a PHP-powered search engine using Hugo, SQLite FTS5, and a JavaScript frontend.

## What You'll Build

A fast, server-side search engine that:
- Indexes your Hugo content automatically during build
- Uses SQLite FTS5 for powerful full-text search with stemming
- Provides a REST API for searching
- Includes a ready-to-use search page with JavaScript client

## Prerequisites

Before you begin, ensure you have:

- **Hugo** (any recent version) - [Install Hugo](https://gohugo.io/installation/)
- **PHP 7.4+** with SQLite3 extension enabled
- **SQLite3** command-line tools (optional, for debugging)
- **Web server** with PHP support (Apache, Nginx, or PHP built-in server for local development)

### Verify Prerequisites

```bash
# Check Hugo
hugo version

# Check PHP version and SQLite extension
php -v
php -m | grep sqlite3

# Check SQLite (optional)
sqlite3 --version
```

## Installation

### Step 1: Clone or Download the Repository

Get the source code from GitHub:

```bash
# Option 1: Clone with git
git clone https://github.com/dlnorman/hugo-lightweight-search.git
cd hugo-lightweight-search

# Option 2: Download ZIP
# Visit https://github.com/dlnorman/hugo-lightweight-search
# Click "Code" → "Download ZIP" and extract
```

### Step 2: Copy Files to Your Hugo Site

Copy the required files from this repository to your Hugo site:

```bash
# Navigate to your Hugo site directory
cd /path/to/your-hugo-site

# Copy the necessary files (adjust paths as needed)
cp -r /path/to/hugo-lightweight-search/layouts/* layouts/
cp -r /path/to/hugo-lightweight-search/scripts scripts/
cp -r /path/to/hugo-lightweight-search/static/api static/
cp /path/to/hugo-lightweight-search/build.sh .
cp /path/to/hugo-lightweight-search/content/pages/search.md content/

# Make build script executable
chmod +x build.sh
```

The copied files include:

- `layouts/_default/search-data.json` - JSON output template for search data
- `layouts/page/search.html` - Search page template with UI and JavaScript
- `scripts/build-search-index.php` - Index builder script
- `static/api/search.php` - Search API endpoint
- `content/pages/search.md` or `content/search.md` - Search page content
- `build.sh` - Build automation script

### Step 3: Configure Hugo for JSON Output

Add JSON output format to your `hugo.yaml` (or `config.toml`):

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

This configures Hugo to generate `/index.json` with your site's searchable content.

### Step 4: Verify File Structure

After copying, your Hugo site should have this structure:

```
your-hugo-site/
├── layouts/
│   ├── _default/
│   │   └── search-data.json       # JSON output template
│   └── page/
│       └── search.html             # Search page template
├── scripts/
│   └── build-search-index.php      # Index builder script
├── static/
│   └── api/
│       └── search.php              # Search API endpoint
├── content/
│   └── search.md                   # Search page content
└── build.sh                        # Build automation script
```

## Building the Search Index

### Initial Build

Run the build script:

```bash
./build.sh
```

This will:
1. Build your Hugo site with `hugo --minify`
2. Generate `/public/index.json` with searchable content
3. Create `/public/search.db` SQLite database with FTS5 index
4. Set proper file permissions

### Manual Build (Alternative)

If you prefer manual steps:

```bash
# 1. Build Hugo site
hugo --minify

# 2. Build search index
php scripts/build-search-index.php public/search.db public/index.json

# 3. Set permissions
chmod 644 public/search.db
chmod 755 public/api/search.php
```

### Rebuild After Content Changes

Whenever you update your content, rebuild the search index:

```bash
./build.sh
```

The script will rebuild everything from scratch, ensuring your search index stays in sync with your content.

## Testing Locally

### Option 1: PHP Built-in Server

Start PHP's built-in server from your `public` directory:

```bash
cd public
php -S localhost:8080
```

Then visit:
- Site: http://localhost:8080
- Search page: http://localhost:8080/search/
- API test: http://localhost:8080/api/search.php?q=test

### Option 2: Hugo Server with PHP

Run Hugo's development server alongside PHP:

```bash
# Terminal 1: Hugo server
hugo server

# Terminal 2: PHP server for API
cd public
php -S localhost:8081

# Configure your search page to use localhost:8081 for API calls during development
```

### Testing the Search API

Test the API directly:

```bash
# Basic search
curl "http://localhost:8080/api/search.php?q=getting+started"

# Search with section filter
curl "http://localhost:8080/api/search.php?q=hugo&section=docs"

# Phrase search
curl "http://localhost:8080/api/search.php?q=\"getting+started\""

# Field-specific search
curl "http://localhost:8080/api/search.php?q=title:search"

# Date range search
curl "http://localhost:8080/api/search.php?q=hugo+after:2024-01-01"

# Get available sections
curl "http://localhost:8080/api/search.php?action=sections"
```

## Deployment

### Deploy to Production Server

1. **Build locally** with production settings:

```bash
# Update baseURL in hugo.yaml first
hugo --minify
php scripts/build-search-index.php public/search.db public/index.json
```

2. **Upload the `public` directory** to your web server:

```bash
# Example using rsync
rsync -avz --delete public/ user@yourserver.com:/var/www/html/

# Or using FTP/SFTP client
```

3. **Verify permissions** on the server:

```bash
chmod 644 /var/www/html/search.db
chmod 644 /var/www/html/api/search.php
```

### Web Server Configuration

#### Apache

No special configuration needed. The `.php` files will be processed automatically if `mod_php` or PHP-FPM is configured.

Optional `.htaccess` for cleaner URLs:

```apache
RewriteEngine On
RewriteRule ^search$ /search/ [R=301,L]
```

#### Nginx

Add PHP processing to your server block:

```nginx
server {
    listen 80;
    server_name yoursite.com;
    root /var/www/html;
    index index.html;

    # PHP processing for search API
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Hugo static files
    location / {
        try_files $uri $uri/ =404;
    }
}
```

### CI/CD Integration

#### GitHub Actions Example

```yaml
name: Build and Deploy

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Hugo
        uses: peaceiris/actions-hugo@v2
        with:
          hugo-version: 'latest'

      - name: Build site and search index
        run: |
          chmod +x build.sh
          ./build.sh

      - name: Deploy
        run: |
          rsync -avz --delete public/ ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:${{ secrets.DEPLOY_PATH }}
```

## Troubleshooting

### Search Returns No Results

**Problem**: Search API returns empty results or errors.

**Solutions**:
1. Verify database exists: `ls -lh public/search.db`
2. Check database content: `sqlite3 public/search.db "SELECT COUNT(*) FROM search_content;"`
3. Verify JSON was generated: `ls -lh public/index.json`
4. Check PHP error logs: `tail -f /var/log/php-errors.log`
5. Test API directly: `curl "http://localhost:8080/api/search.php?q=test"`

### Database File Not Found

**Problem**: `search.db` not created or missing.

**Solutions**:
1. Ensure PHP has SQLite extension: `php -m | grep sqlite3`
2. Check file permissions in `public` directory
3. Run build script with verbose output: `bash -x build.sh`
4. Manually run PHP script: `php scripts/build-search-index.php public/search.db public/index.json`

### JSON Not Generated

**Problem**: `index.json` not created by Hugo.

**Solutions**:
1. Verify Hugo config has JSON output format (see Step 2)
2. Check template exists: `layouts/_default/search-data.json`
3. Build with verbose: `hugo --verbose`
4. Check for template errors in Hugo output

### Permission Denied Errors

**Problem**: Web server cannot read database or execute PHP.

**Solutions**:
1. Set correct permissions:
   ```bash
   chmod 644 public/search.db
   chmod 755 public/api/search.php
   ```
2. Check web server user owns files: `chown www-data:www-data public/search.db`
3. Verify SELinux context if on RHEL/CentOS: `chcon -R -t httpd_sys_content_t public/`

### Search Query Syntax Not Working

**Problem**: Advanced search features (phrases, field searches) don't work.

**Solutions**:
1. Check query encoding in URLs (spaces should be `+` or `%20`)
2. Escape quotes properly: `q="exact phrase"` → `q=%22exact%20phrase%22`
3. Verify FTS5 is enabled: `sqlite3 public/search.db "SELECT * FROM search_fts LIMIT 1;"`
4. Review search.php logs for query parsing errors

### Slow Search Performance

**Problem**: Search takes too long to return results.

**Solutions**:
1. Ensure FTS5 index is optimized (build script does this automatically)
2. Limit result size with `&limit=10` parameter
3. Check database size: `ls -lh public/search.db`
4. Consider splitting large sites into section-specific indexes
5. Enable PHP opcache for better performance

### Build Script Fails

**Problem**: `build.sh` exits with errors.

**Solutions**:
1. Check Hugo is installed: `hugo version`
2. Verify PHP is installed: `php -v`
3. Ensure script is executable: `chmod +x build.sh`
4. Run with debugging: `bash -x build.sh`
5. Check disk space: `df -h`

## Next Steps

Now that your search is working:

1. **Customize the search page** - Edit `/layouts/page/search.html` to match your site's design
2. **Configure search behavior** - Modify `/static/api/search.php` for custom ranking or filters
3. **Exclude pages from search** - Add `search: false` to page front matter
4. **Add search to navigation** - Link to `/search/` from your main menu
5. **Monitor performance** - Check database size and query performance as content grows

## Advanced Features

The implementation includes powerful features out of the box:

- **Porter Stemming**: Automatically matches word variants (searching "run" finds "running", "runs")
- **BM25 Ranking**: Advanced relevance scoring that considers term frequency and document length
- **Field Weighting**: Title matches ranked higher than content matches
- **Phrase Search**: Use quotes for exact matches: `"getting started"`
- **Field-Specific Search**: Target specific fields: `title:hugo`, `tags:tutorial`
- **Date Filtering**: Filter by date: `after:2024-01-01`, `before:2024-12-31`
- **Boolean Operators**: Use OR for alternatives: `hugo OR jekyll`
- **Fuzzy Matching**: Automatic prefix matching for partial terms

## Performance Characteristics

Typical performance metrics:
- **Index Build Time**: ~0.1-0.5 seconds per 100 pages
- **Database Size**: ~50-200 KB per 100 pages (highly compressed with FTS5)
- **Search Query Time**: <50ms for most queries on databases with 1000+ pages
- **Memory Usage**: Minimal - SQLite loads only required data

This makes it suitable for sites ranging from small blogs to large documentation sites with thousands of pages.

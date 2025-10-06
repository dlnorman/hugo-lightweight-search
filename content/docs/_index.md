---
title: "Documentation"
date: 2025-10-06
draft: false
url: /docs/
---

# Hugo SQLite Search Documentation

Welcome to Hugo SQLite Search - a powerful, lightweight search engine for your Hugo static sites. Built with PHP, SQLite FTS5, and a simple JavaScript frontend, it delivers fast, server-side full-text search without complex dependencies.

## Overview

Hugo SQLite Search is a complete search solution that:

- **Indexes during build** - Automatically creates a SQLite FTS5 database from your Hugo content
- **Searches server-side** - PHP-powered REST API with advanced query capabilities
- **Ranks intelligently** - BM25 algorithm with field weighting and Porter stemming
- **Stays portable** - Single SQLite database file, easy to deploy anywhere
- **Performs fast** - Sub-50ms queries on databases with 1000+ pages

Perfect for blogs, documentation sites, and content-rich Hugo projects of any size.

## Documentation Sections

### [Getting Started](getting-started/)
Complete installation guide to get search running on your Hugo site in under 10 minutes. Includes step-by-step setup, build scripts, and deployment instructions.

### [API Reference](api-reference/)
Comprehensive API documentation covering all search endpoints, query parameters, response formats, and advanced search syntax.

### [Configuration](configuration/)
Learn how to customize search behavior, adjust ranking weights, configure field indexing, and optimize performance for your specific needs.

### [Examples](examples/)
Real-world implementation examples including custom search interfaces, section filtering, advanced queries, and integration patterns.

## Quick Start

```bash
# 1. Copy files to your Hugo site
# 2. Configure Hugo for JSON output
# 3. Run the build script
./build.sh

# 4. Test locally
cd public && php -S localhost:8080
```

Visit [Getting Started](getting-started/) for detailed instructions.

## Common Tasks

- **First-time setup** - Follow the [Installation guide](getting-started/#installation)
- **Rebuild search index** - Run `./build.sh` after content updates
- **Customize search UI** - Edit `/layouts/page/search.html` template
- **Exclude pages** - Add `search: false` to page front matter
- **Test search API** - Use `curl` or visit `/api/search.php?q=test`
- **Debug issues** - Check the [Troubleshooting section](getting-started/#troubleshooting)

## System Requirements

### Required
- **Hugo** - Any recent version ([Install Hugo](https://gohugo.io/installation/))
- **PHP 7.4+** - With SQLite3 extension enabled
- **Web server** - Apache, Nginx, or PHP built-in server

### Optional
- **SQLite3 CLI** - For database debugging and inspection

### Verify Your Setup
```bash
hugo version              # Check Hugo installation
php -v                    # Check PHP version
php -m | grep sqlite3     # Verify SQLite extension
```

## Key Features

- **Porter Stemming** - Matches word variants automatically (search "run" finds "running", "runs")
- **Phrase Search** - Exact matching with quoted queries: `"getting started"`
- **Field-Specific Search** - Target fields: `title:hugo`, `tags:tutorial`
- **Date Filtering** - Time-based queries: `after:2024-01-01`, `before:2024-12-31`
- **Section Filtering** - Limit results by content section
- **BM25 Ranking** - Sophisticated relevance scoring
- **Fuzzy Matching** - Automatic prefix matching for partial terms
- **Boolean Operators** - Combine terms with OR logic

## Learn More

### Tutorials and Guides
Visit our [blog](/posts/) for in-depth tutorials:
- [Introducing Lightweight Search](/posts/introducing-lightweight-search/) - Project overview and philosophy
- [Search Performance Tips](/posts/search-performance-tips/) - Optimization strategies
- [Building Custom Tokenizers](/posts/building-custom-tokenizers/) - Advanced customization

### Performance
- **Build time**: ~0.1-0.5 seconds per 100 pages
- **Database size**: ~50-200 KB per 100 pages
- **Query time**: <50ms typical for 1000+ page sites
- **Memory usage**: Minimal - SQLite loads only needed data

### Get Help
- Browse the documentation sections above
- Check [Troubleshooting](getting-started/#troubleshooting) for common issues
- Review [Examples](examples/) for implementation patterns

---

Ready to add search to your Hugo site? Start with [Getting Started](getting-started/).

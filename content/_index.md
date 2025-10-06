---
title: "Home"
---

# Hugo SQLite Search

A blazing-fast search solution for Hugo sites using SQLite FTS5 and PHP.

## Key Features

- **Lightning Fast**: SQLite FTS5 full-text search with BM25 ranking delivers results in milliseconds
- **Easy Integration**: Drop into any Hugo site with minimal configuration
- **Minimal Footprint**: Small SQLite database, no external dependencies
- **Advanced Query Syntax**: Supports phrases, field-specific searches, date filters, and Boolean operators
- **Stemming**: Automatic word variant matching using Porter stemmer
- **Self-Hosted**: Keep your search data under your control

## How It Works

1. **Hugo generates** a JSON index of your content during build
2. **PHP script converts** JSON to an optimized SQLite database with FTS5 indexes
3. **PHP API** provides fast search with advanced query capabilities
4. **JavaScript frontend** delivers a smooth search experience

## Performance

Built for speed:
- Sub-10ms query response times
- Compact database size (typical sites: 1-5MB)
- BM25 ranking for relevance
- Automatic query optimization

## Use Cases

Perfect for:
- Documentation sites
- Blogs and personal sites
- Knowledge bases
- Content-heavy Hugo sites
- Projects requiring self-hosted search

## Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/dlnorman/hugo-lightweight-search.git

# 2. Copy files to your Hugo site

# 3. Build your site with search
./build.sh

# 4. Deploy and search!
```

See our [Getting Started](/docs/getting-started/) guide for full installation instructions.

---
title: "About"
date: 2025-10-06
draft: false
showtoc: false
---

# About Hugo SQLite Search

Hugo SQLite Search is an open-source, self-hosted search solution specifically designed for Hugo static sites.

## Our Mission

To provide a search solution that is:
- **Simple** to integrate into any Hugo site
- **Fast** enough for production use
- **Powerful** with advanced query features
- **Self-hosted** for complete data ownership

## The Problem

Static sites need search functionality, but most solutions require:
- External services (privacy concerns, costs)
- Complex JavaScript libraries (large bundle sizes)
- Client-side indexing (slow initial load)
- Limited query capabilities

## Our Solution

Hugo SQLite Search uses a proven tech stack:

### SQLite FTS5
- Fast full-text search engine
- BM25 ranking algorithm
- Porter stemming for word variants
- Compact database format

### PHP API
- Fast, efficient backend
- Advanced query parsing
- Field-specific searches
- Date range filtering
- Pagination and sorting

### Simple Integration
- Builds during Hugo site generation
- Single database file to deploy
- No external dependencies
- Works with any web server supporting PHP

## Technical Approach

1. **Build Time**: Hugo generates JSON with all searchable content
2. **Index Generation**: PHP script creates optimized SQLite database
3. **Runtime**: PHP API handles search requests
4. **Frontend**: Vanilla JavaScript provides search interface

## Open Source

This project is open source and MIT licensed. Contributions welcome!

## Use Cases

- **Personal blogs**: Fast search without external services
- **Documentation sites**: Advanced query syntax for technical docs
- **Knowledge bases**: Stem ming and relevance ranking
- **Content-heavy sites**: Efficient indexing of thousands of pages
- **Privacy-focused projects**: Self-hosted, no tracking

## Requirements

- Hugo static site generator
- PHP 7.4+ (with SQLite3 support)
- Web server (Apache, Nginx, etc.)

## License

MIT License - free for commercial and personal use.

#!/bin/bash

# Hugo Search Build Script
# Builds Hugo site and creates SQLite search index

set -e  # Exit on any error

# ============================================================================
# Configuration
# ============================================================================

# Set this to match the publishDir in your Hugo config (hugo.yaml/config.toml)
# Default: "public"
#
# If you've customized Hugo's output directory with:
#   publishDir: "dist"
# or
#   publishdir = "dist"
#
# Then update this variable to match:
publishDir="public"

# ============================================================================

echo "🏗️  Building Hugo site with search…"

# Build Hugo site
echo "📝 Building Hugo site…"
hugo --minify --cleanDestinationDir

# Find the generated search data file
SEARCH_DATA_FILE="$publishDir/index.json"

if [ ! -f "$SEARCH_DATA_FILE" ]; then
    echo "❌ Error: Search data not generated. Check your Hugo configuration."
    exit 1
fi

echo "✅ Hugo build complete"
echo "📄 Found search data: $SEARCH_DATA_FILE"

# Build search database
echo "🔍 Building search database…"
php scripts/build-search-index.php "$publishDir/search.db" "$SEARCH_DATA_FILE"

# Remove the temporary index.json file - no need to upload it to the server
# The SQLite database contains all the search data
rm "$publishDir/index.json"

echo "✅ Search database created"

# Set proper permissions for web server
chmod 644 "$publishDir/search.db"
chmod 755 "$publishDir/api/search.php"

echo "🎉 Build complete!"
echo ""
echo "🚀 Ready to deploy!"


# echo "this would be a handy place to add an rsync command to automatically publish the site to your server…"

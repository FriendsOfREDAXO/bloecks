#!/bin/bash
# bloecks asset sync script
# This script is now simplified since the Grunt build system 
# already handles asset compilation to the correct directories.

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

# Clean up any duplicate asset files that may have been created by previous builds
# The build system now places assets directly in the correct /assets/ directories

echo "Cleaning up duplicate asset files..."

# Remove duplicate CSS/JS files in plugin root directories
# (These are created by the old rsync logic and should not exist)
find "$SCRIPT_DIR/plugins" -maxdepth 2 -name "css" -type d -path "*/plugins/*/css" -not -path "*/assets/*" -exec rm -rf {} + 2>/dev/null || true
find "$SCRIPT_DIR/plugins" -maxdepth 2 -name "js" -type d -path "*/plugins/*/js" -not -path "*/assets/*" -exec rm -rf {} + 2>/dev/null || true

# Remove duplicate files in addon root directory 
rm -rf "$SCRIPT_DIR/css" 2>/dev/null || true
rm -rf "$SCRIPT_DIR/js" 2>/dev/null || true

echo "Asset cleanup complete. Assets are now only in proper /assets/ directories."

exit 0

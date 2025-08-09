# blÖcks REDAXO Addon

Always follow these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

blÖcks is a REDAXO CMS addon that adds features to content modules including drag & drop reordering, cut & copy functionality, and online/offline status management. The addon uses a Node.js/Grunt build system for frontend asset compilation.

## Working Effectively

### Initial Setup
- Bootstrap the development environment:
  - Node.js v20+ and npm are pre-installed
  - Run `npm install` -- takes ~35 seconds, may show vulnerabilities that don't affect functionality
  - Grunt CLI is pre-installed globally

### Build Process
- **Development build with watch mode:**
  - `grunt` -- NEVER CANCEL: runs build then starts watch mode indefinitely. Use Ctrl+C to stop when done.
  - Processes LESS → CSS, concatenates/uglifies JS, syncs assets to correct directories
  - Watch mode automatically rebuilds when source files change

- **Production build:**
  - `grunt --production` -- takes ~2 seconds, NEVER CANCEL: Set timeout to 30+ seconds
  - Compresses CSS, uglifies JS, generates distribution-ready assets
  - Does not include source maps

### Asset Synchronization
- `./rsync.sh` -- takes <1 second
  - Syncs compiled assets from individual plugin directories to main assets directory
  - Uses rsync.exclude file to filter files
  - Required after manual asset changes

### Release Management  
- **Version updates:**
  - `./version.sh "4.0.3"` -- DOES NOT WORK on Linux (macOS-specific sed syntax)
  - Updates version numbers in .php and .yml files
  - Document this limitation: "version.sh script fails on Linux due to macOS-specific sed syntax"

- **Create release package:**
  - `./zip.sh` -- takes ~2 seconds, NEVER CANCEL: Set timeout to 30+ seconds  
  - Runs production build first, then creates zip file in parent directory
  - Excludes development files (node_modules, assets_src, .git, etc.)
  - Creates bloecks.zip ready for REDAXO installer

## Validation

### Build Validation
- Always run `grunt --production` after making changes to verify assets compile correctly
- Check that files are created in assets/css/ and assets/js/ directories
- Verify plugin assets are synced to correct locations after rsync

### Manual Testing Scenarios
- This is a backend-focused addon - no frontend UI to test
- Verify that build process completes without errors
- Check that all LESS files compile to CSS without syntax errors
- Confirm JavaScript files concatenate and uglify properly

## File Structure & Navigation

### Key Directories
- `/lib/` - Core PHP classes (bloecks_abstract, bloecks_backend, Bloecks)  
- `/assets_src/` - Source files for compilation (LESS, JS)
- `/assets/` - Compiled output files (CSS, JS)
- `/plugins/cutncopy/` - Cut & copy functionality plugin
- `/plugins/dragndrop/` - Drag & drop reordering plugin
- `/pages/` - Admin interface pages
- `/lang/` - Translation files

### Important Files
- `package.yml` - Addon metadata and dependencies
- `boot.php` - Addon initialization
- `Gruntfile.js` - Build configuration
- `package.json` - Node.js dependencies
- `README.developers.md` - Development documentation (German)

### Asset Processing
- Source files in `**/assets_src/less/be.less` → compiled to `**/assets/css/be.css`
- Source files in `**/assets_src/js/be/**/*.js` → concatenated to `**/assets/js/be.js`
- Frontend files follow same pattern with `fe.less` and `fe/` directory
- Plugin assets follow same structure within their directories

## Common Tasks

The following are validated commands and their expected outputs:

### Install Dependencies
```bash
npm install
# Takes ~35 seconds
# Shows deprecation warnings and 14 vulnerabilities - these don't affect functionality
# Use timeout of 60+ seconds
```

### Development Build
```bash  
grunt
# Compiles assets then starts watch mode
# Initial build takes ~2 seconds, then waits for file changes
# Stop with Ctrl+C when done
# NEVER CANCEL: Set timeout to indefinite for watch mode
```

### Production Build
```bash
grunt --production
# Takes ~2 seconds
# Outputs: "Done."
# Creates minified, production-ready assets
# NEVER CANCEL: Set timeout to 30+ seconds  
```

### Create Release
```bash
./zip.sh
# Takes ~2 seconds total (includes grunt --production)
# Creates ../bloecks.zip file ready for distribution
# NEVER CANCEL: Set timeout to 30+ seconds
```

### Sync Assets
```bash
./rsync.sh  
# Takes <1 second
# No output on success
```

## Repository Information

### Technology Stack
- **Backend:** PHP 7+ (REDAXO CMS addon)
- **Frontend Build:** Node.js, Grunt, LESS, UglifyJS
- **Asset Management:** rsync for directory synchronization

### Dependencies
- REDAXO ^5.5.0
- PHP >=7  
- structure/content ^2.1.0
- Node.js for build system

### Plugin Architecture
- `cutncopy` - Copy and paste content blocks between articles
- `dragndrop` - Drag and drop reordering of content blocks
- Status management was deprecated in v3.0.0 (now in REDAXO core)

Always run production builds and asset sync after making changes to ensure compatibility with the REDAXO addon system.
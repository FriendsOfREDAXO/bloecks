# blÖcks REDAXO Addon

Always follow these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

blÖcks is a REDAXO CMS addon that adds features to content modules including drag & drop reordering, cut & copy functionality, and online/offline status management. The addon uses a modern pnpm/PostCSS build system for frontend asset compilation.

## Working Effectively

### Initial Setup
- Bootstrap the development environment:
  - Node.js v20+ and pnpm are pre-installed
  - Run `pnpm install` -- takes ~30 seconds, may show vulnerabilities that don't affect functionality

### Build Process
- **Development build:**
  - `pnpm run build` -- builds CSS and JS assets from source files
  - `pnpm run dev` or `pnpm run watch` -- NEVER CANCEL: runs build then starts watch mode indefinitely. Use Ctrl+C to stop when done.
  - Processes CSS with PostCSS/Autoprefixer, concatenates/minifies JS
  - Watch mode automatically rebuilds when source files change

- **Production build:**
  - `pnpm run build:prod` -- takes ~2 seconds, NEVER CANCEL: Set timeout to 30+ seconds
  - Minifies CSS and JS, generates distribution-ready assets
  - No source maps in production mode

### Asset Management
- **No rsync required** - Direct compilation from `assets_src/` to `assets/`
- CSS: `assets_src/css/be.css` → `assets/css/be.css` (PostCSS with Autoprefixer)
- JS: `assets_src/js/be/*.js` → concatenated and minified to `assets/js/be.js`
- Modern CSS replaces LESS - no preprocessor dependencies

### Release Management  
- **Version updates:**
  - `pnpm run version "4.0.3"` -- cross-platform Node.js script
  - Updates version numbers in package.json, package.yml and PHP files
  - Works on all operating systems (replaces macOS-only version.sh)

- **Create release package:**
  - `pnpm run package` -- takes ~2 seconds, NEVER CANCEL: Set timeout to 30+ seconds  
  - Runs production build first, then creates zip file in parent directory
  - Excludes development files (node_modules, assets_src, .git, scripts/, etc.)
  - Creates bloecks.zip ready for REDAXO installer

## Validation

### Build Validation
- Always run `pnpm run build:prod` after making changes to verify assets compile correctly
- Check that files are created in assets/css/ and assets/js/ directories
- CSS should be autoprefixed and optimized for browser compatibility
- JS should be concatenated and minified properly

### Manual Testing Scenarios
- This is a backend-focused addon - no frontend UI to test
- Verify that build process completes without errors
- Check that all CSS files compile with proper vendor prefixes
- Confirm JavaScript files concatenate and minify properly

## File Structure & Navigation

### Key Directories
- `/lib/` - Core PHP classes (bloecks_abstract, bloecks_backend, Bloecks, bloecks_cutncopy, bloecks_dragndrop)  
- `/assets_src/` - Source files for compilation (CSS, JS)
- `/assets/` - Compiled output files (CSS, JS)
- `/pages/` - Admin interface pages (overview, cutncopy, dragndrop, docs)
- `/scripts/` - Build and version management scripts
- `/lang/` - Translation files

### Important Files
- `package.yml` - Addon metadata and dependencies
- `boot.php` - Addon initialization
- `package.json` - pnpm dependencies and build scripts
- `pnpm-lock.yaml` - Dependency lock file
- `README.developers.md` - Development documentation (German)

### Asset Processing
- Source CSS in `assets_src/css/be.css` → compiled to `assets/css/be.css` with PostCSS
- Source JS in `assets_src/js/be/*.js` → concatenated and minified to `assets/js/be.js`
- No plugin directories - all functionality integrated into main addon
- Modern CSS instead of LESS preprocessing

## Common Tasks

The following are validated commands and their expected outputs:

### Install Dependencies
```bash
pnpm install
# Takes ~30 seconds
# May show warnings about package manager conflicts - these don't affect functionality
# Use timeout of 60+ seconds
```

### Development Build
```bash  
pnpm run build
# Compiles CSS and JS assets
# Takes ~2 seconds
# Creates development assets with proper vendor prefixes
```

### Development with Watch Mode
```bash
pnpm run dev
# Runs build then starts watch mode
# Initial build takes ~2 seconds, then waits for file changes
# Stop with Ctrl+C when done
# NEVER CANCEL: Set timeout to indefinite for watch mode
```

### Production Build
```bash
pnpm run build:prod
# Takes ~2 seconds
# Creates minified, production-ready assets
# NEVER CANCEL: Set timeout to 30+ seconds  
```

### Update Version
```bash
pnpm run version "4.0.3"
# Updates version in package.json, package.yml, and PHP files
# Works on all operating systems
# Takes <1 second
```

### Create Release Package
```bash
pnpm run package
# Takes ~2 seconds total (includes production build)
# Creates ../bloecks.zip file ready for distribution
# NEVER CANCEL: Set timeout to 30+ seconds
```

## Repository Information

### Technology Stack
- **Backend:** PHP 7+ (REDAXO CMS addon)
- **Frontend Build:** Node.js, pnpm, PostCSS, Terser
- **Asset Management:** Direct compilation from assets_src to assets

### Dependencies
- REDAXO ^5.5.0
- PHP >=7  
- structure/content ^2.1.0
- Node.js + pnpm for build system

### Integrated Architecture
- **Cut & Copy** - Copy and paste content blocks between articles (formerly cutncopy plugin)
- **Drag & Drop** - Drag and drop reordering of content blocks (formerly dragndrop plugin)
- **No plugins** - All functionality integrated into main addon for REDAXO 6 compatibility
- Status management was deprecated in v3.0.0 (now in REDAXO core)

Always run production builds after making changes to ensure compatibility with the REDAXO addon system.
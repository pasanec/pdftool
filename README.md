# Pdf Tool (AI generated)

A Nextcloud app to merge and split PDF files directly in the Files app.

## Installation

Place this app in **nextcloud/apps/** directory.

### System Requirements

- **Nextcloud**: Version 30-32
- **PHP**: 8.0 or higher (recommended: 8.3+)
- **Node.js**: Version 16+ (recommended: 20+)
- **npm**: Latest version recommended
- **Ghostscript**: Required for PDF processing operations
  - Install on Ubuntu/Debian: `sudo apt-get install ghostscript`
  - Install on RHEL/CentOS: `sudo yum install ghostscript`
  - Install on macOS: `brew install ghostscript`

## Building the app

The app uses modern build tools including Webpack, TypeScript, and Vue.js. Build the app using the provided Makefile:

```bash
make
```

This command will:
1. Install PHP dependencies via Composer (if `composer.json` is present)
2. Install JavaScript/TypeScript dependencies via npm (if `package.json` is present)
3. Build the frontend assets using Webpack

### Build Requirements

The following tools must be available:

- **make**: For running the build process
- **which**: For detecting installed tools
- **tar**: For creating distribution archives
- **curl**: For fetching dependencies if composer is not installed locally
- **npm**: For managing JavaScript dependencies and building frontend assets
- **composer**: For managing PHP dependencies (will be downloaded if not present)

### Frontend Build System

The app uses the following modern frontend stack:

- **Webpack 5**: Module bundler configured via `@nextcloud/webpack-vue-config`
- **TypeScript 5**: Type-safe JavaScript with `.ts` files in `src/`
- **Vue 2.7**: Component-based UI framework
- **Vue Class Component**: Class-style Vue components with TypeScript decorators

### Available npm Scripts

```bash
# Build for production (minified, optimized)
npm run build

# Build for development (with source maps)
npm run dev

# Watch mode for development (auto-rebuild on changes)
npm run watch

# Development server with hot reload
npm run serve

# Lint JavaScript and Vue files
npm run lint

# Lint and auto-fix issues
npm run lint:fix

# Lint CSS/SCSS files
npm run stylelint

# Lint and auto-fix CSS/SCSS issues
npm run stylelint:fix
```

## PHP Dependencies

The app uses the following PHP libraries (managed via Composer):

- **tecnickcom/tcpdf**: PDF generation and manipulation
- **setasign/fpdi-tcpdf**: Import existing PDF documents into TCPDF
- **smalot/pdfparser**: Parse and extract information from PDF files
- **phpunit/phpunit**: Unit testing framework (dev dependency)

Install or update PHP dependencies:

```bash
composer install --prefer-dist
```

## Running Tests

### PHP Tests

Run all PHP tests (unit and integration):

```bash
make test
```

Or run tests individually:

```bash
# Unit tests
./vendor/phpunit/phpunit/phpunit -c phpunit.xml

# Integration tests
./vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml
```

### JavaScript Tests

Currently, JavaScript tests are not configured. The npm test script returns an error indicating this is a work in progress.

## Linting and Code Quality

### JavaScript/TypeScript Linting

The app uses `@nextcloud/eslint-config` for code style enforcement:

```bash
npm run lint        # Check for issues
npm run lint:fix    # Auto-fix issues
```

### CSS/SCSS Linting

The app uses `@nextcloud/stylelint-config` for CSS code style:

```bash
npm run stylelint      # Check for issues
npm run stylelint:fix  # Auto-fix issues
```

## Publishing to App Store

### Create App Store Package

First, ensure the app builds successfully, then create the distribution package:

```bash
make && make appstore
```

The archive will be created at:
```
build/artifacts/appstore/pdftool.tar.gz
```

This archive can be uploaded to the [Nextcloud App Store](https://apps.nextcloud.com/).

### What's Included in the App Store Package

The app store package excludes:
- Version control files (`.git`, `.gitignore`)
- Build tools and configuration (`Makefile`, `webpack.config.js`, etc.)
- Development dependencies (`node_modules/`, `tests/`, `composer.*`)
- Test files and configurations (`phpunit.xml`, etc.)
- Build artifacts (`build/`)
- Log files

### Source Package

To create a source package (for developers):

```bash
make source
```

The source archive will be created at:
```
build/artifacts/source/pdftool.tar.gz
```

## Development Workflow

1. **Initial Setup**:
   ```bash
   composer install
   npm install
   ```

2. **Development Build** (with file watching):
   ```bash
   npm run watch
   ```

3. **Before Committing**:
   ```bash
   npm run lint:fix
   npm run stylelint:fix
   make test
   ```

4. **Production Build**:
   ```bash
   make
   ```

## Clean Up

Remove build artifacts:

```bash
make clean
```

Remove all build artifacts and dependencies (complete cleanup):

```bash
make distclean
```

This will remove:
- `build/` directory
- `vendor/` (Composer packages)
- `node_modules/` (npm packages)

## Project Structure

```
pdftool/
├── appinfo/           # App metadata and configuration
├── css/               # Stylesheets
├── img/               # Icons and images
├── js/                # Built JavaScript files (generated)
├── lib/               # PHP backend code
├── src/               # TypeScript/Vue source files
│   ├── main.js        # Main app entry point
│   ├── admin.js       # Admin settings entry point
│   ├── Merge.vue      # Merge PDF component
│   ├── Split.vue      # Split PDF component
│   └── *.ts           # TypeScript modules
├── templates/         # PHP templates
├── tests/             # Unit and integration tests
├── vendor/            # PHP dependencies (generated)
├── node_modules/      # Node dependencies (generated)
├── composer.json      # PHP dependencies
├── package.json       # JavaScript dependencies
├── tsconfig.json      # TypeScript configuration
├── webpack.config.js  # Webpack build configuration
├── Makefile          # Build automation
└── README.md         # This file
```

## Troubleshooting

### Build Fails

1. Ensure all system requirements are installed
2. Clear caches and rebuild:
   ```bash
   make distclean
   composer install
   npm install
   npm run build
   ```

### Ghostscript Not Found

If PDF operations fail, verify Ghostscript is installed:

```bash
which gs
gs --version
```

### Permission Issues

Ensure the web server has read/write access to:
- The app directory
- Nextcloud's data directory

## License

This app is licensed under the GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See the COPYING file for details.

### Third-Party Dependencies and Attributions

This application uses the following third-party libraries and software, each subject to their respective licenses:

#### PHP Dependencies

- **TCPDF** (LGPL-3.0)
  - Copyright © 2002-2025 Nicola Asuni - Tecnick.com LTD
  - Licensed under the GNU Lesser General Public License v3.0
  - Website: https://tcpdf.org

- **FPDI-TCPDF** (MIT License)
  - Copyright © 2020 Setasign GmbH & Co. KG
  - Licensed under the MIT License
  - Website: https://www.setasign.com

- **PDFParser** (LGPL-3.0)
  - Copyright © Sébastien Malot
  - Licensed under the GNU Lesser General Public License v3.0
  - Repository: https://github.com/smalot/pdfparser

#### JavaScript/TypeScript Dependencies

- **Vue.js** (MIT License)
  - Copyright © 2013-present Yuxi (Evan) You
  - Licensed under the MIT License
  - Website: https://vuejs.org

- **@nextcloud/vue** (AGPL-3.0-or-later)
  - Copyright © Nextcloud GmbH
  - Licensed under the GNU Affero General Public License v3.0 or later
  - Repository: https://github.com/nextcloud/nextcloud-vue

- **vue-class-component** (MIT License)
  - Licensed under the MIT License
  - Repository: https://github.com/vuejs/vue-class-component

- **vue-property-decorator** (MIT License)
  - Licensed under the MIT License
  - Repository: https://github.com/kaorun343/vue-property-decorator

- **vuedraggable** (MIT License)
  - Copyright © 2016-2019 David Desmaisons
  - Licensed under the MIT License
  - Repository: https://github.com/SortableJS/Vue.Draggable

#### System Dependencies

- **Ghostscript** (AGPL-3.0+)
  - Copyright © 2023 Artifex Software, Inc.
  - Licensed under the GNU Affero General Public License v3.0 or later
  - Website: https://www.ghostscript.com
  - Note: Ghostscript is required for PDF processing operations

#### License Texts

- **MIT License**: Allows commercial use, modification, distribution, and private use. Requires preservation of copyright and license notices.
- **LGPL-3.0**: GNU Lesser General Public License allows linking from proprietary software under certain conditions. Full text available at: https://www.gnu.org/licenses/lgpl-3.0.html
- **AGPL-3.0**: GNU Affero General Public License requires source code disclosure for network use. Full text available at: https://www.gnu.org/licenses/agpl-3.0.html

For complete license texts of all dependencies, please refer to the respective `LICENSE` files in the `vendor/` and `node_modules/` directories.

## Links

- **Issues**: https://github.com/pasanec/pdftool/issues
- **Repository**: https://github.com/pasanec/pdftool
- **App Store**: https://apps.nextcloud.com/

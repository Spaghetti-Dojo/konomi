# Konomi - WordPress Plugin Development Guide

## Project Overview
**Konomi** is a WordPress plugin that enables users to save posts as favorites using WordPress's Interactivity API. It combines a PHP 8.4+ backend with a TypeScript/React frontend, organized in a modular architecture where each feature has its own dedicated module.

- **Repository Size**: Medium (~40 source files)
- **Primary Languages**: PHP 8.4, TypeScript 5.3
- **Frameworks**: WordPress 6.8, React 18.2, WordPress Interactivity API
- **Test Frameworks**: Pest PHP, Jest
- **Build Tools**: Webpack 5, wp-scripts
- **Package Managers**: Composer (PHP), Yarn (JavaScript)

## Project Structure

### Key Directories
- **`sources/`** - All source code (PHP and TypeScript organized by module)
  - `ApiFetch/` - API fetch client and middleware
  - `Blocks/` - WordPress blocks (Konomi, Reaction, Bookmark, UserProfile)
  - `Configuration/` - Configuration module
  - `Functions/` - Utility functions
  - `Icons/` - Icon components and assets
  - `Post/`, `User/`, `Rest/` - Core domain modules
- **`tests/`** - All tests organized by type and language
  - `unit/php/` - PHP unit tests
  - `integration/php/` - PHP integration tests
  - `functional/php/` - PHP functional tests
  - `unit/js/` - JavaScript/TypeScript tests (*.spec.ts files)
- **`@types/`** - Custom TypeScript type definitions
- **`resources/`** - Static resources
- **`.github/workflows/`** - CI/CD pipeline definitions

### Key Configuration Files
- **`composer.json`** - PHP dependencies and scripts (requires PHP 8.4+)
- **`package.json`** - Node dependencies and build scripts (requires Node 20+)
- **`phpcs.xml`** - PHP CodeSniffer configuration (Inpsyde standards)
- **`phpstan.neon`** - PHPStan static analysis (level 9)
- **`phpunit.xml`** - PHPUnit/Pest configuration
- **`jest.config.ts`** - Jest testing configuration
- **`.eslintrc.json`** - ESLint configuration (strict TypeScript rules)
- **`tsconfig.json`** - TypeScript compiler configuration
- **`webpack.scripts.config.js`** - Webpack for blocks
- **`webpack.modules.config.js`** - Webpack for modules
- **`.wp-env.json`** - WordPress local environment config

## Build & Development Workflow

### Environment Requirements
- **Node.js**: 20+ (specified in package.json engines)
- **PHP**: 8.4+ (specified in composer.json)
- **Package Managers**: Yarn 1.22+, Composer 2+

### Installation Steps (ALWAYS run in this order)
1. **Install JavaScript dependencies first** (takes ~80 seconds):
   ```bash
   yarn install --frozen-lockfile
   ```
   Note: You may see peer dependency warnings - these are expected and non-blocking.

2. **Install PHP dependencies** (can take 3-5 minutes, be patient):
   ```bash
   composer install
   ```
   Important: This command takes significantly longer than typical npm installs. Use appropriate timeouts (180+ seconds).

### Build Process
The build compiles TypeScript and SCSS into production-ready assets. ALWAYS run builds in this order:

```bash
yarn build
```
This runs both `yarn build:scripts` and `yarn build:modules` sequentially (~9 seconds total).

**Build Output Locations**:
- `sources/Icons/client/dist/` - Icon library
- `sources/Blocks/*/dist/` - Individual block assets (JS/CSS)
- `sources/Configuration/client/dist/` - Configuration module
- `sources/ApiFetch/client/dist/` - API fetch module

**Build Notes**:
- Each build runs TypeScript type checking (`tsc --noEmit`) before webpack
- Builds create both `.js` and `.asset.php` files for WordPress dependency management
- The baseline-browser-mapping warning is cosmetic and can be ignored
- Builds always succeed unless there are TypeScript type errors

### Development Builds (with watch mode)
For active development with file watching:
```bash
yarn build:dev:scripts  # Watch scripts
yarn build:dev:modules  # Watch modules
```

### Linting

**JavaScript/TypeScript Linting**:
```bash
yarn lint:js      # Check for issues
yarn lint:js:fix  # Auto-fix issues
```
Configuration: `.eslintrc.json` (strict rules, complexity max 3, max 25 lines per function)

**CSS/SCSS Linting**:
```bash
yarn lint:css      # Check styles
yarn lint:css:fix  # Auto-fix styles
```
Configuration: `.stylelintrc.json`

**PHP Linting**:
```bash
composer cs      # Check PHP code style (PHPCS with Inpsyde standards)
composer cs:fix  # Auto-fix PHP code style
```
Configuration: `phpcs.xml`

**PHP Static Analysis**:
```bash
composer analysis     # Run PHPStan (level 9)
composer analysis:ci  # CI-formatted output
```
Configuration: `phpstan.neon`

### Testing

**JavaScript Tests** (Jest, ~7 seconds):
```bash
yarn test                  # Run all JS tests
yarn test:coverage         # With coverage report
yarn test:update-snapshots # Update snapshots
```
- Tests located in `tests/unit/js/`
- Uses `@jest/globals` for imports
- 17 test suites, 73 tests currently
- Configuration: `jest.config.ts`

**PHP Tests** (Pest PHP):
```bash
composer test:unit              # Unit tests only
composer test:integration       # Integration tests only
composer test:functional        # Functional tests only
composer tests                  # All PHP tests
composer test:unit:coverage     # With coverage
```
- Tests use `describe()` and `it()` syntax (Pest)
- Configuration: `phpunit.xml`, `tests/Pest.php`

**Quality Assurance** (run everything):
```bash
yarn qa      # JS: TypeScript check + linting + tests with coverage
composer qa  # PHP: Code style + static analysis + all tests
```

## CI/CD Pipelines

The project has 4 GitHub Actions workflows that run on PRs and pushes:

### 1. Client Linting (`.github/workflows/client-linting.yml`)
Runs on TS/TSX/SCSS file changes:
- Node 20 setup
- `yarn install --frozen-lockfile`
- `yarn lint:css`
- `yarn lint:js`

### 2. Server Linting (`.github/workflows/server-linting.yml`)
Runs on PHP file changes:
- PHP 8.4 setup
- **KNOWN ISSUE**: Workflow has incorrect path `./src/` in syntax check command. The correct path should be `./sources/`
- `composer install`
- `composer cs` (code style)
- `composer analysis:ci` (PHPStan)

### 3. Testing Coverage (`.github/workflows/testing-coverage.yml`)
Comprehensive testing workflow:
- Node 20 and PHP 8.4 setup
- `yarn install --frozen-lockfile`
- `composer install`
- **IMPORTANT**: `yarn build` - artifacts must be built before tests
- `composer test:unit:coverage`
- `composer test:integration:coverage`
- `composer test:functional:coverage`
- `yarn test:coverage`
- Uploads to Codecov

### 4. Artifacts (`.github/workflows/artifacts.yml`)
Runs on tag pushes to build release artifacts.

**CI Skip**: Add `--skip ci` to commit message to skip CI runs.

## Common Gotchas & Solutions

### PHP Issues
1. **Path Error in Server Linting Workflow**: The syntax check uses `./src/` but should use `./sources/`. This is a known issue in the workflow file.
2. **PHP Version**: Code requires PHP 8.4+. Ensure version matches in all environments.
3. **Composer Install Timeout**: This takes 3-5+ minutes. Use timeouts of 180+ seconds.
4. **Namespace**: All PHP code uses `SpaghettiDojo\Konomi` namespace.

### JavaScript/TypeScript Issues
1. **Peer Dependency Warnings**: Yarn install shows many peer dependency warnings - these are expected and do not break builds.
2. **Build Before Tests**: Always run `yarn build` before running functional tests that depend on built artifacts.
3. **TypeScript Strict Mode**: The project uses very strict TypeScript settings (see `tsconfig.json`). Ensure all types are properly defined.
4. **Import Paths**: Use configured path aliases from `tsconfig.json`:
   - `@konomi/wp-types` → `./@types/wp`
   - `@konomi/types` → `./@types`
   - `@konomi/api-fetch` → `./sources/ApiFetch/client`
   - `@konomi/configuration` → `./sources/Configuration/client`
   - `@konomi/icons` → `./sources/Icons/client`
   - `@test/helpers` → `./tests/helpers.ts`

### Build Issues
1. **TypeScript Errors Block Build**: Fix all `tsc --noEmit` errors before webpack runs.
2. **Clean Build**: If builds fail mysteriously, clear dist directories:
   ```bash
   find sources -name "dist" -type d -exec rm -rf {} +
   ```

## Coding Standards

### PHP Guidelines
- **Modular Design**: Each feature is a self-contained module (see `sources/package.php`)
- **Dependency Injection**: Use constructor injection with `::new()` factory pattern, never global state
- **PSR-4 Autoloading**: `SpaghettiDojo\Konomi\` → `sources/`
- **SOLID Principles**: Strictly enforced
- **Testing**: Unit (isolated), Integration (with dependencies), Functional (end-to-end)
- Use `declare(strict_types=1)` in all PHP files
- Use `describe()` and `it()` for Pest tests
- Mock WordPress functions with Brain\Monkey, classes with Mockery

### TypeScript Guidelines
- **Module Organization**: Client-side code in `client/` subdirectories within modules
- **Block Structure**: WordPress blocks in `sources/Blocks/` with `index.ts`, `view/`, `edit/`
- **Type Safety**: Strict mode enabled, no implicit any
- **Immutability**: Use `Readonly<>` types for data structures
- Import Jest globals: `import { jest, describe, it, expect, beforeEach } from '@jest/globals'`
- Use `@total-typescript/shoehorn` for partial mocking, not `as` keyword

## Validation Checklist

Before submitting changes, ensure:
- [ ] `yarn lint:js` passes (zero errors)
- [ ] `yarn lint:css` passes (zero errors)
- [ ] `yarn test` passes (all 73+ tests)
- [ ] `yarn build` completes successfully
- [ ] `composer cs` passes (if PHP changed)
- [ ] `composer analysis` passes (if PHP changed)
- [ ] `composer tests` passes (if PHP changed)

## WordPress Environment

The project uses `@wordpress/env` for local WordPress development:
```bash
yarn wp-env start    # Start WordPress environment
yarn wp-env stop     # Stop environment
```
Configuration: `.wp-env.json` (PHP 8.4, WordPress 6.8, includes Query Monitor plugin)

## Trust These Instructions

These instructions have been validated by running actual commands and observing their behavior. If you encounter issues not documented here, investigate first but trust this guide as your baseline. Only perform additional searches if the information here is incomplete or incorrect.

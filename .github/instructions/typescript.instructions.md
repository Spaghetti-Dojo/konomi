# TypeScript / JavaScript Contributor Instructions

This file contains the project guidelines and commands that apply to the JavaScript/TypeScript frontend of Konomi.

## Environment Requirements
- Node.js: 20+ (specified in `package.json` engines)
- Yarn: 1.22+

## Installation (frontend first)
Always install JavaScript dependencies first.

```bash
yarn install --frozen-lockfile
```

Note: Peer dependency warnings are expected and non-blocking.

## Build (frontend)
Compile TypeScript and SCSS into production-ready assets. ALWAYS run builds in this order:

```bash
yarn build
```

This runs `yarn build:scripts` and `yarn build:modules` sequentially.

Build output locations:
- `sources/Icons/client/dist/`
- `sources/Blocks/*/dist/`
- `sources/Configuration/client/dist/`
- `sources/ApiFetch/client/dist/`

Build notes:
- Each build runs TypeScript type checking (`tsc --noEmit`) before webpack.
- Builds create both `.js` and `.asset.php` files for WordPress dependency management.
- Fix any `tsc` type errors before webpack runs.

Development builds (watch):

```bash
yarn build:dev:scripts  # Watch scripts
yarn build:dev:modules  # Watch modules
```

## Linting (JS/TS & CSS)
JavaScript/TypeScript linting:

```bash
yarn lint:js      # Check for issues
yarn lint:js:fix  # Auto-fix issues
```

CSS/SCSS linting:

```bash
yarn lint:css      # Check styles
yarn lint:css:fix  # Auto-fix styles
```

Notes:
- ESLint configuration is strict (`.eslintrc.json`), with complexity max 3 and max 25 lines per function.

## Testing (Jest)
Frontend tests use Jest.

```bash
yarn test                  # Run all JS tests
yarn test:coverage         # With coverage report
yarn test:update-snapshots # Update snapshots
```

- Tests located in `tests/unit/js/`.
- Uses `@jest/globals` for imports.

## Import paths / TypeScript aliases
Use configured path aliases from `tsconfig.json`:
- `@konomi/wp-types` → `./@types/wp`
- `@konomi/types` → `./@types`
- `@konomi/api-fetch` → `./sources/ApiFetch/client`
- `@konomi/configuration` → `./sources/Configuration/client`
- `@konomi/icons` → `./sources/Icons/client`
- `@test/helpers` → `./tests/helpers.ts`

## TypeScript Guidelines
- Module organization: client-side code lives in `client/` subdirectories inside each module.
- Blocks: WordPress blocks in `sources/Blocks/` with `index.ts`, `view/`, `edit/`.
- Type safety: Strict mode enabled — avoid implicit `any`.
- Immutability: Prefer `Readonly<>` for shared data shapes.
- Testing: Import Jest globals as `import { jest, describe, it, expect, beforeEach } from '@jest/globals'`.
- Use `@total-typescript/shoehorn` for partial mocking rather than `as` casts.

## Common TypeScript Gotchas
- Peer dependency warnings during `yarn install` are expected.
- Always run `yarn build` before running functional tests that depend on built artifacts.
- TypeScript errors will block webpack; fix `tsc --noEmit` errors first.
- If builds fail mysteriously, clean `dist` directories:

```bash
find sources -name "dist" -type d -exec rm -rf {} +
```

## Validation Checklist (frontend)
Before submitting frontend changes ensure:
- `yarn lint:js` passes (zero errors)
- `yarn lint:css` passes (zero errors)
- `yarn test` passes (all JS tests)
- `yarn build` completes successfully

## WordPress Environment (frontend)
The project uses `@wordpress/env` for local WordPress development:

```bash
yarn wp-env start    # Start WordPress environment
yarn wp-env stop     # Stop environment
```

Configuration: `.wp-env.json` (PHP 8.4, WordPress 6.8, includes Query Monitor plugin)

## Where to find more
See the repository root `package.json`, `tsconfig.json`, and `.eslintrc.json` for exact rules and scripts.

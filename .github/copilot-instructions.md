# Konomi - Contributor Instructions (overview)

This repository's detailed contributor instructions have been split by language. Please open the language-specific files for full guidelines:

- `.github/instructions/typescript.instructions.md` — TypeScript/JavaScript frontend instructions
- `.github/instructions/php.instructions.md` — PHP backend instructions

Below is a short high-level overview. For step-by-step commands, linting rules, and CI notes, open the referenced files.

## Project at a glance
Konomi is a WordPress plugin combining a PHP 8.4+ backend with a TypeScript/React frontend. The codebase is organized under `sources/` with tests in `tests/` and build artifacts in `build-modules/` and `resources/modules/`.

Primary tools and frameworks:
- PHP 8.4+, Composer
- Node 20+, Yarn, Webpack, wp-scripts
- React 18, Jest, Pest PHP

## Where to look next
- Frontend developer? Open: `.github/instructions/typescript.instructions.md`
- Backend developer? Open: `.github/instructions/php.instructions.md`

If you maintain documentation or CI config, update both instruction files as appropriate.

---
name: wp-block-development
description: "Use when you work on the Konomi blocks: block.json, the edit code, render.php, the Context/TemplateRender layer, the blocks manifest, and the pnpm build."
---

# Konomi Block Development

## When to use

Use this skill when you change a block under `sources/Blocks/`, or add a new one.

## Project facts

The plugin ships four dynamic blocks. All are server-rendered and driven by the Interactivity API.

| Block                 | Directory                   | Role                                                     |
| --------------------- | --------------------------- | -------------------------------------------------------- |
| `konomi/konomi`       | `sources/Blocks/Konomi`     | Container. Inner blocks. Hooked after `core/post-title`. |
| `konomi/reaction`     | `sources/Blocks/Reaction`   | Reaction control with an optional count.                 |
| `konomi/bookmark`     | `sources/Blocks/Bookmark`   | Bookmark control.                                        |
| `konomi/user-profile` | `sources/Blocks/UserProfile`| Favorites table of the current user.                     |

Per block directory: `block.json`, `index.ts` (editor entry), `edit/` (React editor code), `view/` (Interactivity
store), `render.php` (server render), and `Context.php` (server context service) where the block needs one. Build output goes to `dist/` (scripts)
and `build-module/` (script modules). Both are git-ignored.

Read `docs/blocks.md` and `docs/extending.md` before you change block behavior.

## Procedure

### 1) block.json

- `apiVersion` is 3. Keep `"supports": { "interactivity": true }` on the interactive blocks.
- Entries point at build output: `editorScript` to `file:./dist/js/...`, `viewScriptModule` to
  `file:./build-module/...`, `style` to `file:./dist/css/...`, `render` to `file:./render.php`.
- Text domain is `konomi`.
- `konomi/reaction` and `konomi/bookmark` declare `"parent": ["konomi/konomi"]`.

Background: `references/block-json.md`, `references/attributes-and-serialization.md`,
`references/supports-and-wrappers.md`, `references/inner-blocks.md`.

### 2) Registration

Do not call `register_block_type()` in new code. `Blocks\BlockRegistrar` scans `sources/Blocks`, loads
`blocks-manifest.php` with `wp_register_block_metadata_collection()`, and registers every directory that holds a
`block.json`. `Blocks\Module::run()` drives it.

After you change any `block.json`, regenerate the manifest:

```bash
pnpm build:blocks-manifest
```

Background: `references/registration.md`.

### 3) render.php

`render.php` runs in the block namespace and receives `$attributes`, `$content`, `$block`.

- Get the server context with `Blocks\context(Context::class)`, then `merge([...])` for per-render values.
- Print it with `wp_interactivity_data_wp_context($context->toArray())`.
- Render partials with `Blocks\renderer()->render('partials/<name>', [...])`, not with inline HTML.
- Build inline custom properties with `Blocks\style()` and `Blocks\CustomProperty::new(...)`, each with a sanitize
  callback.
- Escape every attribute. Where PHPCS cannot see the escaping, add a narrow `phpcs:ignore` line, as the existing files do.

Background: `references/dynamic-rendering.md`.

### 4) Editor code

`edit/index.tsx` uses `useBlockProps`; the container uses `useInnerBlocksProps` with a fixed `template` and
`templateLock: 'insert'`. TypeScript is strict; types live beside the block in `edit/types.ts`.

### 5) Build

```bash
pnpm build              # scripts and modules
pnpm build:dev:scripts  # watch, editor bundles
pnpm build:dev:modules  # watch, view script modules
```

Every build script runs `tsc --noEmit` first.

## Verification

```bash
pnpm qa      # tsc, lint:js, lint:css, lint:md, jest with coverage
composer qa  # PHPCS, PHPStan, Pest
```

Functional PHP tests snapshot the rendered markup. If the markup changes on purpose, update the snapshots:

```bash
composer test:snapshots:update
```

To see a block in a real site, use the `run-konomi` skill.

## Failure modes / debugging

- A block does not appear: the manifest is stale. Run `pnpm build:blocks-manifest`.
- The view module does not load: `build-module/` is missing. Run `pnpm build:modules`.
- The context is empty on the client: `render.php` did not print `wp_interactivity_data_wp_context()`, or the
  `data-wp-interactive` namespace does not match the store namespace.

Background: `references/debugging.md`, `references/tooling-and-testing.md`, `references/deprecations.md`,
`references/creating-new-blocks.md`. These describe common WordPress practice. When they disagree with `docs/`, the
`docs/` directory wins.

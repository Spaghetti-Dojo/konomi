# Konomi Block Hooks — inject default inner blocks on auto-insertion

- **Date:** 2026-07-04
- **Branch:** `fix/konomi-block-hooks-inner-blocks`
- **Status:** Approved (Approach 1)

## Problem

`konomi/konomi` declares `blockHooks: { "core/post-title": "after" }`. On a fresh
install the block is auto-inserted after the post title on the single view, but it
renders **empty** on the front-end. Confirmed live in `wp-env`:

- `get_hooked_blocks()['core/post-title']['after']` = `['konomi/konomi']` ✓ (hook fires)
- The `data-wp-interactive="konomi"` wrapper is present right after the `<h1>` title ✓
- `konomi-reaction` / `konomi-bookmark` markup count = **0** ✗ (block is empty)

### Root cause

The block's visible content lives entirely in **inner blocks**: the editor
(`sources/Blocks/Konomi/edit/index.tsx`) uses `useInnerBlocksProps` with a `template`
of a `core/group` containing `konomi/reaction` + `konomi/bookmark`, and `save.tsx`
returns `<InnerBlocks.Content />`. `render.php` echoes those serialized inner blocks via
`$content`.

Block Hooks auto-insertion inserts a **bare** `<!-- wp:konomi/konomi /-->` with no inner
blocks (WordPress does not apply the editor `template` to hooked blocks). In the editor
the `edit` component re-materialises the children, which is why it looks fine there; on
the front-end there is no `edit`, so `$content` is empty.

## Approach (chosen: inject default inner blocks on hook)

Register a `hooked_block_konomi/konomi` filter (WP 6.6+). When WordPress auto-inserts the
block, the filter populates its `innerBlocks` / `innerContent` / `innerHTML` with the same
`core/group → reaction + bookmark` structure the editor template defines, by building that
markup and running it through `parse_blocks()`. The hooked instance then renders fully on
the front-end and stays a normal, fully-editable nested-block instance in the editor.

Rejected: `ServerSideRender` in the editor (kills native per-child-block editing, a hard
requirement) and a `render.php` empty-`$content` fallback (second render path, structurally
empty hooked instances).

## Components

- **`sources/Blocks/Konomi/HookedContent.php`** — new feature class.
  - `injectDefaultInnerBlocks($parsedHookedBlock, $type, $position, $anchor, $context)`.
  - Guard 1: if `$parsedHookedBlock` is not an array (a prior filter suppressed insertion),
    return it unchanged.
  - Guard 2: if it already has non-empty `innerBlocks`, return unchanged (never override a
    hand-placed / already-populated instance).
  - Build `<!-- wp:konomi/konomi -->{default inner markup}<!-- /wp:konomi/konomi -->`,
    `parse_blocks()` it, and copy `innerBlocks` / `innerContent` / `innerHTML` onto the
    hooked block (preserving its incoming `attrs`).
  - The default inner markup mirrors `edit/index.tsx` — a cross-referencing comment lives
    in both files.
- **`sources/Blocks/Module.php`** — wire it up, mirroring `ConditionalBlockRender`:
  add `Konomi\HookedContent::class` to `services()` and register the filter from `run()`
  (new `initBlockHooks()` helper, priority 10, 5 args).

## Data flow

WP auto-inserts `konomi/konomi` after `core/post-title` (front-end template build **and**
editor both go through `get_hooked_block_markup()`, which applies the filter). The block now
carries group→reaction+bookmark, is serialized into the template, and on render `render.php`
echoes `$content`; `render_block` recursively renders the children → buttons appear under the
post title. Native per-child editing is preserved because the inserted block is a normal
nested-block instance.

## Testing

Pest + WorDBless, matching `tests/functional/php/Blocks/KonomiTest.php`:

- **Unit-ish:** call the filter with a bare parsed `konomi/konomi` → assert `innerBlocks`
  resolves to a `core/group` containing `konomi/reaction` + `konomi/bookmark`.
- **Guard:** filter is a no-op when `innerBlocks` already present (manual placement).
- **End-to-end:** `apply_block_hooks_to_content('<!-- wp:post-title /-->', $post)` then
  `do_blocks()` → output contains `konomi-reaction` and `konomi-bookmark` after the title.

> NOTE: `composer test:functional` is currently red on the parent branch due to a
> Modularity double-`build()` in the test bootstrap (separate pre-existing issue). Live
> verification is done against `wp-env`; the functional tests land here for when the harness
> is green.

## Non-goals

No editor-UX change, no `ServerSideRender`, no change to how the `active` attribute drives
rendering.

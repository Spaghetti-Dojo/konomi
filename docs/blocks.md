---
slug: /
---

# Blocks

Konomi ships the blocks that let a visitor react to or bookmark a post and see the counts, plus a favorites table for
the current user. They are dynamic (server-rendered) blocks driven by the WordPress Interactivity API. This page is
about placing and integrating those blocks — the blocks are not meant to be forked or re-implemented.

## The blocks

| Block                 | Role                                                                   |
| --------------------- | ---------------------------------------------------------------------- |
| `konomi/konomi`       | Container. Auto-inserted after `core/post-title` via Block Hooks.      |
| `konomi/reaction`     | A reaction (e.g. like) control with an optional count.                 |
| `konomi/bookmark`     | A bookmark control.                                                    |
| `konomi/user-profile` | Renders the current user's favorites table (single instance per page). |

## Using it

### Render the favorite controls from PHP

`renderKonomiBlock()` returns a `konomi/konomi` container pre-composed with a reaction and a bookmark. Run it through
`kses()` — which allows exactly the Interactivity attributes Konomi emits — before echoing:

```php
use function SpaghettiDojo\Konomi\Blocks\{kses, renderKonomiBlock};

// Inside a post context; the block reads the current post.
echo kses(renderKonomiBlock());
```

## API

### Functions — `SpaghettiDojo\Konomi\Blocks`

```php
function renderKonomiBlock(): string;             // reaction + bookmark container, as HTML
function kses(string $content): string;           // wp_kses() pre-configured for Konomi's Interactivity markup
```

## Related

- [User](./user.md) — the favorite state (`User\Item`, `ItemGroup`) the blocks display.
- [REST](./rest.md) — the `konomi/v1` endpoints the block front-end stores call.
- [Icons](./icons.md) — the icons the controls render.
- [Storage](./storage.md) — where the favorites the blocks display are persisted.

# Blocks

Konomi ships four dynamic blocks — `konomi/konomi` (the container), `konomi/reaction`, `konomi/bookmark` and
`konomi/user-profile` — all rendered server-side and driven by the WordPress Interactivity API. Each block pairs a PHP
`render.php` template with a **Context** service: a small object that assembles the `data-wp-context` payload the
front-end store hydrates from. This document shows how to build your own Konomi block on that pattern, how to feed
content into the container block, and the public helpers in `sources/Blocks/api.php` you compose with.

All helpers live in the `SpaghettiDojo\Konomi\Blocks` namespace. Context services are resolved from the container
obtained via `\SpaghettiDojo\Konomi\package()`.

## What you can do

- Render a Konomi block anywhere in PHP with `renderKonomiBlock()` (a `konomi/konomi` container pre-composed with
    `konomi/reaction` + `konomi/bookmark` inner blocks).
- Build your own dynamic block that reuses Konomi's Interactivity context/template plumbing via a `Context` class +
    `render.php`.
- Inject default inner blocks into an auto-inserted (Block Hooks) `konomi/konomi` via the `hooked_block_konomi/konomi`
    filter.
- Render PHP templates with `renderer()`, and hook their data/path with `konomi.template.render.data` /
    `konomi.template.render.path`.
- Build a safe inline `style` attribute of sanitized CSS custom properties with `style()` + `CustomProperty`.
- Escape Interactivity markup for output with `kses()`, and iterate posts with global post data set up via `loop()`.

## How-to recipes

### 1. Render the Konomi block from PHP

`renderKonomiBlock()` returns the fully rendered HTML of a `konomi/konomi` container holding a centered `core/group`
with a `konomi/reaction` (count hidden) and a `konomi/bookmark`. Use it to drop the favorite controls into any template
— a query loop row, a custom archive, a shortcode.

```php
use function SpaghettiDojo\Konomi\Blocks\kses;
use function SpaghettiDojo\Konomi\Blocks\renderKonomiBlock;

// Inside a post context (the block reads the current post via get_the_ID()).
echo kses(renderKonomiBlock());
```

`renderKonomiBlock()` produces trusted plugin markup, but running it through `kses()` keeps only the Interactivity
attributes Konomi actually uses (see the reference below). This is exactly how the User Profile table renders per-row
actions (`sources/Blocks/UserProfile/partials/table.php`).

### 2. Create a new Konomi block

A Konomi block is a standard dynamic block (`block.json` with `"render": "file:./render.php"`) plus two Konomi-specific
pieces:

1. A **Context** service implementing `Blocks\Context`, registered in the container under its class name.
2. A `render.php` that fetches that context with `context()`, emits it as `data-wp-context`, and renders its markup with
   `renderer()`.

Model it on `konomi/reaction`. First, the context class — compose the two traits so you get the post helpers and the
`merge()` implementation for free:

```php
<?php

declare(strict_types=1);

namespace MyPlugin\Blocks\Highlight;

use SpaghettiDojo\Konomi\Blocks;
use SpaghettiDojo\Konomi\User;

final class Context implements Blocks\Context
{
    use Blocks\PostContextTrait;      // postId(), postType()
    use Blocks\MergeableContextTrait; // merge() + $this->extra

    public static function new(User\UserFactory $userFactory, Blocks\InstanceId $instanceId): self
    {
        return new self($userFactory, $instanceId);
    }

    private function __construct(
        private readonly User\UserFactory $userFactory,
        private readonly Blocks\InstanceId $instanceId
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->postId(),
            'isActive' => false,
            ...$this->extra, // values merged in from render.php
        ];
    }

    public function instanceId(): Blocks\InstanceId
    {
        return $this->instanceId;
    }
}
```

Register it as a shared service (in your module's `services()`), so `context(Context::class)` can resolve it:

```php
Context::class => static fn (ContainerInterface $c) => Context::new(
    $c->get(User\UserFactory::class),
    $c->get(Blocks\InstanceId::class)
),
```

Then `render.php` — fetch the context, optionally `merge()` in per-render attribute values, print the Interactivity
wrapper, and render a template:

```php
<?php

declare(strict_types=1);

namespace MyPlugin\Blocks\Highlight;

use SpaghettiDojo\Konomi\Blocks;

$attributes = (array) ($attributes ?? null);

$renderer = Blocks\renderer();
$context = Blocks\context(Context::class)->merge([
    'label' => (string) ($attributes['label'] ?? ''),
]);

$uuid = $context->instanceId()->current();
$anchor = "--konomi-{$uuid}";
?>

<div
    data-wp-interactive="myHighlight"
    class="my-highlight"
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wp_interactivity_data_wp_context($context->toArray()); ?>
>
    <?=
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $renderer->render('partials/button', [
        'anchor' => $anchor,
        'label' => __('Highlight', 'my-plugin'),
        'icon' => 'heart',
    ]) ?>
</div>
```

Notes on the mechanics:

- **`context(Context::class)`** returns the container-registered singleton. Because it is shared, the same instance is
    reused for every occurrence of the block on the page — that is what `InstanceId` disambiguates.
- **`InstanceId`** yields a per-render counter used to build a unique CSS anchor name (`--konomi-{n}`). The container
    `konomi/konomi` block calls `->instanceId()->reset()` at the end of its render to bump the counter for the next
    instance; a standalone block that owns its own anchor scope should do the same if it renders repeatedly.
- **`merge($array)`** stores values on `$this->extra`; spread them last in `toArray()` so per-render attributes
    override defaults. It returns the context (fluent), as declared by `Blocks\Context::merge()`.
- **`renderer()->render($path, $data)`** resolves `$path` relative to `sources/Blocks/` and appends `.php` if no
    extension is given. Konomi's own blocks reuse the shared `partials/button` template; your block can point at its own
    templates instead.

### 3. Inject content into `konomi/konomi` via Block Hooks

`konomi/konomi`'s visible content lives entirely in its inner blocks. When you place it by hand the editor template
supplies them, but when it is **auto-inserted** through Block Hooks (its `block.json` declares
`"blockHooks": {"core/post-title": "after"}`), WordPress inserts a _bare_ container with no inner blocks — so
`render.php` would echo an empty `$content`.

Konomi solves this with a `hooked_block_konomi/konomi` filter that injects the default inner-block structure
(`Konomi\HookedContent::injectDefaultInnerBlocks`). You can hook the same filter to change what an auto-inserted
container contains. The filter receives the standard Block Hooks arguments:

```php
add_filter(
    'hooked_block_konomi/konomi',
    static function (
        mixed $parsedHookedBlock,   // array<string,mixed>|null — the block being inserted
        string $hookedBlockType,    // 'konomi/konomi'
        string $relativePosition,   // 'after'
        mixed $parsedAnchorBlock,   // the anchor block (e.g. core/post-title)
        mixed $context              // WP_Block_Template | WP_Post | array
    ): mixed {
        // A prior filter may have suppressed insertion.
        if (!is_array($parsedHookedBlock)) {
            return $parsedHookedBlock;
        }
        // Respect an instance that already carries inner blocks.
        if (!empty($parsedHookedBlock['innerBlocks'])) {
            return $parsedHookedBlock;
        }

        $default = parse_blocks(
            '<!-- wp:konomi/konomi -->'
            . '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->'
            . '<div class="wp-block-group"><!-- wp:konomi/reaction /--></div>'
            . '<!-- /wp:group -->'
            . '<!-- /wp:konomi/konomi -->'
        )[0];

        $parsedHookedBlock['innerBlocks'] = $default['innerBlocks'];
        $parsedHookedBlock['innerContent'] = $default['innerContent'];
        $parsedHookedBlock['innerHTML'] = $default['innerHTML'];

        return $parsedHookedBlock;
    },
    10,
    5
);
```

Register with priority/arg-count `10, 5`. Return `$parsedHookedBlock` unchanged to opt out, or `null` to suppress
insertion entirely. Konomi's own callback only fills inner blocks when they are empty, so add yours at a different
priority if you mean to replace them.

### 4. Conditionally suppress blocks (`pre_render_block`)

Konomi hides every other `konomi/*` block on a page that contains the `konomi/user-profile` block, so the profile page
shows only the favorites table and not stray reaction/bookmark controls. It does this with a `pre_render_block` filter
(`UserProfile\ConditionalBlockRender::hideBlocksInProfilePage`): returning a non-null value short-circuits WordPress's
own render and outputs that value instead.

The same technique lets you gate any block's render from PHP:

```php
add_filter(
    'pre_render_block',
    static function (mixed $preRender, array $parsedBlock): mixed {
        $blockName = (string) ($parsedBlock['blockName'] ?? '');

        // Only act on our own condition; otherwise let WP render normally.
        if ($blockName === 'konomi/reaction' && is_front_page()) {
            return ''; // replace the rendered output with nothing
        }

        return $preRender; // null => proceed with the default render
    },
    10,
    2
);
```

Return `null` (the incoming `$preRender`) to let rendering continue; return a string to replace the output.

## Public API

### Functions — `sources/Blocks/api.php`

```php
namespace SpaghettiDojo\Konomi\Blocks;

// Resolve a container-registered Context service by class/service name.
// Throws InvalidArgumentException if the service is not a Blocks\Context.
function context(string $contextName): Context;

// The shared template renderer (templates resolved relative to sources/Blocks/).
function renderer(): TemplateRender;

// A fresh Style builder for assembling an inline CSS-custom-property string.
function style(): Style;

// wp_kses() pre-configured with the exact tags/attributes Konomi markup uses
// (div/button/svg/path/span/dialog/h2/p/a + data-wp-* Interactivity attributes).
function kses(string $content): string;

// Iterate post ids, setting up global $post + setup_postdata() for each,
// then wp_reset_postdata() at the end.
// @param array<positive-int> $ids
// @param callable(\WP_Post $post): void $callback
function loop(array $ids, callable $callback): void;

// Render a konomi/konomi container pre-composed with reaction + bookmark
// inner blocks; returns the block HTML.
function renderKonomiBlock(): string;
```

Usage sketches:

```php
$context = Blocks\context(Reaction\Context::class); // Blocks\Context
$html    = Blocks\renderer()->render('partials/button', ['label' => 'Save', 'icon' => 'heart']);
$css     = (string) Blocks\style()->add(
    Blocks\CustomProperty::new('--konomi-color--active', '#e2264d', 'sanitize_hex_color')
);
$safe    = Blocks\kses($html);

Blocks\loop([12, 34, 56], static function (\WP_Post $post): void {
    echo esc_html(get_the_title($post));
});
```

### `Blocks\Context` interface

Every block context implements this. `context()` only returns objects that satisfy it.

```php
namespace SpaghettiDojo\Konomi\Blocks;

interface Context
{
    /** The data-wp-context payload. @return array<string, mixed> */
    public function toArray(): array;

    public function instanceId(): InstanceId;

    /** Merge per-render values (stored and re-emitted by toArray()). @param array<string, mixed> $mergeable */
    public function merge(array $mergeable): Context;
}
```

Implement `toArray()` and `instanceId()` yourself; get `merge()` from `MergeableContextTrait`.

### Traits

- **`PostContextTrait`** — private helpers `postId(): int` (`get_the_ID()`) and `postType(): string`
    (`get_post_type()`). Use inside a context to read the current post.
- **`MergeableContextTrait`** — implements `merge(array): Context`, storing the array on `private array $extra`.
    Spread `...$this->extra` into your `toArray()` return so merged values apply.

### `TemplateRender` (via `renderer()`)

```php
public function render(string $path, array $data): string;
```

`$path` is resolved against `sources/Blocks/` (`.php` appended when omitted); `$data` is extracted into the template
scope as `$data`. In debug mode a missing template throws `RuntimeException`; otherwise `render()` swallows errors and
returns `''`. Two filters run per render:

- `konomi.template.render.data` — `apply_filters('konomi.template.render.data', array $data, string $path)`; return a
    modified `$data` array to alter the values a template receives.
- `konomi.template.render.path` — `apply_filters('konomi.template.render.path', string $path, array $data)`; return a
    different absolute path to override which file is included (theme template overrides, A/B variants, etc.).

### `Style` + `CustomProperty` (via `style()`)

Build a validated inline `style` string of CSS custom properties. Each `CustomProperty` runs its value through the given
sanitizer callback and is skipped when the value is empty.

```php
$style = (string) Blocks\style()->add(
    Blocks\CustomProperty::new('--konomi-color--inactive', $inactiveColor, 'sanitize_hex_color'),
    Blocks\CustomProperty::new('--konomi-color--active', $activeColor, 'sanitize_hex_color'),
);
// => "--konomi-color--active:#e2264d;"  (invalid/empty properties omitted)

echo '<div style="' . esc_attr($style) . '">';
```

`CustomProperty::new(string $key, string $value, callable $sanitizer)` — the sanitizer receives the raw value and
returns the string used in output; `isValid()` (and therefore inclusion) is true only when `$value` is non-empty.

### Hooks a developer uses

| Hook                          | Type                   | Signature                                                                                                                                             |
| ----------------------------- | ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `hooked_block_konomi/konomi`  | filter (`10, 5`)       | `(array\|null $parsedHookedBlock, string $hookedBlockType, string $relativePosition, array\|null $parsedAnchorBlock, mixed $context)` → `array\|null` |
| `pre_render_block`            | filter (core, `10, 2`) | `(mixed $preRender, array $parsedBlock)` → `mixed` — return non-null to replace a block's output                                                      |
| `konomi.template.render.data` | filter                 | `(array $data, string $path)` → `array`                                                                                                               |
| `konomi.template.render.path` | filter                 | `(string $path, array $data)` → `string`                                                                                                              |

### Blocks reference

| Block                 | Parent          | Notable attributes                                                                                                                                  |
| --------------------- | --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `konomi/konomi`       | —               | `active: string[]` (default `['reaction','bookmark']`); auto-inserted after `core/post-title` via Block Hooks; only allows `core/group` inner block |
| `konomi/reaction`     | `konomi/konomi` | `inactiveColor`, `activeColor` (string), `showCount: bool` (default `true`)                                                                         |
| `konomi/bookmark`     | `konomi/konomi` | `inactiveColor`, `activeColor` (string)                                                                                                             |
| `konomi/user-profile` | —               | renders the current user's favorites table (single instance; `multiple: false`)                                                                     |

All four declare `supports.interactivity: true` and are rendered by their `render.php`.

## Related

- [User](./user.md) — the `User\UserFactory` / `User\Item` / `User\ItemGroup` the contexts read favorite state from.
- [REST](./rest.md) — the `konomi/v1` add-reaction / add-bookmark endpoints the block view stores call.
- [Icons](./icons.md) — the `Icons\icon()->render()` service used by the shared button template.
- [Storage Drivers](./storage-drivers.md) — where the favorites the blocks display are persisted.

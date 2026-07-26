# Icons

Konomi ships a small set of SVG icons and two ways to use them: render one to sanitized inline SVG from PHP, or consume
them as React components in the browser through the `konomi-icons` script.

## Concepts

**Name = filename.** An icon is addressed by the basename of its SVG file. `render('heart')` reads
`sources/Icons/icons/heart.svg`. The shipped set is `bookmark`, `close`, `heart`.

**Safe by construction.** Server-side rendering runs the SVG through `wp_kses()` with a fixed allowlist — `<svg>` (with
`width`, `height`, `fill`, `class`, `viewBox`, `version`, `xmlns`, `xmlns:svg`) and `<path d>`. Anything outside it is
stripped, so the return value is safe to echo. An icon you add must stay within that allowlist to survive intact.

## Using it

### Render an icon from PHP

```php
use function SpaghettiDojo\Konomi\Icons\icon;

echo icon()->render('heart');
```

Results are cached per name for the request, so repeated renders of the same icon reuse the sanitized markup.

### Add an icon

1. Drop `sources/Icons/icons/{name}.svg` in, keeping to the allowlist above. It is immediately available server-side as
   `icon()->render('{name}')`.
2. For client use, import it in `sources/Icons/client/index.ts` (icons are imported there via SVGR's `ReactComponent`)
   and rebuild the client bundle.

### Use the icons in the browser

Enqueue the `konomi-icons` script, or declare it as a dependency, to get the icons as React components (`SvgHeart`,
`SvgBookmark`, …):

```php
wp_register_script(
    'my-plugin-ui',
    plugins_url('build/ui.js', __FILE__),
    ['konomi-icons'],  // dependency
    '1.0.0',
    true
);
```

## API

### PHP

```php
namespace SpaghettiDojo\Konomi\Icons;

function icon(): Render;                 // the shared Render service

class Render
{
    public function render(string $name): string;   // sanitized SVG for sources/Icons/icons/{name}.svg
}
```

### JavaScript

| Handle         | Description                                                                                                                      |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `konomi-icons` | Registered script exporting the icons as React components (`SvgHeart`, `SvgBookmark`). Enqueue it or declare it as a dependency. |

## Related

- [Configuration](./configuration.md) — publishes the `iconsUrl` / `iconsPath` the icons live under.
- [API Fetch](./api-fetch.md) — the other client building block, `@konomi/api-fetch`.

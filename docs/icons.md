# Icons

Konomi ships a small set of SVG icons and two ways to use them: render an icon to sanitized SVG markup server-side
through the `Icons\icon()` helper, or enqueue/depend on the `konomi-icons` script to consume them as React components on
the client.

## What you can do

- Render a bundled icon to safe, `wp_kses`-filtered inline SVG from PHP.
- Reference any icon by name (its SVG file basename) — results are cached per name for the request.
- Enqueue the `konomi-icons` script, or declare it as a dependency, to use the icons as React components in the
    browser.
- Add your own icon by dropping an SVG into the icons directory (client use needs a rebuild).

## How-to recipes

### 1. Render an icon server-side

Call `\SpaghettiDojo\Konomi\Icons\icon()` to obtain the shared `Render` service, then call `render()` with the icon name
(the SVG filename without extension). It returns sanitized SVG markup ready to print.

```php
use function SpaghettiDojo\Konomi\Icons\icon;

echo icon()->render('heart');
```

- The name maps directly to `sources/Icons/icons/{name}.svg`; `render('heart')` reads `heart.svg`.
- Output is passed through `wp_kses()` with an allowlist limited to `<svg>` (with `width`, `height`, `fill`, `class`,
    `viewBox`, `version`, `xmlns`, `xmlns:svg`) and `<path d>`. Anything outside that allowlist is stripped, so the
    return value is safe to echo directly.
- Results are cached in a static per-name map, so repeated `render()` calls for the same icon in one request re-use
    the sanitized markup.

### 2. Available icons / add a new icon

Icons live as plain SVG files in `sources/Icons/icons/`. Currently:

- `bookmark.svg`
- `close.svg`
- `heart.svg`

Each icon's name is its filename without the `.svg` extension — that is the string you pass to `render()`. The SVG
source carries its own `class` (e.g. `konomi-icon konomi-icon--heart`) and `viewBox`.

To add an icon:

1. Add `sources/Icons/icons/{your-name}.svg`. Keep markup within the `wp_kses` allowlist above (`<svg>` + `<path d>`) so
   nothing is stripped when rendered server-side.
2. It is immediately available server-side: `icon()->render('{your-name}')`.
3. For client-side (React) use, the icon must also be exported from the build. Import it in
   `sources/Icons/client/index.ts` (icons are imported there via SVGR's `ReactComponent`, e.g.
   `import { ReactComponent as SvgHeart } from '../icons/heart.svg'`) and rebuild the client so
   `sources/Icons/client/dist/konomi-icons.js` picks it up.

### 3. Use the `konomi-icons` script

The Icons module registers a classic script under the handle `konomi-icons` (built from
`sources/Icons/client/dist/konomi-icons.js`) on both `wp_enqueue_scripts` and `admin_enqueue_scripts`. Enqueue it, or
list it as a dependency, to use the icons as React components:

```php
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_script('konomi-icons');
});
```

Or depend on it from your own script:

```php
wp_register_script(
    'my-plugin-ui',
    plugins_url('build/ui.js', __FILE__),
    ['konomi-icons'],
    '1.0.0',
    true
);
```

The bundle exports the icons as React components (e.g. `SvgHeart`, `SvgBookmark`).

## Public API

### PHP — `SpaghettiDojo\Konomi\Icons`

```php
function icon(): Render
```

Returns the shared `Render` service from the container.

`Render` (`@api`):

```php
public function render(string $name): string
```

Reads `sources/Icons/icons/{$name}.svg`, sanitizes it with `wp_kses()`, caches the result per name, and returns the SVG
markup.

### JavaScript

| Handle         | Source                                      | Description                                                                                                                                     |
| -------------- | ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `konomi-icons` | `sources/Icons/client/dist/konomi-icons.js` | Registered classic script exporting the icons as React components (`SvgHeart`, `SvgBookmark`). Enqueue it or declare it as a script dependency. |

## Related

- [API Fetch](api-fetch.md) — the `@konomi/api-fetch` client module and module-import hooks.
- [Storage Drivers](storage-drivers.md) — swapping the persistence backend.

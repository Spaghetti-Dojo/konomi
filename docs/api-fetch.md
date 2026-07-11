# API Fetch

Konomi ships a thin wrapper around WordPress' `@wordpress/api-fetch` as the `@konomi/api-fetch` script module. It is a browser-side building block: import it from your own client script module to get a pre-configured `apiFetch` that already has Konomi's error-handling middleware installed, without wiring `wp-api-fetch` yourself. On the PHP side, Konomi exposes reusable helpers to run code the moment a specific script module is imported into the page's import map.

## What you can do

- Depend on `@konomi/api-fetch` from your own client script module and call `apiFetch()` with Konomi's middleware already applied.
- Get automatic debug logging of failed requests (errors are caught and logged when Konomi debug mode is on).
- Run arbitrary PHP the first time a given script module (e.g. `@konomi/api-fetch`) is imported into the page — the idiomatic place to enqueue a legacy script the module depends on.
- Register a one-shot conditional filter that removes itself after it first fires.

## How-to recipes

### 1. Depend on `@konomi/api-fetch` in your own script module

`@konomi/api-fetch` re-exports WordPress' `apiFetch` with Konomi's `catchErrors` middleware pre-registered via `apiFetch.use(...)`. Import it instead of `@wordpress/api-fetch` so every request routes through that middleware.

```ts
// your-client/index.ts
import { apiFetch } from '@konomi/api-fetch';

export async function toggleFavorite( postId: number ) {
    return apiFetch( {
        path: `/konomi/v1/favorites/${ postId }`,
        method: 'POST',
    } );
}
```

Register your script module against the `@konomi/api-fetch` id so WordPress resolves the import at runtime:

```php
add_action('wp_enqueue_scripts', static function (): void {
    wp_register_script_module(
        '@my-plugin/favorites',
        plugins_url('build/index.js', __FILE__),
        ['@konomi/api-fetch'], // dependency id
        '1.0.0'
    );
    wp_enqueue_script_module('@my-plugin/favorites');
});
```

What the module provides: a single named export, `apiFetch`, which is the standard `@wordpress/api-fetch` function with the `catchErrors` middleware already applied. `catchErrors` invokes the next middleware, and on rejection logs the error to the console only when Konomi's `isDebugMode` configuration flag is set.

### 2. Run PHP code when a specific script module is imported

When WordPress prints an import map for a script module dependency, Konomi lets you hook the exact moment a module id appears in that map. `@konomi/api-fetch` uses this itself to enqueue the classic `wp-api-fetch` script only when something actually imports it:

```php
use function SpaghettiDojo\Konomi\Functions\add_action_on_module_import;

add_action_on_module_import(
    '"@konomi\/api-fetch"',
    static function (): void {
        wp_enqueue_script('wp-api-fetch');
    }
);
```

Matching notes:

- The first argument is matched with `str_contains()` against the raw import-map JSON that WordPress prints inside the `<script type="importmap">` tag. Because that JSON quotes module ids and escapes forward slashes, pass the id in the same on-the-wire form: wrap it in double quotes and escape the slash — `'"@konomi\/api-fetch"'`.
- The action fires at most once: the underlying filter removes itself the first time its condition matches (see `add_single_conditional_filter`).
- Use it to lazily attach any dependency a module needs — a legacy `wp_enqueue_script(...)`, an inline script, extra data — so the cost is only paid on pages that import the module.

## Public API

### JavaScript

| Export | Module id | Description |
| --- | --- | --- |
| `apiFetch` | `@konomi/api-fetch` | `@wordpress/api-fetch` with Konomi's `catchErrors` middleware pre-applied. Declare `@konomi/api-fetch` as a dependency of your own script module to import it. |

### PHP — `SpaghettiDojo\Konomi\Functions` (`@api`)

```php
function add_action_on_module_import(string $moduleName, callable $action): void
```

Runs `$action` (a zero-argument callable) the first time `$moduleName` is found inside a printed script-module import map. `$moduleName` is matched with `str_contains()` against the raw import-map JSON, so supply the id exactly as it is serialized — quoted and slash-escaped, e.g. `'"@konomi\/api-fetch"'`. Fires once, then unhooks itself.

```php
function add_single_conditional_filter(string $name, callable $condition, callable $callback): void
```

Adds a filter on `$name` that evaluates `$condition(...$args)` each time the filter runs. While the condition is false the original value passes through untouched; the first time it is true the filter removes itself and returns `$callback(...$args)`. `add_action_on_module_import()` is built on top of this against the `wp_inline_script_attributes` filter.

## Related

- [Icons](icons.md) — the `konomi-icons` script and server-side icon rendering.
- [Storage Drivers](storage-drivers.md) — swapping the persistence backend.

# Functions

A pair of general-purpose PHP helpers for WordPress filters that should fire **once** and then remove themselves. Konomi
uses them to attach a script-module's dependency the first time that module is imported into a page, so the cost is paid
only where the module is actually used.

## Concept

**Self-removing filter.** A normal `add_filter()` runs on every pass. These helpers wrap that: the filter checks a
condition each time and, the first time it is true, runs its callback and unhooks itself. Later passes are untouched.

## Using it

### Run code once, when a script module is imported

```php
use function SpaghettiDojo\Konomi\Functions\add_action_on_module_import;

add_action_on_module_import(
    '"@konomi\/api-fetch"',
    static function (): void {
        wp_enqueue_script('wp-api-fetch');
    }
);
```

Matching is literal against the printed import-map JSON, so pass the module id in its on-the-wire form: quoted, with the
slash escaped — `'"@konomi\/api-fetch"'`. The callback fires at most once.

### Run any filter once, on a condition

```php
use function SpaghettiDojo\Konomi\Functions\add_single_conditional_filter;

add_single_conditional_filter(
    'the_content',
    static fn (string $content): bool => is_singular('book'),
    static fn (string $content): string => $content . render_book_footer()
);
```

While the condition is false the value passes through untouched; the first time it is true the filter runs its callback
and removes itself.

## API — `SpaghettiDojo\Konomi\Functions`

```php
function add_action_on_module_import(string $moduleName, callable $action): void;
```

Runs `$action` (zero-argument) the first time `$moduleName` is found inside a printed script-module import map. Matched
with `str_contains()` against the raw JSON, so supply the id quoted and slash-escaped. Fires once, then removes itself.

```php
function add_single_conditional_filter(string $name, callable $condition, callable $callback): void;
```

Adds a filter on `$name` that checks `$condition(...$args)` each time it runs. While false the value passes through
untouched; the first time it is true the filter removes itself and returns `$callback(...$args)`.
`add_action_on_module_import()` is built on this against the `wp_inline_script_attributes` filter.

## Related

- [API Fetch](./api-fetch.md) — the `@konomi/api-fetch` client whose dependency is loaded via
    `add_action_on_module_import()`.

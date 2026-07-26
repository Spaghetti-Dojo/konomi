# API Fetch

`@konomi/api-fetch` is WordPress' `apiFetch` with Konomi's error handling already wired in. Import it instead of
`@wordpress/api-fetch` and every request routes through Konomi's middleware — you configure nothing.

## Concept

**Pre-wired client.** The module re-exports `@wordpress/api-fetch` with a `catchErrors` middleware applied. That
middleware forwards each request and, on rejection, logs the error to the console — but only when Konomi's `isDebugMode`
config flag is on. You get consistent, debug-aware error handling for free.

## Using it

Import it in place of `@wordpress/api-fetch`:

```ts
import { apiFetch } from '@konomi/api-fetch';

export async function toggleFavorite( postId: number ) {
 return apiFetch( { path: `/konomi/v1/favorites/${ postId }`, method: 'POST' } );
}
```

Register your script module against the `@konomi/api-fetch` id so WordPress resolves the import at runtime:

```php
wp_register_script_module(
    '@my-plugin/favorites',
    plugins_url('build/index.js', __FILE__),
    ['@konomi/api-fetch'],   // dependency id
    '1.0.0'
);
```

## API

| Export     | Module id           | Description                                                                |
| ---------- | ------------------- | -------------------------------------------------------------------------- |
| `apiFetch` | `@konomi/api-fetch` | `@wordpress/api-fetch` with Konomi's `catchErrors` middleware pre-applied. |

## Related

- [Icons](./icons.md) — the other client building block, `konomi-icons`.
- [Configuration](./configuration.md) — the `isDebugMode` flag `catchErrors` reads.
- [Functions](./functions.md) — the `add_action_on_module_import()` helper used to load this module's dependency
    lazily.

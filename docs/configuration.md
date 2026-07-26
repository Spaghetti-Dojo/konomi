# Configuration

Konomi publishes a few plugin-level values to the browser — where the icons live, whether debug is on — and gives you a
small client helper to read them. On the server the same values are available as a container service. This page is about
consuming those values; the client helper and the server service are the two entry points.

## The fields

| Key           | Type     | Meaning                                      |
| ------------- | -------- | -------------------------------------------- |
| `iconsUrl`    | `string` | Public URL of Konomi's icons directory.      |
| `iconsPath`   | `string` | Filesystem path of Konomi's icons directory. |
| `isDebugMode` | `bool`   | Whether the plugin is running in debug mode. |

## Using it

### Read the config on the client

The `@konomi/configuration` script module exports a `configuration()` function. Call it, then read values with
`get(key, default)`:

```ts
import { configuration } from '@konomi/configuration';

const iconsUrl = configuration().get( 'iconsUrl', '' );
const isDebug = Boolean( configuration().get( 'isDebugMode', false ) );
```

`get()` returns the default when the key is absent, so you never touch the raw data tag.

Declare `@konomi/configuration` as a dependency of your own script module so WordPress loads it and prints its data:

```php
wp_register_script_module(
    '@my-plugin/ui',
    plugins_url('build/ui.js', __FILE__),
    ['@konomi/configuration'],   // dependency id
    '1.0.0'
);
```

### Read the config on the server

Resolve the `Configuration` service:

```php
use SpaghettiDojo\Konomi\Configuration\Configuration;

$config = \SpaghettiDojo\Konomi\package()
    ->container()
    ->get(Configuration::class);

$iconsUrl = $config->iconsUrl();
$debug    = $config->isDebugMode();
```

## Related

- [API Fetch](./api-fetch.md) — a real consumer: reads `isDebugMode` through `configuration()`.
- [Icons](./icons.md) — what the `iconsUrl` / `iconsPath` values point at.

# Configuration

Konomi exposes a small set of plugin-level configuration values to the browser through a WordPress **script module**.
The `Configuration\Module` registers the `@konomi/configuration` script module on both front-end and admin, and attaches
the configuration payload to it via WordPress' `script_module_data_@konomi/configuration` filter. Client code that
depends on `@konomi/configuration` reads the values from the JSON WordPress prints for that module; server code can
resolve the `Configuration` service from the container. This document shows how to consume and extend that data.

## What you can do

- **Read Konomi's config on the client** by consuming the data WordPress attaches to the `@konomi/configuration`
    script module.
- **Extend or override the config payload** with the `script_module_data_@konomi/configuration` filter.
- **Read config on the server** by resolving the `Configuration` service from the container.

## The exposed fields

`Configuration::toArray()` is the payload attached to the script module. It exposes:

| Key           | Type     | Meaning                                                                   |
| ------------- | -------- | ------------------------------------------------------------------------- |
| `iconsUrl`    | `string` | Public URL of Konomi's icons directory.                                   |
| `iconsPath`   | `string` | Filesystem path of Konomi's icons directory.                              |
| `isDebugMode` | `bool`   | Whether the plugin is running in debug mode (from the plugin properties). |

## How-to recipes

### Read Konomi configuration on the client

WordPress serializes script-module data into a `<script type="application/json">` tag keyed by the module id. Any client
script module that declares `@konomi/configuration` as a dependency can read it.

1. Ensure your client script module is registered as a WordPress script module and depends on `@konomi/configuration` so
   WordPress prints the data tag.
2. Read and parse the data element for the module id.

```js
function readKonomiConfiguration() {
 const el = document.getElementById( 'wp-script-module-data-@konomi/configuration' );

 if ( ! el ) {
  return {};
 }

 try {
  return JSON.parse( el.textContent );
 } catch {
  return {};
 }
}

const config = readKonomiConfiguration();
// config.iconsUrl, config.iconsPath, config.isDebugMode
```

The registration side (for reference — this is how Konomi itself registers the module) uses
`wp_register_script_module()` with the id `@konomi/configuration`; declaring that id in your own module's dependencies
is what makes WordPress emit the data tag on the page.

### Extend or override the configuration data

The payload is produced through the `script_module_data_@konomi/configuration` filter. Hook it to add your own keys or
override Konomi's. The filter receives (and must return) the associative data array.

```php
add_filter(
    'script_module_data_@konomi/configuration',
    static function (array $data): array {
        // Add a custom value.
        $data['myFeatureEnabled'] = get_option('my_feature_enabled', false);

        // Override an existing one.
        $data['isDebugMode'] = true;

        return $data;
    }
);
```

Konomi attaches its own payload to this filter when the assets are enqueued (on `wp_enqueue_scripts` /
`admin_enqueue_scripts`); register your filter before the enqueue runs (e.g. at `plugins_loaded` or module bootstrap) so
it is in place when the data is assembled. The updated array is what the client recipe above reads.

### Read configuration on the server

The `Configuration` object is a container service, resolvable by its class name:

```php
use SpaghettiDojo\Konomi\Configuration\Configuration;

$configuration = \SpaghettiDojo\Konomi\package()
    ->container()
    ->get(Configuration::class);

$iconsUrl = $configuration->iconsUrl();
$debug    = $configuration->isDebugMode();
```

## Public API

### `Configuration`

```php
namespace SpaghettiDojo\Konomi\Configuration;

class Configuration
{
    public function iconsUrl(): string;
    public function iconsPath(): string;
    public function isDebugMode(): bool;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
```

- `iconsUrl()` — public URL to the icons directory.
- `iconsPath()` — filesystem path to the icons directory.
- `isDebugMode()` — plugin debug flag.
- `toArray()` — the `{ iconsUrl, iconsPath, isDebugMode }` map exposed to the client (before the
    `script_module_data_@konomi/configuration` filter is applied by consumers).

The service is registered under `Configuration::class` and resolved from `\SpaghettiDojo\Konomi\package()->container()`.

## Related

- [Storage Drivers](./storage-drivers.md) — extending Konomi services the Modularity way.
- [Activation Tasks](./activation.md) — running setup/teardown on plugin lifecycle events.

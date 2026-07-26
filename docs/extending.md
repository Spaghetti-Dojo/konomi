# Extending Konomi

Konomi is assembled from [Inpsyde Modularity](https://inpsyde.github.io/modularity/) modules sharing one PSR-11
container. Every service it registers is addressable by id, so you can add your own services alongside Konomi's or
replace one of its implementations without forking anything.

This page covers the wiring at once. The module documents describe _what_ each extension point is; they link here for
_how_ to attach to it.

## Concepts

- **Package** — the container plus the list of modules that filled it. Konomi's is returned by
  `SpaghettiDojo\Konomi\package()`.
- **Service id** — the key a service is registered under. Konomi uses the class or interface name, e.g.
  `Storage\Storage::class`.
- **Module** — an object contributing to the container. Three kinds matter here: `ServiceModule` adds new services,
  `ExtendingModule` replaces or decorates existing ones, and `Activable` (Konomi's own contract) declares plugin
  lifecycle tasks.

## When you can add a module

The container is locked once Konomi finishes building it, which happens while `konomi.php` runs. Calling
`package()->addModule()` after that throws `Can't add module ... at this point of application.`

Modularity fires an action mid-build for exactly this reason. Hook it and add your module to the package it passes you:

```php
use Inpsyde\Modularity\Package;

add_action(
    'inpsyde.modularity.konomi.init',
    static function (Package $package): void {
        $package->addModule(MyPlugin\Storage\Module::new());
    }
);
```

Register the hook before Konomi loads. A plugin that loads after Konomi is too late; put the `add_action()` call at the
top level of a plugin that WordPress loads earlier, or in a mu-plugin.

## Adding services

Read the Modularity [documentation](https://inpsyde.github.io/modularity/Modules/).

## Replacing a Konomi service

Read the Modularity [documentation](https://inpsyde.github.io/modularity/Modules/).

## Resolving a service

Read from the container once it is built — from `plugins_loaded` onward, or inside a factory that receives it:

```php
$storage = \SpaghettiDojo\Konomi\package()->container()->get(Storage::class);
```

Prefer a module's named API function where one exists (`Blocks\context()`, `Icons\icon()`, `User\currentUser()`); those
are the supported entry points and hide the container entirely.

## Lifecycle tasks

A module in Konomi's package can run work on plugin activation, deactivation, or uninstall by implementing `Activable`.
Konomi collects those tasks while it builds and runs them on _Konomi's_ lifecycle. An independent plugin manages its own
setup and teardown through WordPress' native activation hooks instead. See [Activation](./activation.md) for both.

## Related

- [Activation](./activation.md) — declaring lifecycle tasks.
- [Storage](./storage.md) — the service most worth replacing.
- [Configuration](./configuration.md) — extending the client payload through a filter instead of a module.

# Activation

Some plugin work can only happen at the edges of its life: create a table when the plugin is switched on, clear
scheduled events when it is switched off, delete everything when it is removed. Konomi collects that work in one shared
registry and connects it to WordPress' activation, deactivation, and uninstall hooks. You add tasks to the registry;
Konomi runs them at the right moment.

## Concepts

**The three events.** WordPress fires activation once when the plugin is enabled, deactivation when it is disabled, and
uninstall when it is deleted. Each has its own task list.

**Bound to Konomi's lifecycle.** The registry is wired to Konomi's own plugin file, so its tasks run when _Konomi_ is
activated, deactivated, or uninstalled. This is a mechanism for code that is part of the Konomi package — not a way to
manage a separate plugin's own lifecycle (for that, see the last section).

**Declared at bootstrap.** Activation is special: WordPress fires the activation hook for a plugin _before_
`plugins_loaded`, in the same request that first loads it. So Konomi registers its lifecycle hooks and collects its
tasks at bootstrap, at the top level of its main file — anything hooked to `plugins_loaded` would be too late for the
activation event. The way in is therefore the `Activable` contract, which Konomi inspects while it builds.

## Using it

### From a module (`Activable`)

A module that is part of Konomi's package implements `Activable`. Konomi hands it the shared registry while it builds,
so `activate()` runs once at bootstrap and only _declares_ tasks — the callables themselves run later, when the event
fires.

```php
namespace MyPlugin\Storage;

use Inpsyde\Modularity\Module\{ModuleClassNameIdTrait, ServiceModule};
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\{Activable, ActivationTasks};

final class Module implements ServiceModule, Activable
{
    use ModuleClassNameIdTrait;

    public static function new(): self
    {
        return new self();
    }

    private function __construct() {}

    public function services(): array
    {
        return [
            TableInstaller::class => static fn (): TableInstaller => TableInstaller::new(),
        ];
    }

    public function activate(ActivationTasks $tasks, ContainerInterface $container): void
    {
        $tasks
            ->addActivationTask(
                static fn () => $container->get(TableInstaller::class)->create()
            )
            ->addUninstallTask(
                static fn () => $container->get(TableInstaller::class)->drop()
            );
    }
}
```

Resolve dependencies inside the callable, not in `activate()` — the callable is what runs at event time, and it should
be self-contained.

`Activable::activate()` only fires for modules Konomi collects while it builds — the modules bundled in its package. A
module you register later, through the `inpsyde.modularity.konomi.init` hook, adds its services but its `activate()` is
not called, because the tasks have already been gathered by then. Lifecycle tasks therefore belong to the bundled
package, not to code attached after build. See [Extending Konomi](./extending.md) for module registration.

### Deactivation vs uninstall

Deactivation runs when the plugin is switched off but not removed — use it for reversible cleanup, not data destruction:

```php
$tasks->addDeactivationTask(static function (): void {
    wp_clear_scheduled_hook('my_plugin_daily_sync');
});
```

Uninstall runs when the plugin is deleted. WordPress re-includes the main plugin file during uninstall, which re-runs
Konomi's bootstrap and repopulates the registry in the same request — so tasks declared at bootstrap are present.
Uninstall callables must be self-contained: they resolve their own dependencies when invoked.

### Managing your own plugin's lifecycle

Because the registry is tied to Konomi's plugin file, it is the wrong tool for a _separate_ plugin that needs to set up
or tear down its own resources — those tasks would run on Konomi's activation, not yours. Use WordPress' native hooks,
bound to your own main file:

```php
register_activation_hook(__FILE__, static fn () => MyPlugin\Installer::create());
register_uninstall_hook(__FILE__, [MyPlugin\Installer::class, 'destroy']);
```

## API

### `Activable`

```php
namespace SpaghettiDojo\Konomi\Activation;

use Psr\Container\ContainerInterface;

interface Activable
{
    public function activate(ActivationTasks $tasks, ContainerInterface $container): void;
}
```

Implement on a module to declare lifecycle tasks. `$tasks` is the shared registry to append to; `$container` resolves
services inside your callables.

### `ActivationTasks`

The registry. The `add*` methods are fluent, so calls chain. Every task is a `callable(): void`.

```php
namespace SpaghettiDojo\Konomi\Activation;

final readonly class ActivationTasks
{
    public function addActivationTask(callable $callback): self;
    public function addDeactivationTask(callable $callback): self;
    public function addUninstallTask(callable $callback): self;
}
```

It is registered under `ActivationTasks::class` and shared: every `Activable` module appends to the one instance Konomi
reads when a lifecycle event fires.

## Related

- [Extending Konomi](./extending.md) — registering modules and resolving services.
- [Database](./database.md) — a real activation task: creating and dropping the `konomi_interactions` table.

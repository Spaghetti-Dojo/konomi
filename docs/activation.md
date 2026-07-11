# Activation Tasks

Konomi runs setup/teardown work that must fire on the WordPress plugin **activation**, **deactivation**, and **uninstall** events — for example creating a custom table when the plugin is switched on and dropping it when the plugin is removed. Any module can opt into these events by implementing the `SpaghettiDojo\Konomi\Activation\Activable` contract. When a module implements it, Konomi hands the module a shared `ActivationTasks` registry into which it appends callables; Konomi then wires those callables to WordPress' `register_activation_hook()`, `register_deactivation_hook()`, and `register_uninstall_hook()` for you. This document shows how to register lifecycle tasks and documents the contracts you use.

## What you can do

- Run a callable when the plugin is **activated** (`addActivationTask`).
- Run a callable when the plugin is **deactivated** (`addDeactivationTask`).
- Run a callable when the plugin is **uninstalled** (`addUninstallTask`).
- Resolve services from the container inside your tasks — the PSR-11 container is handed to `Activable::activate()`.
- Participate in the lifecycle from any module added to the Konomi package, in the order the modules were registered.

## How-to recipes

### Register an activation & uninstall task

The pattern: make your module implement `Activable`, and in `activate()` append tasks to the supplied `ActivationTasks` registry. The container is also supplied, so tasks can lazily resolve services when they actually run.

The example below creates a custom table on activation and drops it on uninstall. This mirrors what Konomi's own `Database\Module` does (see [Database](./database.md)).

1. Implement `Activable` on your module (a module can implement `ServiceModule`, `ExecutableModule`, etc. at the same time).
2. In `activate()`, append a callable with `addActivationTask()` and another with `addUninstallTask()`.
3. Keep the heavy work inside the callable and resolve services lazily via the injected container — `activate()` runs at bootstrap for every module, but the callables only run on the actual lifecycle event.
4. Add the module to the Konomi package so it participates in the lifecycle.

```php
namespace MyPlugin\Storage;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;
use SpaghettiDojo\Konomi\Activation\Activable;
use SpaghettiDojo\Konomi\Activation\ActivationTasks;

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

Register the module with the Konomi package so its tasks are collected:

```php
\SpaghettiDojo\Konomi\package()->addModule(MyPlugin\Storage\Module::new());
```

### Add a deactivation task

Deactivation runs when the plugin is switched off but not removed — use it for transient cleanup (scheduled events, caches) rather than destroying data:

```php
public function activate(ActivationTasks $tasks, ContainerInterface $container): void
{
    $tasks->addDeactivationTask(static function (): void {
        wp_clear_scheduled_hook('my_plugin_daily_sync');
    });
}
```

## Lifecycle

- **`activate()` timing.** Every module in the package is inspected once at bootstrap (right after the container is built). Modules that implement `Activable` have `activate()` called and append their callables to the one shared `ActivationTasks` registry. `activate()` itself does not perform activation work — it only *declares* the tasks.
- **Ordering.** Modules are iterated in registration order, so activation, deactivation, and uninstall callables run in the same order the modules were added to the package.
- **Only registered modules participate.** A module that is never added to the package is never inspected, so its tasks never run.
- **When each set runs.**
  - Activation tasks run once, when WordPress fires the plugin activation hook.
  - Deactivation tasks run when the plugin is deactivated.
  - Uninstall tasks run when the plugin is deleted. WordPress re-includes the main plugin file during uninstall, which re-runs the bootstrap and repopulates the registry in the same request, so uninstall tasks declared in `activate()` are available. Keep uninstall callables self-contained (they resolve their dependencies from the container when invoked).

## Public API

### `Activable`

```php
namespace SpaghettiDojo\Konomi\Activation;

use Psr\Container\ContainerInterface;

interface Activable
{
    public function activate(ActivationTasks $tasks, ContainerInterface $container): void;
}
```

Implement this on any module that needs lifecycle tasks. `$tasks` is the shared registry to append to; `$container` is the plugin's PSR-11 container, for resolving services inside your task callables.

### `ActivationTasks`

The registry passed to `Activable::activate()`. The `add*` methods are fluent (they return the same instance) so calls can be chained. Every task is a `callable(): void`.

```php
namespace SpaghettiDojo\Konomi\Activation;

final readonly class ActivationTasks
{
    public function addActivationTask(callable $callback): self;
    public function addDeactivationTask(callable $callback): self;
    public function addUninstallTask(callable $callback): self;

    /** @return list<callable(): void> */
    public function activationTasks(): array;
    /** @return list<callable(): void> */
    public function deactivationTasks(): array;
    /** @return list<callable(): void> */
    public function uninstallTasks(): array;
}
```

The `add*` methods are what you call from `activate()`. The `*Tasks()` getters return the collected callables and are used by Konomi's lifecycle orchestrator; you rarely need them directly.

## Related

- [Database](./database.md) — a real `Activable` module that creates and drops the `konomi_interactions` table.
- [Storage Drivers](./storage-drivers.md) — the storage layer that table backs.

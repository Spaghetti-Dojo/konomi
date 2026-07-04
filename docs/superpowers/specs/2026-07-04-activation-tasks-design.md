# Design Spec: Modular Activation Task System

## Date
2026-07-04

## Context
The current plugin activation hook registration in `sources/Database/Module.php` happens within the `run()` method of the module. Since the modularity system boots these `run()` methods during the `plugins_loaded` action, the hooks are registered too late to be caught by the WordPress activation process, which occurs before `plugins_loaded`.

The goal is to create a decentralized, DI-driven system that allows individual modules to specify their activation, deactivation, and uninstall tasks while ensuring these tasks are registered with WordPress at the correct time.

## Architecture

### 1. The `Activation` Module
A new module dedicated to managing the lifecycle of plugin setup and cleanup.

#### `ActivationTasks` (Service)
A fluent registry that collects callables to be executed during plugin lifecycle events.
- **Responsibilities**: Store lists of activation, deactivation, and uninstall tasks.
- **Interface**: 
  - `addActivationTask(callable $callback): self`
  - `addDeactivationTask(callable $callback): self`
  - `addUninstallTask(callable $callback): self`
  - `getActivationTasks(): array` (and similar for others)

#### `ActivationExecute` (Service)
The orchestrator that connects the modularity system to WordPress hooks.
- **Dependencies**: `Package`, `ActivationTasks`, `ContainerInterface`.
- **Key Methods**:
  - `prepare(array $modules): void`: Iterates through provided modules. If a module implements `Activable`, it calls `$module->activate($this->tasks, $this->container)`.
  - `registerActivationLogic(): void`: Calls `register_activation_hook()` using the main plugin file. The callback iterates through and executes all tasks in `ActivationTasks`.
  - `registerDeactivationLogic(): void`: Similar to activation, using `register_deactivation_hook()`.
  - `registerUninstallLogic(): void`: Similar to activation, using `register_uninstall_hook()`.

### 2. The `Activable` Interface
Defines a contract for modules that require custom activation logic.

```php
interface Activable {
    public function activate(ActivationTasks $tasks, ContainerInterface $container): void;
}
```

## Data Flow & Lifecycle

### Registration Flow (Bootstrapping)
1. `konomi.php` defines a list of modules and adds them to the `Package`.
2. `$package->build()` is called, initializing the PSR-11 container.
3. `ActivationExecute::prepare($modules)` is invoked.
   - Each `Activable` module is called.
   - Modules inject closures into `ActivationTasks` (e.g., `fn() => $container->get(SchemaManager::class)->create()`).
4. `ActivationExecute` registers the 3 primary WordPress hooks.
5. The rest of the system boots on `plugins_loaded`.

### Execution Flow (WordPress Event)
1. User activates the plugin.
2. WordPress triggers the `activate_{plugin}` action.
3. The closure registered by `ActivationExecute` runs.
4. `ActivationExecute` retrieves the ordered list of tasks from `ActivationTasks` and executes each callable sequentially.

## Testing & Verification
- **Verification**: Deactivate and reactivate the plugin to ensure `SchemaManager::create()` is called and the database table is generated.
- **Isolation**: Verify that modules not implementing `Activable` are ignored during the `prepare` phase.
- **Order**: Verify that tasks are executed in the order the modules were registered in `konomi.php`.

---
name: wp-plugin-development
description: "Use when you work on Konomi plugin code: modules and the container, hooks, activation/deactivation/uninstall tasks, storage, security, and the QA commands."
---

# Konomi Plugin Development

## When to use

Use this skill for work on the plugin PHP code:

- a new module, or a change to an existing module
- a new service in a container
- activation, deactivation, or uninstall behavior
- data storage and the custom table
- security (capabilities, sanitization, escaping, SQL safety)

## Project facts

- One plugin. Main file: `konomi.php`. Namespace `SpaghettiDojo\Konomi`, PSR-4 over `sources/`.
- PHP 8.4 minimum. WordPress 6.8 stubs. PHPStan level 9.
- Architecture: `inpsyde/modularity`. `konomi.php` builds the package and adds the modules in a fixed order.
- Package access: `SpaghettiDojo\Konomi\package()` returns the package; `package()->container()` returns the container.
- Public API per domain: a `api.php` file with plain functions, listed in `composer.json` `autoload.files`.
- The plugin has no settings page, no options page, and no cron event. Do not add them without a request.
- Read `docs/` first. It describes each domain: activation, blocks, storage, rest, user, post, icons, configuration, api-fetch, functions, extending.

## Procedure

### 1) Locate the domain

Every domain is a directory under `sources/` with a `Module.php`. Read `docs/<domain>.md` before you change code.

### 2) Follow the module contract

- `Module` implements `ServiceModule` when it publishes services, `ExecutableModule` when it registers hooks in `run()`.
- Use `ModuleClassNameIdTrait` for the module id.
- Use the `public static function new(): self` plus `final private function __construct()` pattern. All classes in
  `sources/` use it.
- Declare services as closures in `services()`. Resolve dependencies from the `ContainerInterface` parameter.
- Register hooks in `run()` only. Do not call `add_action()` at file load time.
- Add a new module to the `$modules` array in `konomi.php`.

### 3) Lifecycle work

Do not call `register_activation_hook()` yourself. Implement `Activation\Activable` on the module and add tasks to
`ActivationTasks`. Konomi collects the tasks at bootstrap and runs them at event time. Resolve services inside the task
callable, not in `activate()`.

Read `docs/activation.md`. Example: `sources/Database/Module.php`.

### 4) Storage and the custom table

Favorites live in the `konomi_interactions` custom table, not in user meta. `Database\SchemaManager` creates and drops
it; `Storage\TableStorage` reads and writes it through the `Storage\Storage` contract.

Read `docs/database.md` and `docs/storage.md` before you touch persistence.

### 5) Security baseline

- Escape on output: `esc_attr()`, `esc_html()`, `wp_kses()`. `Blocks\kses()` is the allowed list for the Interactivity
  markup.
- Use `$wpdb->prepare()` for every SQL statement.
- REST input arrives as `WP_REST_Request` and is validated by the schema. Never read `$_GET` or `$_POST` in a controller.

Background: `references/security.md`.

## Verification

Run the commands of the repository:

```bash
composer cs           # PHPCS, Inpsyde coding standards
composer analysis     # PHPStan level 9
composer tests        # Pest: unit, integration, functional
composer qa           # all of the above
```

Integration and functional tests use WorDBless (SQLite). Test files live in `tests/unit/php`,
`tests/integration/php`, and `tests/functional/php`, with the `Test.php` suffix.

## Failure modes / debugging

- A service is not found: the module is not in the `$modules` array of `konomi.php`, or the service id is wrong.
- An activation task does not run: the module does not implement `Activable`, or the module is added after `build()`.
- PHPStan errors on a container `get()`: the return type comes from `bnf/phpstan-psr-container`. Give the class name
  string, not a variable.

Background: `references/structure.md`, `references/lifecycle.md`, `references/data-and-cron.md`,
`references/debugging.md`. These describe common WordPress practice. When they disagree with `docs/`, the `docs/`
directory wins.

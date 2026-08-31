---
name: wp-phpstan
description: "Use when you run or fix PHPStan in Konomi: level 9 over sources/, the WordPress 6.8 stubs, and the PSR container extension."
---

# Konomi PHPStan

## When to use

Use this skill when `composer analysis` reports an error, or when you change `phpstan.neon`.

## Project facts

`phpstan.neon`:

- `level: 9` — the strictest level. Do not lower it.
- `paths: sources` — the tests are not analyzed.
- `scanFiles`: `vendor/inpsyde/wp-stubs-versions/6.8.php`, `tests/Helpers/functions.php`,
  `tests/Helpers/integration.php`.
- `treatPhpDocTypesAsCertain: false`.
- Includes `vendor/bnf/phpstan-psr-container/extension.neon`, so `$container->get(Foo::class)` returns `Foo`.
- There is no baseline file. Fix every error; do not start one.

Run it:

```bash
composer analysis      # vendor/bin/phpstan analyse
composer analysis:ci   # GitHub error format
```

## Procedure

### 1) Fix the type, do not ignore the error

Level 9 rejects `mixed`. The usual fixes in this codebase:

- Give an array shape or a generic array type in PHPDoc: `@param array<string, mixed>`, `@return array<Item>`.
- Cast values that come from WordPress with an explicit cast, as `render.php` does:
  `$showCount = (bool) ($attributes['showCount'] ?? null);`.
- Type the container result with a class-string argument, not a variable. The PSR container extension needs the literal
  class name.
- Add `/** @var Foo $foo */` above a `get()` call only where the extension cannot infer the type, as
  `Blocks\Module::registerBlocks()` does.

Background: `references/wordpress-annotations.md`.

### 2) Stubs

WordPress functions come from `inpsyde/wp-stubs-versions` 6.8, pinned in `composer.json`. If a core function is
unknown, first check that it exists in WordPress 6.8. Do not add another stubs package without a request.

Background: `references/third-party-classes.md`.

### 3) Configuration changes

Keep `paths` at `sources`. Add a `scanFiles` entry only for a file that declares symbols which the analysis needs. Keep
every `ignoreErrors` entry narrow, and write the reason next to it.

Background: `references/configuration.md`.

## Verification

```bash
composer analysis
composer qa   # cs, analysis, tests
```

The analysis must end with no error. A new `ignoreErrors` entry needs a comment that states why the type cannot be
fixed.

## Failure modes / debugging

- "Class not found" for a Konomi class: the file is not under the PSR-4 root `sources/`, or the namespace does not
  match the directory.
- An unknown WordPress function: it is newer than 6.8, or the stubs are not installed. Run `composer install`.
- `get()` returns `mixed`: the argument is not a literal class name.

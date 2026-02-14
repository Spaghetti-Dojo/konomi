# PHP Contributor Instructions

This file contains the project guidelines and commands that apply to the PHP/backend side of Konomi.

## Environment Requirements
- PHP: 8.4+ (required by `composer.json`)
- Composer: 2+

## Installation (PHP dependencies)
After installing JavaScript dependencies, install PHP dependencies:

```bash
composer install
```

Note: Composer install can take 3-5+ minutes. Use appropriate timeouts (180+ seconds) in CI.

## PHP Linting & Static Analysis
PHPCS (code style):

```bash
composer cs      # Check PHP code style (PHPCS with Inpsyde standards)
composer cs:fix  # Auto-fix PHP code style
```

PHPStan (static analysis):

```bash
composer analysis     # Run PHPStan (level 9)
composer analysis:ci  # CI-formatted output
```

## Testing (Pest PHP)
PHP tests use Pest.

```bash
composer test:unit              # Unit tests only
composer test:integration       # Integration tests only
composer test:functional        # Functional tests only
composer tests                  # All PHP tests
composer test:unit:coverage     # With coverage
```

- Tests use `describe()` and `it()` syntax (Pest).
- Configuration files: `phpunit.xml`, `tests/Pest.php`.

## PHP Coding Standards & Patterns
- Use `declare(strict_types=1)` in all PHP files.
- PSR-4 autoloading: namespace `SpaghettiDojo\Konomi\` maps to `sources/`.
- Modular design: each feature is a self-contained module (see `sources/package.php`).
- Dependency injection: prefer constructor injection and `::new()` factories. Avoid global state.
- Use Brain\Monkey to mock WordPress functions and Mockery for class mocks in tests.

## CI Notes (PHP workflows)
- The server linting workflow in `.github/workflows/server-linting.yml` has a known issue: it uses `./src/` in a syntax check; the correct path is `./sources/`.
- Ensure CI uses PHP 8.4 and allows longer timeouts for `composer install`.

## Validation Checklist (backend)
Before submitting PHP changes ensure:
- `composer cs` passes (PHPCS)
- `composer analysis` passes (PHPStan)
- `composer tests` passes (Pest PHP)

## Where to find more
See `composer.json`, `phpcs.xml`, `phpstan.neon`, and `phpunit.xml` for exact rules and scripts.

---
name: wp-rest-api
description: "Use when you work on the Konomi REST layer: Route, Schema, Controller, and the middleware chain under sources/Rest, or the konomi/v1 endpoints."
---

# Konomi REST API

## When to use

Use this skill when you add or change an endpoint, a schema, a controller, or a middleware.

## Project facts

Konomi does not call `register_rest_route()` in feature code. It uses a typed layer in `sources/Rest`:

| Piece                    | Contract                                                            |
| ------------------------ | -------------------------------------------------------------------- |
| `Rest\Route`             | `Route::post(namespace, path, Schema, Controller)`, then `register()` |
| `Rest\Schema`            | `toArray(): array` — a JSON Schema                                   |
| `Rest\Controller`        | `__invoke(WP_REST_Request): WP_REST_Response\|WP_Error`              |
| `Rest\Middleware`        | `__invoke(WP_REST_Request, callable $next)`                          |
| `Rest\ErrorFactory`      | Builds every `WP_Error`                                              |

Existing endpoints, both `POST` under the `konomi/v1` namespace:

- `/user-reaction`
- `/user-bookmark`

Both are registered in `Blocks\Module::initRest()` on `rest_api_init`, with the schema and controller resolved from the
container through `AddSchemaFactory` and `AddControllerFactory`.

Read `docs/rest.md` before you change this layer. It is the reference for the whole design.

## Procedure

### 1) Register the route

```php
add_action('rest_api_init', static function () use ($container): void {
    Rest\Route::post('konomi/v1', '/my-route', $schema, $controller)
        ->withMiddleware($container->get(Rest\Middlewares\ErrorCatch::class))
        ->withMiddleware($container->get(Rest\Middlewares\Authentication::class))
        ->register();
});
```

`register()` derives the `args` from the schema, publishes the schema, and sets an allow-all permission callback. The
route does not authenticate on its own. Attach `Authentication` for a route that needs a logged-in user.

**Attachment order is nesting order.** Attach `ErrorCatch` first, so it wraps everything.

### 2) Write the schema

Return a JSON Schema array from `toArray()`. WordPress validates and sanitizes the arguments before the controller
runs, so the controller can trust the declared parameters. Read the params with `$request->get_param()`. Never read
`$_GET` or `$_POST`.

Background: `references/schema.md`.

### 3) Write the controller

One `__invoke` method. Return a `WP_REST_Response`, or an error from `ErrorFactory` with an explicit status. Build the
controller through a factory service when it needs dependencies, as `AddControllerFactory` does.

### 4) Write a middleware

Implement `Rest\Middleware`. Call `$next($request)` to continue inward. Return a response or an error to stop the
chain. Add the middleware as a service in `Rest\Module::services()`.

Background: `references/routes-and-endpoints.md`, `references/responses-and-fields.md`, `references/authentication.md`.

## Verification

```bash
composer test:integration   # WorDBless, the REST layer
composer analysis           # PHPStan level 9
```

Check the endpoint by hand with the `run-konomi` skill, or with `curl` against the `wp-env` site. The namespace must
appear in `/wp-json/`.

## Failure modes / debugging

- 404: the route is registered outside `rest_api_init`, or the module is not in `konomi.php`.
- 401 where you did not expect it: the `Authentication` middleware is attached to a public route.
- An unhandled exception returns a raw 500: `ErrorCatch` is not the first middleware attached.
- An invalid parameter passes: the schema does not declare it, so WordPress does not validate it.

The reference files describe common WordPress practice, `register_rest_route()` included. Use them for the underlying
behavior only. For the shape of Konomi code, `docs/rest.md` wins.

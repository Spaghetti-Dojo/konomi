# REST

Konomi registers its REST endpoints through a small typed layer instead of raw `register_rest_route()` arrays. An
endpoint is described as data — a route, a schema, a controller — and cross-cutting behavior (auth, error handling) is
composed as a chain of middleware wrapped around the controller, rather than tangled into one handler.

It is a thin wrapper over WordPress' REST infrastructure: requests still flow through `rest_api_init` and use
`WP_REST_Request` / `WP_REST_Response` / `WP_Error`.

## Concepts

**Route.** Binds a namespace and path to a `Schema` and a `Controller`, and carries the middleware to run around them.
Built with `Route::post(...)` and finalized with `register()`.

**Schema.** A JSON-Schema array. WordPress uses it to validate and sanitize request args _before_ the controller runs
and publishes it for endpoint self-description — so inside the controller, declared params are already checked.

**Controller.** The handler: one `__invoke` returning a response or an error.

**Middleware chain.** Each middleware receives the request and a `$next` callable. Call `$next` to continue inward;
return a response or error to short-circuit. **Attachment order is nesting order** — the first middleware attached is
the outermost wrapper. That is why error handling is attached first (it must wrap everything) and auth after it.

## Using it

### Register an endpoint

```php
use SpaghettiDojo\Konomi\Rest;

add_action('rest_api_init', static function (): void {
    Rest\Route::post('my-plugin/v1', '/ping', new PingSchema(), new PingController())
        ->register();
});
```

`register()` derives the validated `args` from the schema, publishes the schema for self-documentation, and sets the
permission callback to allow-all — **the route does not authenticate on its own**. Gate access with the `Authentication`
middleware or your own.

### Wrap it in middleware

```php
Rest\Route::post('my-plugin/v1', '/ping', new PingSchema(), new PingController())
    ->withMiddleware($errorCatch)      // outermost: wraps everything below
    ->withMiddleware($authentication)  // runs after errorCatch, before the controller
    ->register();
```

### The built-in middlewares

Both are container services, so resolve them to get their dependencies wired:

- **`Middlewares\Authentication`** — returns a `401` when the current user is not logged in, otherwise calls `$next`.
- **`Middlewares\ErrorCatch`** — wraps `$next` in try/catch and turns any `\Throwable` into a safe `500` `WP_Error`.
    Attach it first so it is outermost.

```php
$c = \SpaghettiDojo\Konomi\package()->container();

$route
    ->withMiddleware($c->get(Rest\Middlewares\ErrorCatch::class))
    ->withMiddleware($c->get(Rest\Middlewares\Authentication::class))
    ->register();
```

### Write a middleware

```php
use SpaghettiDojo\Konomi\Rest;

final class RequireCapability implements Rest\Middleware
{
    public function __construct(
        private readonly string $capability,
        private readonly Rest\ErrorFactory $errorFactory,
    ) {}

    public function __invoke(\WP_REST_Request $request, callable $next): \WP_REST_Response|\WP_Error
    {
        if (!current_user_can($this->capability)) {
            return $this->errorFactory->create('forbidden', 'Forbidden', ['status' => 403]);
        }
        return $next($request);
    }
}
```

### Write a schema and controller

```php
final class PingSchema implements Rest\Schema
{
    public function toArray(): array
    {
        return [
            'title' => 'ping',
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
        ];
    }
}

final class PingController implements Rest\Controller
{
    public function __construct(private readonly Rest\ErrorFactory $errorFactory) {}

    public function __invoke(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $message = (string) $request->get_param('message'); // already schema-validated
        if ($message === '') {
            return $this->errorFactory->create('bad_request', 'Empty message', ['status' => 400]);
        }
        return new \WP_REST_Response(['pong' => $message], 200);
    }
}
```

Put the HTTP status in the error `data` (`['status' => 4xx|5xx]`) so WordPress sends the right code.

## API

All types are in `SpaghettiDojo\Konomi\Rest` (middlewares under `Rest\Middlewares`).

### `Route`

```php
final class Route
{
    public static function post(string $namespace, string $route, Schema $schema, Controller $controller): self;
    public function withMiddleware(Middleware $middleware): self;  // fluent; first attached = outermost
    public function register(): void;
}
```

The constructor is private — start from `Route::post()`. `POST` is the only public factory today.

### `Controller`

```php
interface Controller
{
    public function __invoke(\WP_REST_Request $request): \WP_REST_Response|\WP_Error;
}
```

### `Schema`

```php
interface Schema
{
    /** @return array<mixed> JSON-Schema for the endpoint */
    public function toArray(): array;
}
```

### `Middleware`

```php
interface Middleware
{
    /** @param callable(\WP_REST_Request): (\WP_REST_Response|\WP_Error) $next */
    public function __invoke(\WP_REST_Request $request, callable $next): \WP_REST_Response|\WP_Error;
}
```

Return `$next($request)` to continue inward; return a response/error to short-circuit.

### `ErrorFactory`

```php
final class ErrorFactory
{
    public static function new(): ErrorFactory;
    public function create(int|string $code = '', string $message = '', mixed $data = ''): \WP_Error;
}
```

### Middlewares

```php
final class Middlewares\Authentication implements Rest\Middleware  // 401 when not logged in
final class Middlewares\ErrorCatch     implements Rest\Middleware  // \Throwable -> 500 WP_Error
```

Resolve both from the container so their dependencies are injected.

## Related

- [Blocks](./blocks.md) — the front-end that calls these endpoints.
- [User](./user.md) — the domain the reaction/bookmark controllers drive.
- [Storage](./storage.md) — where the saved data lands.

# REST

Konomi exposes its custom REST endpoints through a small routing layer in `SpaghettiDojo\Konomi\Rest`. A `Rest\Route` binds a namespace + path to a `Schema` (JSON-Schema for arg validation and endpoint self-description) and a `Controller` (the handler), then wraps the handler in an ordered stack of `Middleware`. Core uses it to register `konomi/v1/user-reaction` and `konomi/v1/user-bookmark`; you use the same API to add your own endpoints, reuse the built-in middlewares, or write new ones.

This layer is a thin, typed wrapper over `register_rest_route()` — it does not replace WordPress' REST infrastructure. Requests still flow through the normal `rest_api_init` lifecycle and `WP_REST_Request`/`WP_REST_Response`/`WP_Error` objects.

## What you can do

- **Register a custom REST route** with a typed builder (`Rest\Route::post(...)->register()`) instead of a raw `register_rest_route()` array.
- **Attach middleware** to a route — cross-cutting concerns (auth, error handling, logging) run around your controller in a defined order.
- **Reuse the built-in middlewares** — `Middlewares\Authentication` (reject anonymous requests) and `Middlewares\ErrorCatch` (turn uncaught exceptions into a safe 500 `WP_Error`).
- **Write your own middleware** by implementing a single-method interface; return early to short-circuit the chain, or call `$next` to continue.
- **Describe request/response shape** with a `Schema`, and **build error responses** with `ErrorFactory`.

All public types (`@api`) live in `sources/Rest/`. `Method` and `MiddlewareProcess` are `@internal`.

## How-to recipes

### 1. Register a new REST route

`Rest\Route` is built with a static factory and registered inside a `rest_api_init` callback. Only `POST` has a public factory today:

```php
public static function post(
    string $namespace,
    string $route,
    Schema $schema,
    Controller $controller
): self;
```

A complete minimal endpoint (`POST /wp-json/my-plugin/v1/ping`):

```php
use SpaghettiDojo\Konomi\Rest;

add_action('rest_api_init', static function (): void {
    Rest\Route::post(
        'my-plugin/v1',
        '/ping',
        new PingSchema(),      // implements Rest\Schema
        new PingController()   // implements Rest\Controller
    )->register();
});
```

`register()` (from `RestRegistTrait`) calls `register_rest_route()` for you. It:

- sets `methods` from the route's `Method`;
- derives `args` from your schema via `rest_get_endpoint_args_for_schema($schema->toArray(), $method)`, so declared properties are validated/sanitized by WordPress before your controller runs;
- publishes the schema under the endpoint's `schema` key (`draft-04`) for self-documentation;
- sets `permission_callback` to `__return_true`. **The route itself does not authenticate** — gate access with the `Authentication` middleware (recipe 3) or your own permission middleware.

To pull collaborators from Konomi's container instead of `new`-ing them, resolve them inside the callback:

```php
$container = \SpaghettiDojo\Konomi\package()->container();

add_action('rest_api_init', static function () use ($container): void {
    Rest\Route::post('my-plugin/v1', '/ping', new PingSchema(), new PingController())
        ->register();
});
```

### 2. Attach middleware to a route

`withMiddleware()` appends a middleware and returns the route (fluent). Attachment order is the **execution nesting order**: the first middleware attached is the **outermost** wrapper (it runs first on the way in and last on the way out), the controller is innermost.

```php
Rest\Route::post('my-plugin/v1', '/ping', new PingSchema(), new PingController())
    ->withMiddleware($errorCatch)      // outermost — wraps everything below
    ->withMiddleware($authentication)  // runs after errorCatch, before controller
    ->register();
```

Here `ErrorCatch` wraps `Authentication` wraps the controller, so an exception thrown anywhere below is caught. This is exactly how core registers its routes:

```php
// sources/Blocks/Module.php — the user-reaction route (usage example)
Rest\Route::post(
    'konomi/v1',
    '/user-reaction',
    $container->get(AddSchemaFactory::class)->create('user-reaction'),
    $container->get(AddControllerFactory::class)->create(
        User\ItemGroup::REACTION,
        AddResponse::new(
            User\ItemGroup::REACTION,
            'Like saved',
            'Invalid Like data, please contact the support or try again later.',
            'Failed to save like'
        )
    )
)
    ->withMiddleware($container->get(Rest\Middlewares\ErrorCatch::class))
    ->withMiddleware($container->get(Rest\Middlewares\Authentication::class))
    ->register();
```

`konomi/v1/user-bookmark` is registered the same way with `User\ItemGroup::BOOKMARK`.

### 3. Use the built-in middlewares

Both ship as container services and are constructed via `new()`; resolve them from the container so their dependencies are wired for you.

**`Middlewares\Authentication`** — returns a `401 unauthorized` `WP_Error` when the current user is not logged in, otherwise calls `$next`. Attach it to any endpoint that requires a session:

```php
$auth = \SpaghettiDojo\Konomi\package()->container()
    ->get(Rest\Middlewares\Authentication::class);

$route->withMiddleware($auth);
```

Its factory (already satisfied by the container) is:

```php
Authentication::new(User\User $user, Rest\ErrorFactory $errorFactory): Authentication;
```

**`Middlewares\ErrorCatch`** — wraps `$next` in a `try/catch` and converts any `\Throwable` into a `500 internal_error` `WP_Error` (with a generic, translated message; exception details ride along in the error `data`). Attach it **first** so it is the outermost wrapper and catches everything downstream:

```php
$errorCatch = \SpaghettiDojo\Konomi\package()->container()
    ->get(Rest\Middlewares\ErrorCatch::class);

$route
    ->withMiddleware($errorCatch)   // outermost
    ->withMiddleware($auth)
    ->register();
```

Factory:

```php
ErrorCatch::new(Rest\ErrorFactory $errorFactory): ErrorCatch;
```

### 4. Write a custom middleware

Implement `Rest\Middleware`. The single method receives the request and a `$next` callable; return a `WP_REST_Response`/`WP_Error` to short-circuit, or return `$next($request)` to pass control inward:

```php
use SpaghettiDojo\Konomi\Rest;

final class RequireCapability implements Rest\Middleware
{
    public function __construct(
        private readonly string $capability,
        private readonly Rest\ErrorFactory $errorFactory,
    ) {
    }

    public function __invoke(
        \WP_REST_Request $request,
        callable $next
    ): \WP_REST_Response|\WP_Error {
        if (!current_user_can($this->capability)) {
            // Short-circuit: $next is never called, inner middlewares/controller skipped.
            return $this->errorFactory->create('forbidden', 'Forbidden', ['status' => 403]);
        }

        return $next($request); // continue the chain
    }
}
```

Attach it like any other: `->withMiddleware(new RequireCapability('edit_posts', ErrorFactory::new()))`.

How the chain runs: `MiddlewareProcess::run()` folds the middleware list around the controller so that calling `$next` steps one level inward. Returning without calling `$next` stops the chain there and nothing further executes — that is how `Authentication` blocks anonymous requests. Middleware runs *after* WordPress has already validated `args` against your schema.

### 5. Define a schema and controller

A route needs one of each.

**`Schema`** — return the JSON-Schema array WordPress uses for arg validation and endpoint description:

```php
use SpaghettiDojo\Konomi\Rest;

final class PingSchema implements Rest\Schema
{
    public function toArray(): array
    {
        return [
            'title' => 'ping',
            'type' => 'object',
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'required' => true,
                ],
            ],
        ];
    }
}
```

Declared properties become validated/sanitized `args`, so inside the controller `$request->get_param('message')` is already checked against the schema.

**`Controller`** — a single `__invoke` that returns a response or an error:

```php
use SpaghettiDojo\Konomi\Rest;

final class PingController implements Rest\Controller
{
    public function __construct(
        private readonly Rest\ErrorFactory $errorFactory,
    ) {
    }

    public function __invoke(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $message = (string) $request->get_param('message');

        if ($message === '') {
            return $this->errorFactory->create('bad_request', 'Empty message', ['status' => 400]);
        }

        return new \WP_REST_Response(['pong' => $message], 200);
    }
}
```

**`ErrorFactory`** — a tiny factory over `WP_Error`. Construct it via `ErrorFactory::new()` (the constructor is private):

```php
public static function new(): ErrorFactory;
public function create(int|string $code = '', string $message = '', mixed $data = ''): \WP_Error;
```

Put an HTTP status in `data` (`['status' => 4xx|5xx]`) so WordPress sends the right response code:

```php
$errorFactory = Rest\ErrorFactory::new();
return $errorFactory->create('not_found', 'Nothing here', ['status' => 404]);
```

For a real controller that resolves collaborators, model it on `Blocks\Rest\AddController`: validate the request into a domain object, persist through a repository, and return distinct success/error responses.

## Public API

All types are in namespace `SpaghettiDojo\Konomi\Rest` (middlewares under `...\Rest\Middlewares`).

### `Route` (`@api`)

Fluent builder that registers a route.

```php
final class Route
{
    use RestRegistTrait;

    public static function post(
        string $namespace,
        string $route,
        Schema $schema,
        Controller $controller
    ): self;

    public function withMiddleware(Middleware $middleware): self; // returns $this; first = outermost
}
```

The constructor is private/`final` — always start from `Route::post(...)`. There is no public `get()`/`put()`/`delete()` factory yet, so this layer registers `POST` routes.

### `RestRegistTrait`

Provides `register(): void` to `Route`. Calls `register_rest_route()` using the route's method, `$schema->toArray()` (for both `args` and the published `schema`), and a `__return_true` permission callback (authenticate with middleware, not here). Wraps the controller in the middleware chain via `MiddlewareProcess::run()`.

### `Controller` (`@api`)

```php
interface Controller
{
    public function __invoke(\WP_REST_Request $request): \WP_REST_Response|\WP_Error;
}
```

### `Schema` (`@api`)

```php
interface Schema
{
    /** @return array<mixed> JSON-Schema for the endpoint */
    public function toArray(): array;
}
```

### `Middleware` (`@api`)

```php
interface Middleware
{
    /**
     * @param callable(\WP_REST_Request): (\WP_REST_Response|\WP_Error) $next
     */
    public function __invoke(
        \WP_REST_Request $request,
        callable $next
    ): \WP_REST_Response|\WP_Error;
}
```

Return `$next($request)` to continue inward; return a response/error to short-circuit.

### `MiddlewareProcess` (`@internal`)

Runs the chain — you don't call it directly (`RestRegistTrait` does).

```php
public static function run(
    array $middlewares,          // array<Middleware>
    callable $controller,        // callable(\WP_REST_Request): (\WP_REST_Response|\WP_Error)
    \WP_REST_Request $request
): \WP_REST_Response|\WP_Error;
```

It reverses the list and folds each middleware around the controller, so the first-attached middleware ends up outermost.

### `ErrorFactory`

```php
final class ErrorFactory
{
    public static function new(): ErrorFactory;
    public function create(int|string $code = '', string $message = '', mixed $data = ''): \WP_Error;
}
```

### `Method` (`@internal`)

```php
enum Method: string { case POST = 'POST'; case GET = 'GET'; }
```

Chosen for you by the `Route` factory; not part of the surface you construct.

### `Middlewares\Authentication`

```php
final class Authentication implements Rest\Middleware
{
    public static function new(User\User $user, Rest\ErrorFactory $errorFactory): Authentication;
    // __invoke: 401 'unauthorized' WP_Error when !$user->isLoggedIn(), else $next($request)
}
```

Resolve from the container (`Middlewares\Authentication::class`) so `User\User` and `ErrorFactory` are injected.

### `Middlewares\ErrorCatch`

```php
final class ErrorCatch implements Rest\Middleware
{
    public static function new(Rest\ErrorFactory $errorFactory): ErrorCatch;
    // __invoke: try { return $next($request); } catch (\Throwable) { return 500 'internal_error' WP_Error }
}
```

Attach it outermost (first) to catch everything below. Resolve from the container (`Middlewares\ErrorCatch::class`).

## Related

- [`./blocks.md`](./blocks.md) — the Interactivity API blocks that call these endpoints, and the `Blocks\Rest\*` schema/controller/response classes used as the reference implementation above.
- [`./user.md`](./user.md) — `User\User`, `User\ItemFactory`, `User\ItemGroup` and the persistence the reaction/bookmark controllers drive.
- [`./storage-drivers.md`](./storage-drivers.md) — where the data those controllers save ultimately lands.
</content>
</invoke>

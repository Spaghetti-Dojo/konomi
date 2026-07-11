# User

The `SpaghettiDojo\Konomi\User` module is the entry point for reading and writing the interactions (reactions and bookmarks) that belong to a user. A single value object — `User\Item` — represents one interaction: a post id, a type, an active flag, and an `ItemGroup` (reaction or bookmark). The `User` object is the façade you work with; a `User\Repository` persists items through the shared [`Storage`](./storage.md) driver on the `Axis::User` axis.

This document is a how-to for developers building on Konomi: how to get the current user, read their saved items, and create/save an item — plus the `konomi.user.repository.save-successfully` action the [Post module](./post.md) listens to.

## What you can do

- Get the current logged-in user as a `User\User` object via the `currentUser()` function.
- Read every item a user has saved for a group — `all(ItemGroup::REACTION)` or `all(ItemGroup::BOOKMARK)`.
- Look up a single item by post id and group with `findItem()`, and inspect it (`isActive()`, `isValid()`, `type()`).
- Build items with `User\ItemFactory` and persist them with `saveItem()` (toggle on/off via the item's active flag).
- Hook `konomi.user.repository.save-successfully` to run side effects whenever an item is saved.

## How-to recipes

### 1. Get the current user

`currentUser()` (in `sources/User/api.php`) resolves the `UserFactory` from the container and returns a `User\User` wrapping `wp_get_current_user()`.

```php
use function SpaghettiDojo\Konomi\User\currentUser;

$user = currentUser();

if (!$user->isLoggedIn()) {
    return; // anonymous visitor
}

$userId = $user->id(); // 0 when not logged in
```

`User\User` exposes:

- `isLoggedIn(): bool`
- `id(): int` — WordPress user id, `0` when anonymous
- `findItem(int $id, ItemGroup $group): Item`
- `saveItem(Item $item): bool`
- `all(ItemGroup $group): array` — `array<Item>`
- `merge(Item ...$items): array` — `array<int, array<Item>>`, grouping items by post id

### 2. Read a user's saved items

`all()` returns every stored `Item` for one `ItemGroup`. The group is an enum with exactly two cases — `ItemGroup::REACTION` (`'reaction'`) and `ItemGroup::BOOKMARK` (`'bookmark'`).

```php
use SpaghettiDojo\Konomi\User\ItemGroup;
use function SpaghettiDojo\Konomi\User\currentUser;

$user = currentUser();

$reactions = $user->all(ItemGroup::REACTION); // array<Item>
foreach ($reactions as $item) {
    $postId = $item->id();
    $type = $item->type();      // e.g. the post type
    $active = $item->isActive();
}

$bookmarks = $user->all(ItemGroup::BOOKMARK);
```

To check a single post, use `findItem()`. It always returns an `Item`; when nothing is stored you get a null item (`isValid() === false`, `isActive() === false`), so you never have to null-check the return value.

```php
$item = $user->findItem(123, ItemGroup::BOOKMARK);

if ($item->isActive()) {
    // post 123 is currently bookmarked by this user
}
```

### 3. Create and save an item

Build items with `User\ItemFactory` (it accepts an `ItemGroup` or its string value and validates it), then persist with `saveItem()`. The active flag is the toggle: `true` stores the item, `false` removes it from the user's set on the next save.

```php
use SpaghettiDojo\Konomi\User\ItemFactory;
use SpaghettiDojo\Konomi\User\ItemGroup;
use function SpaghettiDojo\Konomi\User\currentUser;

$user = currentUser();
$factory = ItemFactory::new();

// Bookmark post 123
$item = $factory->create(123, 'post', true, ItemGroup::BOOKMARK);
$saved = $user->saveItem($item); // bool

// Remove the bookmark: same item, isActive = false
$removed = $factory->create(123, 'post', false, ItemGroup::BOOKMARK);
$user->saveItem($removed);
```

You can also build the value object directly with `Item::new()`; the group defaults to `ItemGroup::REACTION`:

```php
use SpaghettiDojo\Konomi\User\Item;

$reaction = Item::new(123, 'post', true); // group => ItemGroup::REACTION
$user->saveItem($reaction);
```

`saveItem()` returns `false` without touching storage when the user is anonymous (`id() === 0`) or the item is invalid (`id <= 0` or empty `type`). On a successful write it fires the action below.

### 4. React to a successful save

When a save is persisted, `User\Repository::save()` fires `konomi.user.repository.save-successfully`. This is the seam the Post module uses to mirror the record on the entity axis, and you can hook it for your own side effects (cache busting, notifications, counters).

```php
use SpaghettiDojo\Konomi\User;
use SpaghettiDojo\Konomi\Storage;

add_action(
    'konomi.user.repository.save-successfully',
    static function (
        User\Item $item,
        User\User $user,
        User\ItemGroup $group,
        Storage\StorageKey $storageKey
    ): void {
        // Runs after the item was written on Axis::User.
        error_log(sprintf('User %d saved post %d (%s)', $user->id(), $item->id(), $group->value));
    },
    10,
    4
);
```

The action passes four arguments, in order: the `User\Item`, the `User\User`, the item's `User\ItemGroup`, and the `Storage\StorageKey`. (The Post module subscribes with only the first two — see [post.md](./post.md).)

Two read actions are also available for observing lookups: `konomi.user.repository.find` (`$item, $user, $storageKey, $id`) and `konomi.user.repository.all` (`$items, $user, $storageKey`).

## Public API

### `sources/User/api.php`

```php
namespace SpaghettiDojo\Konomi\User;

function currentUser(): User;
```

Returns the current user as a `User` object (wraps `wp_get_current_user()`).

### `User` (interface, `@api`)

```php
interface User
{
    public function isLoggedIn(): bool;
    public function id(): int;
    public function findItem(int $id, ItemGroup $group): Item;
    public function saveItem(Item $item): bool;
    /** @return array<Item> */
    public function all(ItemGroup $group): array;
    /** @return array<int, array<Item>> */
    public function merge(Item ...$items): array;
}
```

`CurrentUser` is the shipped `@internal` implementation; obtain it via `currentUser()` rather than constructing it directly.

### `Item` (value object, `@api`)

```php
final class Item
{
    public static function null(): self;
    public static function new(int $id, string $type, bool $isActive, ItemGroup $group = ItemGroup::REACTION): self;

    public function id(): int;
    public function type(): string;
    public function isActive(): bool;
    public function isValid(): bool; // id > 0 && type !== ''
    public function group(): ItemGroup;
}
```

### `ItemGroup` (enum, `@api`)

```php
enum ItemGroup: string
{
    case REACTION = 'reaction';
    case BOOKMARK = 'bookmark';

    public static function fromValue(string|ItemGroup $value): ItemGroup; // throws \ValueError on unknown value
}
```

### `ItemFactory` (`@api`)

```php
class ItemFactory
{
    public static function new(): self;
    public function create(int $id, string $type, bool $isActive, ItemGroup|string $group): Item;
}
```

Accepts an `ItemGroup` or its string value (`'reaction'` / `'bookmark'`); an unknown string throws `\ValueError` via `ItemGroup::fromValue()`.

### `UserFactory` (`@api`)

```php
class UserFactory
{
    public static function new(Repository $itemRepository): UserFactory;
    public function create(): User;
}
```

Registered in the container; `currentUser()` resolves it for you. Resolve it manually only if you need a fresh `User` outside the helper:

```php
use SpaghettiDojo\Konomi\User\UserFactory;

$user = \SpaghettiDojo\Konomi\package()
    ->container()
    ->get(UserFactory::class)
    ->create();
```

### `Repository`, `CurrentUser`, `ItemRegistry`, `ItemRegistryKey` (`@internal`)

These back the `User` façade and are not part of the supported surface. `Repository` performs the actual `Storage` reads/writes on `Storage\Axis::User` and fires the repository actions; `ItemRegistry` / `ItemRegistryKey` provide the per-request in-memory cache keyed by `{userId}.{group}`. Reach them through `User`'s methods (`all()`, `findItem()`, `saveItem()`) and the documented actions rather than calling them directly.

## Related

- [post.md](./post.md) — the Post module reads a post's interactions and reacts to `konomi.user.repository.save-successfully`.
- [storage.md](./storage.md) — the `Storage` driver both repositories write through; `User\Repository` always uses `Axis::User`.

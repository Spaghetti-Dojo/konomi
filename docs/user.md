# User

The User module answers one question — _what has this user reacted to or bookmarked?_ — and lets you record new answers.
You work with a single `User` object and small value objects; where and how the data is stored is out of view.

## Concepts

**Item** — one interaction. It carries a post id, a post type, an active flag, and a group. The active flag is the whole
toggle: an active item is "saved", an inactive one removes that interaction on the next write. An item is _valid_ when
its id is positive and its type is non-empty; invalid items are ignored by writes.

**Group** — the kind of interaction, an enum with two cases: `ItemGroup::REACTION` and `ItemGroup::BOOKMARK`. Every read
and write is scoped to a group.

**Null item** — a lookup that finds nothing still returns an `Item`, one that reports `isValid() === false`. You never
null-check a lookup result.

## Using it

### Get the current user

```php
use function SpaghettiDojo\Konomi\User\currentUser;

$user = currentUser();

if (!$user->isLoggedIn()) {
    return; // anonymous visitor; id() would be 0
}
```

### Read what a user saved

`all()` returns every item in a group; `findItem()` checks a single post:

```php
use SpaghettiDojo\Konomi\User\ItemGroup;

$bookmarks = $user->all(ItemGroup::BOOKMARK); // array<Item>

$item = $user->findItem(123, ItemGroup::BOOKMARK);
if ($item->isActive()) {
    // post 123 is currently bookmarked by this user
}
```

### Save or remove an interaction

Build an item with `ItemFactory`, then `saveItem()`. Active saves it; inactive removes it.

```php
use SpaghettiDojo\Konomi\User\{ItemFactory, ItemGroup};

$factory = ItemFactory::new();

$user->saveItem($factory->create(123, 'post', true, ItemGroup::BOOKMARK));   // bookmark
$user->saveItem($factory->create(123, 'post', false, ItemGroup::BOOKMARK));  // un-bookmark
```

`saveItem()` returns `false` without touching storage when the user is anonymous or the item is invalid. A successful
write fires the save action below.

### React to a save

When a save persists, the module fires `konomi.user.repository.save-successfully`. Hook it for side effects — cache
busting, counters, notifications. This is also the seam the [Post](./post.md) module uses to mirror the interaction onto
the post index.

```php
use SpaghettiDojo\Konomi\User;

add_action(
    'konomi.user.repository.save-successfully',
    static function (User\Item $item, User\User $user): void {
        if ($item->isActive()) {
            // e.g. bump an analytics counter for $item->id()
        }
    },
    10,
    2 // request only the args you need
);
```

## API

### `currentUser()`

```php
namespace SpaghettiDojo\Konomi\User;

function currentUser(): User;
```

The current WordPress user as a `User`. Prefer this over resolving `UserFactory` yourself.

### `User`

```php
interface User
{
    public function isLoggedIn(): bool;
    public function id(): int;                              // 0 when anonymous
    public function findItem(int $id, ItemGroup $group): Item;
    public function saveItem(Item $item): bool;
    /** @return array<Item> */
    public function all(ItemGroup $group): array;
    /** @return array<int, array<Item>> */
    public function merge(Item ...$items): array;           // items grouped by post id
}
```

### `Item`

```php
class Item
{
    public static function new(int $id, string $type, bool $isActive, ItemGroup $group = ItemGroup::REACTION): self;
    public static function null(): self;

    public function id(): int;
    public function type(): string;
    public function isActive(): bool;
    public function isValid(): bool;      // id > 0 && type !== ''
    public function group(): ItemGroup;
}
```

`Item::new()` builds one directly; the group defaults to `REACTION`.

### `ItemGroup`

```php
enum ItemGroup: string
{
    case REACTION = 'reaction';
    case BOOKMARK = 'bookmark';

    public static function fromValue(string|ItemGroup $value): ItemGroup; // \ValueError on unknown value
}
```

### `ItemFactory`

```php
class ItemFactory
{
    public static function new(): self;
    public function create(int $id, string $type, bool $isActive, ItemGroup|string $group): Item;
}
```

Accepts a group as enum or string (`'reaction'` / `'bookmark'`); an unknown string throws `\ValueError`.

### `UserFactory`

```php
class UserFactory
{
    public function create(): User;
}
```

Backs `currentUser()`. Registered under `UserFactory::class`; resolve it only if you need a fresh `User` outside the
helper.

### Action: `konomi.user.repository.save-successfully`

Fired after a successful save.

```php
do_action(
    'konomi.user.repository.save-successfully',
    User\Item $item,
    User\User $user,
    User\ItemGroup $group,
    Storage\StorageKey $storageKey
);
```

Two read actions also exist for observing lookups: `konomi.user.repository.find` (`$item, $user, $storageKey, $id`) and
`konomi.user.repository.all` (`$items, $user, $storageKey`).

## Related

- [Post](./post.md) — the read side; counts per post, kept in sync by the save action above.
- [Storage](./storage.md) — where items are persisted; the User side always uses `Axis::User`.

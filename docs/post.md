# Post

The `SpaghettiDojo\Konomi\Post` module is the read side of an interaction, indexed by post. Where the
[User module](./user.md) answers "what has _this user_ saved?", the Post module answers "how many _users_ have reacted
to / bookmarked _this post_?". It stores the same `User\Item` records, but on the entity axis of the shared
[`Storage`](./storage.md) driver (`Axis::Entity`), so counts per post can be read without scanning every user.

The two sides are kept in sync by an event: when `User\Repository` saves an item it fires
`konomi.user.repository.save-successfully`, and this module's `Module::run()` subscribes to that action and mirrors the
record into the post-indexed store. This document shows how to read a post's interactions and how to hook the same event
for your own side effects.

## What you can do

- Count how many users have saved a post for a group with `Post::countForPost($postId, User\ItemGroup::REACTION)` (or
    `BOOKMARK`).
- Hook `konomi.user.repository.save-successfully` to run side effects when any post is reactioned or bookmarked.
- Rely on the event-driven mirror: a `User\Repository` save automatically becomes a `Post\Repository` save on the
    entity axis.

## How-to recipes

### 1. Read a post's interaction count

`Post` is a container service. Resolve it and call `countForPost()` with the post id and an `ItemGroup`.

```php
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\User\ItemGroup;

$post = \SpaghettiDojo\Konomi\package()
    ->container()
    ->get(Post\Post::class);

$reactionCount = $post->countForPost(123, ItemGroup::REACTION); // int
$bookmarkCount = $post->countForPost(123, ItemGroup::BOOKMARK);
```

`countForPost()` returns the number of distinct users who currently have an active item stored for that post and group.
Under the hood it calls `Post\Repository::find()`, which reads the entity-axis records into a registry keyed by user id
and returns them; `Post` counts that array.

### 2. React to a saved interaction

The same `konomi.user.repository.save-successfully` action documented in [user.md](./user.md) is a general extension
point. The callback receives the `User\Item` that was saved and the `User\User` who saved it — enough to run any side
effect when a post is reactioned or bookmarked.

```php
use SpaghettiDojo\Konomi\User;

add_action(
    'konomi.user.repository.save-successfully',
    static function (User\Item $item, User\User $user): void {
        $postId = $item->id();
        $group = $item->group(); // ItemGroup::REACTION | ItemGroup::BOOKMARK

        if ($item->isActive()) {
            // e.g. bust a cached count, send a notification, bump an analytics counter
            do_action('myplugin.recount', $postId, $group->value);
        }
    },
    10,
    2
);
```

The action actually passes four arguments (`$item`, `$user`, `$group`, `$storageKey`); request only the ones you need
via the `add_action` arg count. This module registers with `2`.

### 3. Understand the event-driven link

You do not call `Post\Repository::save()` yourself — the module wires it to the User save. In `Post\Module::run()`:

```php
add_action(
    'konomi.user.repository.save-successfully',
    static fn (User\Item $item, User\User $user) => $container
        ->get(Repository::class)
        ->save($item, $user),
    10,
    2
);
```

So the full flow for a single toggle is:

1. A caller builds a `User\Item` and calls `$user->saveItem($item)` (see [user.md](./user.md)).
2. `User\Repository::save()` writes the record on `Storage\Axis::User` and fires
   `konomi.user.repository.save-successfully`.
3. This module's subscriber calls `Post\Repository::save($item, $user)`, which writes the _same_ record on
   `Storage\Axis::Entity` — mirroring the interaction onto the post index (and firing its own
   `konomi.post.collection.save` action before the write).

Because both axes live in the one `konomi_interactions` table, the user-indexed and post-indexed views stay consistent.
See [storage.md](./storage.md) for the axis model.

## Public API

### `Post` (`@api`)

```php
namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

class Post
{
    public static function new(Repository $repository): Post;
    public function countForPost(int $id, User\ItemGroup $group): int;
}
```

Resolve it from the container (`package()->container()->get(Post\Post::class)`); it is registered under `Post::class`.
`countForPost()` is the module's public read method.

### Action: `konomi.user.repository.save-successfully`

Fired by `User\Repository` after a successful save; the Post module subscribes to it. Signature (arg order):

```php
do_action(
    'konomi.user.repository.save-successfully',
    User\Item $item,
    User\User $user,
    User\ItemGroup $group,
    Storage\StorageKey $storageKey
);
```

### Action: `konomi.post.collection.save`

Fired inside `Post\Repository::save()` just before the entity-axis write, so you can observe the mirror step:

```php
do_action(
    'konomi.post.collection.save',
    User\Item $item,
    User\User $user,
    Storage\StorageKey $storageKey
);
```

### `Repository`, `ItemRegistry`, `ItemRegistryKey` (`@internal`)

Not part of the supported surface — use `Post::countForPost()` and the actions above instead.

- `Repository` reads/writes on `Storage\Axis::Entity`.
    `find(int $entityId, User\ItemGroup $group): array<int, User\Item>` returns the post's items keyed by user id;
    `save(User\Item $item, User\User $user): bool` is invoked by the event subscriber, not by callers. `save()` returns
    `false` when the user is anonymous or the item is invalid.
- `ItemRegistry` / `ItemRegistryKey` provide the per-request in-memory cache, keyed by `{postId}.{group}` and indexed
    internally by user id.

## Related

- [user.md](./user.md) — read and write a user's reactions and bookmarks; source of the `save-successfully` action.
- [storage.md](./storage.md) — the shared `Storage` driver; `Post\Repository` always uses `Axis::Entity`.

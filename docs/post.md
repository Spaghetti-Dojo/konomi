# Post

The Post module is the read side of an interaction, indexed by post instead of by user. Where the [User](./user.md)
module answers "what has _this user_ saved?", Post answers "how many _users_ reacted to or bookmarked _this post_?" —
without scanning every user.

## Concepts

**Two views of one fact.** A single interaction is reachable two ways: along the user axis (the User module) and along
the post axis (this module). Both are stored in the same table; the axis is just which side you enter from. See
[Storage](./storage.md) for the axis model.

**Kept in sync by an event, not by you.** You never call the post-side save yourself. When the User module persists an
interaction it fires `konomi.user.repository.save-successfully`; the Post module subscribes and mirrors the record onto
the post index. Reading a post's count and reacting to saves is the whole public surface.

## Using it

### Count a post's interactions

```php
use SpaghettiDojo\Konomi\Post;
use SpaghettiDojo\Konomi\User\ItemGroup;

$post = \SpaghettiDojo\Konomi\package()
    ->container()
    ->get(Post\Post::class);

$reactions = $post->countForPost(123, ItemGroup::REACTION); // int
$bookmarks = $post->countForPost(123, ItemGroup::BOOKMARK);
```

`countForPost()` returns the number of distinct users who currently have an active item for that post and group.

### React to any save

The same `konomi.user.repository.save-successfully` action from the [User](./user.md) module is a general extension
point — the callback gets the item and the user, enough to run any side effect:

```php
use SpaghettiDojo\Konomi\User;

add_action(
    'konomi.user.repository.save-successfully',
    static function (User\Item $item, User\User $user): void {
        if ($item->isActive()) {
            do_action('myplugin.recount', $item->id(), $item->group()->value);
        }
    },
    10,
    2
);
```

## API

### `Post`

```php
namespace SpaghettiDojo\Konomi\Post;

use SpaghettiDojo\Konomi\User;

class Post
{
    public function countForPost(int $id, User\ItemGroup $group): int;
}
```

Registered under `Post::class`; resolve it from the container.

### Action: `konomi.post.collection.save`

Fired just before the post-axis write, so you can observe the mirror step:

```php
do_action(
    'konomi.post.collection.save',
    User\Item $item,
    User\User $user,
    Storage\StorageKey $storageKey
);
```

The upstream `konomi.user.repository.save-successfully` action (documented in [User](./user.md)) is what triggers this
step.

## Related

- [User](./user.md) — the write side and the source of the save event.
- [Storage](./storage.md) — the shared store; the Post side always uses `Axis::Entity`.

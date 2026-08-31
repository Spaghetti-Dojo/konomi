# Post

The Post module is the read side of an interaction, indexed by post instead of by user. Where the [User](./user.md)
module answers "what has _this user_ saved?", Post answers "how many _users_ reacted to or bookmarked _this post_?" —
without scanning every user.

## Concepts

**Two views of one fact.** A single interaction is reachable two ways: along the user axis (the User module) and along
the post axis (this module). Both are stored in the same table; the axis is just which side you enter from. See
[Storage](./storage.md) for the axis model.

**Written once, readable from either side.** An interaction is written one time only, on the user axis, by the
[User](./user.md) module. The row it stores already carries the post id, so the post axis reads the same row back. The
Post module never writes and never mirrors anything: it is a read view over the rows the User module persists. Reading a
post's count is its whole public surface.

**A count resolved in a request is not refreshed by a later save in that request.** The post-side index is built on the
first read of a post and group, then served from memory for the rest of the request. A save that happens after that read
lands in the table, but the already-resolved count keeps its value until the next request rebuilds the index. This is an
accepted trade-off: counts are rendered on page load, before any save in the same request.

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

## Related

- [User](./user.md) — the only writer of an interaction and the source of the save event.
- [Storage](./storage.md) — the shared store; the Post side always uses `Axis::Entity`.

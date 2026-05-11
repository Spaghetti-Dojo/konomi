## Context

`custom-table-storage` introduced two `Storage` backends backed by the shared `konomi_interactions` table. Both `TableStorage` classes only need the `group_key` portion of the key (e.g. `"reaction"`) to populate the `group_key` column. `StorageKey::for()` builds a composite `"_konomi_items.reaction"` string, then `Database\StorageKeyParser` re-extracts the trailing segment. The composite shape exists solely because the original `MetaStorage` backend uses the full string as a `meta_key`.

This change relocates the base prefix to where it is actually used: inside `MetaStorage`. `StorageKey` becomes a group-only sanitizer/validator.

## Goals

- One source of truth for the `meta_key` shape: `MetaStorage` itself.
- Remove the parser roundtrip for `TableStorage`.
- Keep the `Storage::read/write(int $id, string $key, ...)` signature unchanged so downstream consumers and the interface contract stay stable.
- Keep `Repository` code unchanged in shape — still calls `$this->key->for($group)`.

## Non-Goals

- Merging `Post\TableStorage` and `User\TableStorage` into a single class.
- Collapsing `Post\StorageKey` and `User\StorageKey`.
- Changing the `konomi_interactions` schema or column names.
- Touching `MetaStorage` callers (still wired off, kept for future toggle).

## Decisions

### Decision 1: `StorageKey::for()` returns sanitized group only

```php
final class StorageKey
{
    public static function new(): self { return new self(); }
    final private function __construct() {}

    public function for(ItemGroup $group): string
    {
        $value = $group->value;
        if ($value === '') {
            throw new \InvalidArgumentException('Group value cannot be empty');
        }
        $sanitized = preg_replace('/[^a-z0-9_]/', '', $value);
        if ($sanitized !== $value) {
            throw new \UnexpectedValueException('Group value contains invalid characters');
        }
        return $sanitized;
    }
}
```

Rationale: `StorageKey` keeps its role as the trusted producer of the key string, but the string it produces is the bare group. Sanitization rules drop the `.` from the allowlist since it is no longer part of the output.

### Decision 2: `MetaStorage` owns the base prefix

```php
class MetaStorage implements Storage
{
    private const BASE = '_konomi_items';

    public function read(int $id, string $key): array
    {
        $metaKey = self::BASE . '.' . $key;
        // ...get_post_meta / get_user_meta with $metaKey
    }
    public function write(int $id, string $key, array $data): bool
    {
        $metaKey = self::BASE . '.' . $key;
        // ...update_post_meta / update_user_meta with $metaKey
    }
}
```

Rationale: the base is a `meta_key` namespacing concern. It does not belong on the `Storage` contract or on the domain key.

### Decision 3: `TableStorage` uses `$key` directly as `group_key`

```php
// Drop StorageKeyParser dependency.
// $key already equals the group, e.g. "reaction".
WHERE group_key = $key
```

### Decision 4: `StorageKeyParser` deleted

No remaining callers once `TableStorage` stops parsing. Service registration in `Database\Module` removed.

## Risks / Trade-offs

- **Stored data shape unchanged**: `MetaStorage` still produces `_konomi_items.reaction` meta_keys; `TableStorage` still stores `reaction` in `group_key`. No data migration needed.
- **Backward compatibility**: `Storage::read/write` signature unchanged. Internal call sites update.
- **Future merge of Post/User logic**: explicitly out of scope; no design lock-in either way.
- **Test updates**: TableStorage tests currently pass `'_konomi_items.reaction'` as the key; they update to `'reaction'`. Functional MetaStorage tests (if any) keep asserting the composite meta_key written to the DB.

## Migration

None. Brand-new project, no production data, no archived state to convert.

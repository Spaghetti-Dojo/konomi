# Extract Shared Storage Module

## Summary

Extract the persistence layer duplicated across `Post` and `User` modules into a single shared `sources/Storage/` namespace. Replace opaque array shapes with a `Record` DTO, parameterize the table-side filter column with an `Axis` enum injected at construction, drop `MetaStorage` from production code (preserved as documented reference impl), flatten the vestigial `[[id, type]]` envelope on the Post side, and delete `RawDataAssert` in favor of boundary validation in `TableStorage`. Repositories stay module-specific and consume the shared `Storage` interface.

## Motivation

The current code has two near-identical persistence stacks:

- `Post\Storage` / `User\Storage` interfaces — identical except namespace
- `Post\TableStorage` / `User\TableStorage` — ~95% duplicated; differ only in filter column (`entity_id` vs `user_id`) and result-map keying
- `Post\MetaStorage` / `User\MetaStorage` — parallel; differ only in WP meta function (`get/update_post_meta` vs `get/update_user_meta`)
- `Post\StorageKey` / `User\StorageKey` — near-identical
- `Post\RawDataAssert` / `User\RawDataAssert` — diverge only because Post uses a nested `[[id, type]]` envelope that was never multi-element in practice

Both `TableStorage` impls write to the same `{prefix}konomi_interactions` table. The duplication is a copy-paste artifact of two domains evolving in parallel, not a real shape divergence. The Post nested envelope was introduced for symmetry with the User shape, never for actual multi-item-per-user storage. Removing it eliminates the "first-item-per-user persisted" quirk codified in the existing spec.

`MetaStorage` is no longer wired in any production container — both modules bind `Storage::class` to `TableStorage`. It exists only as legacy fallback kept "in case." A documented swap pattern via DI override serves users on hosts where custom tables are not viable, without dragging unused code through future refactors.

## Scope

### Added (`sources/Storage/`)

- `Storage` interface, DTO-typed: `read(int $id, string $groupKey): list<Record>`, `write(int $id, string $groupKey, list<Record> $records): bool`
- `Record` readonly DTO: `entityId`, `userId`, `entityType`
- `Axis` enum: `Entity | User`; `column(): string` returns `"entity_id"` or `"user_id"`
- `TableStorage` implementing `Storage`; constructor takes `InteractionsTable` + `Axis`; validates rows at boundary (skips malformed); preserves transactional write
- `StorageKey` (moved from per-module copies), no `base` field, `for(ItemGroup): string` sanitizes and returns the group value

### Changed

- `Post\Repository` consumes `Storage`; `serializeData` emits flat `list<Record>` (one per `userId`); registry-based uniqueness replaces dedupe quirk
- `User\Repository` consumes `Storage`; `serializeData` emits flat `list<Record>` (one per `entityId`)
- `Post\Module` binds `Storage::class` → `TableStorage::new($table, Axis::Entity)`
- `User\Module` binds `Storage::class` → `TableStorage::new($table, Axis::User)`

### Removed

- `sources/Post/Storage.php`, `sources/Post/TableStorage.php`, `sources/Post/MetaStorage.php`, `sources/Post/RawDataAssert.php`, `sources/Post/StorageKey.php`
- `sources/User/Storage.php`, `sources/User/TableStorage.php`, `sources/User/MetaStorage.php`, `sources/User/RawDataAssert.php`, `sources/User/StorageKey.php`
- Post nested `[[id, type]]` in-memory envelope and the "first-item-per-user persisted" requirement (vestigial; never written nested to disk)

### Documentation

- `docs/storage-drivers.md`: documents `Storage` interface contract, provides `MetaStorage` reference impl (post + user variants) and a container-override snippet for swapping the bound `Storage::class` driver per environment

## Non-goals

- No DB schema migration. The on-disk row format is already flat per row; the nested envelope existed only in PHP memory.
- No change to repository semantics (find/save/all signatures, action hooks, registry snapshot/rollback flow).
- No new domains or new `Axis` cases; enum has exactly the two cases needed today.
- No change to `Database\InteractionsTable` or `Database\SchemaManager`.
- No new storage backends shipped in core.

## Risks

- Type-narrowing the `Storage` interface from `array<mixed>` to `list<Record>` is a breaking change at the iface level. Mitigated by replacing all callers in this same change (only two: `Post\Repository`, `User\Repository`).
- Test fixtures using the nested `[[id, type]]` literal must be updated alongside. Mitigated by full test pass requirement (`composer cs`, `composer analysis`, `composer tests`) before merge.
- Registry snapshot/rollback invariant must be preserved through repo refactor. Mitigated by leaving `loadItems → snapshot → mutate → write → rollback-on-failure` flow structurally intact in both repos.

## Part of

Continuation of the Storage refactor sequence:
1. Extract Storage interface (archived 2026-05-08)
2. Custom table storage (archived 2026-05-08)
3. Simplify StorageKey (archived 2026-05-08)
4. Split TableStorage query/build (archived 2026-05-08)
5. **Extract shared Storage module** ← this change

---

## Final Agreement (with artifacts)

### Agreements

1. **New shared module** `sources/Storage/` — persistence primitives only.
2. **DTO contract**: `Record { entityId, userId, entityType }` readonly. Replaces all `array<mixed>` shapes.
3. **`groupKey`** = method param (scope), not on Record.
4. **`Axis` enum** (`Entity | User`) injected via `TableStorage` constructor. Repos axis-blind. `match` exhaustiveness enforces lockstep on new Axis cases.
5. **Repos stay module-specific.** `Post\Repository` + `User\Repository` keep intent, registry, save/find. Consume shared `Storage` iface.
6. **`StorageKey`** moves to shared as-is. No `base` field. Single class, ctor takes nothing, `for(ItemGroup)` returns sanitized group value. Prefix concerns are driver-local.
7. **`MetaStorage` dropped from code.** Documented in `docs/storage-drivers.md` as reference impl + container override how-to.
8. **Flatten Post in-memory shape** `[[id, type]]` → `[id, type]`. Vestigial nested envelope removed.
9. **Post quirk eliminated.** Map-keyed-by-userId guarantees uniqueness. No dedupe step.
10. **`RawDataAssert` deleted from both modules.** Validation collapses into `TableStorage` row mapping; malformed rows skipped at boundary. DTO list IS the validated contract.
11. **Registry snapshot/rollback preserved.** Repo-side `clone $registry` before mutation, `replace(snapshot)` on write failure. DB-side TX rollback in `TableStorage::write` is complementary. Refactor leaves this flow untouched — invariant to preserve.
12. **Per-module DI**: `Module::services()` binds `Storage::class` → `TableStorage::new($table, Axis::{Entity|User})`.
13. **Drop**: `Post\Storage`, `Post\TableStorage`, `Post\MetaStorage`, `Post\RawDataAssert`, `Post\StorageKey`, `User\Storage`, `User\TableStorage`, `User\MetaStorage`, `User\RawDataAssert`, `User\StorageKey`.

### Structure

```
sources/
├── Storage/                      ← NEW (shared)
│   ├── Storage.php               interface (DTO-typed)
│   ├── TableStorage.php          impl, axis-bound; validates rows at boundary
│   ├── Record.php                readonly DTO
│   ├── Axis.php                  enum: Entity | User; +column():string
│   └── StorageKey.php            sanitize group → string; no base
├── Post/
│   ├── Repository.php            consumes Storage; flat serialization
│   ├── Module.php                Storage::class → TableStorage(Axis::Entity)
│   ├── ItemRegistry.php
│   ├── ItemRegistryKey.php
│   └── Post.php
├── User/
│   ├── Repository.php            consumes Storage
│   ├── Module.php                Storage::class → TableStorage(Axis::User)
│   ├── Item.php / ItemFactory.php / ItemGroup.php / ItemRegistry.php / User.php / ...
│   └── (no Storage / TableStorage / MetaStorage / RawDataAssert / StorageKey)
└── Database/
    └── InteractionsTable.php     unchanged

docs/
└── storage-drivers.md            ← NEW: MetaStorage example + DI override snippet
```

### Component diagram

```
        ┌────────────────────────────────────────────────────┐
        │  sources/Storage/                                  │
        │                                                    │
        │   ┌──────────────┐   ┌──────────────┐              │
        │   │  Storage     │   │  Record      │              │
        │   │  «interface» │   │  «DTO»       │              │
        │   └──────▲───────┘   └──────────────┘              │
        │          │ implements                              │
        │   ┌──────┴────────┐   ┌──────────────┐             │
        │   │ TableStorage  │──▶│  Axis «enum» │             │
        │   │  axis-bound   │   │ Entity│User  │             │
        │   │  validates    │   └──────────────┘             │
        │   │  rows at      │   ┌──────────────┐             │
        │   │  boundary     │   │  StorageKey  │             │
        │   └──────┬────────┘   └──────────────┘             │
        └─────────┼─────────────────────▲────────────────┬───┘
                  │                     │                │
       ┌──────────┴──────────┐   ┌──────┴───────────┐    │
       │ Post\Module         │   │ User\Module      │    │
       │ Storage::class →    │   │ Storage::class → │    │
       │ TableStorage(       │   │ TableStorage(    │    │
       │   Axis::Entity)     │   │   Axis::User)    │    │
       └──────────┬──────────┘   └─────────┬────────┘    │
                  │                        │             │
       ┌──────────▼─────────┐   ┌──────────▼────────┐    │
       │ Post\Repository    │   │ User\Repository   │    │
       │ flat serialize     │   │ flat serialize    │    │
       │ snapshot+rollback  │   │ snapshot+rollback │    │
       └────────────────────┘   └───────────────────┘    │
                                                         │
                       ┌─────────────────────────────────┘
                       ▼
              ┌──────────────────┐
              │ InteractionsTable│ (wp_konomi_*)
              └──────────────────┘

        ┌──────────────────────────────────────────────┐
        │ docs/storage-drivers.md                      │
        │  - Storage interface contract                │
        │  - MetaStorage reference impl (post + user)  │
        │  - Container override snippet                │
        └──────────────────────────────────────────────┘
```

### UML class

```
┌────────────────────────────────────────────────┐
│ «interface» Storage                            │
├────────────────────────────────────────────────┤
│ +read(id:int, groupKey:string): list<Record>  │
│ +write(id:int, groupKey:string,                │
│        records: list<Record>): bool           │
└──────────────────▲─────────────────────────────┘
                   │ implements
        ┌──────────┴────────────┐
        │  TableStorage         │
        ├───────────────────────┤
        │ -table: InteractionsTable │
        │ -axis : Axis          │
        ├───────────────────────┤
        │ +new(table, axis)     │
        │ +read(id, groupKey)   │
        │ +write(id, groupKey,  │
        │        records)       │
        │ -mapRow(row): ?Record │  ← boundary validation
        └───────────────────────┘

        ┌────────────────────┐         ┌──────────────────────┐
        │ «enum» Axis        │         │ Record  «readonly»   │
        ├────────────────────┤         ├──────────────────────┤
        │ Entity             │         │ +entityId  : int     │
        │ User               │         │ +userId    : int     │
        ├────────────────────┤         │ +entityType: string  │
        │ +column():string   │         └──────────────────────┘
        └────────────────────┘

        ┌────────────────────────┐
        │ StorageKey             │
        ├────────────────────────┤
        │ +new(): self           │
        │ +for(group): string    │  sanitize + return
        └────────────────────────┘

        ┌──────────────────────────────┐
        │ Post\Repository              │
        ├──────────────────────────────┤
        │ -storage   : Storage         │  TableStorage(Axis::Entity)
        │ -storageKey: StorageKey      │
        │ -registry  : ItemRegistry    │
        │ -itemFactory: User\ItemFactory│
        ├──────────────────────────────┤
        │ +find(entityId, group)       │
        │ +save(item, user)            │
        │ -serialize(): list<Record>   │  flat; one Record per userId
        └──────────────────────────────┘

        ┌──────────────────────────────┐
        │ User\Repository              │
        ├──────────────────────────────┤
        │ -storage   : Storage         │  TableStorage(Axis::User)
        │ -storageKey: StorageKey      │
        │ -registry  : ItemRegistry    │
        │ -itemFactory: ItemFactory    │
        ├──────────────────────────────┤
        │ +find(user, id, group)       │
        │ +all(user, group)            │
        │ +save(user, item)            │
        │ -serialize(): list<Record>   │
        └──────────────────────────────┘
```

### Read flow

```
Post\Repository::find(postId, group)
        │
        │ key = storageKey.for(group)
        ▼
Storage::read(postId, key)              ◀── TableStorage(Axis::Entity)
        │
        ▼
TableStorage::read(id, key)
        │ col = axis.column()                  // "entity_id"
        │ SELECT entity_id, user_id, entity_type
        │ FROM %i WHERE {col}=%d AND group_key=%s
        │
        │ for each row:
        │   mapRow(row) → ?Record              // null = malformed, skip
        ▼
   list<Record>
        │
        ▼
Repository::loadItems
   for each Record r:
     item = factory.create(r.entityId, r.entityType, true, group)
     item.isValid() && registry.set(postId, r.userId, item)
        │
        ▼
   registry.all(postId, group)


User flow: same shape; TableStorage(Axis::User); col="user_id";
repo keys registry by r.entityId.
```

### Write flow

```
Post\Repository::save(item, user)
        │
        │ loadItems()                          // ensure hydrated
        │ snapshot = clone registry
        │ item.isActive()
        │   ? registry.set(postId, user.id, item)
        │   : registry.unset(postId, user.id, group)
        │
        │ records = serialize(postId, group):
        │   ┌─ for each registry.all(postId, group) as $userId => $item:
        │   │    records[] = new Record(item.id, $userId, item.type)
        │   └─ return records                  // list<Record>
        ▼
Storage::write(postId, key, records)           ◀── TableStorage(Axis::Entity)
        │
        ▼
TableStorage::write(id, key, records)
        │ col = axis.column()
        │ BEGIN TX
        │ DELETE FROM %i WHERE {col}=id AND group_key=key
        │   on fail → ROLLBACK; return false
        │ for each Record r:
        │   force r.{axisField} = id           // axis invariant
        │   INSERT (entity_id, user_id, entity_type, group_key)
        │   on fail → ROLLBACK; return false
        │ COMMIT
        ▼
   bool
        │
        ▼
Repository
   true  → fire `konomi.post.collection.save` (Post)
            or `konomi.user.repository.save-successfully` (User)
   false → registry.replace(snapshot)          ← memory rollback


User-side write: same shape; TableStorage(Axis::User);
serialize foreach registry.all(user, group) as $item → Record(item.id, user.id, item.type).
```

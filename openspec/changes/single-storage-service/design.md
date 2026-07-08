## Context

`docs/storage-drivers.md` documents driver swapping via re-registering each module's `Repository::class` binding — which contradicts Inpsyde Modularity's `ExtendingModule` mechanism for modifying already-registered services. That mechanism needs the driver to be a real container service, but today each `Repository` factory builds `TableStorage` inline.

Investigation established that there is a **single** `konomi_interactions` table (one `InteractionsTable` value object, one `SchemaManager`), and that the two storage instances differ **only** by `Storage\Axis`: the axis selects which column `$id` filters on (`entity_id` vs `user_id`) in the read `WHERE` / write `DELETE`, and which column a write forces to `$id`. Everything else is identical. The two instances exist purely because the `Storage` interface hides the axis at the call site, forcing it to be baked into the instance.

## Goals / Non-Goals

**Goals:**
- One shared `Storage` service in the container under `Storage::class`.
- Driver swap via a single `ExtendingModule` extension on `Storage::class`.
- Preserve all existing storage behaviour (validation, transactional write, axis invariant, `Record` mapping).

**Non-Goals:**
- Changing the `konomi_interactions` schema or `SchemaManager`.
- Changing `Repository` public behaviour or the `Record` / `StorageKey` contracts.
- Supporting genuinely different backends per axis via separate services.

## Decisions

- **Lift `Axis` into the method signature** (`read(Axis, id, key)`, `write(Axis, id, key, records)`) rather than keeping it on the instance. This is the minimal change that lets a single instance serve both domains. Alternative — two marker-interface services (`EntityStorage`/`UserStorage`) — was rejected: two of everything for an axis-only difference. Alternative — a `Criteria` value object — was rejected as YAGNI given the shared schema.
- **Repositories pass their axis inline** at their two call sites (each repository is inherently axis-specific by namespace). No new `Repository` constructor parameter; the injected dependency stays typed `Storage\Storage`.
- **New `Storage\Module` (ServiceModule)** owns the single `Storage::class` binding (`TableStorage::new($interactionsTable)`), registered in `konomi.php` after `Database\Module`. A dedicated module keeps storage as its own domain and gives consumers a stable id to extend. Alternative — registering in `Database\Module` — was rejected as conflating schema management with the storage driver.
- **`Axis` becomes public contract** (drops `@internal`) since custom drivers now receive it.

## Risks / Trade-offs

- [A consumer wants different backends for post vs user] → One service cannot bind two backends; the custom driver branches on the `Axis` argument it receives per call. Acceptable given identical schema/logic.
- [Interface signature change breaks any existing `Storage` implementers] → In-tree there is only `TableStorage`; the reference `MetaStorage` lives in docs and is updated in the same change. Flagged BREAKING in the proposal.

## Migration Plan

1. Change the `Storage` interface and `TableStorage` (axis-less ctor, axis-per-call).
2. Add `Storage\Module`; register in `konomi.php`.
3. Update `Post\Module` / `User\Module` to consume `Storage::class`; update repositories to pass their axis.
4. Update tests and `docs/storage-drivers.md`.

Rollback is a straight revert of the branch; no data migration is involved (schema unchanged).

## Open Questions

None.

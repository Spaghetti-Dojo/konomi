## Context

`Post\TableStorage` and `User\TableStorage` each implement two public methods (`read`, `write`) that braid together input validation, raw `$wpdb` SQL calls, and array coercion/payload shaping. The two classes are ~90% identical and total ~250 lines. Before any future cross-class refactor (shared storage module, bulk inserts), the internal structure of each class needs to clearly separate "talk to DB" from "shape data".

## Goals / Non-Goals

**Goals:**
- Inside each `TableStorage`, isolate `$wpdb` calls into private helpers and isolate array coercion/payload building into separate private helpers.
- Keep public method signatures, return shapes, transactional semantics, and validation behavior identical.
- Leave existing quirks intact (Post `write` only persists `$rawItems[0]`; Post `read` returns one row per user via the `[userId => [[…]]]` shape).

**Non-Goals:**
- No shared base class, trait, or new module across Post/User. Tracked for a later iteration.
- No bulk multi-VALUES INSERT. Tracked for a later iteration.
- No change to SQL statements, table layout, or service wiring.
- No new tests added as part of this change (test suite untouched).

## Decisions

### Split each method into two private helpers

`read()` becomes:
- `fetchRows(int $id, string $key): array` — only `$wpdb->prepare` + `$wpdb->get_results`. Returns the raw `ARRAY_A` rows (or `[]` on failure).
- `mapRows(array $rows): array` — pure coercion/shaping into the documented return type.

`write()` becomes:
- `buildPayloads(array $data): array` — pure coercion. Returns a list of associative payloads ready for `$wpdb->insert` (Post payload includes `user_id`; User payload omits it because `$id` is the user ID and is injected by the persist helper).
- `persist(int $id, string $key, array $payloads): bool` — owns the transaction, the DELETE, and the per-row INSERT loop.

Rationale: smallest change that lets the eye see "DB call" vs "data shape" at a glance, and that lets a future test target either side without `$wpdb` in scope. Alternative considered: extract a single private `query()` helper. Rejected — it would still leave coercion tangled inside `read`/`write`.

### Keep `$wpdb` access inline (no abstraction)

Helpers continue to call `global $wpdb` directly. No injected gateway, no `Database` wrapper. The proposal explicitly scopes shared abstractions to a later iteration.

### Preserve all existing quirks

- Post `buildPayloads` reads only `$rawItems[0]` per user, matching current semantics confirmed during exploration.
- Post `mapRows` writes `$result[$userId] = [[…]]`, the list-of-list shape kept for downstream consistency.
- All silent-skip branches (non-array row, non-scalar value, non-positive ID, empty type) remain.

### Public surface frozen

`read(int $id, string $key): array` and `write(int $id, string $key, array $data): bool` keep their exact signatures, return types, and error semantics. Guard checks (`$id <= 0 || $key === ''`) stay in the public methods so the helpers can assume valid inputs.

## Risks / Trade-offs

- [Behavioral drift while refactoring] → Reuse exact existing coercion logic in `buildPayloads`/`mapRows` line-for-line. No tests cover these paths today, so manual diff review is the safety net.
- [Helper proliferation] → Two extra private methods per class (4 per file). Acceptable given the clarity gain; no helpers shared across classes yet.
- [Post `[0]`-only quirk hidden behind helper] → Mitigation: short inline comment in `Post\TableStorage::buildPayloads` noting the behavior is intentional, so a future reader does not "fix" it.

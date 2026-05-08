## 1. Refactor Post\TableStorage

- [ ] 1.1 Extract `fetchRows(int $id, string $key): array` private helper from `read()` containing only the `$wpdb->prepare` + `$wpdb->get_results` call and the `is_array` guard, returning the raw `ARRAY_A` rows or `[]`
- [ ] 1.2 Extract `mapRows(array $rows): array` private helper from `read()` containing the row coercion loop that produces `[userId => [[entityId, entityType]]]`
- [ ] 1.3 Reduce `read()` body to: input guard, `$rows = $this->fetchRows($id, $key)`, `return $this->mapRows($rows)`
- [ ] 1.4 Extract `buildPayloads(array $data, string $key): array` private helper from `write()` that returns a list of associative payloads `['entity_id', 'user_id', 'entity_type', 'group_key']`, preserving the `$rawItems[0]`-only behavior and silent skips; add a one-line comment noting the `[0]`-only behavior is intentional
- [ ] 1.5 Extract `persist(int $id, string $key, array $payloads): bool` private helper from `write()` that owns `START TRANSACTION`, the prepared `DELETE`, the per-row `$wpdb->insert` loop, and `COMMIT`/`ROLLBACK` semantics
- [ ] 1.6 Reduce `write()` body to: input guard, `$payloads = $this->buildPayloads($data, $key)`, `return $this->persist($id, $key, $payloads)`

## 2. Refactor User\TableStorage

- [ ] 2.1 Extract `fetchRows(int $id, string $key): array` private helper from `read()` containing only the `$wpdb->prepare` + `$wpdb->get_results` call and the `is_array` guard
- [ ] 2.2 Extract `mapRows(array $rows): array` private helper from `read()` containing the row coercion loop that produces `[entityId => [entityId, entityType]]`
- [ ] 2.3 Reduce `read()` body to: input guard, `$rows = $this->fetchRows($id, $key)`, `return $this->mapRows($rows)`
- [ ] 2.4 Extract `buildPayloads(array $data): array` private helper from `write()` that returns a list of partial payloads `['entity_id', 'entity_type']`, preserving silent skips for non-array entries and zero/empty values
- [ ] 2.5 Extract `persist(int $id, string $key, array $payloads): bool` private helper from `write()` that owns `START TRANSACTION`, the prepared `DELETE`, the per-row `$wpdb->insert` loop (injecting `user_id => $id` and `group_key => $key` at insert time), and `COMMIT`/`ROLLBACK` semantics
- [ ] 2.6 Reduce `write()` body to: input guard, `$payloads = $this->buildPayloads($data)`, `return $this->persist($id, $key, $payloads)`

## 3. Verify

- [ ] 3.1 Run `composer run-script phpcs` (or equivalent) and resolve any new violations introduced by the refactor
- [ ] 3.2 Run `composer run-script phpstan` (or equivalent) and resolve any new type errors
- [ ] 3.3 Run the existing test suite and confirm no regressions
- [ ] 3.4 Manually diff `read()` and `write()` behavior against the pre-refactor version: inputs in → identical SQL executed, identical return values, identical transactional behavior
- [ ] 3.5 Run `openspec validate split-table-storage-query-build --strict` and confirm the change validates

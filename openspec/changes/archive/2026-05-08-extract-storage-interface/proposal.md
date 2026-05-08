# Extract Storage Interface

## Summary

Extract `Post\Storage` and `User\Storage` concrete classes into interfaces, renaming current implementations to `MetaStorage`. This creates the seam needed to swap storage backends (e.g., Dbal custom tables) without changing Repository code.

## Motivation

Konomi stores user reactions and bookmarks as serialized arrays in `wp_usermeta`/`wp_postmeta`. This blocks structured queries, search, and integration with WP Data Views. Moving to custom tables requires a storage abstraction — which doesn't exist yet since `Storage` classes are concrete with hardcoded `get_*_meta`/`update_*_meta` calls.

## Scope

- `Post\Storage` becomes an interface with `read(int, string): array` and `write(int, string, array): bool`
- `Post\MetaStorage` implements `Post\Storage` with current `get_post_meta`/`update_post_meta` logic
- `User\Storage` becomes an interface with same contract
- `User\MetaStorage` implements `User\Storage` with current `get_user_meta`/`update_user_meta` logic
- `Post\Module` and `User\Module` updated to wire `MetaStorage` where `Storage` was constructed
- All tests updated to reflect rename

## Non-goals

- No new storage backends (Dbal comes in a follow-up change)
- No signature changes — interface matches current `read`/`write` exactly
- No data migration

## Risks

- Low risk. Pure refactor, no behavior change. Existing tests validate.

## Part of

This is Step 1 of moving storage from meta to custom tables:
1. **Extract Storage interface** ← this change
2. Add Dbal dependency + table creation
3. Implement `DbalStorage`
4. (Later) Normalize to single table

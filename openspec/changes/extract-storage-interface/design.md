# Design: Extract Storage Interface

## Overview

Convert `Post\Storage` and `User\Storage` from concrete classes to interfaces. Current implementations become `MetaStorage`. No behavior change — pure structural refactor.

## Current State

```
Post\Storage (concrete)
├── read(int $id, string $key): array    → get_post_meta()
└── write(int $id, string $key, array $data): bool → update_post_meta()

User\Storage (concrete)
├── read(int $id, string $key): array    → get_user_meta()
└── write(int $id, string $key, array $data): bool → update_user_meta()
```

Both registered in their Module under `Storage::class` as service ID.

## Target State

```
Post\Storage (interface)
├── read(int $id, string $key): array
└── write(int $id, string $key, array $data): bool

Post\MetaStorage implements Post\Storage
├── read() → get_post_meta()
└── write() → update_post_meta()

User\Storage (interface)
├── read(int $id, string $key): array
└── write(int $id, string $key, array $data): bool

User\MetaStorage implements User\Storage
├── read() → get_user_meta()
└── write() → update_user_meta()
```

## Decisions

### Interface contract matches current signatures exactly

`read(int $id, string $key): array` and `write(int $id, string $key, array $data): bool`. No changes to what flows through. Repository code untouched.

### Service ID stays `Storage::class`

Modules register under `Storage::class` (the interface). Factory returns `MetaStorage::new()`. Consumers resolve `Storage::class` — decoupled from implementation.

```php
// Post\Module — before
Storage::class => static fn () => Storage::new(),

// Post\Module — after
Storage::class => static fn () => MetaStorage::new(),
```

### Static factory `new()` moves to MetaStorage

Interface has no constructor. `MetaStorage::new()` replaces `Storage::new()`.

### `@internal` stays on MetaStorage

Both current Storage classes are `@internal`. MetaStorage keeps that. Interface itself is not `@internal` — it's the public contract.

## Files Changed

| File | Change |
|------|--------|
| `sources/Post/Storage.php` | Concrete class → interface |
| `sources/Post/MetaStorage.php` | New file, current impl moved here |
| `sources/User/Storage.php` | Concrete class → interface |
| `sources/User/MetaStorage.php` | New file, current impl moved here |
| `sources/Post/Module.php` | `Storage::new()` → `MetaStorage::new()` |
| `sources/User/Module.php` | `Storage::new()` → `MetaStorage::new()` |
| `tests/unit/php/Post/StorageTest.php` | Rename to MetaStorageTest, update references |
| `tests/unit/php/User/StorageTest.php` | Rename to MetaStorageTest, update references |
| `tests/integration/php/PostRepositoryTest.php` | Update if references Storage directly |
| `tests/integration/php/UserRepositoryTest.php` | Update if references Storage directly |

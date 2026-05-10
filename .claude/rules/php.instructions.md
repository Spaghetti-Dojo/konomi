---
paths:
    - 'sources/**/*.scss'
---

## Interfaces

- Never suffix interfaces with `Interface`.
- Never prefix interfaces with `I`.
- Interfaces are the components we inject among classes via DI, no prefixes no suffixes.

## Classes

- When a Class `implements` an interface, always use a name explaining the scope of the
  implementation followed by the `interface` name. e.g. `MetaStorage implements Storage`.
- Classes are the opaque components we passed to constructors at configuration time, mostly as
  services.

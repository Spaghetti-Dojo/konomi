# Konomi

> A WordPress plugin to save posts as favorite using the new Interactivity API

[![Artifacts](https://github.com/spaghetti-dojo/konomi/actions/workflows/artifacts.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/artifacts.yml)
[![Client Linting](https://github.com/spaghetti-dojo/konomi/actions/workflows/client-linting.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/client-linting.yml)
[![Server Linting](https://github.com/spaghetti-dojo/konomi/actions/workflows/server-linting.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/server-linting.yml)
[![codecov](https://codecov.io/github/Spaghetti-Dojo/konomi/graph/badge.svg?token=P2JI2UD0RQ)](https://codecov.io/github/Spaghetti-Dojo/konomi)

## Architecture

Konomi is built on [Inpsyde Modularity](https://github.com/inpsyde/modularity): the plugin is a
`Package` composed of independent **modules**, each registering its services into a shared PSR‑11
container. Modules are wired in `konomi.php` in this order:

```
Configuration → Database → Storage → ApiFetch → Icons → User → Post → Rest → Blocks → Activation
```

All source lives under `sources/` (PSR‑4 root `SpaghettiDojo\Konomi\`). The interaction data flow is:
a user reacts/bookmarks a post in a **Blocks** front-end block → a **Rest** endpoint validates and
handles the request → the **User** domain saves the item and fires
`konomi.user.repository.save-successfully` → the **Post** domain mirrors the record on the entity
axis → both write through the shared **Storage** service into the `konomi_interactions`
**Database** table.

## Module Documentation

Developer guides — what you can build with each module and how. Each is task-oriented (recipes +
public API), not internal wiring reference.

| Module | What you can do |
| --- | --- |
| [Blocks](./docs/blocks.md) | Reaction, bookmark, and container blocks — render them, extend the Konomi block via Block Hooks, and build your own Context-driven block |
| [Rest](./docs/rest.md) | Register custom REST endpoints with a typed route builder, schemas, and a composable middleware chain |
| [User](./docs/user.md) | Read & write a user's reactions and bookmarks — get the current user, query items, save/toggle, and hook save events |
| [Post](./docs/post.md) | React to interaction saves and read a post's reaction/bookmark counts |
| [Storage](./docs/storage.md) | Swap the interactions storage backend for both the Post and User domains via one shared service |
| [Storage Drivers](./docs/storage-drivers.md) | Full worked example of writing and registering a custom storage driver |
| [Database](./docs/database.md) | The `konomi_interactions` table shape and adding your own tables via the activation pattern |
| [Activation](./docs/activation.md) | Run tasks on plugin activation, deactivation, and uninstall |
| [Configuration](./docs/configuration.md) | Read and extend the client-facing plugin configuration |
| [Icons](./docs/icons.md) | Render Konomi SVG icons server-side and enqueue the `konomi-icons` script |
| [ApiFetch](./docs/api-fetch.md) | Share the `@konomi/api-fetch` client module and run PHP on script-module import |

## License

This software is released under the ["GNU General Public License v2.0 or later"](./LICENSE) license.

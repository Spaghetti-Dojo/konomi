# Konomi

> A WordPress plugin to save posts as favorite using the new Interactivity API

[![Artifacts](https://github.com/spaghetti-dojo/konomi/actions/workflows/artifacts.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/artifacts.yml)
[![Client Linting](https://github.com/spaghetti-dojo/konomi/actions/workflows/client-linting.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/client-linting.yml)
[![Server Linting](https://github.com/spaghetti-dojo/konomi/actions/workflows/server-linting.yml/badge.svg)](https://github.com/spaghettidojo/konomi/actions/workflows/server-linting.yml)
[![codecov](https://codecov.io/github/Spaghetti-Dojo/konomi/graph/badge.svg?token=P2JI2UD0RQ)](https://codecov.io/github/Spaghetti-Dojo/konomi)

## Documentation

| Module                                   | What you can do                                                                                                                          |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| [Blocks](./docs/blocks.md)               | Reaction, bookmark, and container blocks — render them, extend the Konomi block via Block Hooks, and build your own Context-driven block |
| [Rest](./docs/rest.md)                   | Register custom REST endpoints with a typed route builder, schemas, and a composable middleware chain                                    |
| [User](./docs/user.md)                   | Read & write a user's reactions and bookmarks — get the current user, query items, save/toggle, and hook save events                     |
| [Post](./docs/post.md)                   | React to interaction saves and read a post's reaction/bookmark counts                                                                    |
| [Storage](./docs/storage.md)             | Swap the interactions storage backend for both the Post and User domains via one shared service                                          |
| [Extending](./docs/extending.md)         | Add services, replace a Konomi implementation, or hook the plugin lifecycle from your own code                                           |
| [Database](./docs/database.md)           | The `konomi_interactions` table shape and adding your own tables via the activation pattern                                              |
| [Activation](./docs/activation.md)       | Run tasks on plugin activation, deactivation, and uninstall                                                                              |
| [Configuration](./docs/configuration.md) | Read and extend the client-facing plugin configuration                                                                                   |
| [Icons](./docs/icons.md)                 | Render Konomi SVG icons server-side and enqueue the `konomi-icons` script                                                                |
| [ApiFetch](./docs/api-fetch.md)          | Import the shared `@konomi/api-fetch` client with Konomi's error-handling middleware pre-applied                                         |
| [Functions](./docs/functions.md)         | Run a WordPress filter once and self-remove — e.g. load a script module's dependency on first import                                     |

## License

This software is released under the ["GNU General Public License v2.0 or later"](./LICENSE) license.

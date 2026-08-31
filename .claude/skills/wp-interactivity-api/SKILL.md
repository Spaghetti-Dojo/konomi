---
name: wp-interactivity-api
description: "Use when you work on the Konomi front-end behavior: the data-wp-* directives in render.php and the partials, and the Interactivity stores under sources/Blocks/*/view."
---

# Konomi Interactivity API

## When to use

Use this skill when a click, a state change, or a REST call of the front-end blocks does not behave as expected, or
when you add such behavior.

## Project facts

- Every interactive block ships a store under `sources/Blocks/<Block>/view/`: `index.ts` (entry, calls `init()`),
  `store.ts` (the store), plus command files, for example `add-reaction-command.ts`.
- Store namespaces: `konomi` (container), `konomiReaction`, `konomiBookmark`, `konomiProfilePagination`.
- The stores are TypeScript, strictly typed. Each store file exports its `Context` type. The inner stores import the
  container `Context` type from `../../Konomi/view/store`.
- The server prints the context. `render.php` builds it with `Blocks\context(Context::class)` and prints it with
  `wp_interactivity_data_wp_context()`. The client reads it with `getContext<Context>('<namespace>')`.
- Markup comes from `render.php` and from the partials under `sources/Blocks/*/partials/`. The directives live there,
  not in a JavaScript template.
- The allowed directive list is `Blocks\kses()` in `sources/Blocks/api.php`. If you add a new `data-wp-*` attribute to
  markup that passes through `kses()`, add it to that list too.
- Stores are built as script modules into `build-module/` and declared in `block.json` as `viewScriptModule`.

## Procedure

### 1) Read the context contract first

The server `Context` class and the client `Context` type describe the same data. Change both, or the client reads
`undefined`.

Container context fields: `id`, `type`, `isUserLoggedIn`, `loginRequired`, `error`.

### 2) Write the action

- Use `store('<namespace>', { state, actions, callbacks })`.
- Use a generator function with `yield` for an asynchronous action, as `updateUserPreferences` does.
- Update the local context first, then persist, then revert on failure. `toggleStatus` plus `revertStatus` is the
  pattern.
- Check `outerContext.isUserLoggedIn` before a write. Set `loginRequired = true` instead of calling the REST endpoint.

### 3) Call the REST endpoints

Use `@konomi/api-fetch`, not `@wordpress/api-fetch`. It carries Konomi error handling. Read `docs/api-fetch.md`.
Keep the request in a command file beside the store.

### 4) Directives in markup

Use the directives the project already uses: `data-wp-on-async--click`, `data-wp-class--is-active`,
`data-wp-run--*`, `data-wp-bind--*`, `data-wp-text`. The pagination store of `konomi/user-profile` reads the server
state with `getServerState()` and wraps a handler with `withSyncEvent()`.

Background: `references/directives-quickref.md`, `references/server-side-rendering.md`.

## Verification

```bash
pnpm qa                    # tsc, eslint, stylelint, jest
composer test:functional   # rendered markup snapshots
```

The JavaScript unit tests live in `tests/unit/js`. To drive a real button in a browser, use the `run-konomi` skill.

## Failure modes / debugging

- The action does not run: the `data-wp-interactive` namespace in the markup does not match the `store()` namespace.
- The context is `undefined`: `getContext` uses the wrong namespace, or `render.php` does not print the context.
- The directive disappears from the output: `Blocks\kses()` does not allow the attribute.
- The store does not load: `build-module/` is stale. Run `pnpm build:modules`.

Background: `references/debugging.md`. It describes common WordPress practice. When it disagrees with `docs/`, the
`docs/` directory wins.

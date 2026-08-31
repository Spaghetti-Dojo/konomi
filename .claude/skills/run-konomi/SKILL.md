---
name: run-konomi
description: Run, build, screenshot and smoke-test the Konomi WordPress plugin. Use when asked to start the plugin, launch wp-env, screenshot a page, drive a reaction or bookmark button in a browser, or check that a change works in the real site instead of only in the test suite.
---

# Run Konomi

Konomi is a WordPress plugin. It has no server of its own: it runs inside a WordPress site that
`@wordpress/env` builds in Docker, at <http://localhost:8888> (admin `admin` / `password`).
The interesting surface is the front end — the reaction and bookmark buttons, driven by the
Interactivity API — so the agent path is a Playwright driver, `.claude/skills/run-konomi/driver.mjs`,
which starts the environment, logs in, clicks the buttons and reads the database back.

All paths and commands below are relative to the repository root.

## Prerequisites

- Docker Desktop, running. `wp-env` fails immediately without it.
- Node >= 20 (`node -v` reported v24.14.1 here).
- PHP >= 8.4 CLI and Composer, for the PHP test suites only. The site itself runs PHP 8.4 in the container.

## Setup

```bash
composer install                   # PHP deps; also copies the WorDBless drop-in
pnpm install                       # JS deps, including Playwright
pnpm exec playwright install chromium    # the browser the driver needs
pnpm build                         # ~5s; writes sources/**/dist and sources/**/build-module
```

`sources/**/dist/` and `sources/**/build-*/` are in `.gitignore`. A fresh clone renders blocks
without scripts or styles until `pnpm build` runs.

## Run: agent path

```bash
node .claude/skills/run-konomi/driver.mjs up      # wp-env start + activate the plugin
node .claude/skills/run-konomi/driver.mjs smoke   # the full user flow, exits non-zero on failure
```

`smoke` output on a healthy site:

```
post: http://localhost:8888/?p=1
logged out -> login modal open: true
before: count=0 active=false
after click: count=1
after reload: active=true count=1
konomi_interactions:
id	entity_id	user_id	entity_type	group_key
51	1	1	post	reaction
after second click: count=0
reaction rows after toggle off: 0
bookmark rows after click: 1
bookmark rows after toggle off: 0
konomi_interactions at the end:
(empty)
screenshots: .claude/skills/run-konomi/out
SMOKE OK
```

It walks five steps, and both storage paths:

1. A logged out visitor clicks the heart. The login dialog opens and nothing is stored.
2. An admin clicks the heart. The button turns active and the counter moves to 1.
3. A reload keeps that state, which proves the row reached `wp_konomi_interactions` — the write path,
   `User\Repository::save()` -> `Storage::write()`.
4. A second click turns the heart off. The counter returns to 0 and the row is gone — the delete path,
   `User\Repository::save()` -> `Storage::delete()`.
5. The bookmark button toggles on and off, so the second block and its own `group_key` are covered too.

Steps 4 and 5 read the row count straight from the database, not only from the DOM, so a button that
looks right while nothing was persisted still fails the run.

| Command | What it does |
| --- | --- |
| `status` | Containers, site HTTP code, plugin list. |
| `up` | `wp-env start`, activate `konomi`, then `status`. |
| `down` | `wp-env stop`. (Not exercised in this session, to leave the environment up.) |
| `smoke` | The three-step flow above. **Deletes every row of `wp_konomi_interactions` first.** |
| `shot <url> [file]` | Log in as admin, screenshot full page. Default file `out/shot.png`. |
| `dump <url> [file]` | Log in as admin, save the rendered HTML. Default file `out/page.html`. |
| `cli <args...>` | WP-CLI in the container, with the wp-env noise filtered out. |

Examples that ran here:

```bash
node .claude/skills/run-konomi/driver.mjs cli post list --post_type=page --fields=ID,post_title,post_name
node .claude/skills/run-konomi/driver.mjs shot "http://localhost:8888/?page_id=7" .claude/skills/run-konomi/out/profile.png
node .claude/skills/run-konomi/driver.mjs dump "http://localhost:8888/?p=1" .claude/skills/run-konomi/out/p1.html
```

Screenshots land in `.claude/skills/run-konomi/out/` (git-ignored): `logged-out-modal.png`,
`reaction-active.png`, `reaction-persisted.png`, `reaction-removed.png`, `bookmark-active.png`.
**Open the PNG and look at it.**
Page ID 7 is the Profile page, which lists what the current user saved — the best single view for
checking that a save round-trips.

Environment overrides: `KONOMI_SITE`, `KONOMI_USER`, `KONOMI_PASS`, `KONOMI_OUT`, and `KONOMI_HEADED=1`
to watch the browser instead of running headless.

## Run: human path

```bash
npx wp-env start
```

Then open <http://localhost:8888> and log in at `/wp-login.php` with `admin` / `password`. Nothing
needs to be authored: the reaction and bookmark buttons are injected into every post by Block Hooks,
so `?p=1` already shows them.

## Test

The PHP suites need no containers — they run against Brain Monkey and WorDBless (SQLite):

```bash
composer test:unit          # 114 tests, ~2s
composer test:integration   # 18 tests, ~1s
composer test:functional    # 38 tests, ~27s — the slow one
composer tests              # all three
composer analysis           # PHPStan, 92 files
composer cs                 # PHPCS, 140 files
pnpm test                   # Jest, 17 suites
```

## Direct invocation

Most changes here touch one PHP class, not the UI. Run a single test rather than the whole suite:

```bash
./vendor/bin/pest --testsuite=unit --filter='ItemRegistry'
```

To inspect the stored state without a browser:

```bash
node .claude/skills/run-konomi/driver.mjs cli db query "SELECT * FROM wp_konomi_interactions"
```

## Gotchas

- **Every `wp-env run` call prints an Xdebug warning** — `Xdebug: [Step Debug] Could not connect to
  debugging client. Tried: host.docker.internal:9003`. It is noise, not a failure. `driver.mjs` strips
  it, along with the `Starting '...'` / `Ran ...` chatter, so `cli` output can be parsed.
- **The login modal is a `<dialog>`, not a popover.** Checking `:popover-open` returns `false` even
  when the modal is visible. Read the `open` property instead: `el.open`.
- **The table columns are not what the domain language suggests.** It is
  `wp_konomi_interactions(id, entity_id, user_id, entity_type, group_key)` — no `post_id`, no `type`.
- **`smoke` truncates `wp_konomi_interactions`** so the counter assertion starts from zero. Do not run
  it against an environment whose data matters.
- **Query Monitor is installed and prints PHP deprecations** (`Constant E_STRICT is deprecated`) at the
  bottom of every front-end page under PHP 8.4. It comes from Query Monitor 3.11.0, not from Konomi.
- **Blocks appear without editing a post.** Block Hooks inject `konomi/konomi` into post templates, so
  a stock "Hello world!" post is enough to test.
- **`Post\Repository` never writes.** Only `User\Repository::save()` persists a row, so a save is one
  `REPLACE` or one `DELETE`. If a change seems to need a post-side write, read `docs/post.md` first.
- **A count already rendered in a request is not refreshed by a later save in that same request.** This
  is intended. Reload before asserting a new count.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `browserType.launch: Executable doesn't exist at .../chromium_headless_shell-1228/...` | `pnpm exec playwright install chromium`. A `pnpm install` that bumps Playwright invalidates the cached build. |
| `Cannot read a post URL from WP-CLI: ...` | The containers are down or the site is not ready. Run `driver.mjs up`, then `driver.mjs status` and check for `HTTP 200`. |
| Buttons render but nothing happens on click | The view modules are missing. Run `pnpm build`, then reload. |
| `smoke` fails with `counter is 0, expected 1` | The click did not reach the REST route. Re-run with `KONOMI_HEADED=1` and watch, or `dump` the page and check that `data-wp-interactive="konomiReaction"` is present. |
| wp-env commands hang or fail to connect | Docker Desktop is not running. Start it and retry. |

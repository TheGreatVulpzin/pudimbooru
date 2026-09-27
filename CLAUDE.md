# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Pudimbooru is a Portuguese-localized fork of [Shimmie2](https://github.com/shish/shimmie2), a PHP tag-based
image gallery/imageboard. It tracks upstream Shimmie2 closely; fork-specific changes are: PT-BR translation
(`PudimbooruLocale`), an SMTP email + password-reset system, a custom theme, a few UI fixes, and a
`counter8` extension. Upstream docs (install, Docker, upgrade, dev info, performance) live on the
[Shimmie2 wiki](https://github.com/shish/shimmie2/wiki) and still apply here.

## Commands

All commands run through Composer (PHP 8.4+, requires `composer install` first).

- `composer install` — install PHP deps (required before running anything, including `index.php`)
- `composer test` — run the full PHPUnit suite (`core/*Test.php` + `ext/*test.php`)
- `composer stan` (alias `composer analyse`) — run PHPStan (level 8, see `phpstan.dist.neon`)
- `composer format` — auto-fix code style with php-cs-fixer
- `composer format-ci` — check style without fixing (used in CI)
- `composer check` — runs format + analyse + test, i.e. everything CI checks
- `php vendor/bin/phpunit --filter TestClassName::testMethodName` — run a single test
- `php vendor/bin/phpunit core/ImageBoard/PostTest.php` — run a single test file
- `./.docker/run.php` — build and run the app via Docker (also `composer docker-run`)

Tests need a database DSN via `TEST_DSN` (defaults to `sqlite::memory:` per `tests/bootstrap.php`). CI runs
against Postgres; `.github/setup-db.sh {pgsql,mysql,sqlite}` shows how to stand one up.

The `main` branch requires format, stan, and test to all pass — the same three `composer` scripts above.

## Architecture

Shimmie2 is an **event-bus, extension-based** system — there's no MVC framework or router in the usual
sense. Reading `index.php` top-to-bottom is the fastest way to understand a request's lifecycle:

1. `vendor/autoload.php` loads; if `data/config/shimmie.conf.php` doesn't exist, `Installer::install()` runs
   and the request stops there (first-run setup).
2. `_load_ext_files()` includes every enabled extension's `main.php` (and dependents), registering their
   `Extension` subclasses.
3. `Ctx::$cache`, `Ctx::$database`, `Ctx::$config` (a `DatabaseConfig`) are constructed, in that dependency
   order.
4. `_load_theme_files()` loads the active theme's PHP, then `Ctx::$page` is built (a theme can override
   `Page`).
5. `Ctx::$event_bus = new EventBus()` — extensions self-register as listeners at construction time.
6. The rest of the request is just `send_event(...)` calls: `DatabaseUpgradeEvent`, `InitExtEvent`, then
   either a `CliGenEvent`/CLI app (for `PHP_SAPI === 'cli'`) or `UserLoginEvent` + `PageRequestEvent` for a
   normal HTTP request. Extensions listening for `PageRequestEvent` (via routing helpers) build up
   `Ctx::$page`, which is `display()`-ed at the end.

**`Ctx`** (`core/Extension/Ctx.php`) replaces the old global-variable soup (`$config`, `$database`, `$user`,
`$page`, `$cache`, `$event_bus`, `$tracer`). Always use `Ctx::$xxx`, never introduce new globals.

**Extensions** (`ext/<name>/`) are the unit of feature/plugin code. A typical extension directory has:
- `main.php` — the `Extension` subclass (or `AvatarExtension`/`DataHandlerExtension`/`FormatterExtension`
  base class) plus any `Event` subclasses it defines, and its event listener methods (`onPageRequest`,
  `onInitExt`, etc. — method name is matched by type to the `Event` subclass, dispatched via the event bus).
- `info.php` — `ExtensionInfo` metadata (name, category, visibility, docs) used by the extension manager.
- `theme.php` — a `Themelet` subclass with HTML-rendering helpers for this extension.
- `config.php`, `permissions.php`, `test.php` — optional: a `ConfigGroup`, permission constants, PHPUnit
  tests for the extension.

Extensions communicate almost exclusively via events (`core/Events/`), not direct calls — e.g. tagging a
post fires `TagSetEvent`, uploads fire `PostAdditionEvent`/`DataHandlerExtension` hooks, deletions fire
`PostDeletionEvent`. When adding behavior that reacts to something happening elsewhere, look for an
existing event before adding a direct dependency between extensions.

**Themes** (`themes/<name>/`) mirror the extension pattern: each extension can have a `Themelet` per theme,
selected at runtime by the active theme's name (`SysConfig`/`ThemeConfig`). `themes/pudimbooru` is this
fork's custom theme; `themes/default`, `themes/danbooru2`, `themes/futaba`, `themes/lite`, `themes/warm` are
upstream themes kept for compatibility/choice.

**`core/`** holds the framework itself, organized by concern (`Config`, `Database`, `Events`, `Extension`,
`ImageBoard`, `Media`, `Network`, `Page`, `Search`, `User`, `Util`, `Crud`, `Cache`, `Exceptions`, `Testing`).
Several of these have their own `README.md` worth reading before working in that area (e.g.
`core/Extension/README.md` on `Ctx` and the extension base classes).

**Autoloading**: `Shimmie2\` namespace maps to all of `core/*` subdirectories (flat, no per-subdir
namespace), per `composer.json`'s `psr-4` block; a few files (`microhtml.php`, `polyfills.php`, `util.php`)
are loaded unconditionally as global functions.

## Type system notes (PHPStan level 8)

`phpstan.dist.neon` defines project-specific type aliases used throughout the codebase — recognize these in
PHPDoc annotations:
- `tag-string` — a single tag (non-empty-string)
- `tag-array` — `list<tag-string>`, sorted/deduped/zero-indexed
- `tag-pattern-string` — a tag with an optional `*` wildcard
- `meta-tag-string` — a special search/tag-input token (e.g. `height>=1080`, `parent_id:123`)
- `search-term-string` / `search-term-array` — tag, pattern, or metatag (possibly `-`-prefixed to negate)

PHP arrays must have their key/value types documented in PHPDoc (`@param array<string, Foo> $x`) — this is
the most common PHPStan complaint on this codebase (see `.github/CONTRIBUTING.md` for the canned
explanation).

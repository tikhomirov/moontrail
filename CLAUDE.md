# CLAUDE.md — tikhomirov/moontrail

This file provides **project-specific** guidance for Claude Code when working in this repository.

## 1) Project overview

`tikhomirov/moon-trail` is a Laravel package that integrates `spatie/laravel-activitylog` into the MoonShine admin panel and adds:

- Diff viewer (field-level HTML diff)
- Versioning (`model_versions` snapshots)
- Transactional rollback (validated restore + audit trail)

**Package type:** Composer library

**Minimum PHP:** 8.2 (`composer.json`)

**Target Laravel:** 10.x–12.x (via `moonshine/moonshine ^4.8`)

**Core dependencies:**

- `moonshine/moonshine ^4.8`
- `spatie/laravel-activitylog ^4.7`

## 2) Commands (local)

All commands run from the package root.

```bash
# Full local CI pipeline (must pass before pushing)
composer ci              # Rector dry-run → Pint --test → PHPStan → Pest

# Auto-fix style + refactoring
composer ci:fix          # Rector + Pint

# Individual steps
composer test            # Pest
composer test:types      # PHPStan (level 9)
composer lint            # Pint (fix)
composer lint:test       # Pint --test
composer refactor        # Rector
composer refactor:test   # Rector --dry-run

# Run a single test file or filter
./vendor/bin/pest tests/Feature/MoonTrailResourceTest.php
./vendor/bin/pest --filter "it can track model changes"
```

Tests use **Orchestra Testbench** and the `testing` DB connection (SQLite in-memory).

## 3) Repository map (authoritative)

```
config/                  # publishable config
database/migrations/      # model_versions table
lang/                     # translations (en/ru)
resources/views/          # Blade components (diff/timeline/rollback)
routes/moontrail.php   # HTTP endpoints (diff/rollback)
src/                      # package code (contracts, services, UI)
tests/                    # Pest + Testbench
```

## 4) Architecture rules (must follow)

- **This is a package, not an app.** Keep the public surface small and stable.
- **All container bindings** are registered in `src/MoonTrailServiceProvider.php`.
- **Auto-discovery** is enabled via `composer.json` (`extra.laravel.providers`).
- **Config key:** `moontrail`.
  - Published file: `config/moontrail.php`
  - Source file: `config/moontrail.php`
- **Views namespace:** `moontrail::...`
- **Translations namespace:** `moontrail::ui...`
- **Routes:** loaded from `routes/moontrail.php`.
  - Prefix: `config('moonshine.prefix', 'admin') . '/moontrail'`
  - Names: `moonshine.moontrail.*` (do not rename)

### IoC bindings (swappable via container)

| Contract | Default implementation |
|---|---|
| `DiffRendererContract` | `Diff\\HtmlDiffRenderer` |
| `VersionManagerContract` | `Versioning\\VersionManager` |
| `RollbackStrategyContract` | `Versioning\\RollbackService` |
| `ActivityFormatterContract` | `Diff\\DefaultActivityFormatter` |

You may swap them in a host application service provider via `$this->app->bind(...)`.

### Core data flow

1. Model event → Spatie activity recorded (or package observer for auto-tracked models)
2. `VersionManagerContract::createVersion()` stores snapshot in `model_versions`
3. UI (MoonShine Resource / Components) displays timeline and lazy-loads diffs
4. `DiffComputer` computes changes → `DiffRendererContract` renders HTML
5. Rollback endpoint calls `RollbackStrategyContract::rollback()` (transactional)

## 5) Public API rules (backwards compatibility)

Treat the following as **public API**:

- Anything in `src/Contracts/*`
- Traits: `src/Traits/HasMoonTrail.php`, `src/Traits/WithMoonTrailTab.php`
- `src/MoonTrailServiceProvider.php`
- Config key and config structure under `moontrail.*`
- Route names and URL structure under `/moontrail/*`

### Key entry points (what users actually touch)

- `Traits\\HasMoonTrail` (model-side tracking)
- `Traits\\WithMoonTrailTab` (MoonShine Resource tab)
- `Resources\\MoonTrailResource` (global history)
- `Support\\MoonTrailMenuItem` (menu helper)

Rules:

- Do **not** introduce breaking changes without explicit instruction.
- Do **not** rename config keys, route names, contracts, or published asset tags.
- If a breaking change is unavoidable: document migration steps and follow SemVer.

### Protected areas (do not change without explicit approval)

- `src/Contracts/*` signatures
- Existing migrations in `database/migrations/*` (add a new migration instead)
- `composer.json` `require` version constraints
- Route names under `moonshine.moontrail.*`

## 6) Code standards (enforced)

- `declare(strict_types=1);` in every PHP file.
- Prefer `final` classes (remove only with a clear extension need).
- Prefer `readonly` where applicable.
- Use constructor property promotion.
- Add explicit return types everywhere.
- Avoid `mixed` unless there is no alternative (explain why).
- Follow the repo Pint rules (`pint.json`).

## 7) Laravel conventions for this package

- Inside core services/classes:
  - Prefer **dependency injection**.
  - Prefer contracts, not concrete classes.
  - Avoid facades and `app()`.
- Using `config()` is acceptable in **entry points** (service provider, route file, controllers), not deep inside pure services.
- Do not modify Spatie-owned schema:
  - **Never alter** `activity_log` table migrations.
  - Store package data in `model_versions`.
- If you need to change database structure:
  - Create a **new migration** (never edit an existing published migration).

## 7.1) Toolchain notes (Pint / Rector / PHPStan)

- Pint rules are in `pint.json` and are expected to pass in CI.
- Rector is configured in `rector.php` (PHP 8.2 sets + quality/type/privatization).
- PHPStan is configured in `phpstan.neon.dist` and runs at **level 9**.

Common PHPStan pitfalls in this repo:

- `getKey()` returns `mixed` — cast before interpolation
- `parse_url(..., PHP_URL_QUERY)` returns `string|false|null` — check before `parse_str`
- `array_filter` callbacks must accept `mixed`, not a concrete type
- `Collection::all()` returns `array<int, T>` not `list<T>` — use `array_values()` if a list is required

## 8) Testing rules

- Use **Pest**.
- All new features and bug fixes must include tests.
- Prefer Unit tests for pure logic (`DiffComputer`, renderers) and Feature tests for HTTP/UI wiring.
- No external HTTP requests.
- Avoid `sleep()`.

Test structure:

- `tests/Unit/` — pure logic (`DiffComputer`, renderers, versioning/rollback)
- `tests/Feature/` — HTTP endpoints and integration wiring (routes/controllers/MoonShine)

## 9) Rules for generating code in this repo

When implementing changes:

- Make small, reviewable diffs.
- Never generate placeholders like “TODO” for required logic.
- Always include all necessary imports.
- Keep formatting compatible with Pint.
- Update/extend tests for any behavior change.
- If requirements are ambiguous, ask clarifying questions before editing contracts.

## 10) Anti-patterns (do not do this)

### 10.1 Facades or container lookups in core classes

Bad:

```php
Cache::get($key);
app(VersionManager::class);
```

Good:

```php
public function __construct(
    private VersionManagerContract $versions,
) {
}
```

### 10.2 Editing existing migrations

Bad:

- Modifying `database/migrations/2024_01_01_000001_create_model_versions_table.php`

Good:

- Add a new migration and keep the old one immutable.

### 10.3 Breaking changes to contracts without migration path

Bad:

- Renaming methods in `src/Contracts/*`

Good:

- Add a new method, keep the old one (or bump major + document upgrade).

## 11) Before finishing (checklist)

- [ ] `composer ci` passes
- [ ] New/changed behavior has tests
- [ ] No breaking changes to `src/Contracts/*`, config key, route names (unless explicitly approved)
- [ ] No edits to existing migrations (new migration instead)
- [ ] No debug output / dumped code
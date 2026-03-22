# AGENTS.md — tikhomirov/moontrail

> Guidance for AI agents (Copilot, Claude Code, Cursor, etc.) working on this repository.
> Read this file fully before making any changes.

---


## Вход

Email:    test@example.com
Password: password

## 1. Package Purpose and Architecture

`tikhomirov/moontrail` is a Laravel package that integrates
[spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) with the
[MoonShine](https://moonshine-laravel.com) admin panel. It adds three capabilities that
the base Spatie package does not provide out of the box:

| Capability | Description |
|---|---|
| **Diff viewer** | Side-by-side HTML diff of field-level changes between two activity records |
| **Versioning** | Numbered full-attribute snapshots stored in `model_versions`, linked to `activity_log` rows |
| **Rollback** | Transactional, validated restore of any prior version with its own audit trail |

### Dependency chain

```
Your Laravel App
 └── moonshine/moonshine ^4.8          (admin panel UI)
      └── tikhomirov/moontrail (this package)
           └── spatie/laravel-activitylog ^4.7  (audit log engine)
```

The package **wraps** Spatie — it does not replace it. Spatie captures all model events
via its `LogsActivity` trait/observer. This package adds a versioning layer on top and
exposes MoonShine UI components to browse, diff, and roll back that history.

### Service bindings (IoC)

The `MoonTrailServiceProvider` binds contracts to concrete implementations that
can be swapped in the host application:

```
ActivityLoggerContract       → ActivityLog\SpatieActivityLogger (default) or native database logger
DiffRendererContract       → Diff\HtmlDiffRenderer
VersionManagerContract     → Versioning\VersionManager
RollbackStrategyContract   → Versioning\RollbackService
ActivityFormatterContract  → Diff\DefaultActivityFormatter
```

---

## 2. Project Structure

```
moontrail/
├── config/
│   └── moontrail.php                 # Package config (versioning, rollback, UI, auto-track, installer)
│
├── database/
│   └── migrations/
│       └── 2024_01_01_000001_create_model_versions_table.php
│                                     # Polymorphic snapshot store
│
├── lang/
│   ├── en/ui.php                     # English UI strings
│   └── ru/ui.php                     # Russian UI strings
│
├── resources/views/components/
│   ├── activity-tab.blade.php        # Tab wrapper (placeholder)
│   ├── activity-timeline.blade.php   # Chronological activity list
│   ├── diff-viewer.blade.php         # Side-by-side field diff
│   ├── version-badge.blade.php       # Version number badge widget
│   └── rollback-confirm.blade.php    # Confirmation modal before rollback
│
├── resources/views/pages/
│   ├── index-filters.blade.php       # Inline filter form for global history index
│   ├── filter-chips.blade.php        # Active filter chips bar
│   ├── index-kpi.blade.php           # KPI cards grid (total / created / updated / deleted / other)
│   ├── detail-general.blade.php      # Activity detail — General section
│   ├── detail-relations.blade.php    # Activity detail — Relations section
│   ├── detail-changes.blade.php      # Activity detail — Changes/diff section
│   └── detail-history.blade.php      # Activity detail — Version history section
│
├── routes/
│   └── moontrail.php                 # Rollback POST + AJAX endpoints
│
├── src/
│   ├── MoonTrailServiceProvider.php # Boot: migrations, views, lang, routes, publishes
│   ├── MoonTrailObserver.php         # Observer for auto-tracked models
│   │
│   ├── Components/
│   │   ├── ActivityTimeline.php      # MoonShine component: timeline of log entries
│   │   ├── DiffViewer.php           # MoonShine component: HTML diff
│   │   └── ActivityTab.php          # Embeds ActivityTimeline as a Tab in any Resource
│   │
│   ├── Console/Commands/
│   │   ├── InstallMoonTrailCommand.php  # Interactive installer
│   │   └── PruneMoonTrailCommand.php     # Prune old versions/activities
│   │
│   ├── Contracts/
│   │   ├── DiffRendererContract.php      # render(FieldChange[]): string
│   │   ├── VersionManagerContract.php    # createVersion(), getVersions(), getVersion()
│   │   ├── RollbackStrategyContract.php  # rollback(Model, int, ?array): Model
│   │   └── ActivityFormatterContract.php # Human-readable event label / description
│   │
│   ├── Diff/
│   │   ├── DefaultActivityFormatter.php # Default implementation of ActivityFormatterContract
│   │   ├── DiffComputer.php         # Computes FieldChange[] from two attribute arrays
│   │   ├── FieldChange.php          # DTO: field, before, after, changeType
│   │   └── HtmlDiffRenderer.php     # Renders FieldChange[] → HTML
│   │
│   ├── Enums/
│   │   ├── ActivityEvent.php        # created | updated | deleted | restored | rolled_back
│   │   └── ChangeType.php           # added | removed | modified
│   │
│   ├── Events/
│   │   ├── ModelRolledBack.php      # Fired after successful rollback
│   │   ├── ModelRollingBack.php     # Fired before rollback (cancellable)
│   │   └── VersionCreated.php       # Fired when a new version is created
│   │
│   ├── Exceptions/
│   │   ├── ModelVersionNotFoundException.php
│   │   ├── RollbackConflictException.php
│   │   ├── RollbackDeniedException.php
│   │   └── VersionLimitExceededException.php
│   │
│   ├── Http/Controllers/
│   │   ├── RollbackController.php   # POST /moontrail/rollback
│   │   └── ActivityController.php   # GET  /moontrail/{id}/diff (AJAX)
│   │
│   ├── Installer/
│   │   ├── ConfigUpdater.php        # Updates host app config for auto-track
│   │   ├── ModelScanner.php         # Scans app for models to track
│   │   ├── ResourcePatcher.php      # Patches MoonShine resources with WithMoonTrailTab
│   │   └── ResourceScanner.php      # Scans app for MoonShine resources
│   │
│   ├── Models/
│   │   └── ModelVersion.php         # Eloquent model for model_versions table
│   │
│   ├── Pages/
│   │   ├── MoonTrailPage.php        # Base page (menu item wrapper)
│   │   ├── MoonTrailIndexPage.php   # Global history view with filters
│   │   └── MoonTrailDetailPage.php  # Activity detail with diff viewer
│   │
│   ├── Resources/
│   │   └── MoonTrailResource.php  # MoonShine Resource for browsing activity_log
│   │
│   ├── Support/
│   │   ├── ActivityDetailPresenter.php  # Rendering helpers for detail sections (event badges, entity links, section data)
│   │   ├── ActivityLogFilterData.php    # Normalised filter values read from request (direct + nested params)
│   │   ├── ActivityLogFilterOptions.php # Distinct option values for filter UI select boxes
│   │   ├── ActivityLogQuery.php         # Applies ActivityLogFilterData to an Eloquent builder
│   │   ├── ActivityTimelineDataBuilder.php # Computes changesCount for ActivityTimeline
│   │   ├── MoonTrailMenuItem.php    # Dynamic menu item with model sub-items
│   │   └── SvgIcons.php             # SVG icon rendering helpers
│   │
│   ├── Traits/
│   │   ├── HasMoonTrail.php        # Add to Eloquent models: enables tracking + versioning
│   │   └── WithMoonTrailTab.php      # Add to MoonShine Resources: injects ActivityTab
│   │
│   └── Versioning/
│       ├── VersionManager.php       # createVersion(), listVersions()
│       └── RollbackService.php      # Transactional rollback with optional validation
│
└── tests/
    ├── TestCase.php                  # Orchestra\Testbench base; boots Spatie + MoonShine + this pkg
    ├── Pest.php                      # Pest configuration
    ├── database/                     # In-memory SQLite for tests
    ├── Unit/
    │   ├── ConfigUpdaterTest.php
    │   ├── DiffComputerTest.php
    │   ├── HtmlDiffRendererTest.php
    │   ├── ModelScannerTest.php
    │   ├── ObserverSuspendTest.php
    │   ├── ResourcePatcherTest.php
    │   ├── ResourceScannerTest.php
    │   ├── RollbackServiceTest.php
    │   ├── SvgIconsTest.php
    │   └── VersionManagerTest.php
    ├── Feature/
    │   ├── ActivityControllerTest.php
    │   ├── ActivityLogIndexPageTest.php
    │   ├── ActivityLogMenuItemTest.php
    │   ├── ActivityLogResourceTest.php
    │   ├── ActivityTimelineComponentTest.php
    │   ├── ActivityTrackingTest.php
    │   ├── AutoTrackActivityTest.php
    │   ├── DiffViewerComponentTest.php
    │   ├── InstallCommandTest.php
    │   ├── PruneCommandTest.php
    │   ├── RebrandingAdvancedAuditLogTest.php
    │   └── RollbackControllerTest.php
    └── Fixtures/
        ├── TestPost.php              # Basic test model with HasMoonTrail
        ├── TestAutoTrackedPost.php    # Model for auto-track tests
        ├── TestAdmin.php             # Test admin user
        ├── TestPostResource.php      # Basic MoonShine resource
        ├── AllowedRollbackPost.php   # Model allowing rollback
        ├── DeniedRollbackPost.php    # Model denying rollback
        ├── AllowPolicyRollbackPost.php
        ├── DenyPolicyRollbackPost.php
        ├── AllowPolicyRollbackPolicy.php
        ├── DenyPolicyRollbackPolicy.php
        ├── AllowRollbackPolicy.php
        ├── DenyRollbackPolicy.php
        └── AllowedRollbackPostResource.php
```

### Key file roles at a glance

| File | What it owns |
|---|---|
| `MoonTrailServiceProvider` | All boot-time wiring: migrations, routes, publishes, IoC bindings |
| `MoonTrailObserver` | Observer for auto-tracked models; logs activity and creates versions |
| `HasMoonTrail` (trait) | Model-side: hooks into Spatie observers, triggers `VersionManager` |
| `WithMoonTrailTab` (trait) | Resource-side: injects the diff/history tab into MoonShine CRUD pages |
| `VersionManager` | Reads & writes `model_versions`; enforces `max_versions` limit |
| `RollbackService` | Wraps `Model::fill()->save()` in a DB transaction, fires events |
| `RollbackAuthorizationResolver` | Single source of truth for rollback authorization; checks guard, policy, model method |
| `RollbackConflictClassifier` | Maps exceptions to `RollbackConflictException` with semantic `reason` |
| `DiffComputer` | Pure function: `compute(array $before, array $after): FieldChange[]` |
| `HtmlDiffRenderer` | Converts `FieldChange[]` to presentational HTML; swappable via contract |
| `DefaultActivityFormatter` | Human-readable event labels; swappable via contract |
| `ModelVersion` | Single Eloquent model for the `model_versions` table (polymorphic) |
| `InstallMoonTrailCommand` | Interactive installer: scans models, patches resources, publishes config |
| `PruneMoonTrailCommand` | CLI tool to prune old versions and activity log entries |
| `MoonTrailMenuItem` | Dynamic menu item with sub-items for each tracked model |

---

## 3. Development Environment Setup

### Prerequisites

- PHP 8.2+
- Composer 2.x
- A companion Laravel app for manual testing (see below)

### Install package dependencies

```bash
# In the package root:
composer install
```

### Local integration via path repository

The package is tested in a real MoonShine app via Composer path repository:

```jsonc
// /path/to/your-moonshine-app/composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../moontrail",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "tikhomirov/moontrail": "@dev"
    }
}
```

```bash
# In the host app:
composer update tikhomirov/moontrail --with-all-dependencies
php artisan optimize:clear
php artisan migrate
php artisan serve
```

### After editing package source

No reinstall needed because of the symlink. Just clear caches in the host app:

```bash
composer dump-autoload && php artisan optimize:clear
```

### Publishing package assets (optional, for customisation)

```bash
php artisan vendor:publish --tag=moontrail-config
php artisan vendor:publish --tag=moontrail-views
php artisan vendor:publish --tag=moontrail-lang
```

---

## 4. Testing, Linting, and CI Commands

All commands run from the **package root** (`moontrail/`).

### Run the full test suite

```bash
./vendor/bin/pest
```

### Run tests with coverage (requires Xdebug or PCOV)

```bash
./vendor/bin/pest --coverage --min=80
```

### Run a single test file

```bash
./vendor/bin/pest tests/Unit/DiffComputerTest.php
```

### Run a specific test by description

```bash
./vendor/bin/pest --filter="it computes a field change"
```

### Static analysis (PHPStan)

```bash
./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
```

The `phpstan.neon.dist` file is the canonical config. Do not create a local override —
add project-specific rules to `phpstan.neon.dist` and commit it.

### Code style (Laravel Pint)

Pint is configured in `pint.json` and included in `require-dev`.

```bash
composer lint              # fix (via ./vendor/bin/pint)
composer lint:test         # lint only (non-zero exit on violations)
```

### Rector (automated refactoring)

Rector is configured in `rector.php`.

```bash
composer refactor          # apply transformations
composer refactor:test     # dry-run (show proposed changes)
```

### Full CI check (run before every PR)

```bash
composer ci                # runs: rector:test, lint:test, test:types, test
```

Or manually:
```bash
./vendor/bin/pest && ./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=512M
```

---

## 5. Code Style Conventions

### Standards

- **PSR-12** for all PHP files.
- **`declare(strict_types=1)`** at the top of every `.php` file — no exceptions.
- **`final`** classes by default. Remove `final` only when inheritance is intentional and
  documented.
- **Named arguments** are preferred over positional when a function has ≥ 3 parameters.

### Laravel / MoonShine conventions

- Service classes in `src/Versioning/` and `src/Diff/` are plain PHP — no framework
  magic, easy to unit-test without the full app.
- Contracts live in `src/Contracts/`. Every public service must implement a contract.
  Never type-hint concrete classes in constructors — use the contract.
- Use Eloquent **scopes** and **relations** in `ModelVersion`; avoid raw `DB::` calls.
- Route actions go through dedicated controller classes — no closures in route files.
- Blade views use the `moontrail::` view namespace prefix.
- Translation keys use the `moontrail::ui.` prefix.

### Naming

| Entity | Convention | Example |
|---|---|---|
| Classes | PascalCase | `DiffComputer` |
| Methods | camelCase | `createVersion()` |
| Database tables | snake_case | `model_versions` |
| Config keys | snake_case | `versioning.max_versions` |
| Blade components | kebab-case | `activity-timeline.blade.php` |
| Test files | `{Subject}Test.php` | `DiffComputerTest.php` |
| Test descriptions | `it ...` (Pest style) | `it computes a diff for updated fields` |

### Docblocks

Add PHPDoc only when the type cannot be expressed by PHP's type system (e.g. complex
generics, mixed array shapes). Do not add `@param`/`@return` that merely repeat the
signature.

---

## 6. Key Domain Concepts

### 6.1 Activity logging

`spatie/laravel-activitylog` writes a row to `activity_log` for every model `created`,
`updated`, `deleted`, and `restored` event. The `properties` JSON column stores the
`attributes` (new state) and `old` (previous state) arrays.

**To enable tracking on a model**, add the `HasMoonTrail` trait:

```php
use MoonShine\MoonTrail\Traits\HasMoonTrail;

class Post extends Model
{
    use HasMoonTrail;

    // Optionally limit which fields are tracked:
    protected static array $logAttributes = ['title', 'body', 'status'];
    protected static bool $logOnlyDirty = true;
}
```

The trait wraps Spatie's `LogsActivity` (when `activity_logger = 'spatie'` or `'auto'` with Spatie installed) 
and additionally calls `VersionManager` after each write event.

**Alternative without Spatie dependency:** Use `HasMoonTrailVersioning` trait instead of `HasMoonTrail` 
if you don't need Spatie's `LogsActivity` trait. This enables versioning and rollback with the native 
database logger (or any custom logger bound to `ActivityLoggerContract`).

```php
use MoonShine\MoonTrail\Traits\HasMoonTrailVersioning;

class Post extends Model
{
    use HasMoonTrailVersioning;
}
```

### 6.2 Versioning and activity logging backends

MoonTrail supports multiple activity logging backends via the `activity_logger` config option:

| Driver | Description |
|---|---|
| `auto` (default) | Uses Spatie if installed, otherwise falls back to native database logger |
| `spatie` | Uses `spatie/laravel-activitylog` (must be installed via Composer) |
| `database` | Uses MoonTrail's native `moontrail_activity_log` table — no Spatie dependency |
| `none` | Disables activity logging entirely (versioning still works) |
| `custom` | Resolves `ActivityLoggerContract` from the container — bind your own implementation |

Every time a tracked model is saved, `VersionManager::createVersion()` writes a row to
`model_versions` containing:

- **`versionable_type` / `versionable_id`** — polymorphic relation to the model
- **`version`** — auto-incrementing integer scoped per model instance
- **`snapshot`** — full JSON dump of `$model->getAttributes()` at that moment
- **`activity_id`** — FK to the corresponding `activity_log` row (nullable for initial versions)
- **`event`** — `created`, `updated`, `deleted`, `restored`, or `rolled_back`
- **`is_rollback`** — `true` when this version was created by a rollback action
- **`rollback_to_version`** — which version number was restored (set when `is_rollback = true`)

Older versions are pruned automatically when `versioning.max_versions` is exceeded
(strategy: `delete_oldest` by default, configurable to `prevent`).

### 6.3 Diff computation

`DiffComputer::compute(array $before, array $after): FieldChange[]` performs a
field-level comparison of two attribute arrays. It returns a list of `FieldChange` DTOs,
each carrying:

- `field` — attribute name
- `before` — value before the change
- `after` — value after the change
- `changeType` — `ChangeType::added`, `::removed`, or `::modified`

Fields listed in `ui.hidden_fields` (config) are excluded from the diff output.

`HtmlDiffRenderer` converts the `FieldChange[]` array into an HTML table with highlighted
added/removed tokens, suitable for embedding in a Blade view.

### 6.4 Rollback mechanism

`RollbackService::rollback(Model $model, int $targetVersion, ?array $rules = null): Model`

#### Authorization matrix (secure-by-default)

`RollbackAuthorizationResolver` uses a matrix-based approach to determine rollback eligibility:

1. **MoonShine guard not authenticated** → throw `AuthorizationException`
2. **Laravel policy with `rollback` method exists** → delegate to policy (via `Gate::authorize()`)
3. **No policy registered** → check model's `isRollbackAllowed()` method (if exists)
4. **Model doesn't define `isRollbackAllowed()`** → throw `RollbackDeniedException`

This ensures rollback is **denied by default** unless explicitly allowed by a policy or the model.

Model example:
```php
class Post extends Model
{
    public function isRollbackAllowed(): bool
    {
        return auth()->guard('moonshine')->user()?->isSuperAdmin() ?? false;
    }
}
```

Policy example:
```php
class PostPolicy
{
    public function rollback(User $user, Post $post): bool
    {
        return $user->isSuperAdmin();
    }
}
```

#### Rollback execution

1. Fetch the `ModelVersion` with the requested `$targetVersion` for this model.
2. **Validate** the `snapshot` attributes against `$rules` (or the resource's `rules()`
   method if `rollback.validate = true` in config). Throws `ValidationException` on failure.
3. Open a **database transaction**.
4. `$model->fill($snapshot)->save()` — restores all attributes from the snapshot.
5. Call `VersionManager::createVersion()` with `event = 'rolled_back'` and
   `is_rollback = true`, `rollback_to_version = $targetVersion`.
6. If `rollback.log_rollback_event = true`, Spatie will also write an `updated` row in
   `activity_log` automatically (triggered by the save in step 4).
7. Commit the transaction.
8. Return the freshly reloaded model.

#### Error handling

Any exception during the transaction rolls it back. Original data is never touched on failure.

- `RollbackConflictException` is thrown with a semantic `reason` property (mapped by
  `RollbackConflictClassifier`) to distinguish between conflict types:
  - `db_constraint` — unique/foreign-key/not-null violation (SQLSTATE 23xxx)
  - `model_missing` — record deleted or doesn't exist
  - `unknown` — other database or unexpected errors
- `RollbackCancelledException` is thrown if a listener calls `halt()` on the
  `ModelRollingBack` event.

### 6.5 MoonShine UI integration

**Option A — Tab inside a Resource** (most common):

```php
use MoonShine\MoonTrail\Traits\WithMoonTrailTab;

class PostResource extends ModelResource
{
    use WithMoonTrailTab;
}
```

The trait appends an `ActivityTab` to the resource's detail/edit form automatically.

**Option B — Standalone global history page**:

Register `MoonTrailPage` in MoonShine's menu. It shows all activity across all models
with filtering by causer, event type, and date range.

**Option C — Inline component**:

```php
ActivityTimeline::make($post)->render();
```

### 6.6 Auto-tracking (without trait)

For third-party models or when you cannot modify the model class, use auto-tracking:

```php
// config/moontrail.php
'auto_track_models' => [
    \MoonShine\Laravel\Models\MoonshineUser::class,
],
```

The `MoonTrailObserver` will be automatically attached to these models at boot time.
Configure logging behavior:

```php
'auto_track' => [
    'log_to_activity' => true,  // Also write to Spatie activity_log table
],
```

**Silent failures:** Set `silent_failures` to `true` to suppress observer exceptions silently (default: `false`, exceptions are reported via `report()`).

### 6.7 Installer (CLI)

Run the interactive installer to set up tracking in your app:

```bash
php artisan moontrail:install
```

The installer:
1. Scans `app/Models/` for candidate models
2. Offers to add `HasMoonTrail` trait or configure auto-tracking
3. Scans `app/MoonShine/Resources/` for resources to patch with `WithMoonTrailTab`
4. Publishes config, views, and migrations (optional)
5. Runs migrations (optional)

Options:
- `--safe` / `--force` — control whether to skip already-configured files
- `--non-interactive` — run with defaults (useful for CI)

### 6.8 Events

The package fires Laravel events for key actions:

| Event | When fired | Properties |
|---|---|---|
| `ModelRollingBack` | Before rollback | `$model`, `$targetVersion` (cancellable via `halt()`) |
| `ModelRolledBack` | After successful rollback | `$model`, `$targetVersion`, `$newVersion` |
| `VersionCreated` | When a version is saved | `$model`, `$version`, `$event` |

Listen to these in your app for custom workflows (e.g., notifications, audit).

### 6.9 Exceptions

Custom exceptions for error handling:

| Exception | Thrown when | Details |
|---|---|---|
| `ModelVersionNotFoundException` | Target version doesn't exist | — |
| `RollbackConflictException` | Data has changed since version was created | Includes semantic `reason` property: `db_constraint` (unique/FK violation), `model_missing` (record deleted), or `unknown` |
| `RollbackCancelledException` | A listener halts the `ModelRollingBack` event | Indicates user/custom code cancelled the rollback |
| `RollbackDeniedException` | Authorization check fails (no policy, model doesn't allow it) | — |
| `VersionLimitExceededException` | Max versions reached with `overflow_strategy = prevent` | — |

---

## 7. Rules for AI Agents

### Layer architecture rules (enforce strictly)

| Layer | Directories | Rules |
|---|---|---|
| **Domain / core** | `src/Diff/`, `src/Versioning/`, `src/Events/`, `src/Exceptions/`, `src/Models/` | No MoonShine UI component dependencies; no manual HTML assembly; no knowledge of page/resource layout |
| **Application / delivery** | `src/Http/`, `src/Pages/`, `src/Resources/` | Orchestration only; minimal presentation logic; filter/query delegation to `src/Support/` objects |
| **Presentation** | `src/Components/`, `resources/views/` | Receives prepared data; renders HTML; no complex domain/query logic |

Key guardrails:
- `src/Diff/HtmlDiffRenderer` **must** render via `view()` — never via `DiffViewer` or other MoonShine UI components.
- `src/Diff/*` and `src/Versioning/*` **must not** import from `src/Components/`.
- HTML markup **must** live in `resources/views/` Blade templates — not in PHP heredoc strings inside page or resource classes.
- `src/Pages/*` and `src/Resources/*` **must not** build large HTML strings inline; they orchestrate data and call `view()->render()`.
- Filter/query logic **must** live in `src/Support/ActivityLogFilterData`, `ActivityLogQuery`, `ActivityLogFilterOptions`.
- Presentation helpers (event badges, entity links, section headers) **must** live in `src/Support/ActivityDetailPresenter`.
- Changes count computation for the timeline **must** live in `src/Support/ActivityTimelineDataBuilder`.

### What you CAN freely change

- Business logic inside `src/Diff/`, `src/Versioning/` (implementations, not contracts)
- Blade view markup and CSS classes in `resources/views/`
- Translation strings in `lang/`
- Tests in `tests/`
- Config defaults in `config/moontrail.php`
- PHPStan baseline (`phpstan.neon.dist`) — only to reduce suppressions, not to increase them

### What you MUST NOT change without explicit user instruction

| Protected area | Reason |
|---|---|
| Contract interfaces in `src/Contracts/` | Breaking changes affect all downstream users |
| `model_versions` migration schema | Changing columns requires a new migration, not editing the existing one |
| `MoonTrailServiceProvider` bindings | Changing defaults breaks host apps that rely on auto-discovery |
| `composer.json` `require` versions | Version constraints are part of the public API |
| Published config key `moontrail` | Renaming it is a breaking change |
| Route names in `routes/moontrail.php` | Frontend code and tests reference them by name |

### Before modifying a Spatie integration point

Read the [spatie/laravel-activitylog v4 docs](https://spatie.be/docs/laravel-activitylog/v4)
first. The `activity_log` table schema is owned by Spatie — never write a migration that
alters it. Extend via the `properties` JSON column or the `ModelVersion` table instead.

### Adding a new public method to a Contract

1. Add the method signature to the interface.
2. Implement it in the concrete class.
3. Update `tests/` to cover the new behaviour.
4. Document it in this file under §6.

Do **not** add implementation logic directly to contracts.

### Generating commits

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <imperative summary>

[optional body]

[optional footer]
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`, `style`

Scopes (use the closest match): `diff`, `versioning`, `rollback`, `ui`, `resource`,
`config`, `tests`, `ci`, `deps`, `installer`, `events`, `exceptions`, `observer`

Examples:

```
feat(diff): implement HtmlDiffRenderer with token-level highlighting
fix(rollback): wrap restore in DB transaction to prevent partial writes
test(versioning): add unit tests for max_versions overflow strategy
docs: update AGENTS.md with rollback flow diagram
chore(deps): require phpstan/phpstan ^2.0
```

Rules:
- Summary is **imperative mood**, lowercase, no trailing period, ≤ 72 chars.
- If the change breaks a contract or config key, add `BREAKING CHANGE:` in the footer.
- Never commit `vendor/`, `coverage/`, IDE config, or `.env` files.

### Generating PRs

PR title = the commit message of the primary commit (Conventional Commits format).

PR body must include:

1. **What** — one-paragraph description of the change.
2. **Why** — motivation / issue reference.
3. **How** — high-level approach, noting any trade-offs.
4. **Testing** — which tests cover the change; how to verify manually.
5. **Checklist**:
   - [ ] Tests pass (`./vendor/bin/pest`)
   - [ ] PHPStan passes (`./vendor/bin/phpstan analyse`)
   - [ ] No new `declare(strict_types=1)` omissions
   - [ ] Contracts unchanged or migration path documented

---

## 8. Contribution and Branching Strategy

### Branch naming

```
<type>/<short-description>
```

Examples:
```
feat/html-diff-renderer
fix/rollback-transaction-isolation
test/version-manager-overflow
docs/agents-md
chore/phpstan-baseline
```

Use `kebab-case`. Branch names must be ASCII, no spaces.

### Workflow

```
main (stable, tagged releases)
 └── feat/*, fix/*, ... (development branches)
```

1. **Branch from `main`** for every unit of work.
2. Keep branches focused — one feature or fix per branch.
3. Run the full CI check locally before pushing:
   ```bash
   ./vendor/bin/pest && ./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
   ```
4. Open a PR to `main`. Squash-merge is preferred to keep history linear.
5. Tag releases on `main` using [SemVer](https://semver.org/):
   - `PATCH` — bug fixes, no API changes
   - `MINOR` — new features, backwards-compatible
   - `MAJOR` — breaking changes to contracts, config keys, or DB schema

### Recommended implementation order for new contributors

When starting from the current scaffold, implement in this order to minimise rework:

1. **Contracts** — flesh out method signatures in `src/Contracts/`
2. **`HasMoonTrail` trait** — wire Spatie observers, call `VersionManager`
3. **`VersionManager`** — full `createVersion()` + `listVersions()` implementation
4. **`RollbackService`** — transaction + validation + event
5. **`DiffComputer` + `HtmlDiffRenderer`** — pure logic, easy to unit-test first
6. **Blade components** — `activity-timeline`, `diff-viewer`, `rollback-confirm`
7. **MoonShine components** — `ActivityTimeline`, `DiffViewer`, `ActivityTab`
8. **Controllers** — `RollbackController`, `ActivityController`
9. **`MoonTrailResource` / `MoonTrailPage`** — global admin views
10. **Feature tests** — cover the full HTTP layer

### Do not merge if

- Any Pest test fails.
- PHPStan reports new errors (baseline must not grow).
- A contract interface was changed without a `BREAKING CHANGE` footer.
- The `model_versions` migration was edited instead of a new migration being added.
- `vendor/` or generated files are included in the diff.

# MoonTrail for MoonShine

<p align="center">
  <img src="resources/assets/moontrail-logo.svg" alt="MoonTrail logo" width="220" />
</p>

Language: **English** · [Russian translation](README.ru.md)

Package: `tikhomirov/moontrail`

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%2B-red)](https://laravel.com)
[![MoonShine](https://img.shields.io/badge/MoonShine-4.8%2B-purple)](https://moonshine-laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE.md)

**Short description:** Advanced logging for MoonShine with change history, visual diff, model versioning, and safe rollback.

**Full description:** MoonTrail is a complete logging and audit layer for MoonShine admin panels. It extends `spatie/laravel-activitylog` with structured activity history, side-by-side field diff, snapshot-based model versioning, and transactional rollback with audit traceability.

> This release uses **strict breaking rename**:
> use only `MoonShine\\MoonTrail\\*`, `moontrail` config key/file, and `moontrail:*` commands.

---

## Why This Package?

MoonShine provides a powerful admin interface, and Spatie Activity Log tracks model events.
**This package bridges the gap** — giving administrators a visual, interactive history of every change with the ability to compare versions and roll back mistakes.

For teams and corporate environments, this means:
- Full **audit trail** with visual diffs for compliance and accountability
- **One-click rollback** with confirmation dialog and transaction safety
- **Timeline UI** embedded directly in MoonShine resource detail pages
- **Zero-configuration tracking** — add a trait, and everything works

---

## Features

| Feature | Description |
|---|---|
| **Visual Diff Viewer** | Color-coded side-by-side comparison of field changes (added / modified / removed) |
| **Model Versioning** | Automatic full-attribute snapshots on every create, update, delete, and restore |
| **Transactional Rollback** | Restore any model to a previous version with row-level locking and validation |
| **Timeline Component** | Chronological history with dates, authors, event badges, and inline diff |
| **Rollback Confirmation** | Alpine.js modal with snapshot timestamp and warnings before destructive actions |
| **Rollback Authorization** | Secure-by-default: rollback requires explicit opt-in via model method or Laravel policy |
| **Activity Log Resource** | Global MoonShine resource with filters, search, and paginated log browsing |
| **Auto-Tracking** | Track third-party models (e.g. `MoonshineUser`) via config — no trait needed |
| **Menu Integration** | `MoonTrailMenuItem::make()` helper for one-line menu setup |
| **Prune Command** | `moontrail:prune` with `--days`, `--model`, `--versions-only` options |
| **Extensible** | Swap DiffRenderer, VersionManager, RollbackStrategy, ActivityFormatter via IoC |
| **Localized** | English and Russian translations included |
| **Dark Mode** | Full dark mode support in all UI components |

---

## Requirements

- **PHP** 8.2+
- **Laravel** 11+
- **MoonShine** 4.8+
- **spatie/laravel-activitylog** 4.7+ (installed automatically)
- **Tailwind CSS** configured in the host app (required for package UI styles)
- **Alpine.js** in the host app (required for rollback modal and JSON expand/copy controls)

### Host Frontend Requirements (Important)

This package follows the common Laravel package approach:
- Blade views use inline Tailwind classes
- There is no frontend builder inside the package (`package.json`, Vite, Tailwind build pipeline)
- CSS compilation is the responsibility of the host application

Add package paths to `content` in your host app `tailwind.config.*`:

```js
content: [
    './resources/**/*.blade.php',
    './app/**/*.php',
    './vendor/tikhomirov/moontrail/resources/**/*.blade.php',
    './vendor/tikhomirov/moontrail/src/**/*.php',
]
```

If these paths are missing, UI elements (badges, sections, timeline, diff table) may render without proper styling.

---

## Installation

```bash
composer require tikhomirov/moontrail
php artisan migrate
```

The package auto-discovers via Laravel's package discovery. No manual provider registration needed.

## Rebranding Migration Notes

If you are upgrading from the original `tikhomirov/moon-trail` coordinates:

- Update `composer.json` to use `tikhomirov/moontrail`;
- Use namespace `MoonShine\\MoonTrail\\...`;
- Use config file/key `moontrail`.

See detailed checklist in `docs/v2/UPGRADE-GUIDE-REBRANDING.md`.

### Installation Wizard (recommended)

```bash
php artisan moontrail:install
```

Wizard can:
- check environment (MoonShine + DB connection);
- publish config/views/lang/assets;
- run migrations;
- select tracked models and update `auto_track_models` + `tracked_models`;
- print safe instructions for adding `WithMoonTrailTab` to selected resources.

Flags:

```bash
# force vendor:publish overwrite
php artisan moontrail:install --force

# safe mode (default): no PHP file modifications
php artisan moontrail:install --safe=true

# enable auto-patch mode for resources (asks explicit confirmation)
php artisan moontrail:install --auto-patch

# non-interactive mode (uses installer.non_interactive config defaults)
php artisan moontrail:install --no-interaction
```

> **Production environments:** the installer detects `APP_ENV=production` and asks for
> explicit confirmation before running migrations or patching resources.
> Pass `--no-interaction` to suppress all prompts (ensure config defaults are correct first).
>
> **Missing config:** if `config/moontrail.php` is not published yet, the installer will
> offer to publish it automatically before proceeding.

### Publish Assets (optional)

```bash
# Configuration
php artisan vendor:publish --tag=moontrail-config

# Blade views (for customization)
php artisan vendor:publish --tag=moontrail-views

# Translations
php artisan vendor:publish --tag=moontrail-lang

# Package CSS assets (fallback styles for detail/timeline/diff)
php artisan vendor:publish --tag=moontrail-assets
```

> **Note:** If you publish views with `--tag=moontrail-views`, you must re-publish after
> upgrading the package to pick up any changes to the bundled templates.
> Alternatively, delete the published copies to fall back to the package originals.

> **After publishing config or views in production**, clear the application cache:
> ```bash
> php artisan config:clear
> php artisan view:clear
> ```

---

## Quick Start

### Step 1: Add Trait to Your Model

```php
use MoonShine\MoonTrail\Traits\HasMoonTrail;

class Post extends Model
{
    use HasMoonTrail;

    protected $fillable = ['title', 'body', 'status'];
}
```

This enables:
- Automatic activity logging via Spatie
- Version snapshots on every model event
- Rollback support (disabled by default — see [Enabling Rollback](#enabling-rollback))

### Step 2: Add History Tab to Your MoonShine Resource

```php
use MoonShine\MoonTrail\Traits\WithMoonTrailTab;

class PostResource extends ModelResource
{
    use WithMoonTrailTab;
    // History tab is added automatically to the detail page
}
```

### Step 3: Add Activity Log to the Menu

In your `MoonShineLayout`:

```php
use MoonShine\MoonTrail\Support\MoonTrailMenuItem;

protected function menu(): array
{
    return [
        // ...your menu items...
        MoonTrailMenuItem::make(),
    ];
}
```

The `MoonTrailMenuItem` reads the icon and resource class from config automatically.

**That's it.** Every create, update, and delete on your model is now tracked with full versioning, visual diff, and rollback.

---

## Enabling Rollback

Rollback is **disabled by default** (secure-by-default). The rollback button is only shown when the current user is explicitly authorized.

There are two ways to enable it:

### Option A — `isRollbackAllowed()` in the model (simple)

```php
class Post extends Model
{
    use HasMoonTrail;

    public function isRollbackAllowed(): bool
    {
        return true;
        // or: return auth()->user()?->isAdmin() ?? false;
    }
}
```

### Option B — Laravel Policy (recommended for fine-grained control)

Create a policy with a `rollback` method:

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function rollback(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
```

Register it in `AppServiceProvider` or `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::policy(Post::class, PostPolicy::class);
```

### Authorization matrix (secure-by-default)

| Condition | Result |
|---|---|
| MoonShine guard not authenticated | Deny |
| Policy registered with `rollback` method | Delegate to policy |
| No policy, model has `isRollbackAllowed()` | Use model method |
| Otherwise | Deny |

### When rollback is not allowed

When `showRollback` is enabled but the user does not have permission, the timeline shows a
muted read-only chip with a tooltip `"Rollback is not available: insufficient permissions."`
instead of silently hiding the control. This helps administrators understand that the feature
exists and why it is unavailable.

To hide the control entirely instead of showing the hint, call `->withoutRollback()` on the component:

```php
ActivityTimeline::make('History', $this)->withoutRollback()
```

---

## Rollback Mechanics

### What happens during rollback

1. **Pre-event** `ModelRollingBack` is fired (cancellable — see below).
2. A database transaction is opened with `lockForUpdate()` on the target row.
3. If the model is soft-deleted, it is restored.
4. The snapshot fields are written back to the model.
5. A new version is created with `is_rollback = true` and `rollback_to_version = N`,
   preserving the full audit trail.
6. The transaction is committed.
7. **Post-event** `ModelRolledBack` is fired with `fromVersion`, `toVersion`, and `newVersion`.

### Cancelling a rollback

Any listener that returns `false` from `ModelRollingBack` will cancel the rollback before any
database writes occur:

```php
use MoonShine\MoonTrail\Events\ModelRollingBack;

Event::listen(ModelRollingBack::class, function (ModelRollingBack $event): bool {
    if ($event->model instanceof Post && someCondition($event->model)) {
        return false; // cancel
    }
});
```

When cancelled, `RollbackCancelledException` is thrown and the controller returns HTTP 422.

### HTTP response codes

| Situation | HTTP Status |
|---|---|
| Not authenticated / policy / model denies | 403 |
| Version not found / validation fails / rollback cancelled | 422 |
| DB constraint / concurrent write conflict | 409 |
| Unexpected error | 500 |

### Rollback button visibility rule

The rollback button is shown for all versions **except the earliest** (the first snapshot
of the record). This is because rolling back to the very first version is equivalent to
restoring the initial create state, which is always available.

---

## Configuration

After publishing (`vendor:publish --tag=moontrail-config`):

```php
// config/moontrail.php

return [
    'versioning' => [
        'enabled'           => true,
        'max_versions'      => 50,              // 0 = unlimited
        'overflow_strategy' => 'delete_oldest',  // or 'prevent'
    ],

    'rollback' => [
        // Validate snapshot data against rules before restoring.
        'validate'      => true,

        // When true, rollback without any validation rules is denied.
        // When false (default), rollback without rules proceeds without validation.
        'require_rules' => false,
    ],

    'ui' => [
        'per_page'                 => 20,
        'date_format'              => 'd.m.Y H:i:s',
        'warn_if_tailwind_missing' => true,
        'hidden_fields'            => ['password', 'remember_token', 'two_factor_secret'],
        'masked_fields'            => ['password', 'remember_token', 'two_factor_secret', 'api_key', 'secret', 'token'],
    ],

    'resource' => [
        'class'     => \MoonShine\MoonTrail\Resources\MoonTrailResource::class,
        'in_menu'   => true,
        'menu_icon' => 'clock',
    ],

    // Track models without adding the HasMoonTrail trait
    'auto_track_models' => [
        // \MoonShine\Laravel\Models\MoonshineUser::class,
    ],
];
```

### Key Options

| Option | Default | Description |
|---|---|---|
| `versioning.max_versions` | `50` | Max snapshots per model instance. `0` = unlimited |
| `versioning.overflow_strategy` | `delete_oldest` | What to do when limit reached: `delete_oldest` or `prevent` |
| `rollback.validate` | `true` | Validate snapshot against rules before restoring |
| `rollback.require_rules` | `false` | When `true`, rollback without explicit rules is denied |
| `ui.warn_if_tailwind_missing` | `true` | Logs a warning if host Tailwind config is missing package `content` paths |
| `ui.hidden_fields` | `[password, ...]` | Fields hidden from diff globally |
| `ui.masked_fields` | `[password, ..., token]` | Fields shown in diff as masked placeholders |
| `auto_track_models` | `[]` | Models tracked automatically without the trait |

---

## Model Configuration

### Track Only Specific Fields

```php
class Post extends Model
{
    use HasMoonTrail;

    protected static array $logAttributes = ['title', 'body', 'status'];
    protected static bool $logOnlyDirty = true;
}
```

### Exclude Fields from Version Snapshots

```php
public function getVersionExcludedFields(): array
{
    return ['cached_data', 'temp_token'];
}
```

---

## Auto-Tracking (No Trait Required)

For third-party models you cannot modify (e.g. `MoonshineUser`), use the config:

```php
'auto_track_models' => [
    \MoonShine\Laravel\Models\MoonshineUser::class,
],
```

The package attaches its observer at boot time — no trait or code changes needed.

---

## Programmatic API

### Diff Viewer

```php
use MoonShine\MoonTrail\Diff\DiffComputer;

// From two attribute arrays
$changes = DiffComputer::compute(
    before: ['name' => 'Old', 'status' => 'draft'],
    after:  ['name' => 'New', 'status' => 'draft'],
    hiddenFields: ['password'],
);

// From a Spatie Activity record
$changes = DiffComputer::fromActivity($activity);

// Render to HTML
$html = app(\MoonShine\MoonTrail\Contracts\DiffRendererContract::class)->render($changes);
```

Each `FieldChange` contains: `field`, `oldValue`, `newValue`, and `type` (`Added`, `Modified`, `Removed`, `Unchanged`).

### VersionManager

```php
use MoonShine\MoonTrail\Contracts\VersionManagerContract;

$manager = app(VersionManagerContract::class);

$version = $manager->createVersion($post, 'updated');
$v1      = $manager->getVersion($post, 1);
$diff    = $manager->diff($v1, $v2);         // FieldChange[]
$diff    = $manager->diffWithCurrent($v1, $post);
```

### RollbackService

```php
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;

$service = app(RollbackStrategyContract::class);

// Simple rollback (no validation)
$model = $service->rollback($post, targetVersion: 3);

// Rollback with validation rules
$model = $service->rollback($post, targetVersion: 3, rules: [
    'title' => 'required|min:3',
]);
```

Rollback is wrapped in a database transaction with `lockForUpdate()`. The observer is
automatically suspended during rollback to prevent duplicate version creation.

Possible exceptions:

| Exception | When |
|---|---|
| `ModelVersionNotFoundException` | Target version does not exist |
| `RollbackDeniedException` | Authorization denied (no policy, model denies) |
| `RollbackCancelledException` | A `ModelRollingBack` listener returned `false` |
| `RollbackConflictException` | DB constraint or concurrent write conflict (has `->reason` field) |
| `ValidationException` | Snapshot data failed validation rules |

---

## Events

| Event | Payload | Fired When |
|---|---|---|
| `VersionCreated` | `$model`, `$version` | A new version snapshot is stored |
| `ModelRollingBack` | `$model`, `$targetVersion`, `$version` | Before rollback — **cancellable** (return `false` to abort) |
| `ModelRolledBack` | `$model`, `$fromVersion`, `$toVersion`, `$newVersion` | After successful rollback and transaction commit |

### Event lifecycle and transaction boundaries

```
event(ModelRollingBack)   ← fired BEFORE the transaction; return false to cancel
  └─ DB::transaction
       ├─ lockForUpdate
       ├─ restore (if soft-deleted)
       ├─ fill + save
       └─ createVersion (is_rollback=true)
event(ModelRolledBack)    ← fired AFTER commit; guaranteed data in payload
```

---

## Artisan Commands

```bash
# Prune records older than 90 days (default)
php artisan moontrail:prune

# Custom retention period
php artisan moontrail:prune --days=30

# Selective pruning
php artisan moontrail:prune --versions-only
php artisan moontrail:prune --activity-only

# Prune specific model type
php artisan moontrail:prune --model="App\Models\Post"

# Interactive installer
php artisan moontrail:install
```

Schedule in `routes/console.php`:

```php
Schedule::command('moontrail:prune --days=90')->daily();
```

---

## Contracts & Extensibility

Every core service is bound via an interface and can be swapped:

| Contract | Default | Purpose |
|---|---|---|
| `DiffRendererContract` | `HtmlDiffRenderer` | Renders `FieldChange[]` → HTML |
| `VersionManagerContract` | `VersionManager` | Creates / retrieves / compares versions |
| `RollbackStrategyContract` | `RollbackService` | Executes transactional rollback |
| `ActivityFormatterContract` | `DefaultActivityFormatter` | Formats event labels / icons / colors |

```php
// In a ServiceProvider
$this->app->bind(DiffRendererContract::class, MyCustomDiffRenderer::class);
```

---

## Security

- All package routes are protected by MoonShine's authentication and session middleware.
- Rollback is **secure-by-default**: the button is hidden unless explicitly allowed.
- Authorization uses a single `RollbackAuthorizationResolver` shared by both the controller and the UI component — there is no risk of a mismatch between what the button shows and what the server allows.
- Rollback actions require confirmation via an Alpine.js modal that displays the snapshot timestamp and version number.
- Sensitive fields (`password`, `remember_token`, etc.) are excluded from diffs by default.
- DB conflicts during rollback return HTTP 409 (not 500), so the client can distinguish a conflict from an unexpected server error.

---

## Development

```bash
composer test           # run tests (Pest)
composer test:coverage  # tests with coverage
composer test:types     # PHPStan level 9
composer lint           # fix code style (Pint)
composer lint:test      # check code style only
composer refactor       # apply Rector
composer ci             # full CI: rector + pint + phpstan + tests
```

## OpenCode

This repository is ready to use with OpenCode out of the box.

- `AGENTS.md` is the primary project rules file.
- `opencode.json` adds shared watcher ignores and safer approval prompts for `git commit`, `git push`, `git tag`, and `rm`.
- `.opencode/commands/` includes `/ci`, `/test`, `/types`, `/lint`, `/fix`, and `/review` for the common package workflows.
- `.opencode/agents/package-reviewer.md` adds a read-only reviewer focused on package BC and release safety.

Quick start:

```bash
opencode
```

Useful commands inside OpenCode:

- `/ci`
- `/test`
- `/test tests/Unit/DiffComputerTest.php`
- `/types`
- `/lint`
- `/fix`
- `/review`

Use your personal global OpenCode config or a local uncommitted `tui.json` for private keybinds and UI preferences.

### Troubleshooting

1. **Rollback buttons not visible**
   - **Most likely cause:** `isRollbackAllowed()` returns `false` (the default) and no policy is registered. See [Enabling Rollback](#enabling-rollback).
   - When a user has no permission, a muted read-only hint is shown instead of the button. If you see the hint, rollback is reachable but blocked by authorization.
   - If you published views with `--tag=moontrail-views`, re-publish after upgrading to pick up template changes.

2. **Package UI looks unstyled (Tailwind classes missing)**
   - Ensure your host app includes package paths in `tailwind.config.*` `content`.
   - Ensure Alpine.js is loaded in the host app layout (required for rollback modal and JSON controls).

3. **`composer test:coverage` fails with `No code coverage driver available`**
   - Install and enable one driver: **PCOV** or **Xdebug**.
   - Example (PCOV):

```bash
php -d pcov.enabled=1 ./vendor/bin/pest -c phpunit.xml.dist --coverage --min=80
```

---

## Why "MoonTrail"?

> *"The moon is the sun of the dead, its trail leads where there are no shadows."*

No, this is not a fortune cookie. This is a sacred cosmological concept from the Nenets people — one of the indigenous nations of the Russian Arctic.

In Nenets mythology the universe has three layers: the Upper (light), the Middle (ours), and the Lower world of shadows — *Khyly*. The worlds are mirrors: when the Sun (*Khaer*) shines here, darkness reigns below. When night falls and the Moon (*Iri*) rises for us, it becomes the blazing sun for the spirits of the dead. What we see as a dim glow, they experience as blinding light. And in a world flooded by its own "sun", there are no secondary shadows — the spirits themselves *are* the shadows, and the moonlight makes everything visible.

The author is deeply fascinated by the North and its epics — Nenets, Selkup, Nganasan mythology — and this concept resonated perfectly with an audit log package. **MoonTrail** sees everything: deleted records, overwritten fields, rolled-back changes. Nothing hides from the moon's trail.

And yes — *MoonShine* literally means "moonlight", but it also means homemade liquor. So if MoonShine is the good stuff you brew in your admin panel, **MoonTrail** is the morning-after evidence trail that tells you exactly what happened and who did it. 🥃

The visual identity is built on the [Polaris theme for MoonShine](https://github.com/tikhomirov/moonshine-polaris-theme) — a cold, northern palette inspired by the same arctic aesthetics.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE.md](LICENSE.md).

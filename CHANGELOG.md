# Changelog

## 0.4.0

### Rollback finalisation

- **Conflict classification** — `RollbackConflictException` now carries a machine-readable `reason` field (`db_constraint`, `unknown`). A new `RollbackConflictClassifier` maps raw `Throwable`s from inside the transaction to the appropriate reason, so callers can handle them programmatically.
- **Cancellable pre-event** — `ModelRollingBack` is now fired via `event()` before the DB transaction. If any listener returns `false` the rollback is aborted and `RollbackCancelledException` is thrown without touching the database. `ModelRolledBack` is dispatched after commit.
- **HTTP 409 for conflicts** — `RollbackController` now returns HTTP 409 for `RollbackConflictException` (DB constraint / concurrent write conflicts), 422 for validation or not-found version, and 422 for cancelled rollbacks.
- **`rollback.require_rules` config flag** — when `true`, rollbacks that receive no validation rules still run the validator (with an empty rule set, which passes). Default is `false` (unchanged behaviour: no rules → no validation).
- **`RollbackAuthorizationResolver`** — single source of truth for rollback permission checks, used by both `RollbackController` and `ActivityTimeline`. Eliminates the previous duplication.
- **UI: no-rights hint** — when `showRollback=true` but the current user cannot roll back, non-earliest version rows now show a muted, read-only "Rollback" chip with a tooltip `rollback_denied_hint` instead of silently hiding the control.
- **UI: snapshot timestamp in modal** — the rollback confirmation modal now displays the snapshot timestamp of the selected version, making it clear exactly which point in time will be restored.
- **UI: rollback rule** — rollback button is rendered for all versions **except the earliest** (minimum version id in the loaded set). This replaces the previous `$loop->last` heuristic which was unreliable with paginated version sets.
- **New i18n keys** — `rollback_confirm_snapshot_note`, `rollback_error_conflict`, `rollback_error_cancelled`, `rollback_no_rights`, `rollback_denied_hint` (en + ru).

## 0.3.0

### UI Redesign (v3)

- **Stage 5**: Centralised SVG icon library (`src/Support/SvgIcons`) — all icons now managed in one place; callers pass size and extra-class parameters.
- **Stage 6 (P0-01)**: Auto-tracked models now write to Spatie `activity_log` when `auto_track.log_to_activity` is `true` (default). `activity_id` is populated in `model_versions` so Show Diff works out of the box.
- **Stage 7**: Active-filter chips rendered above the index table — each chip removes its own filter; a Clear All link removes all at once.
- **Stage 8**: Accessibility — `role="list"`, `role="listitem"`, `role="dialog"`, `aria-modal`, `aria-labelledby`, `aria-expanded`, `aria-live`, `aria-pressed`, `scope="col"`, `datetime` attribute on `<time>`, `aria-hidden` on decorative elements, `focus-visible` rings, `prefers-reduced-motion` support, and mobile-responsive padding/grid adjustments.
- **Stage 9**: Test coverage — 35 new tests covering `SvgIcons`, KPI block rendering, filter chip logic (`renderActiveFilterChips`, `resolveFilterValue`, `buildRemoveFilterUrl`, `buildClearAllFiltersUrl`), accessibility attributes in timeline/diff-viewer/rollback modal.

## 0.2.0

- Rebranded product positioning to **MoonShine Logs**.
- Updated package metadata and UI-facing product naming (menu/title/translations).
- Added upgrade guide for rebranding and migration paths.
- Strategy B (strict breaking rename): switched public API to `MoonShine\\MoonTrail\\*`, `moontrail` config key/file, `moontrail:*` artisan commands, and `moonshine.moontrail.*` routes.
- Removed legacy `MoonShine\\ActivityLog\\*` branding from package entry points and documentation examples.

## 0.1.0

- Added versioning layer over spatie activity log
- Added rollback service with transaction and validation hooks
- Added MoonShine UI components (timeline, diff viewer, activity tab)
- Added MoonTrailResource and package routes/controllers
- Added unit and feature tests

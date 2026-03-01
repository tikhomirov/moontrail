# docs/v3/ — Canonical Release Documents

> **Status: CANONICAL / CURRENT**
>
> This directory contains the authoritative release documents for the
> `tikhomirov/moontrail` v3 finalization cycle.
> Use these documents for all integration, deployment, and release decisions.

## Document Index

| # | File | Purpose | Status |
|---|---|---|---|
| 01 | `01-PACKAGE-FULL-DESCRIPTION.md` | Package scope, feature list, business value, constraints | Canonical |
| 02 | `02-FINALIZATION-TZ.md` | Release finalization specification — P0/P1/P2 tasks and DoD | Canonical |
| 03 | `03-GAP-MATRIX.md` | Gap analysis matrix: planned vs implemented vs action | Canonical |
| 04 | `04-COMPETITOR-ANALYSIS.md` | Competitive positioning vs Spatie/OwenIt/MoonShine Changelog | Canonical |
| 05 | `05-PORTABILITY-ANALYSIS.md` | Portability strategy and go/no-go (Decision: Strategy A) | Canonical |
| 06 | `06-RELEASE-READINESS-CHECKLIST.md` | Final release readiness gate — checklist and verdict | Canonical |

## Legacy documents

- [`docs/`](../) — V1 historical planning documents (archived, do not use for integration)
- [`docs/v2/`](../v2/) — V2 acceptance records (verified historical, do not modify)

## Package coordinates (canonical)

```
composer require tikhomirov/moontrail
```

- **Namespace:** `MoonShine\MoonTrail\*`
- **Config key:** `moontrail`
- **Route prefix:** `moonshine.moontrail.*`
- **Commands:** `moontrail:install`, `moontrail:prune`
- **Publish tags:** `moontrail-config`, `moontrail-views`, `moontrail-lang`, `moontrail-assets`
- **Resource URL:** `/admin/resource/moontrail-resource/index-page`

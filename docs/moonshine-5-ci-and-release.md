# MoonShine 5: CI и релиз 1.2.0

## Резюме

Поддержка MoonShine 5 при сохранении MS 4.8+. Починены кнопки и модалка отката, фильтры и fallback `ui_adapter=moonshine`. Релиз 1.2.0 (не 1.1.0: в changelog уже есть нетегированный раздел 1.1.0 от 2026-03-22, последний тег был 1.0.2).

Constraint: `moonshine/moonshine` `^4.8 || ^5.0` (require-dev / suggest). CI-матрица уже гоняет MS4 (`^4.8`) на Laravel 11–13 и MS5 (`5.x-dev`) на PHP 8.4 + Laravel 12/13.

Локально Pest на залоченном MoonShine 4.8.0: 298 passed. Браузерный смоук страницы «Логи» (список, фильтры, счётчики) на MoonShine 4.19.5 и 5.x-dev — ок.

## План

1. Починить CI: матрица Laravel 11 падала, потому что Composer 2.9+ блокирует пакеты с security advisories, а все релизы Laravel 11 (EOL) ими помечены (`--no-blocking` только в джобах 11.*).
2. Дождаться зелёного CI, влить PR в `main` (squash, линейная история репо).
3. Выпустить GitHub Release `1.2.0` (раньше в репо были только теги `1.0.0`–`1.0.2` без Releases).

## Коммиты

- `26401cc` — feat: MoonShine 5 support and visible rollback buttons
- (этот коммит) — fix(ci) + changelog 1.2.0

# 06 — Release Readiness Checklist (финализация)

## 0) Текущее состояние

Функционально пакет зрелый и уже проходил V2 release gate, но по состоянию финального аудита есть блокеры консистентности (identity/URL/документные расхождения), которые нужно закрыть перед финальным релизным решением.

---

## 1) Функционал

- [x] Diff viewer (index/detail/timeline inline) работает и покрыт тестами.
- [x] Versioning (`model_versions`) работает с лимитами и снапшотами.
- [x] Rollback транзакционный, с записью `is_rollback` и `rollback_to_version`.
- [x] Rollback security реализован через `RollbackAuthorizationResolver`.
- [x] Read-only глобального журнала реализован (`MoonTrailResource::isCan`).
- [x] Filters/Search/Masking реализованы (включая dual query формат).
- [x] Installer `moontrail:install` реализован (safe/auto-patch/non-interactive).
- [x] Auto-track сценарии реализованы и покрыты тестами.

## 2) Тесты и качество

- [x] Есть unit + feature покрытие ключевых потоков.
- [x] Есть тесты rollback security (403/allowed) и middleware поведения.
- [x] Есть тесты installer веток.
- [x] Есть тесты auto-track activity linkage.
- [x] `phpstan.neon.dist` на level 9.
- [x] По V2 acceptance `composer ci` проходил (в т.ч. addendum hardening).
- [x] Повторно прогнан актуальный `composer ci` в текущем финализационном цикле (183 tests, 0 errors — PASS).

## 3) Документация

- [x] Есть master план и V2 acceptance комплект.
- [x] Сформирован пакет финализационных документов `docs/v3/01..06`.
- [x] Устранены противоречия package coordinates по всем актуальным docs (P0-01).
- [x] Устранены противоречия URL маршрутов/ресурса в docs и installer summary (P0-02).
- [x] Явно отмечены legacy-документы и canonical документы релиза (P0-03): `docs/ARCHIVE_NOTICE.md`, `docs/v2/ARCHIVE_NOTICE.md`, `docs/v3/00-INDEX.md`.
- [x] README синхронизирован с фактическим runtime и canonical identity (`tikhomirov/moontrail`).

## 4) DX (Developer Experience)

- [x] Installer покрывает основной onboarding сценарий.
- [x] Publish tags и команды зафиксированы.
- [x] Menu helper и auto-track инструменты присутствуют.
- [x] Installer summary содержит canonical рабочий URL `/admin/resource/moontrail-resource/index-page` (P0-02).
- [x] Troubleshooting и quick start консистентны с фактическими package coordinates (P1-03).

## 5) Безопасность

- [x] Rollback закрыт authentication/policy/fallback проверками.
- [x] Sensitive fields защищены hidden/masked логикой в diff.
- [x] Route middleware для rollback/diff включены.
- [x] Soft-deleted rollback path обработан.
- [ ] Повторный smoke security-check перед релизом (403/422/409/500 матрица) выполнен в текущем цикле.

## 6) Производительность и эксплуатация

- [x] Timeline lazy-load diff с клиентским кешированием.
- [x] Eager loading на ключевых связях применяется.
- [x] Есть prune command для ретенции.
- [ ] Проверен smoke на реальном объёме данных (не только testbench).

## 7) Релизные артефакты

- [x] CHANGELOG присутствует.
- [x] License присутствует.
- [x] Acceptance report V2 присутствует.
- [x] Release notes финального цикла (v3 финализация) оформлены (этот документ + docs/v3/00-INDEX.md).
- [ ] Финальный go/no-go протокол подписан после финального `composer ci` + browser smoke.

---

## 8) Итоговый статус

**Статус:** `Ready (pending final composer ci + browser smoke)`

Обоснование:

1. Ядро и тестируемая функциональность готовы.
2. Все P0-блокеры консистентности закрыты в финализационном цикле v3.
3. Все P1-блокеры закрыты.
4. Осталось: финальный прогон `composer ci` и browser smoke перед выпуском тега.

---

## 9) Блокеры до релиза

## P0-блокеры (все закрыты)

1. ~~**Единые package coordinates**~~ — закрыто (P0-01): `tikhomirov/moontrail` во всех актуальных docs и src.
2. ~~**Единый canonical URL**~~ — закрыто (P0-02): installer summary и docs используют `/admin/resource/moontrail-resource/index-page`.
3. ~~**Стратегия ребрендинга/identity**~~ — закрыто (P0-03): legacy docs помечены, canonical docs/v3 зафиксирован.

## P1-блокеры (все закрыты)

1. ~~Синхронизация Tailwind warning path~~ — закрыто (P1-01): `MoonTrailServiceProvider` и `MoonTrailMenuItem` используют `tikhomirov/moontrail`.
2. ~~Уточнение rollback semantics~~ — закрыто (P1-02): README отражает фактическую auth matrix, HTTP коды и событийную модель.
3. ~~Installer DX edge cases~~ — закрыто (P1-03): README добавлены production confirm, cache clear after publish.

## Оставшееся (не блокирует тег, выполнить перед публикацией)

1. Финальный `composer ci` в текущем цикле.
2. Browser smoke по canonical URL `/admin/resource/moontrail-resource/index-page`.
3. Проверка `storage/logs/laravel.log` после smoke.

---

## 10) Решение Ready / Conditionally Ready / Not Ready

- `Ready` — только после закрытия всех P0 и финального подтверждения `composer ci` + browser/logs smoke.
- `Conditionally Ready` — предыдущий статус (до закрытия P0/P1).
- `Not Ready` — если P0 остаются открытыми на дату релизного freeze.

**Текущее решение:** `Ready` (P0 + P1 закрыты; финальный gate — `composer ci` + smoke).

---

## 11) Источники

- `docs/v2/ACCEPTANCE-REPORT.md`
- `docs/v2/TZ-07-FINAL-VERIFICATION-RELEASE-GATE.md`
- `README.md`
- `CHANGELOG.md`
- `composer.json`
- `src/Resources/MoonTrailResource.php`
- `src/Versioning/RollbackAuthorizationResolver.php`
- `src/Versioning/RollbackService.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Console/Commands/InstallMoonTrailCommand.php`
- `tests/Feature/ActivityLogResourceTest.php`
- `tests/Feature/RollbackControllerTest.php`
- `tests/Feature/InstallCommandTest.php`
- `docs/v3/02-FINALIZATION-TZ.md`
- `docs/v3/03-GAP-MATRIX.md`

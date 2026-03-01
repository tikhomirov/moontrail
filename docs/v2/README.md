# MoonShine Logs V2 — комплект ТЗ

Этот каталог содержит **актуализированный набор ТЗ** после повторной ревалидации кода, существующей документации и текущего UI состояния пакета.

## Цель комплекта

Довести `tikhomirov/moon-trail` до продуктового состояния **MoonShine Logs** для MoonShine:

- визуальный аудит «было → стало» без чтения сырых JSON;
- полноценная история версий и безопасный rollback;
- enterprise-ready UI (global resource + timeline на сущности);
- безопасная модель доступа и предсказуемый релизный контроль.

---

## Состав документов

1. [00-CURRENT-STATE-AND-GAPS.md](./00-CURRENT-STATE-AND-GAPS.md)  
   Подтверждённый статус реализации и GAP после ревалидации.

2. [01-DELIVERY-PLAN-CHECKLIST.md](./01-DELIVERY-PLAN-CHECKLIST.md)  
   Пошаговый план реализации с чекбоксами и зависимостями этапов.

3. [TZ-01-GLOBAL-RESOURCE-UI.md](./TZ-01-GLOBAL-RESOURCE-UI.md)  
   Доработка global Activity Log Resource (Index + Detail, read-only UX).

4. [TZ-02-TIMELINE-AND-DIFF-UX.md](./TZ-02-TIMELINE-AND-DIFF-UX.md)  
   Доработка timeline/diff UX в карточках сущностей и на detail.

5. [TZ-03-FILTERS-SEARCH-MASKING.md](./TZ-03-FILTERS-SEARCH-MASKING.md)  
   Расширенные фильтры, поиск по изменениям, скрытие/маскирование данных.

6. [TZ-04-ROLLBACK-SECURITY-UX.md](./TZ-04-ROLLBACK-SECURITY-UX.md)  
   Secure-by-default rollback: policy/gate, UI-ограничения, toast, аудируемость.

7. [TZ-05-INTERACTIVE-INSTALLER.md](./TZ-05-INTERACTIVE-INSTALLER.md)  
   Визуальный artisan installer/wizard для первичной настройки.

8. [TZ-06-REBRANDING-ADVANCED-AUDIT-LOG.md](./TZ-06-REBRANDING-ADVANCED-AUDIT-LOG.md)  
   Ребрендинг пакета с учётом совместимости и semver.

9. [TZ-07-FINAL-VERIFICATION-RELEASE-GATE.md](./TZ-07-FINAL-VERIFICATION-RELEASE-GATE.md)  
   Финальная приемка: браузер, логи, `composer ci`, release gate.

10. [ACCEPTANCE-REPORT.md](./ACCEPTANCE-REPORT.md)  
   Итоговый отчет финальной приемки с матрицей PASS/FAIL и вердиктом GO/NO-GO.

---

## Внешние референсы (обязательные)

- Filament Audit Pro (UI-ориентир):  
  https://filamentphp.com/plugins/arnautdev-audit-pro
- Spatie Activity Log:  
  https://spatie.be/docs/laravel-activitylog/introduction
- Spatie GitHub:  
  https://github.com/spatie/laravel-activitylog

---

## Как работать с комплектом

1. Сначала согласовать `00-*` и `01-*`.
2. Затем выполнять ТЗ по порядку: `TZ-01` → `TZ-06`.
3. Завершать только через `TZ-07` (финальный gate обязателен).
4. После `TZ-07` обязательно фиксировать результат в `ACCEPTANCE-REPORT.md`.

> Любой этап считается завершённым только если выполнен его DoD и добавлены тесты.

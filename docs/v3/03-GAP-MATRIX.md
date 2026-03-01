# 03 — GAP-матрица финализации

## 0) Текущее состояние

Функциональное ядро пакета реализовано, но финализация релиза требует закрытия консистентности артефактов (код ↔ документация ↔ release-позиционирование).

---

## GAP Matrix

| Область | Запланировано | Реализовано | Gap | Влияние | Приоритет | Решение |
|---|---|---|---|---|---|---|
| Product scope (diff/versioning/rollback) | Полный контур фич | Реализовано (core + UI + rollback) | Нет функционального разрыва | Высокое позитивное | Закрыто | Оставить без rework; только regression-check |
| Release gate | Browser + logs + `composer ci` | PASS в V2 acceptance | Нет технического разрыва | Высокое позитивное | Закрыто | Сохранить как обязательный финальный gate |
| Read-only audit | Запрет CRUD в global log | `isCan()` блокирует write-abilities | Нет | Высокое позитивное | Закрыто | Поддерживать тестами на abilities |
| Filters/Search/Masking | 8 фильтров + dual query + masked/hidden | Реализовано и покрыто тестами | Нет в коде; есть риск документной деградации | Среднее | P1 | Перепроверить docs на соответствие текущему поведению |
| Rollback security matrix | Policy-first + secure fallback | Реализовано через `RollbackAuthorizationResolver` | Нет функционального gap; частично устаревшие формулировки в старых ТЗ | Высокое | P1 | Обновить docs под фактическую матрицу доступа |
| SoftDeletes rollback | Поддержка отката soft-deleted | Реализовано в контроллере/сервисе + тесты | Нет | Среднее | Закрыто | Держать в regression suite |
| Installer wizard | One-command onboarding | Реализовано (`moontrail:install`) | В summary есть устаревший URL | Высокое (DX) | P0 | Исправить `printSummary()` URL + docs |
| Package coordinates | Единые install/update координаты | Конфликт: `composer.json` = `tikhomirov/moontrail`, docs часто `tikhomirov/moon-trail` | Критичный документно-интеграционный gap | Высокое (блокер установки) | P0 | Принять canonical координаты и унифицировать все документы/примеры |
| Resource URL references | Единый рабочий URL | Конфликт между `moontrail-resource/index-page` и `moon-trail-resource/moon-trail-index-page` | Критичный docs gap | Высокое | P0 | Нормализовать URL во всех чеклистах/README/installer |
| Tailwind host requirements | Чёткие требования + диагностика | Проверка и warning реализованы | Legacy path refs (`vendor/.../moon-trail`) | Среднее | P1 | Синхронизировать пути с canonical package identity |
| Rebranding strategy | Формально зафиксированная стратегия и semver | В V2 docs зафиксирована Strategy A, в CHANGELOG — Strategy B строгий rename | Конфликт стратегий между артефактами | Высокое | P0 | Утвердить единую стратегию релиза и привести changelog/docs к одному варианту |
| Docs governance | Single source of truth | Много исторических планов V1/V2 + новые v3 | Риск чтения неактуальных инструкций | Среднее | P0 | Пометить canonical и legacy документы явно |
| CI/quality claims | Прозрачные метрики качества | Есть PASS `composer ci`; coverage зависит от драйвера | Потенциальная путаница по coverage-порогу | Среднее | P2 | Явно фиксировать prerequisites coverage в readiness/checklist |
| Competitive positioning | Формализованная таблица сильных/слабых сторон | Частично (в раннем плане) | Нет актуального конкурентного документа под финализацию | Среднее | P2 | Подготовить и утвердить `04-COMPETITOR-ANALYSIS.md` |
| Portability decision | Формальный go/no-go | Отсутствует решение | Стратегический gap | Среднее/долгосрочное | P2 | Принять A/B/C стратегию в `05-PORTABILITY-ANALYSIS.md` |

---

## Источники (по матрице)

- `MOONSHINE-ACTIVITY-LOG-PLAN.md` (разделы 1, 5, 6, 10)
- `docs/MASTER-PLAN.md`
- `docs/v2/00-CURRENT-STATE-AND-GAPS.md`
- `docs/v2/01-DELIVERY-PLAN-CHECKLIST.md`
- `docs/v2/ACCEPTANCE-REPORT.md`
- `docs/v2/TZ-06-REBRANDING-ADVANCED-AUDIT-LOG.md`
- `README.md`
- `CHANGELOG.md`
- `composer.json`
- `src/Resources/MoonTrailResource.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Versioning/RollbackService.php`
- `src/Versioning/RollbackAuthorizationResolver.php`
- `src/MoonTrailServiceProvider.php`
- `src/Console/Commands/InstallMoonTrailCommand.php`
- `tests/Feature/ActivityLogResourceTest.php`
- `tests/Feature/RollbackControllerTest.php`
- `tests/Feature/InstallCommandTest.php`

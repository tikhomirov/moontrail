# V2 план реализации (пошагово, с чекбоксами)

## 0) Краткое текущее состояние

Пакет уже имеет рабочее ядро (versioning, diff, rollback, read-only resource), но до уровня **MoonShine Logs** не хватает безопасного rollback-доступа, расширенных фильтров/поиска/маскирования, установщика и формализованного release gate.

---

## 1) Общие правила выполнения

- [x] Любая задача закрывается только вместе с тестами.
- [x] Любая UI-задача закрывается только после ручной проверки в браузере.
- [x] После каждого этапа: проверка логов хоста (`storage/logs/laravel.log`).
- [x] После каждого этапа: `composer ci` в корне пакета.
- [x] Все новые строки UI добавляются в `lang/en/ui.php` и `lang/ru/ui.php`.

---

## 2) Этапы реализации

## Этап 1 — Global Resource UI (Index + Detail)
**ТЗ:** `TZ-01-GLOBAL-RESOURCE-UI.md`  
**Роли:** MoonShine UI developer + Laravel backend developer

- [x] Финализировать структуру index-таблицы и порядок колонок.
- [x] Проверить и закрепить read-only UX (без кнопок create/edit/delete).
- [x] Финализировать detail с 4 секциями: General / Relations / Changes / History.
- [x] Доработать ссылки Open на causer/subject resource detail.
- [x] Обновить feature-тесты на структуру index/detail.

**Gate этапа:** UI соответствует wireframe, read-only подтверждён тестами.

---

## Этап 2 — Timeline + Diff UX
**ТЗ:** `TZ-02-TIMELINE-AND-DIFF-UX.md`  
**Роли:** MoonShine UI developer (Blade/Alpine/Tailwind)

- [x] Стабилизировать lazy-load diff (одна загрузка на версию, без повторных запросов).
- [x] Доработать состояния loading/error/empty в timeline.
- [x] Нормализовать UX diff для JSON (expand/copy, без утечек скрытых значений).
- [x] Проверить responsive-поведение timeline/diff на desktop + mobile.
- [x] Добавить/обновить тесты компонентного рендера и поведения.

**Gate этапа:** timeline предсказуем, diff не «ломается» при первом раскрытии.

---

## Этап 3 — Filters / Search / Masking
**ТЗ:** `TZ-03-FILTERS-SEARCH-MASKING.md`  
**Роли:** Laravel backend developer + MoonShine UI developer

- [x] Добавить фильтры: log_name, event, subject_type/id, causer_type/id, date_from/date_until.
- [x] Поддержать query-форматы `?field=value` и `?filters[field]=value`.
- [x] Расширить поиск по `properties` (best effort, кросс-БД fallback).
- [x] Добавить `ui.masked_fields` + поведение маскирования `********`.
- [x] Зафиксировать приоритет: hidden_fields > masked_fields.
- [x] Закрыть unit/feature тестами.

**Gate этапа:** оператор быстро находит нужную запись, чувствительные данные не раскрываются.

---

## Этап 4 — Rollback Security + UX
**ТЗ:** `TZ-04-ROLLBACK-SECURITY-UX.md`  
**Роли:** Senior Laravel backend developer + MoonShine UI developer

- [x] Внедрить secure-by-default авторизацию rollback (Policy/Gate/fallback).
- [x] Изменить дефолт в `HasMoonTrail::isRollbackAllowed()` на безопасный.
- [x] Скрывать rollback-кнопки в UI при отсутствии прав.
- [x] Добавить toast об успешном rollback.
- [x] Проверить, что rollback фиксируется в истории прозрачно для аудита.
- [x] Добавить тесты на 403/allowed сценарии.

**Gate этапа:** rollback доступен только авторизованным ролям, UX понятен и подтверждён.

---

## Этап 5 — Interactive Installer
**ТЗ:** `TZ-05-INTERACTIVE-INSTALLER.md`  
**Роли:** Laravel backend developer (Artisan/Prompts)

- [x] Реализовать `moontrail:install`.
- [x] Добавить шаги wizard: publish, migrate, model selection, resource integration.
- [x] Реализовать safe mode по умолчанию (без авто-правок кода).
- [x] Добавить optional auto-patch режим только по явному подтверждению.
- [x] Добавить тесты команды в interactive/non-interactive режимах.

**Gate этапа:** пользователь за один запуск получает рабочую интеграцию без ручных ошибок.

---

## Этап 6 — Rebranding в MoonShine Logs
**ТЗ:** `TZ-06-REBRANDING-ADVANCED-AUDIT-LOG.md`  
**Роли:** Тимлид / package maintainer

- [x] Утвердить стратегию ребрендинга (A — совместимая, B — breaking).
- [x] Обновить package metadata/документацию/локализацию.
- [x] Подготовить upgrade guide.
- [x] Зафиксировать semver и релизную стратегию.

**Gate этапа:** новое имя внедрено без неопределённостей для пользователей пакета.

---

## Этап 7 — Финальный release gate
**ТЗ:** `TZ-07-FINAL-VERIFICATION-RELEASE-GATE.md`  
**Роли:** QA engineer + Tech Lead

- [x] Полный ручной проход UI сценариев в браузере.
- [x] Проверка `storage/logs/laravel.log` в хост-приложении.
- [x] Прогон `composer ci` в пакете.
- [x] Оформление acceptance отчёта и решение Go/No-Go.

**Gate этапа:** релиз разрешён только при полном выполнении чеклиста.

---

## 3) Зависимости между этапами

- Этап 2 зависит от Этапа 1 (единая UI-структура).
- Этап 4 зависит от Этапа 2 (чтобы поведение timeline было стабильным).
- Этап 7 зависит от всех этапов.
- Этап 6 можно вести параллельно с 3/4, но выпускать после 7.

---

## 4) Финальный статус

- [x] План V2 закрыт полностью.
- [x] Финальная приемка зафиксирована в `docs/v2/ACCEPTANCE-REPORT.md`.

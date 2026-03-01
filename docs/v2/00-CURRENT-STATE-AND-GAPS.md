# V2-аудит: текущее состояние пакета и подтверждённые GAP

**Дата ревалидации:** 2026-02-28  
**Пакет:** `tikhomirov/moon-trail`  
**Цель ревалидации:** перед формированием нового набора ТЗ подтвердить, что уже сделано, что частично сделано и что критично довести в V2.

**Статус документа:** это снимок состояния **до** финального release gate.  
Итоговое закрытие всех этапов зафиксировано в `docs/v2/ACCEPTANCE-REPORT.md`.

---

## 1) Что проверено

### 1.1 Код и архитектура
- `src/Resources/MoonTrailResource.php`
- `src/Pages/MoonTrailDetailPage.php`
- `src/Components/ActivityTimeline.php`
- `src/Versioning/VersionManager.php`
- `src/Versioning/RollbackService.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Http/Controllers/ActivityController.php`
- `src/Support/MoonTrailMenuItem.php`
- `resources/views/components/*.blade.php`
- `config/moontrail.php`

### 1.2 Документы
- `DEVELOPMENT_CHECKLIST.md`
- `REFINEMENT_REPORT.md`
- `README.md`
- `docs/00-AUDIT-STATUS-AND-GAPS.md`
- `docs/TZ-01...TZ-06`

### 1.3 Проверка качества
- Прогон `composer ci` в корне пакета: **успешно** (0 ошибок).

### 1.4 Проверка UI в браузере
- Доступ к странице: `http://localhost:8000/admin/resource/moontrail-resource/index-page`
- Подтверждено отображение Resource, таблицы, колонок и меню-фильтров по моделям.

---

## 2) Подтверждённые реализованные части (факты)

## 2.1 База продукта уже есть
- Интеграция со Spatie Activity Log реализована.
- Версионирование (`model_versions`) реализовано.
- Rollback реализован транзакционно.
- Diff как отдельный компонент и API есть.

## 2.2 Read-only для глобального журнала реализован
- `MoonTrailResource` блокирует `CREATE/UPDATE/DELETE/MASS_DELETE`.
- Это соответствует требованию «audit log только для чтения».

## 2.3 UI уже вышел за «базовый уровень»
- Есть отдельный `MoonTrailResource` со списком и detail-страницей.
- Detail организован по 4 секциям (General / Relations / Changes / History).
- В `diff-viewer` есть таблица и работа со сложными JSON-значениями (expand/copy).
- В `activity-timeline` есть inline diff и модалка rollback.

## 2.4 DX-часть частично усилена
- Есть `MoonTrailMenuItem::make()` с подпунктами по моделям.
- Есть auto tracking через `auto_track_models` / `tracked_models`.
- Есть команда обслуживания `moontrail:prune`.

## 2.5 Тестовый контур расширен
- Есть unit + feature тесты по ключевым сценариям.
- CI-запуск проходит.

---

## 3) Актуальные GAP, которые остаются после ревалидации

## GAP-01 (P0): Авторизация rollback не доведена до secure-by-default
**Факт:**
- `RollbackController` выполняет rollback без явного policy/gate authorize.
- `HasMoonTrail::isRollbackAllowed()` по умолчанию возвращает `true`.

**Риск:**
- rollback может быть доступен шире, чем требуется корпоративной моделью доступа.

**Что нужно:**
- Ввести строгую стратегию доступа: Policy `rollback` -> fallback -> deny by default.
- Скрывать rollback кнопки в UI для пользователей без прав.

---

## GAP-02 (P1): Фильтры и поиск на index не дотягивают до целевого уровня
**Факт:**
- Сейчас фильтры ограничены в основном `event` + `subject_type`.

**Не хватает:**
- `log_name`, `subject_id`, `causer_type`, `causer_id`, date range.
- Поиска по `properties` (best effort).

---

## GAP-03 (P1): Маскирование чувствительных данных отсутствует как отдельный механизм
**Факт:**
- Есть только `ui.hidden_fields`.

**Не хватает:**
- `ui.masked_fields` (показывать поле, но скрывать значения `********`).

---

## GAP-04 (P1): UX rollback не завершён
**Факт:**
- После rollback нет стандартизированного success toast.
- Нет формализованной модели отображения успеха/ошибки rollback для оператора.

---

## GAP-05 (P1): Интерактивного установщика нет
**Факт:**
- Команды `moontrail:install` пока нет.

**Не хватает:**
- wizard для публикации ассетов, миграций, выбора моделей, инструкций по интеграции в ресурсы.

---

## GAP-06 (P2): Tailwind/Alpine зависимость требует формализации
**Факт:**
- UI пакета зависит от Tailwind классов и Alpine поведения.

**Не хватает:**
- Жёстко прописанных требований в acceptance-части и installer-выводе.

---

## GAP-07 (P2): Ребрендинг в MoonShine Logs не оформлен как релизный план
**Факт:**
- В коде и package metadata всё ещё прежний нейминг.

**Не хватает:**
- стратегии A/B, upgrade guide, semver-правил.

---

## 4) Приоритеты V2 (порядок реализации)

1. **P0:** Rollback Authorization + secure-by-default.
2. **P1:** UI/UX доводка Resource + Timeline + Detail acceptance.
3. **P1:** Filters/Search/Masking.
4. **P1:** Interactive Installer.
5. **P2:** Rebranding.
6. **Release Gate:** финальная end-to-end проверка в браузере, логах и `composer ci`.

---

## 5) Что уже не нужно переделывать в V2

- Базовый backend версии/rollback с нуля.
- Read-only модель ресурса с нуля.
- Компоненты diff/timeline с нуля.

Фокус V2 — **доведение до продуктового уровня**, а не переписывание ядра.

---

## 6) Вывод для планирования

V2-план должен строиться как «доработка зрелого ядра»:
- минимум риска,
- максимальная конкретика по требованиям,
- обязательные приемочные сценарии,
- явный финальный release gate.

---

## 7) Финальный статус после TZ-07

- Все этапы `TZ-01 ... TZ-06` закрыты.
- Финальный `composer ci` — PASS.
- Release gate (`TZ-07`) пройден.
- Вердикт по выпуску: **GO** (см. `docs/v2/ACCEPTANCE-REPORT.md`).

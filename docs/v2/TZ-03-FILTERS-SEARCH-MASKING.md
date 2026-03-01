# TZ-03 — Фильтры, поиск и защита данных (hidden/masked)

## 0) Краткое текущее состояние

В `MoonTrailResource` уже есть базовые фильтры (`event`, `subject_type`) и поиск по базовым полям. Для корпоративного использования этого недостаточно: оператору нужны составные фильтры, поиск по изменениям и гарантированное сокрытие чувствительных данных.

---

## 1) Цель этапа

Сделать global audit log пригодным для реальной эксплуатации в SaaS/CRM/ERP:
- быстро находить записи по объекту, автору, периоду и типу события;
- искать по изменённым значениям;
- исключить утечки секретов в diff/UI.

---

## 2) Кто реализует

- **Роль 1:** Laravel backend developer (ведущий).
  - Навыки: Eloquent query builder, JSON-поиск, кросс-БД совместимость.
- **Роль 2:** MoonShine UI developer.
  - Навыки: MoonShine filters API, отображение значений в таблице.
- **Роль 3:** QA engineer.
  - Навыки: тестовые сценарии фильтрации/поиска/маскирования.

---

## 3) Область изменений

- `src/Resources/MoonTrailResource.php`
- `src/Diff/DiffComputer.php` (если маскирование удобнее внедрять здесь)
- `resources/views/components/diff-viewer.blade.php` (если маскирование на уровне рендера)
- `config/moontrail.php`
- `lang/en/ui.php`, `lang/ru/ui.php`
- `tests/Feature/MoonTrailResourceTest.php`
- `tests/Unit/DiffComputerTest.php` и/или `tests/Feature/DiffViewerComponentTest.php`

---

## 4) Фильтры (Index page)

## 4.1 Список обязательных фильтров

1. `log_name` (select)
2. `event` (select, enum `ActivityEvent`)
3. `subject_type` (select)
4. `subject_id` (text/number)
5. `causer_type` (select)
6. `causer_id` (text/number)
7. `date_from` (date)
8. `date_until` (date)

## 4.2 Источники значений для select

- `log_name`: distinct `activity_log.log_name`.
- `subject_type`: distinct `activity_log.subject_type`.
- `causer_type`: distinct `activity_log.causer_type`.
- `event`: `ActivityEvent::cases()`.

## 4.3 Правила применения

- Все фильтры комбинируются по AND-логике.
- Пустое значение фильтра не влияет на query.
- `date_from` и `date_until` применяются по `created_at`:
  - from: `>= startOfDay`
  - until: `<= endOfDay`

## 4.4 Форматы query-параметров (обязательная совместимость)

Поддержать оба формата:

1) Прямой:
```text
?subject_type=App%5CModels%5CUser&event=updated
```

2) MoonShine-style:
```text
?filters[subject_type]=App%5CModels%5CUser&filters[event]=updated
```

Причина: `MoonTrailMenuItem` уже работает с обоими форматами и это нельзя ломать.

## 4.5 Примеры целевых URL

```text
/admin/resource/moontrail-resource/index-page?subject_type=App%5CModels%5CProduct
/admin/resource/moontrail-resource/index-page?filters[event]=deleted&filters[causer_id]=1
/admin/resource/moontrail-resource/index-page?date_from=2026-02-01&date_until=2026-02-28
```

---

## 5) Поиск

## 5.1 Базовые поля поиска

Поиск обязан находить минимум по:
- `description`
- `event`
- `subject_type`

## 5.2 Поиск по `properties` (изменённые значения)

Требование:
- Поиск должен выполнять `best effort` по JSON изменениям.

Норматив реализации:
- Предпочтительно — нативный JSON поиск per DB driver.
- Обязательный fallback (кросс-БД):
```php
where('properties', 'like', "%{$term}%")
```

Важно:
- Реализация должна проходить тесты в SQLite.

---

## 6) Защита данных: hidden_fields и masked_fields

## 6.1 Разделение механик

### hidden_fields
- Поле полностью исключается из diff.
- Строка не рендерится вообще.

### masked_fields
- Поле присутствует в diff.
- Значения `before/after` показываются как `********`.
- Оригинал не должен утекать через `title`, `data-*`, clipboard, JSON expand.

## 6.2 Конфигурация

В `config/moontrail.php` добавить:
```php
'ui' => [
    'hidden_fields' => ['password', 'remember_token', 'two_factor_secret'],
    'masked_fields' => ['password', 'remember_token', 'two_factor_secret', 'api_key', 'secret', 'token'],
]
```

## 6.3 Приоритет правил

Порядок применения:
1. Если поле в `hidden_fields` — скрыть полностью.
2. Иначе, если в `masked_fields` — показать строку с маской.
3. Иначе — обычный рендер.

---

## 7) UX-правила для masked полей

- В таблице diff отображать:
  - Before: `********`
  - After: `********`
- Кнопки `Expand` и `Copy` для masked JSON отключены.
- Tooltip не содержит исходное значение.

---

## 8) Тесты (обязательные)

## 8.1 Feature
- Фильтры рендерятся и применяются совместно.
- Поддерживаются оба query-формата (`field` и `filters[field]`).
- Поиск затрагивает `properties` (fallback сценарий).

## 8.2 Unit/Feature на маскирование
- `hidden_fields`: строка отсутствует.
- `masked_fields`: рендерятся маски, без исходных значений.
- Для masked JSON нельзя получить оригинал через expand/copy.

---

## 9) Acceptance (DoD)

- На index доступны все фильтры раздела 4.1.
- Фильтры корректно комбинируются.
- Поиск работает по базовым полям и `properties`.
- `hidden_fields` и `masked_fields` работают согласно приоритету.
- В UI нет утечек чувствительных значений.
- `composer ci` — зелёный.
- После ручной проверки нет ошибок в `storage/logs/laravel.log`.

---

## 10) Что не входит в этап

- Авторизация rollback и policy logic (это `TZ-04`).
- Installer wizard (это `TZ-05`).

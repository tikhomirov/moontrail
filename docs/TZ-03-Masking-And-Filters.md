# ТЗ-03: Фильтры, поиск и безопасность данных (маскирование/скрытие)

## 0) Ссылки и контекст

- Текущий статус и GAP-лист: **[00-AUDIT-STATUS-AND-GAPS.md](00-AUDIT-STATUS-AND-GAPS.md)**
- Страница для ручной проверки:
  - `http://localhost:8000/admin/resource/moontrail-resource/index-page`

## 1) Цель этапа

Сделать global audit log пригодным для реального использования в корпоративной админке:

- быстро находить нужные события (фильтры)
- находить по тексту и по изменённым значениям (поиск)
- не раскрывать чувствительные данные в UI (маскирование)

## 2) Исполнитель

- **Профиль:** Laravel backend developer + MoonShine UI developer
- **Компетенции:** Eloquent queries, MoonShine filters/search, работа с JSON в БД, тестирование Pest.

## 3) Область изменений

- `src/Resources/MoonTrailResource.php`
- `config/moontrail.php`
- `src/Diff/DiffComputer.php` и/или `resources/views/components/diff-viewer.blade.php`
- тесты `tests/Feature/*`, `tests/Unit/*`

## 4) Требования: фильтры (Index page)

### 4.1 Набор фильтров

На странице списка должны быть фильтры:

- **Log** (`log_name`)
  - тип: select
  - значения: distinct `Activity::query()->distinct()->pluck('log_name')`
- **Event** (`event`)
  - тип: select
  - значения: `ActivityEvent::cases()`
- **Subject type** (`subject_type`)
  - тип: select
  - значения: distinct `subject_type`
- **Subject ID** (`subject_id`)
  - тип: number/text
- **Causer type** (`causer_type`)
  - тип: select
  - значения: distinct `causer_type`
- **Causer ID** (`causer_id`)
  - тип: number/text
- **Date from / until** (`created_at` range)
  - 2 поля даты: from/until

Примечание:

- Если MoonShine не предоставляет “date range filter” из коробки, использовать два поля даты.

### 4.2 Формат параметров (чтобы было тестируемо)

Требование:

- Все фильтры должны работать как минимум в одном стабильном формате, который можно зафиксировать тестами и использовать в MenuItem/ссылках.

Норматив:

- Поддержать оба формата входных параметров:
  - прямой query param: `?subject_type=App%5CModels%5CUser`
  - MoonShine filter формат: `?filters[subject_type]=App%5CModels%5CUser`

Это требуется, потому что:

- `MoonTrailMenuItem::make()` уже пытается определять активность по `subject_type` и `filters[subject_type]`.

### 4.3 Поведение фильтров

- Фильтры комбинируются (AND логика).
- Кнопка Reset очищает все фильтры.
- Переход по меню (sub-items) должен выставлять `subject_type` фильтр и работать.

## 5) Требования: поиск

### 5.1 Поиск по “человеческим” колонкам

Требование:

- Поиск должен находить записи по:
  - `description`
  - `event`
  - `subject_type`

### 5.2 Поиск по изменённым значениям (properties)

Требование:

- Поиск должен дополнительно искать по полю `properties` (JSON) как минимум “best effort” способом.

Ограничения:

- Реализация должна работать в SQLite (tests), MySQL и PostgreSQL.

Норматив (без двусмысленности):

- Если нет кросс‑DB способа безопасно искать по JSON, допускается fallback:
  - `where('properties', 'like', "%{$term}%")`

Важно:

- В ТЗ-05 будет обязательная ручная проверка поиска по изменениям.

## 6) Требования: скрытие и маскирование данных

### 6.1 Разделение понятий

В UI должны быть 2 независимых механизма:

- **hidden_fields** — поле не показываем вообще (строка diff скрыта).
- **masked_fields** — поле показываем, но значения скрываем (`********`).

### 6.2 Конфигурация

В `config/moontrail.php`:

- оставить `ui.hidden_fields` (уже есть)
- добавить `ui.masked_fields` со значениями по умолчанию:
  - `password`
  - `remember_token`
  - `two_factor_secret`
  - `api_key`
  - `secret`
  - `token`

### 6.3 UI поведение

- Если поле в `hidden_fields`:
  - строка diff не выводится
- Если поле в `masked_fields`:
  - строка diff выводится
  - old/new выводятся как `********`
  - tooltip/title тоже не должен показывать оригинал

Важно:

- Маскирование — это **только UI слой**.
- Рекомендуемая практика: секретные поля также исключать из snapshots через `getVersionExcludedFields()`.

## 7) Тесты (обязательно)

- Feature: фильтры существуют (count/keys), комбинируются (проверка query builder).
- Unit: masked_fields не раскрывают значения в рендере diff.

## 8) Definition of Done (DoD)

- В Index доступны фильтры из раздела 4, они работают совместно.
- Поиск ищет по описанию/событию/типу + по `properties` (fallback допустим).
- `hidden_fields` скрывают строки diff.
- `masked_fields` маскируют значения без утечек через tooltip.
- Ручная проверка на `.../moontrail-resource/index-page` пройдена.
- `composer ci` проходит.

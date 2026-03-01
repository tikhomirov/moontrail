# TZ-01 — Global Activity Log Resource UI (Index + Detail, strict read-only)

## 0) Краткое текущее состояние

В пакете уже есть рабочий `MoonTrailResource` и кастомный detail page, включая 4 секции (General / Relations / Changes / History). Read-only режим на уровне `Ability` реализован, но UI и acceptance-требования нужно зафиксировать и довести до однозначного продуктового стандарта.

---

## 1) Цель этапа

Сделать global страницу аудита уровня **MoonShine Logs**, где оператор:
- быстро находит событие;
- открывает детальную карточку с понятной структурой;
- видит изменения в читаемом виде;
- не может изменять/удалять аудит-данные.

UI-референс: Filament Audit Pro (структура и UX, не pixel-perfect).

---

## 2) Кто реализует

### 2.1 Ответственный специалист
- **Роль:** MoonShine UI developer (lead).
- **Hard skills:** MoonShine 4, Blade, Tailwind, Alpine.js (базово), i18n.
- **Дополнительно:** Laravel/Eloquent для связей subject/causer.

### 2.2 Участники
- Backend developer — для query/форматирования данных и edge-case обработки.
- QA engineer — для приемки браузерных сценариев.

---

## 3) Область изменений

- `src/Resources/MoonTrailResource.php`
- `src/Pages/MoonTrailDetailPage.php`
- `resources/views/components/diff-viewer.blade.php`
- `resources/views/components/activity-timeline.blade.php` (только интеграционная часть в detail)
- `lang/en/ui.php`, `lang/ru/ui.php`
- `tests/Feature/MoonTrailResourceTest.php`

---

## 4) Функциональные требования

## 4.1 Index page — таблица аудита

### 4.1.1 Колонки и порядок (обязательно)
1. `ID` (sortable)
2. `Log` (`log_name`, если пусто — `—`)
3. `Event` (цветной badge)
4. `Subject`
5. `Causer`
6. `Description`
7. `Changed At` (`created_at`, sortable)

### 4.1.2 Правила отображения
- **Event badge:**
  - текст: локализованная метка через enum formatter;
  - цвет: стабилен по типу события (`created/updated/deleted/restored/rolled_back`).
- **Subject/Causer формат:**
  - `{Model} #{id} ({displayName})`;
  - `displayName`: `name` → `title` → `email` → пусто;
  - для system events: `System`.
- **Description:**
  - приоритет `ActivityFormatterContract`;
  - fallback: `activity.description`;
  - если пусто: `—`.

### 4.1.3 Read-only ограничения
- На index/detail **не должно быть** действий create/edit/delete/mass-delete.
- Любые прямые write-endpoints (если доступны в роутинге MoonShine) должны возвращать 403/быть недоступны.

---

## 4.2 Detail page — единая карточка аудита

### 4.2.1 Структура (жестко фиксированная)

```text
┌────────────────────────────────────────────────────────────────────┐
│ Activity #ID                                                      │
├────────────────────────────────────────────────────────────────────┤
│ [General]                                                         │
│ id, log_name, event, description, created_at                      │
├────────────────────────────────────────────────────────────────────┤
│ [Relations]                                                       │
│ causer + subject + кнопки Open (если ресурс найден)               │
├────────────────────────────────────────────────────────────────────┤
│ [Changes]                                                         │
│ diff table: Field | Before | After | Status                       │
├────────────────────────────────────────────────────────────────────┤
│ [History]                                                         │
│ timeline версий и событий                                         │
└────────────────────────────────────────────────────────────────────┘
```

### 4.2.2 Section: General
Поля в фиксированном порядке:
1. `id`
2. `log_name` (если есть)
3. `event`
4. `description`
5. `created_at`

### 4.2.3 Section: Relations
- Отображать `causer` и `subject` в унифицированном формате.
- Кнопка **Open**:
  - показывается только если соответствующий MoonShine resource найден;
  - открывает detail страницы связанной сущности;
  - при отсутствии resource кнопка не показывается.

### 4.2.4 Section: Changes
- Используется `DiffViewer`.
- Для JSON/array значений:
  - в таблице — сокращённый preview;
  - кнопка `Expand` раскрывает prettified JSON;
  - кнопка `Copy` копирует полный JSON.

### 4.2.5 Section: History
- Встраивается `ActivityTimeline`.
- Требования по lazy diff/rollback UX описаны в `TZ-02` и `TZ-04`.

---

## 5) UX и визуальные требования

- Визуальная иерархия: секции читаются с первого экрана.
- Цвета событий консистентны между index/detail/timeline.
- Длинные значения не ломают сетку (ellipsis/scroll/tooltip).
- Mobile: таблица и секции должны быть читаемы на ширине 360px+.
- Dark mode: обязательная поддержка.

---

## 6) Нефункциональные требования

- Нет N+1 на `subject` и `causer` (использовать eager loading).
- Генерация detail не падает при удалённом subject/causer.
- Любая новая строка UI локализована в `en` и `ru`.

---

## 7) Тесты (обязательные)

## 7.1 Feature
- Проверка порядка и состава index-колонок.
- Проверка наличия 4 секций detail.
- Проверка read-only `Ability` для create/update/delete/mass-delete.
- Проверка отображения `System` при `causer_type = null`.

## 7.2 Snapshot/HTML assertions
- Event badge рендерится с ожидаемым классом/меткой.
- В relations-карточке кнопка Open показывается только в допустимом случае.

---

## 8) Acceptance (DoD)

- `http://localhost:8000/admin/resource/moontrail-resource/index-page` соответствует требованиям раздела 4.1.
- Detail соответствует структуре раздела 4.2.
- Read-only поведение подтверждено тестами.
- Нет ошибок в `storage/logs/laravel.log` после ручного прохода.
- `composer ci` — зелёный.

---

## 9) Что не входит в этап

- Логика авторизации rollback (это `TZ-04`).
- Расширенные фильтры/поиск/маскирование (`TZ-03`).
- Интерактивный установщик (`TZ-05`).

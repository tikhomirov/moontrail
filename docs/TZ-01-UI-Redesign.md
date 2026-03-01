# ТЗ-01: UI/UX глобального журнала (MoonTrailResource) + строгий Read-only

## 0) Ссылки и контекст

- Референс (UI/UX): Filament Audit Pro
  - https://filamentphp.com/plugins/arnautdev-audit-pro
- Текущий статус и GAP-лист: **[00-AUDIT-STATUS-AND-GAPS.md](00-AUDIT-STATUS-AND-GAPS.md)**
- Страница для ручной проверки (обязательная):
  - `http://localhost:8000/admin/resource/moontrail-resource/index-page`

Примечание по UI:

- Требуется **функциональное и концептуальное соответствие** (структура экрана, сценарии, читаемость, безопасность).
- Pixel-perfect копирование Filament не требуется.

## 1) Цель этапа

Сделать глобальный UI просмотра audit trail в MoonShine:

- удобным для человека (как инструмент аудита)
- безопасным (audit log **строго read-only**)
- сопоставимым по UX с Filament Audit Pro

## 2) Исполнитель

- **Профиль:** MoonShine UI developer
- **Компетенции:** MoonShine 4, Blade, Tailwind, Alpine.js (минимально), Eloquent relations.

## 3) Термины

- **Activity**: запись из `spatie/laravel-activitylog` (`activity_log`).
- **Subject**: сущность, над которой выполнено действие.
- **Causer**: пользователь/система, выполнившие действие.

## 4) Требования к Read-only (критично)

### 4.1 Запрет CRUD действий

Глобальный журнал — это **не CRUD**. Нельзя допустить изменения или удаления аудита.

Требование:

- На страницах ресурса **не должно быть** действий:
  - Create
  - Edit
  - Delete
  - Mass delete

Техническая реализация (обязательная, без двусмысленности):

- В `MoonTrailResource` переопределить механизм авторизации MoonShine для действий CRUD.
- В MoonShine 4 это делается через проверку `Ability` (см. `MoonShine\Support\Enums\Ability` и `ModelResource::isCan`).
- Для `Ability::CREATE`, `Ability::UPDATE`, `Ability::DELETE`, `Ability::MASS_DELETE` метод должен возвращать `false`.
- Для просмотра (`Ability::VIEW_ANY`, `Ability::VIEW`) должно возвращаться `true`.

### 4.2 DoD Read-only

- На list/detail страницах отсутствуют кнопки Create/Edit/Delete.
- Прямой переход на URL удаления/редактирования (если такой URL существует в MoonShine) должен возвращать **403** или не быть доступным.

## 5) UI требования: Index page (list)

### 5.1 Колонки

Колонки должны быть в следующем порядке:

- **ID** (sortable)
- **Log** (`log_name`, если доступно; если нет — скрыть колонку)
- **Event**
  - отображать как цветной badge
  - текст — локализованный (использовать `ActivityEvent::label()`)
  - цвет — по `ActivityEvent::color()`
- **Subject**
  - формат: `{Model} #{id} ({displayName})`
  - `displayName` определяется как:
    - `name`, иначе `title`, иначе `email`, иначе пусто
- **Causer**
  - если `causer_type=null` → `System`
  - формат: `{Model} #{id} ({displayName})`
- **Description**
  - приоритет: `ActivityFormatterContract::format()` (`description`)
  - fallback: `activity.description` или `—`
- **Changed At** (`created_at`, sortable)

### 5.2 Переход на detail

Требование:

- Должен быть единый, очевидный способ открыть detail.
- Допускается:
  - действие `View`
  - клик по строке

## 6) UI требования: Detail page

### 6.1 Структура экрана

Detail page должна быть визуально разделена на 4 секции.

Wireframe:

```text
┌──────────────────────── View Activity Log #123 ─────────────────────────┐
│ [General]                                                               │
│  ID: 123        Log: default        Event: [Updated badge]              │
│  Description: User was updated                                          │
│  Date: 28.02.2026 13:20:00                                              │
├─────────────────────────────────────────────────────────────────────────┤
│ [Relations]                                                             │
│  Causer: MoonshineUser #1 (Admin)   [Open]                              │
│  Subject: Product #44 (Tracking...) [Open]                              │
├─────────────────────────────────────────────────────────────────────────┤
│ [Changes]                                                               │
│  Field | Before | After | Status                                        │
│  name  | Old    | New   | modified                                      │
│  meta  | {...}  | {...} | modified   [Expand] [Copy]                    │
├─────────────────────────────────────────────────────────────────────────┤
│ [History]                                                               │
│  Version #5 Updated ... [Show diff] [Rollback]                          │
│  Version #4 Created ...                                                 │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 General

Поля (в одном блоке):

- `id`
- `log_name` (если есть)
- `event` (badge)
- `description`
- `created_at`

### 6.3 Relations

- Causer: тип, id, displayName
- Subject: тип, id, displayName

Требование по ссылкам:

- Если для causer/subject в MoonShine зарегистрирован ресурс, то рядом показывать кнопку **Open** ведущую на detail соответствующего ресурса.
- Если ресурс не найден — кнопка **Open** не показывается.

### 6.4 Changes

Компонент:

- Использовать `DiffViewer`.

Требование к отображению сложных значений:

- Если old/new значение является массивом/объектом (JSON), то:
  - в таблице показывать сокращённо (как сейчас: `truncate`)
  - по клику **Expand** показывать форматированный JSON (`pre/code`) для конкретной ячейки
  - по клику **Copy** копировать JSON (old/new) в clipboard

### 6.5 History

- Использовать существующий `ActivityTimeline`.
- Rollback UX/authorization — реализуется в ТЗ-04.

## 7) Тесты (обязательно)

- Добавить/обновить Feature-тесты:
  - на read-only (невозможность create/edit/delete)
  - на наличие нужных колонок в index
  - на наличие ключевых блоков на detail (General/Relations/Changes/History)

## 8) Definition of Done (DoD)

- На `.../moontrail-resource/index-page`:
  - корректные колонки и порядок
  - `Event` отображается как цветной badge (по `ActivityEvent`)
  - нет create/edit/delete
- На detail:
  - 4 секции как в wireframe
  - JSON можно раскрыть (expand) и скопировать (copy)
- Нет ошибок в `storage/logs/laravel.log` хост-приложения.
- `composer ci` проходит в пакете.

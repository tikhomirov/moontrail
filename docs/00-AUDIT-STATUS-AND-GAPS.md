# Аудит текущего состояния пакета (fact-based) и GAP-лист

Дата аудита: 2026-02-28 (обновлено: 2026-03-01)

Документ фиксирует **факты по текущей реализации** пакета `tikhomirov/moon-trail` (по коду репозитория) и перечисляет **конкретные разрывы (gaps)** относительно целевого продукта «MoonShine Logs» и референса Filament Audit Pro.

---

## 1) Что уже реализовано (факты по коду)

### 1.1 Безопасность роутов (admin prefix + middleware)
- **Есть:** `routes/moontrail.php`
  - Prefix: `/{moonshine.prefix}/moontrail` (по умолчанию `admin/moontrail`)
  - Middleware: `moonshine.middleware` + `moonshine.auth.middleware`
  - Endpoints:
    - `POST /rollback` → `RollbackController`
    - `GET /{activity}/diff` → `ActivityController@diff` (JSON: `{ html, event }`)

### 1.2 Глобальный Resource ActivityLog
- **Есть:** `src/Resources/MoonTrailResource.php`
  - `IndexPage` + `DetailPage`
  - Index fields: `id`, `event`, `subject`, `causer`, `description`, `created_at`
  - Detail fields: основные поля + `DiffViewer` + `History` (Timeline по `ModelVersion`)
  - Фильтры: `event`, `subject_type`
  - Search: `description`, `subject_type`, `event`
  - Меню: авто-регистрация ресурса через `MoonTrailServiceProvider` (если `resource.in_menu=true`)

### 1.3 Timeline UI (история версий + inline diff + rollback confirm modal)
- **Есть:** `resources/views/components/activity-timeline.blade.php`
  - Вертикальная timeline
  - Цветные бейджи событий
  - Inline diff панель (AJAX fetch на `/admin/moontrail/{id}/diff`)
  - Кнопка Rollback (для версий кроме первой) + модалка подтверждения
- **Есть:** `resources/views/components/rollback-confirm.blade.php`
  - Alpine.js modal, teleport, confirm form POST

### 1.4 Diff viewer UI (таблица изменений)
- **Есть:** `resources/views/components/diff-viewer.blade.php`
  - Таблица `Field | Before | After | Status`
  - Подсветка строк и статус-точка
  - `truncate + title` для длинных значений

### 1.5 Versioning / rollback backend
- **Есть:** `ModelVersion` + `VersionManager` + `RollbackService`
  - Снимки (snapshot) создаются и лимитируются
  - Rollback транзакционный с `lockForUpdate()`
  - При rollback создается новая версия с `is_rollback=true` и `rollback_to_version`
  - Observer suspend/resume во время rollback (`MoonTrailObserver`)

### 1.6 DX
- **Есть:**
  - Auto-tracking моделей через конфиг: `auto_track_models` + `tracked_models`
  - `MoonTrailMenuItem::make()` генерирует MenuGroup + подменю по моделям
  - `moontrail:prune` artisan command

### 1.7 Read-only аудит (РЕАЛИЗОВАНО ✅)
- **Есть:** `MoonTrailResource::isCan()` переопределён
  - Блокирует: `CREATE`, `UPDATE`, `DELETE`, `MASS_DELETE`
  - Разрешает: `VIEW_ANY`, `VIEW`
  - Тесты: `tests/Feature/MoonTrailResourceTest.php` подтверждают (6 тестов на способности)

---

## 2) Основные GAP’ы (что не соответствует цели и почему)

### ~~GAP-01. Read-only аудит НЕ гарантирован~~ ✅ ЗАКРЫТ
- **Решение:** `MoonTrailResource::isCan()` возвращает `false` для write-abilities.
- **Тесты:** 6 тестов в `MoonTrailResourceTest.php` проверяют все способности.
- **Код:** `src/Resources/MoonTrailResource.php:47-59`

### GAP-02. Inline diff в Timeline может не загружаться при первом раскрытии
- **Факт:** загрузка diff сейчас завязана на `x-init="showDiff && load()"`, но `showDiff` изначально `false`.
- **Ожидание:** diff должен загружаться **в момент открытия**, а не только при initial state.

### GAP-03. UI MoonTrailResource (Index/Detail) не доведён до уровня Filament Audit Pro
- Не хватает строгой структуры экрана:
  - Чётких карточек/секций: General / Relations / Changes / History
  - Корректной визуализации JSON/array
  - Кликабельных ссылок на Subject/Causer ресурсы (если доступны)
  - Нормального отображения `log_name`

### GAP-04. Фильтры и поиск недостаточны
- Нет:
  - Date range (From/Until)
  - Фильтра по causer
  - Фильтра по subject_id
  - Поиска по `properties`/изменённым значениям

### GAP-05. Политики доступа на rollback отсутствуют
- **Факт:** `RollbackController` не делает `authorize()`/Gate/Policy.
- **Факт:** `HasMoonTrail::isRollbackAllowed()` по умолчанию `true`.
- **Ожидание:** rollback — привилегированное действие.

### GAP-06. UX после rollback
- **Факт:** после rollback — `redirect()->back()` без toast/визуального подтверждения.
- **Ожидание:** toast + обновление истории.

### GAP-07. Интерактивного установщика (wizard) нет
- Нужна команда `moontrail:install` с выбором моделей/ресурсов.

### ~~GAP-08. Зависимость от Tailwind CSS в хост-приложении~~ ✅ ЗАКРЫТ
- **Реализовано:** в `README.md` добавлены обязательные требования к Tailwind/Alpine и пример `content`-путей.
- **Реализовано:** в `MoonTrailServiceProvider::boot()` добавлена проверка `tailwind.config.*` и `Log::warning()` при отсутствии/неполной конфигурации.
- **Управление warning:** `config('moontrail.ui.warn_if_tailwind_missing', true)`.
- **Примечание:** архитектурно пакет остаётся без собственного frontend builder — это ожидаемое поведение для Laravel/MoonShine пакета.

---

## 3) Архитектура UI пакета (пояснение)

### Почему нет frontend builder?

Пакет следует стандартной практике Laravel-пакетов:
- **Blade views** с inline Tailwind классами
- **Сборка** — ответственность хост-приложения
- **Tailwind JIT** из хоста подхватывает классы из views пакета

Это **нормально** и не является ошибкой. Большинство MoonShine-пакетов работают так же.

### Требования к хост-приложению

1. Tailwind CSS должен быть установлен и настроен
2. `content` в `tailwind.config.js` должен включать путь к views пакета:
   ```js
   content: [
       './resources/**/*.blade.php',
       './vendor/tikhomirov/moon-trail/resources/**/*.blade.php',
   ]
   ```
3. Alpine.js (для rollback-confirm modal)

---

## 4) Вывод (что брать в работу)

Приоритет по бизнес-ценности и рискам:
1. ~~**Read-only аудит**~~ ✅ DONE
2. **UI MoonTrailResource (Index/Detail)** — GAP-03
3. **Rollback (Authorization + UX)** — GAP-05, GAP-06
4. **Фильтры/поиск/маскирование** — GAP-04
5. **Инсталлятор** — GAP-07
6. **Документирование Tailwind-зависимости** — GAP-08
7. **Финальная проверка и релизные требования**

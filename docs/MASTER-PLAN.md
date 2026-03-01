# Master Plan: Доработка пакета MoonShine Logs

## 1) Цель
Довести пакет `tikhomirov/moon-trail` до состояния корпоративного продукта уровня **MoonShine Logs**:

- **Визуальный diff** «было → стало» в UI.
- **Версионирование** (snapshots) каждой сущности.
- **Rollback** (откат) до любой версии с логированием факта отката.
- **UI для просмотра истории** (глобальный журнал + история на карточке сущности) с фильтрами/поиском.
- **Security/Compliance:** аудит-лог должен быть **строго read-only**.

UI/UX референс: Filament Audit Pro

- https://filamentphp.com/plugins/arnautdev-audit-pro

Примечание по UI:

- Требуется **функциональное и концептуальное соответствие** (структура экрана, сценарии, читаемость, безопасность).
- Pixel-perfect копирование Filament не требуется.

## 2) Контекст: что уже сделано
Фактический статус текущей реализации и список разрывов фиксируется в документе:

- **[00-AUDIT-STATUS-AND-GAPS.md](00-AUDIT-STATUS-AND-GAPS.md)**

**Ключевые выводы из аудита (обновлено 2026-03-01):**

- ✅ **Read-only аудит реализован** — `MoonTrailResource::isCan()` блокирует write-abilities
- ✅ **Backend ядро работает** — versioning, rollback, observer
- ⚠️ **UI зависит от Tailwind в хосте** — пакет не имеет frontend builder (это нормально, но нужно задокументировать)
- 🔧 **Основной объём доработок** — UI/UX refinements, фильтры/поиск, rollback security/UX, DX (инсталлятор)

## 3) Обязательные условия приёмки проекта (общие)

### 3.1 Проверка в браузере (ручная)
Исполнитель каждого этапа обязан после выполнения задач проверить в браузере:

- `http://localhost:8000/admin/resource/moontrail-resource/index-page`

Проверяются:

- отсутствие ошибок в UI
- корректность фильтров и поиска
- корректность Detail page
- корректная работа diff и rollback

### 3.2 Отсутствие ошибок в логах
После ручной проверки — убедиться, что в логах хост-приложения **нет новых ошибок**:

- `storage/logs/laravel.log` (хост-приложение)

### 3.3 Прохождение CI набора пакета
В корне пакета выполнить:

- `composer ci`

Результат должен быть: **0 ошибок**.

## 4) Разбиение на этапы (удобно брать в работу)

### Этап 0 — GAP baseline (обязательная отправная точка)
- Документ: `00-AUDIT-STATUS-AND-GAPS.md`
- Роль: **тимлид / техлид**
- Результат: согласованный список «что делаем» и «что не трогаем».

### Этап 1 — UI/UX: Activity Log Resource (Index + Detail) refinements
- Документ: **[TZ-01-UI-Redesign.md](TZ-01-UI-Redesign.md)**
- Роль: **MoonShine UI developer** (MoonShine 4, Blade, Tailwind)
- Примечание: read-only уже реализован, этап фокусируется на UI refinements (секции, ссылки, визуализация)

### Этап 2 — DX: Интерактивный установщик
- Документ: **[TZ-02-Interactive-Installer.md](TZ-02-Interactive-Installer.md)**
- Роль: **Laravel backend developer** (Console/Prompts)

### Этап 3 — Фильтры/поиск/маскирование
- Документ: **[TZ-03-Masking-And-Filters.md](TZ-03-Masking-And-Filters.md)**
- Роль: **Laravel backend developer** + **MoonShine UI developer**

### Этап 4 — Rollback (security + UX)
- Документ: **[TZ-04-Rollback-Enhancements.md](TZ-04-Rollback-Enhancements.md)**
- Роль: **Senior Laravel backend developer** (Authorization/Policies) + MoonShine UI

### Этап 5 — Ребрендинг (отдельный этап)
- Документ: **[TZ-06-Rebranding.md](TZ-06-Rebranding.md)**
- Роль: **тимлид / package maintainer** (composer/packagist + docs)

### Этап 6 — Итоговая проверка выполнения всех ТЗ
- Документ: **[TZ-05-Final-Verification.md](TZ-05-Final-Verification.md)**
- Роль: **QA / Tech lead**

## 5) Общие требования к реализации

- **Язык кода:** английский (код/сообщения/строки — как принято в пакете), UI — через локализацию.
- **Strict types:** `declare(strict_types=1);` во всех PHP файлах.
- **Стиль:** Pint (`pint.json`), Rector (`rector.php`).
- **Локализация:** все новые строки добавлять в `lang/en/ui.php` и `lang/ru/ui.php`.
- **Совместимость:** PHP ^8.2, Laravel 11+, MoonShine ^4.8.

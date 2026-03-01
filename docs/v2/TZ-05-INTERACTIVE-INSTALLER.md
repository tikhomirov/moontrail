# TZ-05 — Интерактивный установщик `moontrail:install`

## 0) Краткое текущее состояние

Пакет уже можно подключить вручную (composer + migrate + настройка config + интеграция в ресурсы), но нет единой команды, которая проводит интеграцию по wizard-сценарию. Это повышает риск ошибок и увеличивает время внедрения.

---

## 1) Цель этапа

Сделать «one-command onboarding» для хост-приложения Laravel + MoonShine:
- безопасно и идемпотентно подготовить конфиг/ассеты/миграции;
- помочь выбрать модели для трекинга;
- помочь интегрировать history tab в MoonShine resources;
- исключить риск несанкционированных авто-правок файлов.

---

## 2) Кто реализует

- **Ведущий:** Laravel backend developer.
  - Навыки: Artisan Commands, Laravel Prompts, Filesystem, config management.
- **Соисполнитель:** Senior backend developer.
  - Навыки: безопасный patching/AST, идемпотентные операции.
- **QA:** проверка интерактивного сценария и no-interaction режима.

---

## 3) Область изменений

- `src/Console/Commands/InstallMoonTrailCommand.php` (новый)
- `src/MoonTrailServiceProvider.php` (регистрация команды)
- `composer.json` (если нужно обновить scripts/docs)
- `config/moontrail.php` (доп. ключи по installer)
- `README.md` (раздел Installation Wizard)
- `tests/Feature/InstallCommandTest.php` (новый)
- `tests/Unit/*` (если будет отдельный ConfigUpdater/ResourceScanner)

---

## 4) Команда и интерфейс

## 4.1 Команда

```bash
php artisan moontrail:install
```

## 4.2 Режимы

1. **Interactive (default)** — wizard с вопросами.
2. **Non-interactive** (`--no-interaction`) — безопасные дефолты.

## 4.3 Дополнительные флаги (обязательные)

- `--force` — разрешить overwrite publish-операций при явном подтверждении.
- `--safe` (default=true) — не изменять PHP-файлы ресурсов/моделей.
- `--auto-patch` — включить авто-модификацию resources (только с подтверждением).

---

## 5) Пошаговый сценарий wizard

## Шаг 1 — Проверка окружения

Проверки:
1. установлен ли MoonShine package;
2. доступна ли БД (connection test);
3. текущий env (`production`/не production).

Поведение:
- при проблеме с DB — завершить с ошибкой и подсказкой;
- при production — показать warning и запросить явное подтверждение.

## Шаг 2 — Publish ассетов

Вопросы:
- Publish config?
- Publish views?
- Publish lang?

Команды:
- `vendor:publish --tag=moontrail-config`
- `vendor:publish --tag=moontrail-views`
- `vendor:publish --tag=moontrail-lang`

Требование:
- операция идемпотентна, повторный запуск не падает.

## Шаг 3 — Миграции

Вопрос:
- Run migrations now?

Действие:
- при yes выполнить `artisan migrate --force` (в production только после подтверждения).

## Шаг 4 — Выбор моделей для tracking

### 5.4.1 Источники кандидатов
- классы из `app/Models`;
- системные модели, если существуют:
  - `App\Models\User`
  - `MoonShine\Laravel\Models\MoonshineUser`
  - модель ролей MoonShine (если установлена в проекте)

### 5.4.2 Дефолтно выбранные
По умолчанию preselect:
- user,
- moonshine user,
- roles (если есть).

### 5.4.3 Результат
Записывать выбранные модели в:
- `auto_track_models`
- `tracked_models`

Если конфиг не опубликован:
- сначала предложить publish config;
- при отказе вывести готовый фрагмент для ручной вставки.

## Шаг 5 — Интеграция в MoonShine Resources

### 5.5.1 Сканирование
- каталог: `app/MoonShine/Resources`
- определить связку Resource → Model (`protected string $model`)

### 5.5.2 Выбор
- multi-select ресурсов, куда добавить history tab.

### 5.5.3 Режимы

#### Safe mode (по умолчанию)
- файлы не меняются;
- выводятся точные инструкции по каждому resource:
  1. добавить `use MoonShine\MoonTrail\Traits\WithMoonTrailTab;`
  2. добавить `use WithMoonTrailTab;`
  3. добавить `$this->activityTab()` в `detailFields()`

#### Auto-patch mode (опционально)
- включается только при `--auto-patch` + подтверждение;
- предпочтительно через AST;
- при неоднозначной структуре файла — fallback в safe mode с инструкцией.

## Шаг 6 — Итоговый отчёт

Вывести summary:
- какие publish шаги выполнены;
- выполнены ли миграции;
- какие модели добавлены в tracking;
- какие ресурсы обновлены/требуют ручных действий;
- какие проверки выполнить дальше (URL, logs, composer ci).

---

## 6) UX сценарий (пример)

```text
$ php artisan moontrail:install

✓ MoonShine detected
✓ DB connection ok
⚠ App is in production. Continue? [no]

Publish config? [yes]
Publish views? [no]
Publish lang? [no]

Run migrations now? [yes]

Select models to track:
[x] App\Models\User
[x] MoonShine\Laravel\Models\MoonshineUser
[x] App\Models\Role
[ ] App\Models\Order

Select resources for History tab:
[x] UserResource
[ ] OrderResource

Mode: SAFE (no file modifications)
- Printed patch instructions for UserResource

Done. Next:
1) Open /admin/resource/moontrail-resource/index-page
2) Check storage/logs/laravel.log
3) Run composer ci
```

---

## 7) Нефункциональные требования

- Команда не должна разрушать пользовательский код.
- Любая авто-правка требует явного opt-in.
- Ошибки должны быть человекочитаемыми и с actionable hints.
- Повторный запуск команды безопасен.

---

## 8) Тесты (обязательные)

## 8.1 Feature
- Команда запускается в `--no-interaction` режиме без падений.
- Проверка ветки publish + migrate (mock/artisan spy).
- Проверка вывода инструкций в safe mode.

## 8.2 Unit
- `ModelScanner` корректно находит модели.
- `ConfigUpdater` корректно обновляет массивы моделей.
- `ResourceScanner` корректно определяет ресурсные классы.

## 8.3 Regression
- Повторный запуск не дублирует значения в `auto_track_models/tracked_models`.

---

## 9) Acceptance (DoD)

- Команда `moontrail:install` доступна и работает.
- Сценарий wizard проходит end-to-end без неявных ручных шагов.
- По умолчанию включены дефолтные системные модели (где доступны).
- Safe mode работает без модификации PHP-файлов.
- Auto-patch mode работает только с явным подтверждением.
- Добавлены тесты и `composer ci` зелёный.

---

## 10) Что не входит в этап

- Полный self-healing patcher всех возможных архитектур ресурсов.
- Изменение бизнес-логики rollback/versioning.

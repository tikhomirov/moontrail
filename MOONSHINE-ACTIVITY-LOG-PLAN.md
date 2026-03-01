# MoonShine Activity Log — План разработки пакета

> Полноценная интеграция [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) с MoonShine admin-панелью. Визуальный diff, версионирование, rollback, UI-компоненты.

---

## Оглавление

1. [Обзор и мотивация](#1-обзор-и-мотивация)
2. [Архитектура пакета](#2-архитектура-пакета)
3. [База данных и миграции](#3-база-данных-и-миграции)
4. [Трекинг изменений](#4-трекинг-изменений)
5. [Версионирование и Rollback](#5-версионирование-и-rollback)
6. [MoonShine UI-компоненты](#6-moonshine-ui-компоненты)
7. [API и публичные контракты](#7-api-и-публичные-контракты)
8. [Тестирование](#8-тестирование)
9. [Публикация и документация](#9-публикация-и-документация)
10. [Дорожная карта](#10-дорожная-карта)

---

## 1. Обзор и мотивация

### Проблема

В экосистеме MoonShine существует пакет `moonshine/changelog`, но он:
- Хранит только `states_before` / `states_after` без структурированных метаданных
- Не поддерживает полноценное версионирование с нумерацией
- Восстанавливает записи без валидации и транзакций
- Привязан к `moonshine_user_id` — не работает вне MoonShine-контекста
- Не предоставляет визуальный diff с подсветкой изменённых полей
- Не поддерживает batch-операции и кастомные события

### Решение

Пакет `moonshine/moontrail` поверх `spatie/laravel-activitylog`:
- Использует battle-tested движок Spatie (43M+ скачиваний)
- Добавляет слой версионирования с полными снапшотами модели
- Предоставляет визуальный diff-компонент с подсветкой
- Реализует безопасный rollback с транзакциями и валидацией
- Интегрируется как Tab / Page / standalone компонент

### Отличия от moonshine/changelog

| Возможность | moonshine/changelog | moonshine/moontrail |
|-------------|--------------------|-----------------------|
| Движок | Собственный observer | Spatie Activity Log |
| Метаданные | user_id, states | event, batch_uuid, log_name, properties |
| Версионирование | Нет | Да, с нумерацией |
| Полный снапшот | Нет (только diff) | Да |
| Визуальный diff | Таблица before/after | Side-by-side с подсветкой |
| Rollback | Простой fill+save | Транзакция + валидация + события |
| Работа вне MoonShine | Нет | Да (Spatie ловит всё) |
| Batch-операции | Нет | Да (batch_uuid) |
| Кастомные события | Нет | Да (event column) |
| Фильтрация | Нет | По пользователю, событию, дате |

---

## 2. Архитектура пакета

### 2.1 Структура директорий

```
moontrail/
├── config/
│   └── activity-log.php                    # Конфигурация пакета
├── database/
│   └── migrations/
│       └── 2024_01_01_000001_create_model_versions_table.php
├── lang/
│   ├── en/
│   │   └── ui.php
│   └── ru/
│       └── ui.php
├── resources/
│   └── views/
│       └── components/
│           ├── activity-timeline.blade.php  # Timeline активностей
│           ├── diff-viewer.blade.php        # Визуальный diff
│           ├── version-badge.blade.php      # Бейдж версии
│           └── rollback-confirm.blade.php   # Модалка подтверждения
├── routes/
│   └── activity-log.php                    # Маршруты (rollback, API)
├── src/
│   ├── Components/
│   │   ├── ActivityTimeline.php            # Timeline-компонент
│   │   ├── DiffViewer.php                  # Diff-компонент
│   │   └── ActivityTab.php                 # Tab для встраивания в ресурс
│   ├── Contracts/
│   │   ├── DiffRendererContract.php        # Интерфейс рендера diff
│   │   ├── VersionManagerContract.php      # Интерфейс версионирования
│   │   ├── RollbackStrategyContract.php    # Стратегия отката
│   │   └── ActivityFormatterContract.php   # Форматирование записей
│   ├── Diff/
│   │   ├── DiffComputer.php               # Вычисление diff
│   │   ├── FieldChange.php                # DTO изменения поля
│   │   └── HtmlDiffRenderer.php           # HTML-рендер diff
│   ├── Http/
│   │   └── Controllers/
│   │       ├── RollbackController.php      # Обработка rollback
│   │       └── ActivityController.php      # API для AJAX-запросов
│   ├── Models/
│   │   └── ModelVersion.php               # Версия модели (снапшот)
│   ├── Pages/
│   │   └── MoonTrailPage.php            # Standalone страница истории
│   ├── Resources/
│   │   └── MoonTrailResource.php        # MoonShine Resource для activity_log
│   ├── Traits/
│   │   ├── HasMoonTrail.php             # Trait для моделей
│   │   └── WithMoonTrailTab.php            # Trait для ресурсов
│   ├── Versioning/
│   │   ├── VersionManager.php             # Логика версионирования
│   │   └── RollbackService.php            # Сервис отката
│   ├── Enums/
│   │   └── ChangeType.php                 # created, updated, deleted, restored
│   └── MoonTrailServiceProvider.php      # ServiceProvider
├── tests/
│   ├── Unit/
│   │   ├── DiffComputerTest.php
│   │   ├── VersionManagerTest.php
│   │   └── RollbackServiceTest.php
│   ├── Feature/
│   │   ├── ActivityTrackingTest.php
│   │   ├── RollbackControllerTest.php
│   │   ├── ActivityTimelineComponentTest.php
│   │   └── MoonTrailResourceTest.php
│   ├── Fixtures/
│   │   └── TestPost.php                   # Тестовая модель
│   └── TestCase.php
├── composer.json
├── phpstan.neon.dist
├── LICENSE.md
└── README.md
```

### 2.2 Зависимости

```json
{
    "name": "moonshine/moontrail",
    "description": "Activity log integration with diff viewer, versioning and rollback for MoonShine admin panel",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "spatie/laravel-activitylog": "^4.7",
        "moonshine/laravel": "^4.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "pestphp/pest": "^3.0",
        "phpstan/phpstan": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "MoonShine\\ActivityLog\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MoonShine\\ActivityLog\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MoonShine\\ActivityLog\\MoonTrailServiceProvider"
            ]
        }
    }
}
```

### 2.3 ServiceProvider

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use Illuminate\Support\ServiceProvider;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Diff\HtmlDiffRenderer;
use MoonShine\MoonTrail\Versioning\VersionManager;
use MoonShine\MoonTrail\Versioning\RollbackService;

final class MoonTrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moontrail.php', 'moontrail');

        $this->app->bind(DiffRendererContract::class, HtmlDiffRenderer::class);
        $this->app->bind(VersionManagerContract::class, VersionManager::class);
        $this->app->bind(RollbackStrategyContract::class, RollbackService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'moontrail');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moontrail');
        $this->loadRoutesFrom(__DIR__ . '/../routes/moontrail.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/moontrail.php' => config_path('moontrail.php'),
            ], 'moontrail-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/moontrail'),
            ], 'moontrail-views');

            $this->publishes([
                __DIR__ . '/../lang' => lang_path('vendor/moontrail'),
            ], 'moontrail-lang');
        }
    }
}
```

### 2.4 Конфигурация

```php
<?php
// config/moontrail.php

return [
    /*
    |--------------------------------------------------------------------------
    | Версионирование
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        // Включить автоматическое создание версий при изменении модели
        'enabled' => true,

        // Максимальное количество версий на запись (0 = без ограничений)
        'max_versions' => 50,

        // Стратегия при превышении лимита: 'delete_oldest' | 'prevent'
        'overflow_strategy' => 'delete_oldest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */
    'rollback' => [
        // Требовать подтверждение через модальное окно
        'require_confirmation' => true,

        // Валидировать данные при откате через rules() ресурса
        'validate' => true,

        // Логировать откат как отдельное событие
        'log_rollback_event' => true,

        // Имя события для отката
        'event_name' => 'rolled_back',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        // Количество записей на странице timeline
        'per_page' => 20,

        // Формат даты
        'date_format' => 'd.m.Y H:i:s',

        // Показывать технические поля (id, timestamps)
        'show_technical_fields' => false,

        // Поля, скрытые из diff (глобально)
        'hidden_fields' => ['password', 'remember_token', 'two_factor_secret'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Resource
    |--------------------------------------------------------------------------
    */
    'resource' => [
        // Класс ресурса (можно заменить на кастомный)
        'class' => \MoonShine\MoonTrail\Resources\MoonTrailResource::class,

        // Включить глобальную страницу Activity Log в меню
        'in_menu' => true,

        // Иконка меню
        'menu_icon' => 'heroicons.clock',
    ],
];
```

---

## 3. База данных и миграции

### 3.1 Существующая таблица `activity_log` (Spatie)

Пакет **не модифицирует** таблицу `activity_log`. Используется as-is из Spatie:

```
activity_log
├── id                  bigint unsigned PK
├── log_name            varchar(255) nullable, indexed
├── description         text
├── subject_type        varchar(255) nullable  ─┐ polymorphic
├── subject_id          bigint unsigned nullable ┘
├── event               varchar(255) nullable
├── causer_type         varchar(255) nullable  ─┐ polymorphic
├── causer_id           bigint unsigned nullable ┘
├── properties          json nullable           ← {attributes: {...}, old: {...}}
├── batch_uuid          uuid nullable
├── created_at          timestamp
└── updated_at          timestamp
```

### 3.2 Новая таблица `model_versions`

Хранит полные снапшоты модели для версионирования и rollback:

```php
<?php
// database/migrations/2024_01_01_000001_create_model_versions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();

            // Полиморфная связь с моделью
            $table->morphs('versionable');

            // Номер версии (автоинкремент в рамках конкретной записи)
            $table->unsignedInteger('version');

            // Полный снапшот всех атрибутов модели на момент версии
            $table->json('snapshot');

            // Связь с записью в activity_log (nullable для начальных версий)
            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('activity_log')
                ->nullOnDelete();

            // Кто создал версию
            $table->nullableMorphs('author');

            // Событие (created, updated, rolled_back)
            $table->string('event', 50);

            // Метка: является ли это rollback-версией
            $table->boolean('is_rollback')->default(false);

            // Номер версии, к которой откатились (если is_rollback = true)
            $table->unsignedInteger('rollback_to_version')->nullable();

            $table->timestamps();

            // Уникальность версии в рамках конкретной записи
            $table->unique(['versionable_type', 'versionable_id', 'version']);

            // Индекс для быстрого поиска по модели
            $table->index(['versionable_type', 'versionable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
```

### 3.3 ER-диаграмма

```
┌─────────────────┐       ┌──────────────────┐       ┌─────────────────┐
│   Your Model    │       │   activity_log   │       │ model_versions  │
│─────────────────│       │──────────────────│       │─────────────────│
│ id              │◄──────│ subject_id       │       │ id              │
│ name            │       │ subject_type     │       │ versionable_id  │──► Your Model
│ email           │       │ causer_id ───────│──►    │ versionable_type│
│ ...             │       │ causer_type      │ User  │ version         │
│                 │       │ event            │       │ snapshot (JSON) │
│                 │       │ properties (JSON)│◄──────│ activity_id (FK)│
│                 │       │ batch_uuid       │       │ author_id       │──► User
│                 │       │ log_name         │       │ author_type     │
│                 │       │ created_at       │       │ event           │
└─────────────────┘       └──────────────────┘       │ is_rollback     │
                                                     │ rollback_to_ver │
                                                     │ created_at      │
                                                     └─────────────────┘
```

---

## 4. Трекинг изменений

### 4.1 Trait для модели: `HasMoonTrail`

Обёртка над Spatie `LogsActivity` с дополнительной логикой версионирования:

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use MoonShine\MoonTrail\Models\ModelVersion;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMoonTrail
{
    use LogsActivity;

    // --- Spatie LogsActivity ---

    public function getActivitylogOptions(): LogOptions
    {
        return $this->activityLogOptions();
    }

    /**
     * Override в модели для настройки.
     */
    protected function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event) => class_basename(static::class) . " was {$event}"
            );
    }

    // --- Версионирование ---

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<ModelVersion>
     */
    public function versions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ModelVersion::class, 'versionable')
            ->orderByDesc('version');
    }

    public function latestVersion(): ?\Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(ModelVersion::class, 'versionable')
            ->latestOfMany('version');
    }

    public function currentVersionNumber(): int
    {
        return $this->versions()->max('version') ?? 0;
    }

    /**
     * Поля, исключённые из снапшота версии.
     *
     * @return array<int, string>
     */
    protected function versionExcludedFields(): array
    {
        return ['password', 'remember_token'];
    }

    /**
     * Разрешён ли rollback для этой модели.
     */
    public function isRollbackAllowed(): bool
    {
        return true;
    }
}
```

### 4.2 Observer для версионирования

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use MoonShine\MoonTrail\Contracts\VersionManagerContract;

final class MoonTrailObserver
{
    public function __construct(
        private readonly VersionManagerContract $versionManager,
    ) {}

    public function created(\Illuminate\Database\Eloquent\Model $model): void
    {
        if ($this->shouldVersion($model)) {
            $this->versionManager->createVersion($model, 'created');
        }
    }

    public function updated(\Illuminate\Database\Eloquent\Model $model): void
    {
        if ($this->shouldVersion($model)) {
            $this->versionManager->createVersion($model, 'updated');
        }
    }

    private function shouldVersion(\Illuminate\Database\Eloquent\Model $model): bool
    {
        return config('moontrail.versioning.enabled', true)
            && method_exists($model, 'versions');
    }
}
```

### 4.3 Автоматическая регистрация Observer

ServiceProvider регистрирует observer для всех моделей с `HasMoonTrail`:

```php
// В boot() ServiceProvider
public function boot(): void
{
    // ...

    $this->app->afterResolving('events', function () {
        // Observer регистрируется через trait boot method
        // HasMoonTrail::bootHasMoonTrail() вызывает
        // static::observe(MoonTrailObserver::class)
    });
}
```

Альтернативный подход — регистрация в `bootHasMoonTrail()` внутри trait:

```php
protected static function bootHasMoonTrail(): void
{
    static::observe(app(MoonTrailObserver::class));
}
```

### 4.4 Диаграмма потока данных

```
  Модель::update()
       │
       ▼
  ┌─────────────────────┐
  │  Spatie LogsActivity │  ← Автоматически создаёт запись в activity_log
  │  (trait на модели)   │     с properties: {attributes: {...}, old: {...}}
  └──────────┬──────────┘
             │
             ▼
  ┌─────────────────────┐
  │ MoonTrailObserver  │  ← Перехватывает created/updated события Eloquent
  │                      │
  └──────────┬──────────┘
             │
             ▼
  ┌─────────────────────┐
  │  VersionManager      │  ← Создаёт полный снапшот модели
  │  ::createVersion()   │     в таблице model_versions
  └──────────┬──────────┘
             │
             ▼
  ┌─────────────────────┐
  │  model_versions      │  version=N, snapshot={все атрибуты},
  │  (таблица)           │  activity_id=FK, event='updated'
  └─────────────────────┘
```

---

## 5. Версионирование и Rollback

### 5.1 VersionManager

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

final class VersionManager implements VersionManagerContract
{
    /**
     * Создаёт новую версию модели.
     */
    public function createVersion(
        Model $model,
        string $event,
        ?Activity $activity = null,
    ): ModelVersion;

    /**
     * Получает снапшот конкретной версии.
     */
    public function getVersion(Model $model, int $version): ?ModelVersion;

    /**
     * Вычисляет diff между двумя версиями.
     *
     * @return array<string, FieldChange>
     */
    public function diff(ModelVersion $from, ModelVersion $to): array;

    /**
     * Вычисляет diff между версией и текущим состоянием модели.
     *
     * @return array<string, FieldChange>
     */
    public function diffWithCurrent(ModelVersion $version, Model $model): array;

    /**
     * Применяет лимит версий (удаляет старые).
     */
    public function enforceLimit(Model $model): void;
}
```

### 5.2 RollbackService

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Models\ModelVersion;

final class RollbackService implements RollbackStrategyContract
{
    /**
     * Откатывает модель к указанной версии.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model;
}
```

### 5.3 Алгоритм Rollback

```
  Запрос отката к версии N
           │
           ▼
  ┌───────────────────────────┐
  │ 1. Проверка существования │
  │    версии N для модели    │
  └────────────┬──────────────┘
               │ не найдена → ModelVersionNotFoundException
               ▼
  ┌───────────────────────────┐
  │ 2. Проверка isRollback-   │
  │    Allowed() на модели    │
  └────────────┬──────────────┘
               │ false → RollbackDeniedException
               ▼
  ┌───────────────────────────┐
  │ 3. Получение snapshot     │
  │    из model_versions      │
  └────────────┬──────────────┘
               │
               ▼
  ┌───────────────────────────┐
  │ 4. Валидация snapshot     │
  │    через rules() ресурса  │  ← если config('rollback.validate') === true
  └────────────┬──────────────┘
               │ не прошла → ValidationException
               ▼
  ┌───────────────────────────┐
  │ 5. DB::transaction()      │
  │                           │
  │  a) $model->fill(snapshot)│  ← fillable-only фильтрация
  │  b) $model->save()        │  ← Spatie залогирует как 'updated'
  │  c) VersionManager::      │
  │     createVersion(         │
  │       event: 'rolled_back',│
  │       is_rollback: true,  │
  │       rollback_to: N      │
  │     )                     │
  │                           │
  └────────────┬──────────────┘
               │ исключение → rollback транзакции
               ▼
  ┌───────────────────────────┐
  │ 6. Событие ModelRolledBack│
  │    для расширения         │
  └───────────────────────────┘
```

### 5.4 Обработка конфликтов

| Сценарий | Поведение |
|----------|-----------|
| Модель удалена | `ModelNotFoundException` — rollback невозможен (отдельно реализуется restore для SoftDeletes) |
| Версия не найдена | `ModelVersionNotFoundException` |
| Структура БД изменилась (новые колонки) | Snapshot применяется только к существующим `$fillable` — новые колонки получат значения по умолчанию |
| Валидация не прошла | `ValidationException` — транзакция не начинается |
| Unique constraint violation | Откат транзакции, `RollbackConflictException` с описанием конфликта |
| Concurrent rollback | DB-транзакция + `lockForUpdate()` на модели предотвращают race condition |

### 5.5 DTO изменения поля

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

final readonly class FieldChange
{
    public function __construct(
        public string $field,
        public mixed $oldValue,
        public mixed $newValue,
        public string $type, // 'added' | 'removed' | 'modified' | 'unchanged'
    ) {}
}
```

---

## 6. MoonShine UI-компоненты

### 6.1 Обзор компонентов

```
┌─────────────────────────────────────────────────────────────────┐
│                    Resource DetailPage / FormPage                │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ ActivityTab (компонент-вкладка)                            │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │ ActivityTimeline                                     │  │  │
│  │  │                                                     │  │  │
│  │  │  ● v5 — 28.02.2026 14:30 — Admin updated          │  │  │
│  │  │    [Просмотр diff] [Откатить]                      │  │  │
│  │  │                                                     │  │  │
│  │  │  ● v4 — 27.02.2026 10:15 — Admin updated          │  │  │
│  │  │    [Просмотр diff] [Откатить]                      │  │  │
│  │  │                                                     │  │  │
│  │  │  ● v3 — 26.02.2026 09:00 — Manager rolled back →v1│  │  │
│  │  │    [Просмотр diff] [Откатить]                      │  │  │
│  │  │                                                     │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │ DiffViewer (раскрывается при клике)                  │  │  │
│  │  │                                                     │  │  │
│  │  │  Поле       │  Было           │  Стало             │  │  │
│  │  │  ──────────────────────────────────────────────     │  │  │
│  │  │  name       │  "John"         │  "John Doe"    ●   │  │  │
│  │  │  email      │  "old@mail.com" │  "new@mail.com"●   │  │  │
│  │  │  role       │  —              │  —                  │  │  │
│  │  │                                                     │  │  │
│  │  │  ● = изменено                                       │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 ActivityTimeline — timeline активностей

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use Closure;
use MoonShine\Core\Traits\HasResource;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Traits\WithLabel;

/**
 * @method static static make(Closure|string $label, ModelResource $resource)
 */
final class ActivityTimeline extends MoonShineComponent
{
    use HasResource;
    use WithLabel;

    protected string $view = 'moontrail::components.activity-timeline';

    protected int $limit = 20;
    protected bool $showDiff = true;
    protected bool $showRollback = true;

    public function __construct(
        Closure|string $label,
        ModelResource $resource,
    ) {
        parent::__construct();

        $this->setLabel($label);
        $this->setResource($resource);
    }

    // Fluent API
    public function limit(int $limit): static { /* ... */ }
    public function withoutDiff(): static { /* ... */ }
    public function withoutRollback(): static { /* ... */ }

    protected function viewData(): array
    {
        return [
            'label' => $this->getLabel(),
            'activities' => $this->getActivities(),
            'versions' => $this->getVersions(),
            'showDiff' => $this->showDiff,
            'showRollback' => $this->showRollback,
        ];
    }
}
```

### 6.3 DiffViewer — визуальный diff

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * Визуализация изменений между двумя состояниями модели.
 *
 * @method static static make(array $changes)
 */
final class DiffViewer extends MoonShineComponent
{
    protected string $view = 'moontrail::components.diff-viewer';

    public function __construct(
        /** @var array<string, FieldChange> */
        protected array $changes,
        protected bool $compactMode = false,
    ) {
        parent::__construct();
    }

    // Fluent API
    public function compact(): static { /* ... */ }

    /** Фильтрация: показать только изменённые поля */
    public function onlyChanged(): static { /* ... */ }

    protected function viewData(): array
    {
        return [
            'changes' => $this->changes,
            'compact' => $this->compactMode,
        ];
    }
}
```

### 6.4 ActivityTab — вкладка для ресурса

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use MoonShine\UI\Components\Tabs\Tab;

/**
 * Готовая вкладка для встраивания в DetailPage / FormPage ресурса.
 *
 * Использование:
 *   Tab::make('History', ActivityTab::make($this->getResource()))
 */
final class ActivityTab extends MoonShineComponent
{
    protected string $view = 'moontrail::components.activity-tab';

    public function __construct(
        protected ModelResource $resource,
        protected int $limit = 20,
        protected bool $showRollback = true,
    ) {
        parent::__construct();
    }

    // Внутри содержит ActivityTimeline + DiffViewer
    protected function viewData(): array
    {
        $timeline = ActivityTimeline::make(
            __('moontrail::ui.history'),
            $this->resource,
        )->limit($this->limit);

        if (! $this->showRollback) {
            $timeline->withoutRollback();
        }

        return [
            'timeline' => $timeline,
        ];
    }
}
```

### 6.5 MoonTrailPage — standalone страница

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use MoonShine\Crud\Pages\Page;

/**
 * Глобальная страница Activity Log — показывает все действия в системе.
 * Регистрируется в меню через конфигурацию.
 */
final class MoonTrailPage extends Page
{
    public function getTitle(): string
    {
        return __('moontrail::ui.activity_log');
    }

    // Фильтры: по пользователю, модели, событию, дате
    // Таблица с пагинацией
    // Клик по записи раскрывает DiffViewer
    protected function components(): iterable { /* ... */ }
}
```

### 6.6 MoonTrailResource — MoonShine Resource

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Resources;

use MoonShine\CRUD\Resources\ModelResource;
use Spatie\Activitylog\Models\Activity;

/**
 * MoonShine Resource для просмотра глобального лога активности.
 * Используется как standalone страница или встраивается в меню.
 */
final class MoonTrailResource extends ModelResource
{
    protected string $model = Activity::class;
    protected string $title = 'Activity Log';
    protected string $column = 'id';
    protected string $sortColumn = 'created_at';
    protected string $sortDirection = 'desc';

    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Event', 'event'),
            Text::make('Description', 'description'),
            Text::make('Subject', formatted: fn (Activity $a) =>
                $a->subject_type
                    ? class_basename($a->subject_type) . ' #' . $a->subject_id
                    : '—'
            ),
            Text::make('Causer', formatted: fn (Activity $a) =>
                $a->causer?->name ?? '—'
            ),
            Preview::make('Changes', formatted: fn (Activity $a) =>
                DiffViewer::make(
                    DiffComputer::fromActivityProperties($a->properties)
                )->compact()
            ),
            Date::make('Date', 'created_at'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Event', 'event')
                ->options(['created', 'updated', 'deleted', 'rolled_back']),
            Text::make('Subject Type', 'subject_type'),
            Date::make('From', 'created_at'),
        ];
    }

    protected function search(): array
    {
        return ['description', 'subject_type', 'properties'];
    }
}
```

### 6.7 Trait для Resource: `WithMoonTrailTab`

Упрощённое подключение к существующим ресурсам:

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

use MoonShine\MoonTrail\Components\ActivityTab;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Tabs\Tab;

/**
 * Добавить в ModelResource для автоматического отображения
 * вкладки с историей изменений на Detail/Form страницах.
 *
 * Использование:
 *   class PostResource extends ModelResource
 *   {
 *       use WithMoonTrailTab;
 *   }
 */
trait WithMoonTrailTab
{
    protected function activityTabLabel(): string
    {
        return __('moontrail::ui.history');
    }

    protected function activityTabLimit(): int
    {
        return 20;
    }

    protected function activityTabShowRollback(): bool
    {
        return true;
    }

    /**
     * Возвращает готовый Tab-компонент.
     * Можно добавить в bottomLayer() или в tabs().
     */
    protected function activityTab(): ComponentContract
    {
        return Tab::make($this->activityTabLabel(), [
            ActivityTab::make(
                resource: $this,
                limit: $this->activityTabLimit(),
                showRollback: $this->activityTabShowRollback(),
            ),
        ]);
    }
}
```

### 6.8 Пример интеграции в ресурс

```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use MoonShine\MoonTrail\Traits\WithMoonTrailTab;
use MoonShine\CRUD\Resources\ModelResource;

final class PostResource extends ModelResource
{
    use WithMoonTrailTab;

    // Вариант 1: Через bottomLayer на DetailPage
    protected function detailPageBottomLayer(): array
    {
        return [
            ...parent::detailPageBottomLayer(),
            $this->activityTab(),
        ];
    }

    // Вариант 2: Через onLoad() в tabs
    protected function onLoad(): void
    {
        $this->getDetailPage()->pushToLayer(
            Layer::BOTTOM,
            $this->activityTab(),
        );
    }
}
```

---

## 7. API и публичные контракты

### 7.1 Контракты (Interfaces)

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

interface VersionManagerContract
{
    public function createVersion(Model $model, string $event, ?Activity $activity = null): ModelVersion;

    public function getVersion(Model $model, int $version): ?ModelVersion;

    /** @return array<string, FieldChange> */
    public function diff(ModelVersion $from, ModelVersion $to): array;

    /** @return array<string, FieldChange> */
    public function diffWithCurrent(ModelVersion $version, Model $model): array;

    public function enforceLimit(Model $model): void;
}
```

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Exceptions\RollbackDeniedException;

interface RollbackStrategyContract
{
    /**
     * @throws ValidationException
     * @throws RollbackConflictException
     * @throws RollbackDeniedException
     */
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model;
}
```

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use MoonShine\MoonTrail\Diff\FieldChange;

interface DiffRendererContract
{
    /**
     * @param array<string, FieldChange> $changes
     */
    public function render(array $changes): string;
}
```

```php
<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Spatie\Activitylog\Models\Activity;

interface ActivityFormatterContract
{
    /**
     * Форматирует запись Activity для отображения в UI.
     *
     * @return array{description: string, icon: string, color: string}
     */
    public function format(Activity $activity): array;
}
```

### 7.2 События (Events)

```php
// Событие до rollback — можно отменить
final readonly class ModelRollingBack
{
    public function __construct(
        public Model $model,
        public int $targetVersion,
        public ModelVersion $version,
    ) {}
}

// Событие после rollback
final readonly class ModelRolledBack
{
    public function __construct(
        public Model $model,
        public int $fromVersion,
        public int $toVersion,
        public ModelVersion $newVersion,
    ) {}
}

// Событие при создании версии
final readonly class VersionCreated
{
    public function __construct(
        public Model $model,
        public ModelVersion $version,
    ) {}
}
```

### 7.3 Исключения

```php
namespace MoonShine\MoonTrail\Exceptions;

final class ModelVersionNotFoundException extends \RuntimeException {}
final class RollbackDeniedException extends \RuntimeException {}
final class RollbackConflictException extends \RuntimeException {}
final class VersionLimitExceededException extends \RuntimeException {}
```

### 7.4 Точки расширения

| Что расширять | Как |
|---------------|-----|
| Рендер diff | Реализовать `DiffRendererContract`, привязать в ServiceProvider |
| Стратегия rollback | Реализовать `RollbackStrategyContract` |
| Форматирование записей | Реализовать `ActivityFormatterContract` |
| Поля в diff | Override `versionExcludedFields()` в модели |
| LogOptions | Override `activityLogOptions()` в модели |
| Разрешение rollback | Override `isRollbackAllowed()` в модели |
| Лимит версий | `config('moontrail.versioning.max_versions')` |
| Кастомный Resource | `config('moontrail.resource.class')` |
| Blade-шаблоны | `php artisan vendor:publish --tag=moontrail-views` |

---

## 8. Тестирование

### 8.1 Матрица тестов

| Категория | Файл | Покрытие |
|-----------|-------|----------|
| **Unit** | `DiffComputerTest.php` | Вычисление diff между двумя массивами атрибутов |
| **Unit** | `FieldChangeTest.php` | DTO корректно определяет тип изменения |
| **Unit** | `VersionManagerTest.php` | Создание версий, нумерация, лимиты |
| **Unit** | `RollbackServiceTest.php` | Логика отката, валидация, транзакции |
| **Feature** | `ActivityTrackingTest.php` | Полный цикл: create → update → delete |
| **Feature** | `VersioningTest.php` | Создание и получение версий через модель |
| **Feature** | `RollbackControllerTest.php` | HTTP-тесты rollback endpoint |
| **Feature** | `ActivityTimelineComponentTest.php` | Рендер timeline-компонента |
| **Feature** | `DiffViewerComponentTest.php` | Рендер diff-компонента |
| **Feature** | `MoonTrailResourceTest.php` | Index, фильтрация, поиск |
| **Feature** | `WithMoonTrailTabTest.php` | Trait интеграция с ресурсом |

### 8.2 Примеры тестов

```php
<?php
// tests/Unit/DiffComputerTest.php

use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Diff\FieldChange;

test('detects modified fields', function () {
    $old = ['name' => 'John', 'email' => 'john@example.com'];
    $new = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $changes = DiffComputer::compute($old, $new);

    expect($changes)->toHaveKey('name')
        ->and($changes['name']->type)->toBe('modified')
        ->and($changes['name']->oldValue)->toBe('John')
        ->and($changes['name']->newValue)->toBe('John Doe');
});

test('detects added fields', function () {
    $old = ['name' => 'John'];
    $new = ['name' => 'John', 'role' => 'admin'];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['role']->type)->toBe('added');
});

test('detects removed fields', function () {
    $old = ['name' => 'John', 'role' => 'admin'];
    $new = ['name' => 'John'];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['role']->type)->toBe('removed');
});

test('handles nested JSON values', function () {
    $old = ['settings' => ['theme' => 'light', 'lang' => 'en']];
    $new = ['settings' => ['theme' => 'dark', 'lang' => 'en']];

    $changes = DiffComputer::compute($old, $new);

    expect($changes)->toHaveKey('settings')
        ->and($changes['settings']->type)->toBe('modified');
});

test('returns empty for identical data', function () {
    $data = ['name' => 'John', 'email' => 'john@example.com'];

    $changes = DiffComputer::compute($data, $data);
    $modified = array_filter($changes, fn (FieldChange $c) => $c->type !== 'unchanged');

    expect($modified)->toBeEmpty();
});
```

```php
<?php
// tests/Unit/VersionManagerTest.php

use MoonShine\MoonTrail\Versioning\VersionManager;
use MoonShine\MoonTrail\Models\ModelVersion;

test('creates first version with number 1', function () {
    $post = createTestPost(['name' => 'Test']);

    $manager = app(VersionManager::class);
    $version = $manager->createVersion($post, 'created');

    expect($version->version)->toBe(1)
        ->and($version->event)->toBe('created')
        ->and($version->snapshot)->toBe($post->attributesToArray());
});

test('increments version number', function () {
    $post = createTestPost(['name' => 'Test']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    $post->update(['name' => 'Updated']);
    $v2 = $manager->createVersion($post, 'updated');

    expect($v2->version)->toBe(2);
});

test('enforces max version limit', function () {
    config(['moontrail.versioning.max_versions' => 3]);

    $post = createTestPost(['name' => 'v1']);
    $manager = app(VersionManager::class);

    foreach (range(1, 5) as $i) {
        $post->update(['name' => "v{$i}"]);
        $manager->createVersion($post, 'updated');
    }

    $manager->enforceLimit($post);

    expect($post->versions()->count())->toBe(3);
});

test('computes diff between two versions', function () {
    $post = createTestPost(['name' => 'Old', 'email' => 'same@mail.com']);
    $manager = app(VersionManager::class);

    $v1 = $manager->createVersion($post, 'created');

    $post->update(['name' => 'New']);
    $v2 = $manager->createVersion($post, 'updated');

    $diff = $manager->diff($v1, $v2);

    expect($diff['name']->type)->toBe('modified')
        ->and($diff['name']->oldValue)->toBe('Old')
        ->and($diff['name']->newValue)->toBe('New');
});
```

```php
<?php
// tests/Feature/RollbackControllerTest.php

use MoonShine\MoonTrail\Models\ModelVersion;

test('rollback restores model to target version', function () {
    $post = createTestPost(['name' => 'Original', 'body' => 'Content']);

    // v1: created
    // v2: updated
    $post->update(['name' => 'Modified', 'body' => 'New content']);

    $this->post(route('moonshine.moontrail.rollback', [
        'modelVersion' => $post->versions()->where('version', 1)->first()->id,
        'resourceItem' => $post->id,
    ]))->assertRedirect();

    $post->refresh();
    expect($post->name)->toBe('Original')
        ->and($post->body)->toBe('Content');
});

test('rollback creates new version with is_rollback flag', function () {
    $post = createTestPost(['name' => 'Original']);
    $post->update(['name' => 'Modified']);

    $this->post(route('moonshine.moontrail.rollback', [
        'modelVersion' => $post->versions()->where('version', 1)->first()->id,
        'resourceItem' => $post->id,
    ]));

    $latest = $post->versions()->first();

    expect($latest->is_rollback)->toBeTrue()
        ->and($latest->rollback_to_version)->toBe(1);
});

test('rollback validates data when config enabled', function () {
    config(['moontrail.rollback.validate' => true]);

    $post = createTestPost(['name' => 'Valid', 'email' => 'valid@mail.com']);
    $post->update(['name' => '', 'email' => 'invalid']); // Bad data forced

    // При откате к версии с невалидными данными — ошибка
    // (зависит от rules() ресурса)
});

test('rollback is wrapped in transaction', function () {
    // Тест через mock: при исключении внутри транзакции
    // модель не изменяется
});

test('rollback denied when isRollbackAllowed returns false', function () {
    // Модель с isRollbackAllowed() => false
    // Ожидаем 403
});
```

```php
<?php
// tests/Feature/ActivityTimelineComponentTest.php

test('timeline renders activity entries', function () {
    $post = createTestPost(['name' => 'Test']);
    $post->update(['name' => 'Updated']);

    $component = ActivityTimeline::make('History', $this->postResource);
    $html = $component->render()->toHtml();

    expect($html)
        ->toContain('Updated')
        ->toContain('created')
        ->toContain('updated');
});

test('timeline respects limit', function () {
    $post = createTestPost(['name' => 'Test']);

    foreach (range(1, 10) as $i) {
        $post->update(['name' => "Update {$i}"]);
    }

    $component = ActivityTimeline::make('History', $this->postResource)->limit(3);

    // Проверяем что показано только 3 записи
});

test('timeline hides rollback button when configured', function () {
    $component = ActivityTimeline::make('History', $this->postResource)
        ->withoutRollback();

    $html = $component->render()->toHtml();

    expect($html)->not->toContain('rollback');
});
```

### 8.3 Тестовые фикстуры

```php
<?php
// tests/Fixtures/TestPost.php

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Traits\HasMoonTrail;

final class TestPost extends Model
{
    use HasMoonTrail;

    protected $table = 'test_posts';
    protected $fillable = ['name', 'body', 'email'];

    protected function activityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    protected function versionExcludedFields(): array
    {
        return ['remember_token'];
    }
}
```

```php
<?php
// tests/TestCase.php

namespace MoonShine\MoonTrail\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use MoonShine\MoonTrail\MoonTrailServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ActivitylogServiceProvider::class,
            MoonTrailServiceProvider::class,
            // MoonShine providers...
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        // create test_posts table migration
    }
}
```

---

## 9. Публикация и документация

### 9.1 Packagist

**Имя пакета:** `moonshine/moontrail`

**Шаги публикации:**
1. Создать GitHub-репозиторий `moonshine-software/moontrail`
2. Настроить GitHub Actions (CI):
   - PHP 8.2, 8.3, 8.4
   - Laravel 10, 11, 12
   - `composer test` (Pest)
   - `composer test:types` (PHPStan)
   - `composer cs:fix --dry-run`
3. Создать release tag (semver: `1.0.0`)
4. Зарегистрировать на Packagist.org
5. Настроить webhook для автообновления

### 9.2 GitHub Actions CI

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.2, 8.3, 8.4]
        laravel: [10.*, 11.*, 12.*]
        exclude:
          - php: 8.2
            laravel: 12.*

    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install --no-interaction
      - run: composer test
      - run: composer test:types
```

### 9.3 Структура документации (README.md)

```
README.md
├── Badges (CI, Packagist, License)
├── Features
├── Requirements
├── Installation
│   ├── composer require
│   ├── php artisan migrate
│   └── Publish config (optional)
├── Quick Start
│   ├── 1. Add trait to model
│   ├── 2. Add trait to resource
│   └── 3. Done — видим вкладку History
├── Configuration
│   ├── Versioning
│   ├── Rollback
│   └── UI
├── Usage
│   ├── Model setup (HasMoonTrail trait)
│   ├── Resource integration (WithMoonTrailTab trait)
│   ├── Standalone page (MoonTrailResource)
│   ├── Custom diff renderer
│   └── Programmatic rollback
├── Advanced
│   ├── Custom LogOptions
│   ├── Extending contracts
│   ├── Events & Listeners
│   ├── Batch operations
│   └── SoftDeletes support
├── API Reference
├── Testing
├── Changelog
├── Contributing
└── License
```

### 9.4 Пример Quick Start в README

````markdown
## Quick Start

### 1. Установка

```bash
composer require moonshine/moontrail
php artisan migrate
```

### 2. Добавьте trait к модели

```php
use MoonShine\MoonTrail\Traits\HasMoonTrail;

class Post extends Model
{
    use HasMoonTrail;
}
```

### 3. Добавьте trait к ресурсу

```php
use MoonShine\MoonTrail\Traits\WithMoonTrailTab;

class PostResource extends ModelResource
{
    use WithMoonTrailTab;

    protected function onLoad(): void
    {
        $this->getDetailPage()->pushToLayer(
            Layer::BOTTOM,
            $this->activityTab(),
        );
    }
}
```

Готово! На странице детального просмотра появится вкладка с историей изменений,
визуальным diff и кнопкой отката.
````

---

## 10. Дорожная карта

### Этап 1 — Фундамент (1–2 недели)

**Задачи:**
- [x] Инициализация пакета: `composer.json`, ServiceProvider, конфигурация
- [x] Миграция `model_versions`
- [x] `HasMoonTrail` trait (обёртка над Spatie `LogsActivity`)
- [x] `ModelVersion` Eloquent-модель
- [x] `VersionManager` — создание версий, нумерация, лимиты
- [x] `DiffComputer` — вычисление diff между двумя массивами
- [x] `FieldChange` DTO
- [x] Unit-тесты: DiffComputer, VersionManager

**Критерии готовности:**
- `HasMoonTrail` trait корректно логирует в `activity_log` + создаёт записи в `model_versions`
- `DiffComputer::compute()` возвращает корректный diff
- Версии нумеруются инкрементально, лимит работает
- Все unit-тесты проходят

---

### Этап 2 — Rollback (1 неделя)

**Задачи:**
- [x] `RollbackService` — логика отката с транзакцией
- [x] `RollbackController` — HTTP endpoint
- [x] Маршруты (`routes/moontrail.php`)
- [x] Валидация при откате
- [ ] Конфликт-детекция (unique constraints, deleted models)
- [ ] Events: `ModelRollingBack`, `ModelRolledBack`
- [x] Exceptions: `ModelVersionNotFoundException`, `RollbackDeniedException`, `RollbackConflictException`
- [x] Feature-тесты: RollbackController, edge cases

**Критерии готовности:**
- Rollback восстанавливает модель к целевой версии
- Создаётся новая версия с `is_rollback = true`
- Транзакция откатывается при ошибке — модель не повреждена
- Валидация блокирует откат к невалидному состоянию
- Feature-тесты покрывают happy path и edge cases

---

### Этап 3 — UI-компоненты (1–2 недели)

**Задачи:**
- [x] `ActivityTimeline` — компонент timeline
- [x] `DiffViewer` — компонент визуального diff
- [x] `ActivityTab` — готовая вкладка для ресурса
- [x] `WithMoonTrailTab` — trait для ресурса
- [x] Blade-шаблоны: timeline, diff-viewer, rollback-confirm, version-badge
- [x] Переводы: `en/ui.php`, `ru/ui.php`
- [x] Feature-тесты: рендер компонентов

**Критерии готовности:**
- Timeline корректно отображает историю изменений
- DiffViewer подсвечивает изменённые поля
- Кнопка Rollback открывает модалку подтверждения и выполняет откат
- Blade-шаблоны публикуемы и кастомизируемы
- Компоненты работают в Detail и Form страницах

---

### Этап 4 — Resource и глобальная страница (1 неделя)

**Задачи:**
- [x] `MoonTrailResource` — MoonShine Resource для `activity_log`
- [x] `MoonTrailPage` — standalone страница
- [x] Фильтры: по пользователю, типу события, модели, дате
- [x] Пагинация, поиск
- [x] Интеграция в меню MoonShine через конфигурацию
- [x] Feature-тесты: индекс, фильтрация, поиск

**Критерии готовности:**
- Глобальная страница Activity Log доступна в меню
- Фильтры и поиск работают корректно
- Клик по записи показывает DiffViewer
- Пагинация работает для больших объёмов данных

---

### Этап 5 — Полировка и публикация (1 неделя)

**Задачи:**
- [x] PHPStan на уровне 6+
- [x] PHP-CS-Fixer — единый стиль (Laravel Pint)
- [ ] README.md с полной документацией
- [x] CHANGELOG.md
- [x] LICENSE.md
- [ ] GitHub Actions CI (матрица PHP × Laravel)
- [x] Финальный ревью всех контрактов
- [ ] Публикация на Packagist

**Критерии готовности:**
- `composer test` — все тесты проходят
- `composer test:types` — PHPStan без ошибок
- README содержит Quick Start, Configuration, Advanced Usage, API Reference
- CI зелёный на PHP 8.2/8.3/8.4 × Laravel 10/11/12
- Пакет доступен через `composer require moonshine/moontrail`

---

### Этап 6 (Future) — Расширенные возможности

| Возможность | Описание |
|-------------|----------|
| SoftDeletes restore | Откат удалённых записей через `restore()` |
| Diff для JSON-полей | Глубокий diff вложенных JSON-структур |
| Diff для связей | Показ изменений в `BelongsToMany`, `HasMany` |
| Export истории | Выгрузка в CSV/PDF |
| Webhook при изменении | Уведомления в Slack/Telegram |
| Permissions per field | Скрытие определённых полей в diff по ролям |
| Сравнение версий | Side-by-side сравнение двух произвольных версий |
| Bulk rollback | Откат нескольких записей одной batch-операции |

---

## Приложение: Сводка файлов пакета

| Файл | Назначение | Этап |
|------|------------|------|
| `src/MoonTrailServiceProvider.php` | Регистрация сервисов, миграций, views | 1 |
| `config/moontrail.php` | Конфигурация пакета | 1 |
| `database/migrations/*_create_model_versions_table.php` | Миграция таблицы версий | 1 |
| `src/Traits/HasMoonTrail.php` | Trait для моделей | 1 |
| `src/Models/ModelVersion.php` | Eloquent-модель версии | 1 |
| `src/Versioning/VersionManager.php` | Управление версиями | 1 |
| `src/Diff/DiffComputer.php` | Вычисление diff | 1 |
| `src/Diff/FieldChange.php` | DTO изменения поля | 1 |
| `src/Diff/HtmlDiffRenderer.php` | HTML-рендер diff | 3 |
| `src/Versioning/RollbackService.php` | Сервис отката | 2 |
| `src/Http/Controllers/RollbackController.php` | HTTP endpoint отката | 2 |
| `routes/moontrail.php` | Маршруты пакета | 2 |
| `src/Enums/ChangeType.php` | Enum типов изменений | 1 |
| `src/Components/ActivityTimeline.php` | Timeline-компонент | 3 |
| `src/Components/DiffViewer.php` | Diff-компонент | 3 |
| `src/Components/ActivityTab.php` | Tab-компонент | 3 |
| `src/Traits/WithMoonTrailTab.php` | Trait для ресурсов | 3 |
| `src/Resources/MoonTrailResource.php` | MoonShine Resource | 4 |
| `src/Pages/MoonTrailPage.php` | Standalone страница | 4 |
| `src/Contracts/VersionManagerContract.php` | Интерфейс | 1 |
| `src/Contracts/RollbackStrategyContract.php` | Интерфейс | 2 |
| `src/Contracts/DiffRendererContract.php` | Интерфейс | 3 |
| `src/Contracts/ActivityFormatterContract.php` | Интерфейс | 3 |
| `resources/views/components/*.blade.php` | Blade-шаблоны | 3 |
| `lang/{en,ru}/ui.php` | Переводы | 3 |
| `tests/Unit/*.php` | Unit-тесты | 1–2 |
| `tests/Feature/*.php` | Feature-тесты | 2–4 |

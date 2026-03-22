# MoonTrail для MoonShine

<p align="center">
  <img src="resources/assets/moontrail-logo.svg" alt="Логотип MoonTrail" width="220" />
</p>

Язык: [English](README.md) · **Русский**

Пакет: `tikhomirov/moontrail`

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%2B-red)](https://laravel.com)
[![MoonShine](https://img.shields.io/badge/MoonShine-4.8%2B-purple)](https://moonshine-laravel.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE.md)

**Кратко:** расширенное логирование для MoonShine с историей изменений, визуальным diff, версионированием моделей и безопасным rollback.

**Полное описание:** MoonTrail — это полноценный слой аудита для админ-панелей MoonShine. Пакет предоставляет структурированную историю активности, построчный side-by-side diff, снапшоты версий на основе полных атрибутов и транзакционный rollback с полной трассируемостью аудита. Поддерживает гибридное логирование: Spatie Activity Log, нативный database-логгер или кастомная реализация через контракт.

> Этот релиз использует **strict breaking rename**:
> только `MoonShine\\MoonTrail\\*`, ключ/файл конфигурации `moontrail` и команды `moontrail:*`.

---

## Зачем этот пакет?

MoonShine даёт сильный UI для админки, а Spatie Activity Log фиксирует события моделей.
**MoonTrail закрывает разрыв** — даёт администраторам наглядную и интерактивную историю изменений с возможностью сравнивать версии и откатывать ошибки.

Для команд и корпоративной разработки это означает:
- Полный **audit trail** с визуальными diff для комплаенса и ответственности
- **Откат в один клик** с подтверждением и транзакционной безопасностью
- **Timeline UI** прямо на detail-странице ресурса MoonShine
- **Минимальная настройка** — добавили trait, и всё работает

---

## Возможности

| Возможность | Описание |
|---|---|
| **Visual Diff Viewer** | Цветное side-by-side сравнение изменений полей (добавлено / изменено / удалено) |
| **Model Versioning** | Автосоздание полных снапшотов атрибутов при create, update, delete и restore |
| **Transactional Rollback** | Восстановление модели к любой версии с row-level locking и валидацией |
| **Timeline Component** | Хронологическая история с датами, авторами, бейджами событий и inline diff |
| **Rollback Confirmation** | Alpine.js модалка с временем снапшота и предупреждением перед откатом |
| **Rollback Authorization** | Secure-by-default: rollback доступен только при явном разрешении |
| **Activity Log Resource** | Глобальный ресурс MoonShine с фильтрами, поиском и пагинацией |
| **Auto-Tracking** | Отслеживание сторонних моделей (например `MoonshineUser`) через config — без trait |
| **Menu Integration** | Хелпер `MoonTrailMenuItem::make()` для подключения в меню одной строкой |
| **Prune Command** | `moontrail:prune` с опциями `--days`, `--model`, `--versions-only` |
| **Extensible** | Можно подменять DiffRenderer, VersionManager, RollbackStrategy, ActivityFormatter через IoC |
| **Localized** | Включены переводы EN и RU |
| **Dark Mode** | Полная поддержка тёмной темы во всех UI-компонентах |

---

## Требования

- **PHP** 8.2+
- **Laravel** 11+
- **MoonShine** 4.8+
- **spatie/laravel-activitylog** 4.7+ (ставится автоматически)
- **Tailwind CSS** в host-приложении (нужен для стилей UI пакета)
- **Alpine.js** в host-приложении (нужен для rollback-модалки и JSON expand/copy)

### Требования к frontend в host-приложении (важно)

Пакет следует типичному подходу Laravel-пакетов:
- Blade-шаблоны используют inline-классы Tailwind
- Внутри пакета нет собственного frontend builder (`package.json`, Vite, Tailwind pipeline)
- Компиляция CSS — ответственность host-приложения

Добавьте пути пакета в `content` вашего `tailwind.config.*`:

```js
content: [
    './resources/**/*.blade.php',
    './app/**/*.php',
    './vendor/tikhomirov/moontrail/resources/**/*.blade.php',
    './vendor/tikhomirov/moontrail/src/**/*.php',
]
```

Если этих путей нет, элементы UI (бейджи, секции, timeline, diff-таблица) могут рендериться без корректных стилей.

---

## Установка

```bash
composer require tikhomirov/moontrail
php artisan migrate
```

Пакет подключается через Laravel package discovery. Ручная регистрация провайдера не нужна.

## Переход после ребрендинга

Если обновляетесь с исходных координат `tikhomirov/moon-trail`:

- Обновите `composer.json` на `tikhomirov/moontrail`;
- Используйте namespace `MoonShine\\MoonTrail\\...`;
- Используйте конфиг `moontrail`.

Подробный чеклист: `docs/v2/UPGRADE-GUIDE-REBRANDING.md`.

### Мастер установки (рекомендуется)

```bash
php artisan moontrail:install
```

Мастер умеет:
- проверить окружение (MoonShine + DB connection);
- опубликовать config/views/lang/assets;
- запустить миграции;
- выбрать отслеживаемые модели и обновить `auto_track_models` + `tracked_models`;
- вывести безопасные инструкции по добавлению `WithMoonTrailTab` в нужные ресурсы.

Флаги:

```bash
# принудительная перезапись vendor:publish
php artisan moontrail:install --force

# безопасный режим (по умолчанию): без модификации PHP-файлов
php artisan moontrail:install --safe=true

# режим auto-patch для ресурсов (с явным подтверждением)
php artisan moontrail:install --auto-patch

# неинтерактивный режим (берёт installer.non_interactive из config)
php artisan moontrail:install --no-interaction
```

> **Production:** установщик определяет `APP_ENV=production` и просит явное подтверждение перед миграциями и patch ресурсов.
> Передайте `--no-interaction`, чтобы убрать промпты (предварительно проверьте defaults в config).
>
> **Нет config:** если `config/moontrail.php` ещё не опубликован, установщик предложит сделать это автоматически перед продолжением.

### Публикация ассетов (опционально)

```bash
# Конфигурация
php artisan vendor:publish --tag=moontrail-config

# Blade views (для кастомизации)
php artisan vendor:publish --tag=moontrail-views

# Переводы
php artisan vendor:publish --tag=moontrail-lang

# CSS ассеты пакета (fallback-стили для detail/timeline/diff)
php artisan vendor:publish --tag=moontrail-assets
```

> **Важно:** если вы публиковали views через `--tag=moontrail-views`, после обновления пакета нужно повторно публиковать шаблоны, чтобы подтянуть изменения.
> Либо удалите опубликованные копии, чтобы использовать встроенные шаблоны пакета.

> **После публикации config или views в production** очистите кэш:
> ```bash
> php artisan config:clear
> php artisan view:clear
> ```

---

## Быстрый старт

### Шаг 1: Добавьте trait в модель

```php
use MoonShine\MoonTrail\Traits\HasMoonTrail;

class Post extends Model
{
    use HasMoonTrail;

    protected $fillable = ['title', 'body', 'status'];
}
```

Это включает:
- Автоматическое логирование активности (через Spatie или нативный логгер)
- Снапшоты версий на каждое событие модели
- Поддержку rollback (по умолчанию отключён — см. [Включение rollback](#включение-rollback))

**Без зависимости Spatie:** используйте `HasMoonTrailVersioning` вместо `HasMoonTrail`, если вам не нужен трейт `LogsActivity` от Spatie:

```php
use MoonShine\MoonTrail\Traits\HasMoonTrailVersioning;

class Post extends Model
{
    use HasMoonTrailVersioning;
}
```

### Шаг 2: Добавьте вкладку истории в ресурс MoonShine

```php
use MoonShine\MoonTrail\Traits\WithMoonTrailTab;

class PostResource extends ModelResource
{
    use WithMoonTrailTab;
    // Вкладка истории добавляется автоматически на detail-страницу
}
```

### Шаг 3: Добавьте журнал активности в меню

В вашем `MoonShineLayout`:

```php
use MoonShine\MoonTrail\Support\MoonTrailMenuItem;

protected function menu(): array
{
    return [
        // ...ваши пункты меню...
        MoonTrailMenuItem::make(),
    ];
}
```

`MoonTrailMenuItem` автоматически берёт иконку и класс ресурса из config.

**Готово.** Все create/update/delete по вашей модели теперь отслеживаются с полным версионированием, визуальным diff и rollback.

---

## Включение rollback

Rollback **отключён по умолчанию** (secure-by-default). Кнопка показывается только при явной авторизации текущего пользователя.

Есть два варианта:

### Вариант A — `isRollbackAllowed()` в модели (простой)

```php
class Post extends Model
{
    use HasMoonTrail;

    public function isRollbackAllowed(): bool
    {
        return true;
        // или: return auth()->user()?->isAdmin() ?? false;
    }
}
```

### Вариант B — Laravel Policy (рекомендуется для гибкого контроля)

Создайте policy с методом `rollback`:

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function rollback(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
```

Зарегистрируйте в `AppServiceProvider` или `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::policy(Post::class, PostPolicy::class);
```

### Матрица авторизации (secure-by-default)

| Условие | Результат |
|---|---|
| MoonShine guard не аутентифицирован | Запрет |
| Зарегистрирована policy с `rollback` | Делегирование policy |
| Policy нет, у модели есть `isRollbackAllowed()` | Используется метод модели |
| Иначе | Запрет |

### Когда rollback недоступен

Если `showRollback` включён, но прав недостаточно, в timeline показывается
приглушённый read-only чип с tooltip: `"Rollback is not available: insufficient permissions."`
вместо скрытия контрола. Это помогает понять, что функция есть, но заблокирована правами.

Чтобы полностью скрыть контрол, используйте `->withoutRollback()`:

```php
ActivityTimeline::make('History', $this)->withoutRollback()
```

---

## Механика rollback

### Что происходит при rollback

1. Срабатывает **pre-event** `ModelRollingBack` (можно отменить — см. ниже).
2. Открывается DB-транзакция с `lockForUpdate()` на целевой строке.
3. Если модель soft-deleted, она восстанавливается.
4. Поля из снапшота записываются в модель.
5. Создаётся новая версия с `is_rollback = true` и `rollback_to_version = N`,
   сохраняя полный audit trail.
6. Транзакция коммитится.
7. Срабатывает **post-event** `ModelRolledBack` с `fromVersion`, `toVersion`, `newVersion`.

### Отмена rollback

Любой listener, который вернёт `false` из `ModelRollingBack`, отменит rollback до записи в БД:

```php
use MoonShine\MoonTrail\Events\ModelRollingBack;

Event::listen(ModelRollingBack::class, function (ModelRollingBack $event): bool {
    if ($event->model instanceof Post && someCondition($event->model)) {
        return false; // отмена
    }
});
```

При отмене выбрасывается `RollbackCancelledException`, а контроллер возвращает HTTP 422.

### HTTP-коды ответов

| Ситуация | HTTP статус |
|---|---|
| Не аутентифицирован / policy / модель запрещает | 403 |
| Версия не найдена / валидация не прошла / rollback отменён | 422 |
| DB-ограничение / конфликт конкурентной записи | 409 |
| Неожиданная ошибка | 500 |

### Правило видимости кнопки rollback

Кнопка rollback показывается для всех версий **кроме самой ранней** (первого снапшота записи).
Причина: откат к первой версии эквивалентен возврату к начальному состоянию create.

---

## Конфигурация

После публикации (`vendor:publish --tag=moontrail-config`):

```php
// config/moontrail.php

return [
    'versioning' => [
        'enabled'           => true,
        'max_versions'      => 50,              // 0 = без лимита
        'overflow_strategy' => 'delete_oldest',  // или 'prevent'
    ],

    'rollback' => [
        // Валидировать данные снапшота перед восстановлением.
        'validate'      => true,

        // Если true — rollback без правил валидации запрещён.
        // Если false (по умолчанию) — rollback без правил допустим.
        'require_rules' => false,
    ],

    'ui' => [
        'per_page'                 => 20,
        'date_format'              => 'd.m.Y H:i:s',
        'warn_if_tailwind_missing' => true,
        'hidden_fields'            => ['password', 'remember_token', 'two_factor_secret'],
        'masked_fields'            => ['password', 'remember_token', 'two_factor_secret', 'api_key', 'secret', 'token'],
    ],

    'resource' => [
        'class'     => \MoonShine\MoonTrail\Resources\MoonTrailResource::class,
        'in_menu'   => true,
        'menu_icon' => 'clock',
    ],

    // Отслеживание моделей без добавления HasMoonTrail trait
    'auto_track_models' => [
        // \MoonShine\Laravel\Models\MoonshineUser::class,
    ],
];
```

### Выбор логгера активности

MoonTrail поддерживает несколько бэкендов для логирования. Установите опцию `activity_logger` в `config/moontrail.php`:

```php
'activity_logger' => env('MOONTRAIL_LOGGER', 'auto'),
```

| Драйвер | Описание |
|---|---|
| `auto` (по умолчанию) | Использует Spatie, если установлен, иначе fallback на нативный database-логгер |
| `spatie` | Использует `spatie/laravel-activitylog` (должен быть установлен через Composer) |
| `database` | Использует нативную таблицу `moontrail_activity_log` пакета — без зависимости от Spatie |
| `none` | Полностью отключает логирование активности (версионирование продолжает работать) |
| `custom` | Разрешает `ActivityLoggerContract` из контейнера — подключите свою реализацию |

**Использование нативного database-логгера:**

```bash
composer remove spatie/laravel-activitylog
```

Затем установите `MOONTRAIL_LOGGER=database` в `.env` или обновите конфиг. Таблица `moontrail_activity_log` создаётся автоматически при запуске миграций.

**Кастомный логгер:**

```php
// В вашем ServiceProvider
$this->app->bind(ActivityLoggerContract::class, MyCustomLogger::class);
```

Установите `activity_logger` в `custom`, чтобы пакет разрешал вашу привязку из контейнера.

### Silent Failures

По умолчанию исключения в observer передаются в `report()`. Установите `silent_failures` в `true`, чтобы глотать исключения без уведомлений:

```php
'silent_failures' => false, // по умолчанию: репортить ошибки
```

### Rollback и \$fillable

Rollback восстанавливает только поля, перечисленные в массиве `$fillable` модели. Если `$fillable` пустой (модель полностью защищена), rollback выполнится успешно, но поля не будут восстановлены. Убедитесь, что модель объявляет нужные `$fillable` поля для корректной работы rollback.

### Ключевые опции

| Опция | По умолчанию | Описание |
|---|---|---|
| `versioning.max_versions` | `50` | Максимум снапшотов на экземпляр модели. `0` = без лимита |
| `versioning.overflow_strategy` | `delete_oldest` | Что делать при достижении лимита: `delete_oldest` или `prevent` |
| `rollback.validate` | `true` | Валидировать снапшот по правилам перед восстановлением |
| `rollback.require_rules` | `false` | Если `true`, rollback без явных правил запрещён |
| `ui.warn_if_tailwind_missing` | `true` | Логирует warning, если в host Tailwind config нет путей пакета в `content` |
| `ui.hidden_fields` | `[password, ...]` | Поля полностью скрыты в diff |
| `ui.masked_fields` | `[password, ..., token]` | Поля показываются в diff как маска |
| `auto_track_models` | `[]` | Модели, отслеживаемые автоматически без trait |

---

## Настройка модели

### Отслеживать только выбранные поля

```php
class Post extends Model
{
    use HasMoonTrail;

    protected static array $logAttributes = ['title', 'body', 'status'];
    protected static bool $logOnlyDirty = true;
}
```

### Исключить поля из снапшотов версий

```php
public function getVersionExcludedFields(): array
{
    return ['cached_data', 'temp_token'];
}
```

---

## Auto-Tracking (без trait)

Для сторонних моделей, которые вы не можете менять (например `MoonshineUser`), используйте config:

```php
'auto_track_models' => [
    \MoonShine\Laravel\Models\MoonshineUser::class,
],
```

Пакет подключает observer на этапе boot — без trait и без изменений кода модели.

---

## Программный API

### Diff Viewer

```php
use MoonShine\MoonTrail\Diff\DiffComputer;

// Из двух массивов атрибутов
$changes = DiffComputer::compute(
    before: ['name' => 'Old', 'status' => 'draft'],
    after:  ['name' => 'New', 'status' => 'draft'],
    hiddenFields: ['password'],
);

// Из записи Spatie Activity
$changes = DiffComputer::fromActivity($activity);

// Рендер в HTML
$html = app(\MoonShine\MoonTrail\Contracts\DiffRendererContract::class)->render($changes);
```

Каждый `FieldChange` содержит: `field`, `oldValue`, `newValue` и `type` (`Added`, `Modified`, `Removed`, `Unchanged`).

### VersionManager

```php
use MoonShine\MoonTrail\Contracts\VersionManagerContract;

$manager = app(VersionManagerContract::class);

$version = $manager->createVersion($post, 'updated');
$v1      = $manager->getVersion($post, 1);
$diff    = $manager->diff($v1, $v2);         // FieldChange[]
$diff    = $manager->diffWithCurrent($v1, $post);
```

### RollbackService

```php
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;

$service = app(RollbackStrategyContract::class);

// Простой rollback (без валидации)
$model = $service->rollback($post, targetVersion: 3);

// Rollback с правилами валидации
$model = $service->rollback($post, targetVersion: 3, rules: [
    'title' => 'required|min:3',
]);
```

Rollback выполняется внутри DB-транзакции с `lockForUpdate()`. Observer автоматически
приостанавливается на время rollback, чтобы не создавать дублирующие версии.

Возможные исключения:

| Исключение | Когда возникает |
|---|---|
| `ModelVersionNotFoundException` | Целевая версия не существует |
| `RollbackDeniedException` | Отказ в авторизации (нет policy, модель запрещает) |
| `RollbackCancelledException` | Listener `ModelRollingBack` вернул `false` |
| `RollbackConflictException` | DB-ограничение или конфликт конкурентной записи (есть поле `->reason`) |
| `ValidationException` | Данные снапшота не прошли валидацию |

---

## События

| Событие | Payload | Когда вызывается |
|---|---|---|
| `VersionCreated` | `$model`, `$version` | Сохранён новый снапшот версии |
| `ModelRollingBack` | `$model`, `$targetVersion`, `$version` | Перед rollback — **можно отменить** (вернуть `false`) |
| `ModelRolledBack` | `$model`, `$fromVersion`, `$toVersion`, `$newVersion` | После успешного rollback и commit транзакции |

### Жизненный цикл событий и границы транзакции

```
event(ModelRollingBack)   ← вызывается ДО транзакции; false отменяет rollback
  └─ DB::transaction
       ├─ lockForUpdate
       ├─ restore (если soft-deleted)
       ├─ fill + save
       └─ createVersion (is_rollback=true)
event(ModelRolledBack)    ← вызывается ПОСЛЕ commit; payload уже согласован с БД
```

---

## Artisan-команды

```bash
# Очистка записей старше 90 дней (по умолчанию)
php artisan moontrail:prune

# Кастомный период хранения
php artisan moontrail:prune --days=30

# Выборочная очистка
php artisan moontrail:prune --versions-only
php artisan moontrail:prune --activity-only

# Очистка только по конкретной модели
php artisan moontrail:prune --model="App\Models\Post"

# Интерактивный установщик
php artisan moontrail:install
```

Планировщик в `routes/console.php`:

```php
Schedule::command('moontrail:prune --days=90')->daily();
```

---

## Контракты и расширяемость

Каждый core-сервис привязан через интерфейс и может быть подменён:

| Контракт | По умолчанию | Назначение |
|---|---|---|
| `ActivityLoggerContract` | `SpatieActivityLogger` | Записывает записи лога активности (заменяемый бэкенд) |
| `DiffRendererContract` | `HtmlDiffRenderer` | Рендерит `FieldChange[]` → HTML |
| `VersionManagerContract` | `VersionManager` | Создаёт / получает / сравнивает версии |
| `RollbackStrategyContract` | `RollbackService` | Выполняет транзакционный rollback |
| `ActivityFormatterContract` | `DefaultActivityFormatter` | Форматирует event labels / icons / colors |

```php
// В ServiceProvider
$this->app->bind(DiffRendererContract::class, MyCustomDiffRenderer::class);
```

---

## Безопасность

- Все маршруты пакета защищены middleware аутентификации и сессии MoonShine.
- Rollback работает в режиме **secure-by-default**: кнопка скрыта, пока нет явного разрешения.
- Авторизация идёт через единый `RollbackAuthorizationResolver`, который используется и в контроллере, и в UI-компоненте — расхождений между отображением кнопки и серверной проверкой нет.
- Для rollback требуется подтверждение в Alpine.js модалке с показом времени снапшота и номера версии.
- Чувствительные поля (`password`, `remember_token` и т.п.) исключены из diff по умолчанию.
- DB-конфликты при rollback возвращают HTTP 409 (а не 500), чтобы клиент отличал конфликт от неожиданной ошибки сервера.

---

## Разработка

```bash
composer test           # запустить тесты (Pest)
composer test:coverage  # тесты с coverage
composer test:types     # PHPStan level 9
composer lint           # исправить кодстайл (Pint)
composer lint:test      # проверить кодстайл без исправлений
composer refactor       # применить Rector
composer ci             # полный CI: rector + pint + phpstan + tests
```

## OpenCode

Репозиторий уже подготовлен для работы с OpenCode.

- `AGENTS.md` - основной файл с правилами проекта.
- `opencode.json` добавляет общие ignore-паттерны для watcher и более безопасные подтверждения для `git commit`, `git push`, `git tag` и `rm`.
- В `.opencode/commands/` добавлены `/ci`, `/test`, `/types`, `/lint`, `/fix` и `/review` для типовых сценариев работы с пакетом.
- `.opencode/agents/package-reviewer.md` добавляет read-only reviewer-агента для проверки BC и release safety.

Быстрый старт:

```bash
opencode
```

Полезные команды внутри OpenCode:

- `/ci`
- `/test`
- `/test tests/Unit/DiffComputerTest.php`
- `/types`
- `/lint`
- `/fix`
- `/review`

Личные хоткеи и UI-настройки лучше хранить в глобальном OpenCode-конфиге или в локальном некоммитимом `tui.json`.

### Troubleshooting

1. **Не видны кнопки rollback**
   - **Чаще всего:** `isRollbackAllowed()` возвращает `false` (значение по умолчанию) и policy не зарегистрирована. См. [Включение rollback](#включение-rollback).
   - Если у пользователя нет прав, показывается приглушённая read-only подсказка вместо кнопки. Если подсказку видно, значит rollback доступен функционально, но заблокирован авторизацией.
   - Если вы публиковали views через `--tag=moontrail-views`, после обновления пакета выполните повторную публикацию шаблонов.

2. **UI пакета без стилей (не применяются классы Tailwind)**
   - Убедитесь, что в `tailwind.config.*` host-приложения добавлены пути пакета в `content`.
   - Убедитесь, что в layout host-приложения подключён Alpine.js (нужен для rollback-модалки и JSON-контролов).

3. **`composer test:coverage` падает с `No code coverage driver available`**
   - Установите и включите драйвер: **PCOV** или **Xdebug**.
   - Пример (PCOV):

```bash
php -d pcov.enabled=1 ./vendor/bin/pest -c phpunit.xml.dist --coverage --min=80
```

---

## Почему «MoonTrail»?

> *«Луна — солнце мёртвых, её след ведёт туда, где нет теней.»*

Нет, это не цитата из печенья с предсказанием. Это сакральный космогонический концепт ненцев — одного из коренных народов российской Арктики.

В ненецкой мифологии вселенная делится на три слоя: Верхний (светлый), Средний (наш) и Нижний — мир теней, *Хылы*. Миры зеркальны: когда здесь светит Солнце (*Хаэр*), внизу царит тьма. Когда у нас наступает ночь и восходит Луна (*Ири*), для духов мёртвых она становится ослепительным солнцем. То, что для нас тусклый свет — для них сияние. А в мире, залитом светом собственного «солнца», вторичных теней не существует — ведь духи сами *являются* тенями, и лунный свет делает видимым абсолютно всё.

Автор глубоко увлечён Севером и его эпосами — ненецкой, нганасанской мифологией — и этот образ идеально лёг на аудит-лог. **MoonTrail** видит всё: удалённые записи, перезаписанные поля, откаченные изменения. От лунного следа не скрыться.

И да — *MoonShine* буквально означает «лунный свет», но также и «самогон». Так что если MoonShine — это тот самый добрый продукт, который вы гоните в своей админке, то **MoonTrail** — это утренний протокол, который расскажет, что именно произошло и кто виноват. 🥃

Визуальная айдентика построена на палитре [Polaris темы для MoonShine](https://github.com/tikhomirov/moonshine-polaris-theme) — холодной северной эстетике, вдохновлённой той же Арктикой.

---

## Changelog

См. [CHANGELOG.md](CHANGELOG.md).

## License

MIT — см. [LICENSE.md](LICENSE.md).

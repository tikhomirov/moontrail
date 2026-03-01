# ТЗ-02: Интерактивный установщик (artisan wizard) `moontrail:install`

## 0) Ссылки и контекст

- Текущий статус и GAP-лист: **[00-AUDIT-STATUS-AND-GAPS.md](00-AUDIT-STATUS-AND-GAPS.md)**
- Цель по продукту: см. **[MASTER-PLAN.md](MASTER-PLAN.md)**

## 1) Цель этапа

Сделать установку пакета “одной командой”, минимизируя ручные шаги и ошибки интеграции.

Условия:

- Инсталлятор работает в **хост-приложении** (Laravel + MoonShine), где установлен пакет.
- Инсталлятор **не должен ломать код** (никаких опасных авто-правок файлов без явного подтверждения).

## 2) Исполнитель

- **Профиль:** Laravel backend developer
- **Компетенции:** Artisan commands, файловая система, Composer scripts, понимание MoonShine ресурсов.

## 3) Команда

Добавить команду:

- `php artisan moontrail:install`

Формат интерактива:

- Приоритет: Laravel Prompts (если доступно в окружении).
- Fallback: стандартные `confirm/choice` в `Illuminate\Console\Command`.

## 4) Поведение команды (пошагово, без двусмысленности)

### 4.1 Шаг 1 — Проверка окружения

Команда должна:

- Проверить, что установлен MoonShine.
- Проверить, что доступна БД (коннект), и что приложение не в `production` режиме (если в production — показать предупреждение).

### 4.2 Шаг 2 — Публикация ассетов

Запрос:

- `Publish package assets?` → Yes/No

Если Yes — выполнить:

- `vendor:publish --tag=moontrail-config`
- (опционально) `vendor:publish --tag=moontrail-views`
- (опционально) `vendor:publish --tag=moontrail-lang`

Требование:

- Команда должна быть идемпотентной: повторный запуск не должен приводить к ошибке.

### 4.3 Шаг 3 — Миграции

Запрос:

- `Run migrations now?` → Yes/No

Если Yes:

- выполнить `artisan migrate` (без интерактива)

### 4.4 Шаг 4 — Выбор моделей для трекинга

Цель: заполнить `config/moontrail.php`:

- `auto_track_models`
- `tracked_models` (для меню-быстрых фильтров)

Источники для списка моделей:

- Если существует каталог `app/Models` — просканировать и попытаться определить классы моделей.
- Дополнительно предложить системные модели, если классы существуют:
  - `App\Models\User`
  - `MoonShine\Laravel\Models\MoonshineUser`
  - (если существует) модель ролей MoonShine

UX требования:

- Отображать список как multi-select.
- По умолчанию должны быть выбраны “системные” модели (User/MoonshineUser/roles), если они доступны.

Результат:

- Выбранные модели записываются в конфиг (если конфиг опубликован).
- Если конфиг не опубликован — команда должна предложить сначала опубликовать конфиг.

Примечание:

- **Запрещено** автоматически модифицировать PHP-файлы моделей (вставлять `use HasMoonTrail`) без отдельного шага “Allow file modifications?”.

### 4.5 Шаг 5 — Интеграция с MoonShine Resources (таб истории на карточках)

Задача: помочь пользователю добавить `WithMoonTrailTab` и `$this->activityTab()` в нужные ресурсы.

Команда должна:

- Просканировать `app/MoonShine/Resources` (если каталог существует).
- Сопоставить ресурсы с моделями (по `protected string $model = ...`).
- Дать пользователю multi-select: «В какие ресурсы добавить вкладку истории?»

Режимы работы (строго):

1. **Safe mode (default):**
   - команда **НЕ правит файлы**
   - вместо этого печатает точную инструкцию, что вставить в каждый выбранный ресурс:
     - `use MoonShine\MoonTrail\Traits\WithMoonTrailTab;`
     - `use WithMoonTrailTab;`
     - `$this->activityTab()` в `detailFields()`
2. **Auto-patch mode (опционально):**
   - включается только после явного подтверждения
   - можно реализовать через AST-парсер (предпочтительно) либо осторожный текстовый патч

## 5) Пример сценария (ожидаемое поведение)

```text
$ php artisan moontrail:install

✓ MoonShine detected
✓ DB connection ok

Publish assets?  (Yes)
 - config published

Run migrations now? (Yes)
 - migrations ok

Select models to track:
 [x] App\Models\User
 [x] MoonShine\Laravel\Models\MoonshineUser
 [ ] App\Models\Order

Select resources to add History tab:
 [x] UserResource
 [ ] OrderResource

Safe mode: printing instructions...
Done.
```

## 6) Тесты (обязательно)

- Добавить Feature-тест на выполнение команды в non-interactive режиме (например, через опции `--no-interaction` + разумные defaults).
- Добавить Unit-тест на сервис “config updater” (если выделяется в отдельный класс).

## 7) Definition of Done (DoD)

- Команда `moontrail:install` присутствует и запускается.
- Команда:
  - публикует конфиг/ассеты (идемпотентно)
  - может запускать миграции
  - записывает выбранные модели в конфиг
  - может вывести инструкции по интеграции с ресурсами
- Добавлены тесты.
- После выполнения инструкции — пользователь видит работающий global Activity Log и вкладку History в выбранных ресурсах.

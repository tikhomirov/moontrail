# 05 — Portability-анализ (другие activity engines + Symfony/Yii3)

## 0) Текущее состояние

Архитектура пакета уже содержит полезные абстракции на уровне доменных сервисов (diff/versioning/rollback formatter), но runtime жёстко опирается на Laravel/MoonShine (Eloquent, ServiceProvider, Blade, Gate, config/helpers, MoonShine UI и маршруты).

---

## 1) Можно ли отвязать пакет от текущего activity log-движка?

Короткий ответ: **частично да, полностью — дорого**.

## 1.1 Что уже помогает отвязке

1. Контракты:
   - `VersionManagerContract`
   - `RollbackStrategyContract`
   - `DiffRendererContract`
   - `ActivityFormatterContract`
2. Отдельные сервисы `DiffComputer`, `VersionManager`, `RollbackService`.
3. Событийная модель rollback/version lifecycle.

## 1.2 Что мешает отвязке

1. Прямая зависимость на `Spatie\Activitylog\Models\Activity` в контроллерах/менеджере/ресурсе.
2. Прямая зависимость на Eloquent модели и морф-связи (`ModelVersion`, `subject/causer`).
3. Laravel-specific инфраструктура:
   - ServiceProvider/IoC wiring
   - Gate/Auth
   - helpers (`config`, `route`, `asset`, `event`, `toast`)
4. MoonShine-specific UI слой:
   - `ModelResource`, `DetailPage`, компоненты MoonShine
   - Blade шаблоны под Tailwind/Alpine

Вывод: для engine swap внутри Laravel нужен adapter layer; для multi-framework нужен вынесенный core.

---

## 2) Какие абстракции уже есть / каких не хватает

## 2.1 Уже есть

1. Контрактные сервисы diff/versioning/rollback/formatting.
2. DTO `FieldChange` и enum-семантика событий/типов изменений.
3. Отделение вычисления diff от рендера.

## 2.2 Не хватает

1. `ActivityStoreContract` (чтение/запись activity records без прямой привязки к Spatie Model).
2. `SnapshotStoreContract` (переиспользуемый storage-слой снапшотов вне Eloquent).
3. `AuthorizationPort` для rollback-проверок вне Laravel Gate.
4. `UI Port` (или headless API mode), чтобы core не зависел от MoonShine.
5. Единый cross-framework error model (HTTP-коды сейчас зашиты в Laravel controller flow).

---

## 3) Что нужно для интеграции с Symfony

## 3.1 Обязательный минимум

1. Выделить `moontrail-core` (PHP library без Laravel/MoonShine).
2. Реализовать Symfony adapters:
   - Doctrine-based snapshot repository;
   - activity adapter (Monolog/Audit bundle или custom table);
   - Security voter adapter для rollback авторизации.
3. Сделать отдельный UI модуль:
   - Twig + Stimulus/UX для timeline/diff/rollback.
4. Добавить Symfony bundle для DI/конфига/routes/events.

## 3.2 Сложность

Высокая: потребуется отдельная инфраструктурная команда или минимум 1 senior с опытом Symfony bundle + 1 frontend/UX.

---

## 4) Что нужно для интеграции с Yii3

## 4.1 Обязательный минимум

1. Порт core в framework-agnostic слой.
2. Реализация хранилищ через Yii DB/ActiveRecord (или Doctrine в Yii3 проекте).
3. Авторизация через RBAC/policy-адаптер.
4. UI слой (Yii view/widgets + JS-компоненты) для timeline/diff/rollback.

## 4.2 Сложность

Высокая: Yii3 экосистема менее стандартизована по админ-UI паттернам, больше кастомной сборки.

---

## 5) Оценка затрат

| Зона затрат | Оценка |
|---|---|
| Архитектурная декомпозиция на core + adapters | Высокая |
| Реализация adapter для другого activity backend | Средняя/Высокая |
| Новый UI слой (вне MoonShine) | Высокая |
| Тестовая матрица multi-framework | Высокая |
| Поддержка документации и support-процессов | Средняя |

Оценка по времени (порядок величин):

- **Частичная адаптация в Laravel (другой activity backend):** 3–6 недель.
- **Полноценный Symfony порт:** 3–5 месяцев.
- **Полноценный Yii3 порт:** 4–6 месяцев.
- **Multi-framework core с двумя адаптерами и базовым UI:** 6–9 месяцев.

---

## 6) Стратегии

## A) Не делать portability (рекомендуется на текущем этапе)

**Обоснование:**

1. Основная ценность пакета сейчас в MoonShine-first UX.
2. Есть P0/P1 задачи финализации текущего релиза.
3. Распыление на multi-framework снизит качество основного продукта.

**Когда пересматривать:**

- после 1–2 стабильных релизов и подтверждённого спроса на non-Laravel интеграции.

## B) Частичная адаптация

Scope:

1. Оставить Laravel runtime.
2. Добавить engine-абстракцию и adapter для альтернативного activity backend внутри Laravel.

Плюсы:

- Низкий риск относительно C.
- Повышает устойчивость архитектуры без потери фокуса.

Минусы:

- Не решает Symfony/Yii3.

## C) Полноценный multi-framework core

Scope:

1. Выделение framework-agnostic core.
2. Laravel adapter + Symfony adapter (+ опционально Yii3).
3. Отдельные UI пакеты.

Плюсы:

- Максимальная масштабируемость.

Минусы:

- Существенный time-to-market и стоимость.
- Рост поддержки/тестовой нагрузки.

---

## 7) Рекомендованная дорожная карта и go/no-go критерии

## Этап 1 (текущий): стабилизация MoonTrail

- Закрыть P0/P1 финализации (docs consistency, URL, identity).
- Удержать release quality по `composer ci` + browser smoke.

**Go к этапу 2, если:**

- 2 стабильных релиза подряд без критических регрессий.
- Есть подтверждённые запросы на альтернативный backend activity.

## Этап 2: частичная адаптация (Стратегия B)

- Ввести `ActivityStoreContract` и первый альтернативный adapter в Laravel.
- Не трогать MoonShine UI слой как основной интерфейс.

**Go к этапу 3, если:**

- Adapter доказал ценность в проде.
- Команда готова поддерживать минимум 2 backend implementation.

## Этап 3: решение о full portability (Стратегия C)

- Подготовить RFC по выделению `moontrail-core`.
- Оценить бюджет, команду, roadmap и SLA поддержки.

**No-Go, если:**

- Нет ресурса на поддержку нескольких UI/runtime веток.
- Нет измеримого спроса вне Laravel/MoonShine.

---

## 8) Финальный вывод

Для текущей фазы финализации релиза оптимальная стратегия — **A (не делать portability сейчас)**, с подготовкой к **B** через архитектурные seams в пределах Laravel.

---

## 9) Источники

- `MOONSHINE-ACTIVITY-LOG-PLAN.md` (архитектура, roadmap)
- `docs/MASTER-PLAN.md`
- `src/Contracts/*`
- `src/Resources/MoonTrailResource.php`
- `src/Http/Controllers/ActivityController.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Versioning/RollbackService.php`
- `src/Versioning/VersionManager.php`
- `src/MoonTrailServiceProvider.php`
- `routes/moontrail.php`
- `resources/views/components/*.blade.php`

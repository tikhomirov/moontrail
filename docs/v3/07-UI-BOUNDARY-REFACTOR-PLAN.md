# 07 — План рефакторинга UI-границы и слоистой архитектуры

## 0) Цель

Устранить две архитектурные слабости текущей реализации:

1. HTML и presentation-логика частично собраны прямо в PHP-классах страниц и ресурсов.
2. `HtmlDiffRenderer` зависит от MoonShine component layer, хотя должен быть инфраструктурным renderer-сервисом.

План ниже сохраняет текущие публичные контракты и поведение пакета, но упрощает сопровождение, тестирование и дальнейшее развитие UI.

---

## 1) Проблемы текущего состояния

## 1.1 Смешение presentation и orchestration

На текущий момент:

- [src/Pages/MoonTrailIndexPage.php](../../src/Pages/MoonTrailIndexPage.php) одновременно:
  - читает фильтры из request;
  - собирает HTML через heredoc;
  - подготавливает данные для UI;
  - частично управляет поведением фильтрации.
- [src/Resources/MoonTrailResource.php](../../src/Resources/MoonTrailResource.php) содержит не только resource/query-логику, но и крупные formatted-render callbacks с HTML/presentation responsibility.

Следствие:

- UI трудно менять без правки PHP-кода.
- Повышается связанность page/resource layer и presentation.
- Тесты становятся более хрупкими, потому что HTML и данные перемешаны.

## 1.2 Неправильное направление зависимости в diff rendering

Сейчас [src/Diff/HtmlDiffRenderer.php](../../src/Diff/HtmlDiffRenderer.php) использует [src/Components/DiffViewer.php](../../src/Components/DiffViewer.php).

Это означает, что сервис renderer зависит от MoonShine UI-компонента. Архитектурно это слабое место, потому что:

- слой `Diff` перестает быть независимым от UI;
- усложняется повторное использование renderer вне MoonShine component tree;
- становится труднее тестировать renderer как отдельный сервис.

## 1.3 Перегруженность page/resource слоя

Часть прикладной логики фильтрации и подготовки display-данных находится в page/resource классах вместо выделенных query/presenter объектов.

Следствие:

- страницы знают слишком много о формате query params;
- фильтрация и выборки сложнее переиспользовать;
- рост UI-функциональности будет дальше раздувать классы.

---

## 2) Целевое состояние

Целевая слоистая схема:

### Domain / core

- `src/Diff/*`
- `src/Versioning/*`
- `src/Events/*`
- `src/Exceptions/*`
- `src/Models/ModelVersion.php`

Ограничения:

- без зависимостей на MoonShine UI-компоненты;
- без ручной HTML-сборки;
- без knowledge о page/resource layout.

### Application / delivery

- `src/Http/Controllers/*`
- `src/Pages/*`
- `src/Resources/*`

Ограничения:

- orchestration, маршрутизация, request-to-service flow;
- минимум presentation-логики;
- query/filter delegation в отдельные объекты.

### Presentation

- `src/Components/*`
- `resources/views/*`

Ограничения:

- отвечает за отображение;
- получает подготовленные данные;
- не содержит сложной query/domain-логики.

Служебные presenter/data-builder объекты при этом живут в `src/Support/*`, а не в presentation layer.

---

## 3) Принципы рефакторинга

1. Не менять публичные контракты в `src/Contracts/`.
2. Не ломать текущие route names, config keys и resource registration.
3. Сначала разорвать неправильные зависимости между слоями, потом дробить крупные UI-классы.
4. Выносить HTML в Blade, если это presentation.
5. Выносить фильтрацию и подготовку display-данных в отдельные объекты, если код нужен более чем в одном месте или заметно раздувает resource/page.

---

## 4) План изменений по этапам

## Этап 1 — Разорвать зависимость `HtmlDiffRenderer -> DiffViewer`

### Цель

Сделать `HtmlDiffRenderer` инфраструктурным renderer-сервисом, который умеет рендерить HTML без зависимости от MoonShine component layer.

### Изменения

- Переписать [src/Diff/HtmlDiffRenderer.php](../../src/Diff/HtmlDiffRenderer.php):
  - убрать создание `DiffViewer::make(...)->render()`;
  - рендерить Blade-view напрямую через `view(...)->render()`.
- Оставить [resources/views/components/diff-viewer.blade.php](../../resources/views/components/diff-viewer.blade.php) как канонический шаблон diff UI.
- Оставить [src/Components/DiffViewer.php](../../src/Components/DiffViewer.php) как тонкую MoonShine-обертку над тем же шаблоном.

### Целевой результат

- `HtmlDiffRenderer` зависит максимум от Blade/view system.
- `DiffViewer` зависит от presentation layer.
- направление зависимости становится корректным.

### Риски

- Возможны отличия в HTML между controller-rendered diff и component-rendered diff, если использовать разные data paths.

### Меры

- Зафиксировать единый шаблон и единый набор view data.
- Обновить/добавить тесты на renderer и component.

---

## Этап 2 — Вынести HTML из `MoonTrailIndexPage` в Blade partials

### Цель

Сделать [src/Pages/MoonTrailIndexPage.php](../../src/Pages/MoonTrailIndexPage.php) страницей-оркестратором, а не местом ручной HTML-сборки.

### Изменения

- Вынести в Blade partials следующие обязательные страницы/секции:
  - `resources/views/pages/index-filters.blade.php`
  - `resources/views/pages/filter-chips.blade.php`
  - `resources/views/pages/index-kpi.blade.php`
  - `resources/views/pages/detail-general.blade.php`
  - `resources/views/pages/detail-relations.blade.php`
  - `resources/views/pages/detail-changes.blade.php`
  - `resources/views/pages/detail-history.blade.php`
- В `MoonTrailIndexPage` оставить:
  - сбор данных;
  - вызов view с параметрами;
  - компоновку top-layer компонентов.
- В `MoonTrailResource` оставить:
  - описание полей;
  - orchestration detail sections;
  - делегирование рендера в Blade partials и presenter.

### Целевой результат

- Page class отвечает за flow.
- Blade отвечает за markup.
- UI-правки не требуют редактировать heredoc в PHP.

### Риски

- Возможны мелкие регрессии в CSS hooks и DOM-структуре.

### Меры

- Оставить class names прежними.
- Добавить feature-тесты на ключевые UI-фрагменты.

---

## Этап 3 — Выделить query/filter слой для activity log

### Цель

Убрать из [src/Resources/MoonTrailResource.php](../../src/Resources/MoonTrailResource.php) избыточную ответственность за фильтрацию и подготовку query.

### Изменения

- Создать обязательные support-объекты:
  - `src/Support/ActivityLogFilterData.php`
  - `src/Support/ActivityLogQuery.php`
  - `src/Support/ActivityLogFilterOptions.php`
- Перенести туда:
  - нормализацию request filter values;
  - применение фильтров к `Activity::query()`;
  - сбор distinct options для filters UI.
- В `MoonTrailResource` оставить:
  - описание полей;
  - вызов query object;
  - интеграцию с MoonShine API.

### Целевой результат

- Resource становится тоньше.
- Query-логика тестируется независимо от UI.
- Проще добавлять новые фильтры и поиск.

### Риски

- Неверная миграция nested/direct filter params может сломать существующие URL.

### Меры

- Сохранить текущую поддержку и прямых params, и `filters.*`.
- Добавить тесты на оба формата.

---

## Этап 4 — Выделить presenter/view model для timeline и detail rendering

### Цель

Снизить связанность компонентов и resource/detail-страниц с вычислениями display state.

### Изменения

- Для [src/Components/ActivityTimeline.php](../../src/Components/ActivityTimeline.php) выделить обязательный data-builder:
  - расчет `changesCount`;
  - rollback availability hints;
  - display-ready labels и secondary metadata.
- Для detail-части [src/Resources/MoonTrailResource.php](../../src/Resources/MoonTrailResource.php) вынести presentation helpers в обязательный presenter и Blade partials.

Новые обязательные классы:

- `src/Support/ActivityTimelineDataBuilder.php`
- `src/Support/ActivityDetailPresenter.php`

### Целевой результат

- Компоненты и resource pages становятся тонкими контейнерами.
- Display-логика живет в отдельных объектах, а не в callback-замыканиях.

### Риски

- Есть риск «переархитектурить» код раньше времени.

### Меры

- Выносить только ту логику, которая уже заметно выросла или повторяется.
- Не дробить код искусственно ради формальной чистоты.

---

## Этап 5 — Проверить реализацию на соответствие архитектурным guardrails

### Цель

Чтобы слой presentation снова не протекал в service/domain layer.

### Изменения

- Проверить реализацию против уже зафиксированных правил в [AGENTS.md](../../AGENTS.md):
  - `src/Diff/HtmlDiffRenderer` рендерит только через `view()`;
  - `src/Diff/*` и `src/Versioning/*` не импортируют `src/Components/*`;
  - HTML живет в `resources/views/`;
  - `src/Pages/*` и `src/Resources/*` не собирают большие HTML-строки inline;
  - filter/query логика живет в `src/Support/ActivityLogFilterData`, `ActivityLogQuery`, `ActivityLogFilterOptions`;
  - presentation helpers живут в `src/Support/ActivityDetailPresenter`;
  - timeline changes count живет в `src/Support/ActivityTimelineDataBuilder`.

### Целевой результат

- Появляется понятный guardrail для будущих изменений.

---

## 5) Приоритеты

### P0 — Высокий приоритет

- Этап 1: `HtmlDiffRenderer` без зависимости от `DiffViewer`
- Этап 2: вынос HTML из `MoonTrailIndexPage`

### P1 — Средний приоритет

- Этап 3: query/filter objects для activity log
- Этап 4: presenter/view model слой для timeline/detail

### P2 — Низкий, но обязательный для фиксации результата приоритет

- Этап 5: финальная проверка соответствия `AGENTS.md`

---

## 6) Обязательная структура новых файлов

Обязательные Blade-файлы:

- `resources/views/pages/index-filters.blade.php`
- `resources/views/pages/filter-chips.blade.php`
- `resources/views/pages/index-kpi.blade.php`
- `resources/views/pages/detail-general.blade.php`
- `resources/views/pages/detail-relations.blade.php`
- `resources/views/pages/detail-changes.blade.php`
- `resources/views/pages/detail-history.blade.php`

Обязательные support-классы:

- `src/Support/ActivityLogFilterData.php`
- `src/Support/ActivityLogQuery.php`
- `src/Support/ActivityLogFilterOptions.php`
- `src/Support/ActivityTimelineDataBuilder.php`
- `src/Support/ActivityDetailPresenter.php`

Примечание:

Новые классы не должны нарушать существующие contracts и не должны вводить breaking changes в публичный API пакета.

---

## 7) Критерии готовности

Рефакторинг можно считать завершенным, когда:

1. `HtmlDiffRenderer` больше не зависит от `MoonShine\MoonTrail\Components\DiffViewer`.
2. В `MoonTrailIndexPage` нет крупных heredoc HTML-блоков.
3. В `MoonTrailResource` нет крупных inline HTML-render helpers; detail sections вынесены в `resources/views/pages/detail-*.blade.php` и `ActivityDetailPresenter`.
4. Filter/query logic находится только в `src/Support/ActivityLogFilterData`, `ActivityLogQuery`, `ActivityLogFilterOptions`.
5. Changes count computation вынесен в `src/Support/ActivityTimelineDataBuilder`.
6. Все обязательные Blade-файлы из `AGENTS.md` созданы и используются.
7. Тесты `pest` проходят без регрессий.
8. `phpstan` не показывает новых ошибок.

---

## 8) Тестовая стратегия

### Unit

- `HtmlDiffRendererTest`
- query/filter object tests
- presenter/view model tests

### Feature

- `ActivityControllerTest`
- `DiffViewerComponentTest`
- `ActivityLogIndexPageTest`
- `ActivityTimelineComponentTest`

### Smoke

- Проверить глобальный activity log resource
- Проверить diff AJAX endpoint
- Проверить timeline на ресурсе с `WithMoonTrailTab`
- Проверить rollback visibility/hints после рефакторинга presentation layer

---

## 9) Рекомендуемый порядок выполнения

1. Этап 1
2. Этап 2
3. Этап 3
4. Этап 4
5. Этап 5

Этот порядок минимизирует риск: сначала исправляется архитектурная зависимость между слоями, затем presentation extraction, и только потом дробится более широкая page/resource логика.

---

## 10) Краткий итог

Минимальный practical refactor, который даст наибольший эффект без ломки API:

1. Переписать `HtmlDiffRenderer`, чтобы он рендерил Blade напрямую.
2. Вынести HTML из `MoonTrailIndexPage` в Blade partials.

После этого можно безопасно и поэтапно выносить query/filter/presenter слой из resource/page компонентов.

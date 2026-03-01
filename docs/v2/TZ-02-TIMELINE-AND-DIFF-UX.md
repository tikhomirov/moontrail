# TZ-02 — Timeline и Diff UX (карточка сущности + detail журнала)

## 0) Краткое текущее состояние

Timeline и Diff уже реализованы и работают, включая inline diff и rollback modal. Этап нужен для **стабилизации поведения**, стандартизации UX и устранения неоднозначностей в интерактиве.

---

## 1) Цель этапа

Сделать просмотр истории изменений предсказуемым и быстрым:
- оператор открывает timeline;
- раскрывает diff без перезагрузки;
- читает изменения даже при сложных JSON;
- не сталкивается с «прыгающим» UI и повторными лишними запросами.

---

## 2) Кто реализует

- **Роль:** MoonShine UI developer.
- **Компетенции:** Blade, Alpine.js, Tailwind, UX states (loading/error/empty), базовый JS fetch API.
- **Поддержка:** backend developer (контракт API diff, обработка ошибок).

---

## 3) Область изменений

- `resources/views/components/activity-timeline.blade.php`
- `resources/views/components/diff-viewer.blade.php`
- `src/Http/Controllers/ActivityController.php`
- `lang/en/ui.php`, `lang/ru/ui.php`
- `tests/Feature/ActivityTimelineComponentTest.php`
- `tests/Feature/ActivityControllerTest.php`

---

## 4) Функциональные требования

## 4.1 Timeline card UX

### 4.1.1 Обязательные элементы карточки версии
- Номер версии (`Version #N`).
- Badge события (`created`, `updated`, `deleted`, `restored`, `rolled_back`).
- Дата/время события.
- Автор (если есть).
- Для rollback-версий — метка `→ v{rollback_to_version}`.

### 4.1.2 Визуальная консистентность
Цвет badge должен совпадать с глобальной палитрой ресурса:
- created — green;
- updated — blue;
- deleted — red;
- restored — purple;
- rolled_back — orange.

---

## 4.2 Inline diff (lazy load)

### 4.2.1 API контракт
Запрос:
- `GET /{moonshine_prefix}/moontrail/{activity}/diff`
- Accept: `application/json`

Ответ:
```json
{
  "html": "<table>...</table>",
  "event": "updated"
}
```

### 4.2.2 Поведение при раскрытии
- По клику «Показать diff» панель раскрывается.
- На **первое** раскрытие выполняется загрузка.
- На повторные раскрытия повторный HTTP-запрос **не выполняется** (кеш результата).

### 4.2.3 Состояния
- `loading`: виден spinner + текст.
- `success`: рендер `html`.
- `error`: человекочитаемое сообщение + кнопка `Повторить`.

### 4.2.4 Ограничения
- Для версии без `activity_id` кнопка diff не показывается.
- Ошибка загрузки не должна ломать всю карточку timeline.

---

## 4.3 Diff table UX

### 4.3.1 Обязательные колонки
- `Field`
- `Before`
- `After`
- `Status` (кроме compact mode)

### 4.3.2 Сложные JSON-значения
Для ячеек с object/array:
- short preview (truncate);
- кнопка `Expand` раскрывает prettified JSON;
- кнопка `Copy` копирует полный JSON в clipboard;
- состояние `Copied!` с авто-сбросом.

### 4.3.3 Edge-cases
- Пустой diff: «Изменений не обнаружено».
- Очень длинные строки не ломают верстку.

---

## 4.4 Wireframe (ожидаемый UX)

```text
[Version #12] [Updated]                    [2026-02-28 13:20:00]
Author: Admin
[Показать diff] [Откатить]

┌──────────────────────────────────────────────────────────────┐
│ Field      │ Before               │ After              │ St  │
│ title      │ Old title            │ New title          │ mod │
│ meta       │ {"a":1,...} [Expand][Copy] ...               │
└──────────────────────────────────────────────────────────────┘
```

---

## 5) Нефункциональные требования

- UI корректно работает на desktop и mobile (360px+).
- Нет визуальных артефактов в dark mode.
- Без JS-фаталов в консоли браузера при открытии diff.

---

## 6) Тесты (обязательные)

## 6.1 Feature tests
- Кнопка diff рендерится только при наличии `activity_id`.
- Кнопка rollback рендерится по правилам видимости (детали доступа — TZ-04).
- Пустая история рендерит `no_history`.

## 6.2 API tests
- `ActivityController@diff` возвращает `html` + `event`.
- Для несуществующего id возвращается 404.
- (Доп.) маршрут защищен middleware (без `withoutMiddleware`).

## 6.3 Ручная проверка (обязательная)
- Открыть detail в журнале.
- Несколько раз раскрыть/свернуть один и тот же diff.
- Убедиться, что повторных запросов нет (Network вкладка).

---

## 7) Acceptance (DoD)

- Timeline стабилен и предсказуем по UX.
- Diff грузится lazy и кэшируется на клиенте.
- Все состояния (loading/error/empty/success) корректны.
- `composer ci` — зелёный.
- В логах хоста нет новых ошибок после ручной проверки.

---

## 8) Что не входит в этап

- Политики rollback и secure-by-default (это `TZ-04`).
- Расширенные фильтры/поиск/маскирование (`TZ-03`).

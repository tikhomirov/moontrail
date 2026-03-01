# ТЗ-04: Rollback (security + UX) + стабилизация Timeline

## 0) Ссылки и контекст

- Текущий статус и GAP-лист: **[00-AUDIT-STATUS-AND-GAPS.md](00-AUDIT-STATUS-AND-GAPS.md)**
- UI точка проверки:
  - `http://localhost:8000/admin/resource/moontrail-resource/index-page`
- Реализация backend rollback уже есть:
  - `src/Versioning/RollbackService.php`
  - `src/Http/Controllers/RollbackController.php`

## 1) Цель этапа

Сделать rollback:

- безопасным (авторизация)
- предсказуемым (понятный UI/модалка)
- с явным подтверждением результата (toast)
- с корректным отображением события "rolled_back" в истории

## 2) Исполнитель

- **Профиль:** Senior Laravel backend developer + MoonShine UI developer
- **Компетенции:** Policies/Gate, транзакции, Blade/Alpine, MoonShine UI.

## 3) Требования: авторизация rollback (критично)

### 3.1 Норматив поведения

Rollback — привилегированное действие.

Требование:

- Если пользователь не авторизован — rollback запрещён.
- Если у модели есть Policy с методом `rollback` — решение принимается Policy.
- Если Policy нет, используется fallback:
  - `isRollbackAllowed()` на модели (если метод существует)
  - иначе по умолчанию **запрещено** (secure-by-default)

Важно:

- Текущий default `HasMoonTrail::isRollbackAllowed()` возвращает `true`. Это нужно пересмотреть в рамках реализации, чтобы соответствовать secure-by-default.

### 3.2 Реализация в `RollbackController`

В `RollbackController` после получения `$model`:

- Если у `Gate` есть policy и у policy существует метод `rollback` → вызвать `Gate::authorize('rollback', $model)`.
- Иначе:
  - если у модели есть `isRollbackAllowed()` и он возвращает `false` → вернуть 403
  - иначе → вернуть 403

### 3.3 Реализация в UI (скрывать кнопки)

Rollback кнопка не должна отображаться, если rollback запрещён.

Норматив:

- Определять `canRollback` один раз на уровне Timeline (для subject модели) и передавать в Blade как boolean.
- Если `canRollback=false` — **не рендерить** кнопки rollback ни у одной версии.

## 4) Требования: UX rollback

### 4.1 Кнопки rollback

- В Timeline у каждой версии (кроме самой первой) показывать кнопку:
  - текст: `Откатить к версии #N`
  - цвет: warning/orange
  - кнопка доступна только при `canRollback=true`

### 4.2 Модалка подтверждения

- Использовать существующую модалку `rollback-confirm.blade.php`.
- Текст модалки должен включать:
  - номер версии
  - предупреждение, что будут перезаписаны текущие данные
  - предупреждение, что будет создана новая версия "rolled_back"

### 4.3 Уведомление об успехе (toast)

После успешного rollback:

- показать toast:
  - текст: `Откат выполнен. Создана новая версия истории.`
  - тип: success

Технически:

- использовать `MoonShine\Laravel\MoonShineUI::toast(...)` либо helper `toast(...)` (что принято в MoonShine).

### 4.4 Поведение после rollback

Норматив:

- После rollback пользователь должен увидеть обновлённую историю.

Допускается (по приоритету):

1. Полная перезагрузка страницы (redirect back) + toast
2. Частичное обновление только блока History через async (если это стандартно/удобно в MoonShine)

## 5) Стабилизация Timeline diff (исправление бага)

Факт (см. GAP-лист): сейчас inline diff может не грузиться при первом раскрытии.

Требование:

- При клике "Показать diff" должен выполняться `load()` ровно один раз (с кешированием результата).
- Повторное раскрытие не делает повторный HTTP запрос.

## 6) Опционально: Batch rollback (research)

Если укладываемся по времени:

- добавить возможность отката пакета изменений по `batch_uuid` Spatie.
- UI: кнопка "Откатить batch" в MoonTrailResource detail (у записи, где есть batch).

Если не укладываемся:

- оформить как отдельное ТЗ (не делать в этом релизе).

## 7) Тесты (обязательно)

- Feature:
  - rollback запрещён без policy/без разрешения → 403
  - rollback разрешён через policy → 302 + изменения в БД
- Feature/UI:
  - при `canRollback=false` в HTML timeline нет текста кнопки rollback
- Unit:
  - логика определения `canRollback` (если вынесена в отдельный сервис)

## 8) Definition of Done (DoD)

- Rollback защищён авторизацией (policy + fallback), secure-by-default.
- Кнопки rollback видны только тем, кому можно.
- После rollback показывается success toast.
- Timeline diff стабильно загружается при первом раскрытии.
- Ручная проверка пройдена, ошибок в логах нет.
- `composer ci` проходит.

# TZ-04 — Rollback Security + UX (secure-by-default)

## 0) Краткое текущее состояние

Rollback уже реализован технически (транзакция, lock, создание rollback-версии), но уровень безопасности и операторский UX нужно довести до enterprise-стандарта:
- доступ к rollback должен быть строго контролируем;
- кнопки rollback должны показываться только при наличии прав;
- пользователь должен получать явное подтверждение результата.

---

## 1) Цель этапа

Сделать rollback:
1. **Безопасным** — доступ только у авторизованных ролей.
2. **Предсказуемым** — UI не предлагает действие, если оно запрещено.
3. **Аудируемым** — успешный откат явно виден в истории и в уведомлении.

---

## 2) Кто реализует

- **Ведущий:** Senior Laravel backend developer.
  - Навыки: Policies/Gates, Laravel Authorization, транзакции, exception handling.
- **Соисполнитель:** MoonShine UI developer.
  - Навыки: Blade, conditional rendering, toast-интеграция.
- **QA:** проверка security сценариев (allowed/denied).

---

## 3) Область изменений

- `src/Http/Controllers/RollbackController.php`
- `src/Components/ActivityTimeline.php`
- `resources/views/components/activity-timeline.blade.php`
- `src/Traits/HasMoonTrail.php`
- `lang/en/ui.php`, `lang/ru/ui.php`
- `tests/Feature/RollbackControllerTest.php`
- `tests/Feature/ActivityTimelineComponentTest.php`

---

## 4) Правила авторизации rollback (жёсткий норматив)

## 4.1 Матрица принятия решения

Проверка выполняется в порядке:

1. Есть ли аутентифицированный пользователь MoonShine?  
   - Нет → **403**.
2. Есть ли policy для модели и метод `rollback`?  
   - Да → `Gate::authorize('rollback', $model)`.
3. Если policy нет:
   - если модель имеет `isRollbackAllowed()` и вернула `true` → разрешить;
   - иначе → **403**.

## 4.2 Secure-by-default

- Если нет явного разрешения, rollback запрещён.
- Дефолтный `isRollbackAllowed()` в `HasMoonTrail` должен быть безопасным (не открывать rollback всем).

---

## 5) Требования к UI поведению

## 5.1 Видимость rollback-кнопок

- `canRollback` вычисляется один раз на уровне timeline компонента.
- При `canRollback = false`:
  - кнопки rollback не рендерятся ни у одной версии;
  - rollback modal не должен открываться из UI.

## 5.2 Текст кнопки

Формат:
- `Откатить к версии #N` (RU)
- `Rollback to version #N` (EN)

## 5.3 Модалка подтверждения

Обязательно содержит:
- номер целевой версии;
- предупреждение о перезаписи текущих данных;
- предупреждение о создании новой rollback-записи в истории.

---

## 6) Post-action UX

## 6.1 Успешный rollback

После успешного rollback:
- redirect назад (или на detail subject) допустим;
- показать success toast:
  - RU: `Откат выполнен. Создана новая версия истории.`
  - EN: `Rollback completed. A new history version has been created.`

## 6.2 Ошибка rollback

- Возвращать контролируемую ошибку (403/422/500 по контексту).
- Показывать error toast/flash с безопасным сообщением без утечки внутренних деталей.

---

## 7) Аудитируемость результата

После rollback в истории обязательно должен появиться новый version record:
- `event = rolled_back`
- `is_rollback = true`
- `rollback_to_version = N`

Это требование уже поддерживается backend-ядром и должно быть сохранено после всех изменений.

---

## 8) UI эскиз (целевое поведение)

```text
Version #12 [Updated] [Show diff] [Rollback to version #12]
Version #11 [Created] [Show diff] [Rollback to version #11]
Version #1  [Created] [Show diff]            (без rollback)

[Modal]
Restore model to version #11?
This will overwrite current data.
A new "rolled_back" history version will be created.
[Cancel] [Confirm rollback]
```

---

## 9) Тесты (обязательные)

## 9.1 Feature (security)
- rollback без авторизации → 403.
- rollback при запрете policy → 403.
- rollback при разрешении policy → успех + изменения в БД.
- rollback при отсутствии policy и без явного разрешения модели → 403.

## 9.2 Feature/UI
- При `canRollback=false` HTML timeline не содержит rollback-кнопок.
- При `canRollback=true` кнопка есть у всех версий кроме самой первой.

## 9.3 Regression
- После rollback создается новая версия с `is_rollback=true`.

---

## 10) Acceptance (DoD)

- Авторизация rollback полностью соответствует матрице из раздела 4.
- UI не показывает запрещенные действия.
- После успеха отображается success toast.
- История корректно фиксирует событие `rolled_back`.
- В ручной проверке нет ошибок в логах хоста.
- `composer ci` — зелёный.

---

## 11) Что не входит в этап

- Batch rollback по `batch_uuid` (только как backlog item).
- Ребрендинг и package rename (`TZ-06`).

# 01 — Полное описание пакета MoonTrail (финализация v3)

## 0) Текущее состояние (кратко)

Пакет уже прошёл V2 release gate с вердиктом GO, включая `composer ci` и закрытие TZ-01..TZ-07, но в репозитории остались несогласованности между кодом, документацией и legacy-артефактами, которые нужно закрыть перед финализацией релиза v3 (например, несовпадение package coordinates и устаревшие ссылки/формулировки).

---

## 1) Назначение пакета

`tikhomirov/moontrail` — Laravel-пакет для MoonShine, который добавляет над `spatie/laravel-activitylog` полноценный слой аудита изменений:

1. **Diff viewer** по полям.
2. **Versioning** через `model_versions` (снапшоты состояния модели).
3. **Transactional rollback** с аудитируемым следом.

Пакет **не заменяет** Spatie, а расширяет его для админ-оператора в MoonShine (UI + rollback workflow + удобная навигация).

---

## 2) Какие задачи решает пакет

## 2.1 Бизнес-задачи

1. **Аудит и комплаенс:** прозрачная история изменений по объектам и пользователям.
2. **Контроль риска изменений:** быстрый откат ошибочных правок к предыдущей версии.
3. **Скорость расследований:** оператор видит «что, когда, кем изменено» без чтения сырого JSON.
4. **Подотчётность:** rollback фиксируется как отдельное событие в истории.

## 2.2 Технические задачи

1. **Связка activity + snapshot:** `activity_log` + `model_versions` через `activity_id`.
2. **Read-only глобальный журнал:** запрет write-abilities в `MoonTrailResource`.
3. **Secure-by-default rollback:** единый резолвер авторизации + UI-синхронизация.
4. **DX и внедрение:** installer `moontrail:install`, auto-track, menu helper, publish tags.
5. **Расширяемость:** контракты и IoC bindings для key-сервисов.

---

## 3) Ключевые отличия от аналогов

## 3.1 От `moonshine/changelog`

По исходному плану пакет создавался как более зрелый слой поверх battle-tested engine Spatie: richer metadata, версионирование, транзакционный rollback, визуальный diff и работа вне узкого MoonShine-only сценария.

## 3.2 От «чистого Spatie activitylog»

Spatie даёт engine логирования событий, но не даёт из коробки:

1. Встроенный MoonShine UI (global resource + timeline + detail).
2. Snapshot-versioning (`model_versions`).
3. Rollback-сервис с транзакционной моделью и UX-потоком в админке.

## 3.3 Продуктовая дифференциация

Пакет позиционируется как **операторский audit cockpit** для MoonShine, а не как библиотека логирования «только для разработчика».

---

## 4) Ключевые фичи (фактически в коде)

1. **Global Activity Resource** (index/detail, read-only, фильтры, поиск).
2. **Detail layout из 4 секций**: General / Relations / Changes / History.
3. **Timeline с lazy inline diff**, loading/error/retry, no-repeat fetch.
4. **Masked/hidden безопасность diff** (`hidden_fields` + `masked_fields`, приоритет hidden).
5. **Rollback flow**: auth resolver, modal confirmation, toast, запись `rolled_back` версии.
6. **SoftDeletes rollback path** (включая восстановление soft-deleted моделей).
7. **Installer `moontrail:install`** с safe mode/auto-patch и non-interactive веткой.
8. **Auto-tracking сторонних моделей** через конфиг без добавления trait.
9. **Prune command** для обслуживания ретенции истории.
10. **Контрактная архитектура**: `DiffRendererContract`, `VersionManagerContract`, `RollbackStrategyContract`, `ActivityFormatterContract`.

---

## 5) Ценность для бизнеса

## 5.1 Для конечного бизнеса

1. **Снижение операционных потерь**: rollback вместо ручного восстановления данных.
2. **Compliance-ready аудит**: прозрачный trace изменений и rollback-событий.
3. **Сокращение MTTR расследований**: оператор видит diff и связи causer/subject в одном экране.
4. **Контроль доступа к опасным операциям**: rollback по policy/разрешениям.

## 5.2 Для команды разработки и админов

1. **Наблюдаемость изменений** в MoonShine без custom-инструментов.
2. **Снижение стоимости сопровождения** за счёт installer, menu helper и auto-track.
3. **Гибкость расширения** через контракты, без форка ядра.
4. **Единый UX-паттерн** для index/detail/timeline/diff.

---

## 6) Ограничения и границы ответственности

## 6.1 Что входит в ответственность пакета

1. UI и backend-поток аудита/версий/rollback внутри Laravel + MoonShine.
2. Интеграция с Spatie activity log как источником событий.
3. Безопасный rollback в пределах модели (атрибутов), включая soft-deleted path.

## 6.2 Что не входит

1. **Полноценный multi-framework runtime** (Symfony/Yii3).
2. **UI без Tailwind/Alpine в host app** (пакет требует host frontend wiring).
3. **Откат сложных relation graphs** (`BelongsToMany`/`HasMany`) как продуктовая гарантия.
4. **Независимый от Laravel storage/event/DI контур**.

## 6.3 Выявленные ограничения финализации

1. В репозитории есть документные/именовые рассинхронизации (`moontrail` vs `moon-trail`) и устаревшие URL/формулировки.
2. Исторические документы V1/V2 содержат частично устаревшие решения, что увеличивает риск неправильного внедрения.

---

## 7) Источники фактов

- `MOONSHINE-ACTIVITY-LOG-PLAN.md` (разделы 1, 2, 5, 6, 7, 10)
- `docs/MASTER-PLAN.md`
- `docs/v2/ACCEPTANCE-REPORT.md` (release gate, addendum hardening)
- `docs/v2/00-CURRENT-STATE-AND-GAPS.md`
- `src/Resources/MoonTrailResource.php`
- `src/Components/ActivityTimeline.php`
- `resources/views/components/activity-timeline.blade.php`
- `resources/views/components/diff-viewer.blade.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Versioning/RollbackService.php`
- `src/Versioning/RollbackAuthorizationResolver.php`
- `src/Console/Commands/InstallMoonTrailCommand.php`
- `config/moontrail.php`
- `routes/moontrail.php`
- `tests/Feature/ActivityLogResourceTest.php`
- `tests/Feature/ActivityTimelineComponentTest.php`
- `tests/Feature/RollbackControllerTest.php`
- `tests/Feature/InstallCommandTest.php`
- `tests/Feature/AutoTrackActivityTest.php`

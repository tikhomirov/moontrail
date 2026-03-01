# 02 — ТЗ на финализацию релиза (v3 re-baseline)

## 0) Текущее состояние (кратко)

V2 формально закрыт (release gate PASS, GO), но для финализации релиза v3 остаются приоритетные задачи консистентности: устранение рассинхронизаций в naming/URL/docs, выравнивание фактических package coordinates, и фиксация единого release-ready baseline.

---

## 1) Re-baseline: исходный план vs текущая реализация

## 1.1 Что было запланировано

- Ядро: diff + versioning + rollback.
- UI: global resource + detail + timeline/diff UX.
- Security: read-only audit + secure rollback.
- DX: installer + menu integration.
- Release gate: браузер + логи + `composer ci`.

## 1.2 Что реализовано по факту

1. Core-фичи реализованы (versioning/diff/rollback).
2. Read-only `MoonTrailResource` реализован.
3. Фильтры/поиск/masked реализованы.
4. Installer реализован.
5. Rollback security/UX реализован.
6. V2 acceptance report зафиксирован как GO.

## 1.3 Что осталось на финализацию

Не разработка новых крупных фич, а **release hardening**:

1. Снять противоречия в docs и package metadata.
2. Привести route/resource URL references к фактическому состоянию.
3. Убрать legacy/устаревшие формулировки, мешающие внедрению.
4. Зафиксировать единую финальную матрицу готовности.

---

## 2) Список доработок (P0/P1/P2)

## P0-01 — Консистентность package coordinates и install docs

- **Проблема:** в документации и коде встречаются разные package coordinates (`tikhomirov/moon-trail` vs `tikhomirov/moontrail`), что даёт риск ошибочной установки и обновления.
- **Ожидаемое поведение:** во всех актуальных документах и примерах используется единый, фактический пакетный идентификатор.
- **Конкретные изменения:**
  - docs: обновить все install/update snippets в root/docs/v2/docs/v3;
  - проверить `README.md`, `UPGRADE-GUIDE`, чеклисты, планы;
  - добавить раздел «canonical coordinates» в release readiness док.
- **DoD:**
  - grep по `tikhomirov/moon-` показывает только согласованные значения;
  - нет конфликтующих инструкций в корневых и v2/v3 документах.
- **Риски:** пользователи не смогут повторить установку/апдейт по докам.
- **Оценка:** **S**.

## P0-02 — Нормализация route/resource URL в документации и installer summary

- **Проблема:** в code/docs присутствуют устаревшие или противоречивые URL (например, `moon-trail-resource/moon-trail-index-page` vs `moontrail-resource/index-page`).
- **Ожидаемое поведение:** все ссылки и next steps используют фактический рабочий URL.
- **Конкретные изменения:**
  - исправить вывод `InstallMoonTrailCommand::printSummary()`;
  - унифицировать URL в README, TZ, acceptance/checklists.
- **DoD:**
  - installer печатает валидный URL;
  - документация содержит один canonical URL-паттерн.
- **Риски:** ложные сигналы «пакет не работает» при первом запуске.
- **Оценка:** **S**.

## P0-03 — Финальный документный baseline (single source of truth)

- **Проблема:** одновременно существуют V1/V2/V3 артефакты с разными предпосылками.
- **Ожидаемое поведение:** явно обозначен актуальный комплект документов для релиза.
- **Конкретные изменения:**
  - в `docs/v3` зафиксировать финальную структуру: описание, TZ, gap matrix, competitor, portability, readiness;
  - добавить mapping «какие документы устарели/архивные». 
- **DoD:**
  - есть список canonical docs для релиза;
  - есть явная пометка legacy-доков как исторических.
- **Риски:** команда внедрения читает устаревшие сценарии.
- **Оценка:** **S**.

## P1-01 — Исправление legacy references в Tailwind warning/README

- **Проблема:** в warning-текстах/README встречаются legacy пути vendor (`moon-trail`) и несогласованная терминология бренда.
- **Ожидаемое поведение:** warning и docs совпадают с реальной структурой установки.
- **Конкретные изменения:**
  - `MoonTrailServiceProvider::warnAboutTailwindDependency()` — выровнять vendor content paths;
  - README troubleshooting/requirements — синхронизировать примеры.
- **DoD:**
  - warning соответствует актуальному package install path;
  - примеры `tailwind.config.*` совпадают с runtime ожиданиями.
- **Риски:** ложные предупреждения, невалидная диагностика host app.
- **Оценка:** **M**.

## P1-02 — Уточнение rollback semantics в документации

- **Проблема:** в старых документах встречаются устаревшие формулировки (например, про deny hint vs скрытие кнопок, матрица HTTP статусов).
- **Ожидаемое поведение:** docs отражают фактическую реализацию `RollbackAuthorizationResolver`, `RollbackController`, `RollbackService`.
- **Конкретные изменения:**
  - обновить security/rollback секции README + acceptance docs;
  - синхронизировать с реальными test-сценариями.
- **DoD:**
  - docs и тесты не противоречат друг другу;
  - в release checklist есть отдельный пункт проверки rollback semantics.
- **Риски:** неверные ожидания у операторов и интеграторов.
- **Оценка:** **M**.

## P1-03 — Актуализация DX-документации installer

- **Проблема:** installer реализован шире исходного TZ, но docs частично отстают или конфликтуют по поведению.
- **Ожидаемое поведение:** документировано фактическое поведение interactive/non-interactive/safe/auto-patch.
- **Конкретные изменения:**
  - README + docs/v3 обновить по фактическому command flow;
  - добавить edge cases: missing config, production confirm, cache clear after assets.
- **DoD:**
  - docs покрывают все ветки из `InstallMoonTrailCommand`;
  - есть проверяемый сценарий для no-interaction.
- **Риски:** ошибки первичного онбординга.
- **Оценка:** **M**.

## P2-01 — Конкурентный пакетный месседжинг и позиционирование

- **Проблема:** ценностное позиционирование не закреплено как сравнительная матрица для релиза.
- **Ожидаемое поведение:** есть чёткая таблица «где сильнее/слабее» и roadmap усилений.
- **Конкретные изменения:**
  - оформить `04-COMPETITOR-ANALYSIS.md` с actionable рекомендациями.
- **DoD:**
  - минимум 3 сравнения;
  - есть план усилений на ближайшие 1–2 релиза.
- **Риски:** слабая рыночная коммуникация.
- **Оценка:** **S**.

## P2-02 — Принятие решения по portability

- **Проблема:** нет формального go/no-go по отвязке от Laravel/MoonShine.
- **Ожидаемое поведение:** зафиксирован выбор стратегии A/B/C с критериями.
- **Конкретные изменения:**
  - оформить `05-PORTABILITY-ANALYSIS.md` с оценкой затрат и этапами.
- **DoD:**
  - есть decision record с критериями принятия решения.
- **Риски:** размытие roadmap и ресурсные потери.
- **Оценка:** **M**.

---

## 3) Матрица «задумано / реализовано / gap / риск / действие» (сводно)

| Область | Задумано | Реализовано | Gap | Риск | Действие |
|---|---|---|---|---|---|
| Package identity | Единый идентификатор и бренд | Частично консистентно | Разные coordinates в docs/code | Ошибки установки | P0-01 |
| URL consistency | Единый рабочий URL | Частично | Устаревшие URL в части артефактов | Ложные дефекты UX | P0-02 |
| Release baseline | Single source of truth | Частично | Несколько поколений ТЗ без жёсткого статуса | Ошибки внедрения | P0-03 |
| Tailwind diagnostics | Точные host warnings | Частично | Legacy path refs | Ложные предупреждения | P1-01 |
| Rollback docs | 1:1 с кодом | Частично | Разнобой формулировок | Неверные ожидания | P1-02 |
| Installer DX docs | 1:1 с command flow | Частично | Неполное покрытие веток | Ошибки первичной настройки | P1-03 |

---

## 4) Пошаговый план внедрения (чекбоксы)

## Этап A — P0 (release-blocking)

- [ ] Зафиксировать canonical package coordinates и обновить все install/update snippets.
- [ ] Исправить URL в installer summary и документации.
- [ ] Проставить статус legacy/canonical для планов V1/V2/V3.
- [ ] Прогнать docs-аудит по grep-шаблонам (`moon-trail`, `moontrail-resource`, `index-page`).

## Этап B — P1 (стабилизация релиза)

- [ ] Синхронизировать Tailwind warning paths и README примеры.
- [ ] Привести rollback документацию к фактическому поведению контроллера/сервиса/резолвера.
- [ ] Обновить installer документацию под real command flow.

## Этап C — P2 (продуктовое закрытие цикла)

- [ ] Утвердить конкурентный анализ и список усилений на следующий релиз.
- [ ] Утвердить portability-стратегию (A/B/C) и go/no-go критерии.

## Этап D — Финальная верификация

- [ ] Проверка `composer ci`.
- [ ] Проверка browser smoke по canonical URL.
- [ ] Проверка логов хоста после smoke.
- [ ] Обновление `06-RELEASE-READINESS-CHECKLIST.md` и финальный статус.

---

## 5) Зависимости по реализации

1. P0-01 и P0-02 должны быть завершены до финального release readiness verdict.
2. P1-01 зависит от решения P0-01 (каноничные package paths).
3. P1-02 зависит от кода rollback (уже реализован) и тестов как источника истины.
4. P2 задачи не блокируют hotfix-release, но блокируют продуктовую финализацию цикла.

---

## 6) Источники фактов

- `MOONSHINE-ACTIVITY-LOG-PLAN.md`
- `docs/MASTER-PLAN.md`
- `docs/v2/ACCEPTANCE-REPORT.md`
- `DEVELOPMENT_CHECKLIST.md`
- `README.md`
- `CHANGELOG.md`
- `src/Console/Commands/InstallMoonTrailCommand.php`
- `src/MoonTrailServiceProvider.php`
- `src/Http/Controllers/RollbackController.php`
- `src/Versioning/RollbackService.php`
- `src/Versioning/RollbackAuthorizationResolver.php`
- `tests/Feature/InstallCommandTest.php`
- `tests/Feature/RollbackControllerTest.php`
- `tests/Feature/ActivityTimelineComponentTest.php`

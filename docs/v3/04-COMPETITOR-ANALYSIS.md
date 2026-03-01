# 04 — Сравнительный анализ с конкурентами

## 0) Текущее состояние

MoonTrail уже имеет рабочий enterprise-контур для MoonShine (diff + versioning + rollback + UI), но для финализации релиза нужно формально зафиксировать конкурентное позиционирование и зоны усиления.

---

## 1) Методика сравнения

Критерии (обязательные):

1. Versioning
2. Diff
3. Rollback
4. UI/Admin integration
5. Extensibility
6. Ecosystem maturity
7. Complexity
8. Cost of adoption

Сравниваемые решения:

1. **MoonTrail** (`tikhomirov/moontrail`)
2. **Spatie Laravel Activitylog**
3. **OwenIt Laravel Auditing**
4. **MoonShine Changelog** (референс из исходного плана)

---

## 2) Матрица сравнения

| Критерий | MoonTrail | Spatie Activitylog | OwenIt Auditing | MoonShine Changelog |
|---|---|---|---|---|
| Versioning | **Да** (`model_versions`, нумерация версий) | Нет встроенного snapshot-versioning | Частично (audit records, не полноценный snapshot-version manager как отдельный слой) | Ограниченно (по исходному плану — без полноценной нумерации версий) |
| Diff | **Да** (FieldChange + HTML diff + UI diff-viewer) | Нет полноценного UI diff из коробки | Есть audit data compare на уровне пакета, но без MoonShine-native UX | Базовый before/after, менее структурирован |
| Rollback | **Да** (transactional rollback, auth matrix, soft-delete path) | Нет встроенного rollback workflow | Обычно требует кастомной логики отката | Упрощённый сценарий без явного enterprise security стандарта |
| UI/Admin integration | **Сильная** (MoonShine Resource + Timeline + Tab) | Нет админ-UI в пакете | Нет MoonShine-native UI | Есть интеграция с MoonShine, но более узкий контур |
| Extensibility | **Высокая** (контракты + IoC bindings) | Высокая на уровне engine/hooks | Высокая на уровне drivers/transformers | Ниже относительно MoonTrail |
| Ecosystem maturity | Средняя (молодой специализированный пакет) | **Очень высокая** (де-факто стандарт для activity logging в Laravel) | Высокая (зрелый Laravel-аудит пакет) | Нишевая |
| Complexity | Средняя (больше, чем engine-only) | Низкая/средняя | Средняя | Низкая |
| Cost of adoption | Средняя (UI + конфиг + host Tailwind/Alpine) | Низкая | Средняя | Низкая/средняя |

---

## 3) Преимущества и недостатки

## 3.1 MoonTrail — преимущества

1. Целевой UX для оператора MoonShine (не только developer logs).
2. Полноценный rollback flow с безопасностью и audit trace.
3. Явный versioning-слой и history timeline.
4. Гибкие точки расширения через контракты.

## 3.2 MoonTrail — слабые стороны

1. Привязка к Laravel + MoonShine + host frontend требованиям (Tailwind/Alpine).
2. Ниже ecosystem maturity по сравнению со Spatie/OwenIt.
3. Наличие документных legacy-следов повышает стоимость внедрения без финального hardening.

## 3.3 Spatie Activitylog — сильные стороны

1. Зрелость и устойчивость engine.
2. Простая интеграция в Laravel-проекты.
3. Богатые metadata events.

## 3.4 Spatie Activitylog — слабые стороны относительно MoonTrail

1. Нет готового MoonShine UI audit cockpit.
2. Нет встроенного snapshot versioning + rollback UI workflow.

## 3.5 OwenIt Auditing — сильные стороны

1. Специализация на аудит-trail уровне модели.
2. Зрелый Laravel ecosystem footprint.

## 3.6 OwenIt Auditing — слабые стороны относительно MoonTrail

1. Нет MoonShine-native UI и готового rollback UX.
2. Потребуется дополнительная сборка UI/операторских сценариев.

## 3.7 MoonShine Changelog — сильные стороны

1. Нативная близость к MoonShine.
2. Низкий порог входа для базового журнала изменений.

## 3.8 MoonShine Changelog — слабые стороны относительно MoonTrail

1. Меньшая глубина versioning/rollback-контура.
2. Более ограниченная модель diff и metadata.

---

## 4) Позиционирование MoonTrail

**Позиция:** «MoonShine-first advanced audit layer» между engine-пакетами (Spatie/OwenIt) и простыми changelog-интеграциями.

- Для команд, которым нужен только event log: достаточно Spatie.
- Для команд, которым нужен operator-grade audit UI + rollback в MoonShine: MoonTrail даёт более полный контур из коробки.

---

## 5) Где MoonTrail объективно сильнее/слабее

## Сильнее

1. End-to-end сценарий «увидеть diff → принять решение → выполнить rollback → зафиксировать rollback в истории».
2. MoonShine-native UI (resource/detail/timeline/tab).
3. Контрактная архитектура для кастомных рендеров и стратегий.

## Слабее

1. Ecosystem maturity и «полевое время» уступают лидерам Laravel-экосистемы.
2. Узкая фокусировка на MoonShine ограничивает portability.
3. Документная консистентность требует финального hardening (P0/P1 из TZ финализации).

---

## 6) Рекомендации по усилению конкурентных преимуществ

1. **Закрыть P0-консистентность docs/identity/URLs** как обязательный release blocker.
2. **Подготовить короткий migration playbook** (install, upgrade, rollback security) с copy-paste сценариями.
3. **Укрепить trust signals:** зафиксировать в README стабильный matrix тестов и smoke сценариев.
4. **Усилить продуктовую демонстрацию:** добавить компактный demo workflow (5 минут до рабочего audit UI).
5. **Сделать дорожную карту vNext**: relation rollback policy, deeper JSON diff, экспорт.

---

## 7) Источники

Внутренние источники репозитория:

- `MOONSHINE-ACTIVITY-LOG-PLAN.md` (разделы про сравнение с moonshine/changelog, архитектуру и roadmap)
- `docs/MASTER-PLAN.md`
- `docs/v2/README.md`
- `README.md`
- `src/Resources/MoonTrailResource.php`
- `src/Components/ActivityTimeline.php`
- `src/Versioning/RollbackService.php`
- `src/Contracts/*`

Внешний контекст (для общерыночной зрелости):

- Официальные документы Spatie Activitylog
- Официальные документы OwenIt Laravel Auditing

> Примечание: для юридически строгого vendor benchmark (метрики adoption/downloads/stars на дату релиза) требуется отдельный snapshot внешних данных в release day.

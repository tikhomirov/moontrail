---
description: TZ-03 implementation checklist
---

# TZ-03 — чеклист реализации

- [x] Проанализировать требования TZ-03 и зафиксировать scope изменений
- [x] Расширить фильтры MoonTrailResource до полного набора (8 полей)
- [x] Поддержать dual query-формат (`field` и `filters[field]`) в запросе
- [x] Добавить поиск по `properties` с кросс-БД fallback
- [x] Реализовать `masked_fields` в DiffComputer с приоритетом `hidden_fields`
- [x] Обновить diff-viewer UX для masked-строк (без expand/copy/утечек)
- [x] Обновить config и i18n-ключи
- [x] Добавить/обновить feature + unit тесты по TZ-03
- [x] Прогнать целевые тесты

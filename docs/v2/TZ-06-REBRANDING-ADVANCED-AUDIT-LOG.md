# TZ-06 — Ребрендинг пакета в MoonShine Logs

## 0) Краткое текущее состояние

Функционально пакет уже позиционируется как **MoonShine Logs** c package name `tikhomirov/moon-trail`, но в кодовой базе сохраняются legacy-технические идентификаторы для обратной совместимости. Нужен управляемый ребрендинг, который не ломает существующие интеграции без явного плана миграции.

---

## 1) Цель этапа

Перевести пакет на бренд **MoonShine Logs** так, чтобы:
- маркетинговое и продуктовое позиционирование совпадало с функционалом;
- существующие пользователи понимали миграционный путь;
- релиз соответствовал semver и был воспроизводим в CI.

---

## 2) Кто реализует

- **Ведущий:** Tech Lead / Package Maintainer.
  - Навыки: Composer/Packagist, semver, Laravel package publishing.
- **Соисполнитель:** Senior Laravel developer.
  - Навыки: namespace/config backward compatibility.
- **Техрайтер:** обновление README/upgrade guide.

---

## 3) Стратегии ребрендинга (выбрать одну)

## 3.1 Стратегия A (рекомендуется для ближайшего релиза)

**Совместимый ребрендинг (минимум рисков):**
- новое продуктовое имя в UI/README: **MoonShine Logs**;
- `composer name` можно сменить или отложить (решение владельца пакета);
- namespace PHP оставить `MoonShine\MoonTrail\`;
- config key/file оставить текущими (для обратной совместимости).

Плюсы:
- минимум breaking changes;
- проще обновление пользователей.

Минусы:
- часть старого нейминга остается в технических деталях.

## 3.2 Стратегия B (полный ребрендинг)

**Breaking change:**
- composer package name сменить;
- namespace сменить, например `MoonShine\AdvancedAuditLog\`;
- config/view namespace мигрировать.

Обязательные условия:
- мажорная версия;
- полный upgrade guide с mapping старое->новое;
- миграционные alias/compat слой (по возможности).

---

## 4) Обязательные изменения (для любой стратегии)

## 4.1 Metadata и документация

- `composer.json`:
  - актуализировать `description` под MoonShine Logs;
  - при утверждении — обновить `name`.
- `README.md`:
  - заголовок, позиционирование, разделы установки/миграции;
  - явно фиксировать текущий package name: `tikhomirov/moon-trail`.
- `CHANGELOG.md`:
  - явно описать ребрендинг и impact.

## 4.2 Локализация UI

Обновить ключи и текст в:
- `lang/en/ui.php`
- `lang/ru/ui.php`

Минимум:
- название продукта в меню/заголовках, где это уместно;
- тексты не должны вводить в заблуждение относительно capabilities.

## 4.3 Upgrade guide (обязательно)

Создать отдельный документ `docs/v2/UPGRADE-GUIDE-REBRANDING.md` с разделами:
1. Кому нужно обновляться.
2. Шаги для стратегии A.
3. Шаги для стратегии B.
4. Известные несовместимости.
5. Чеклист верификации после обновления.

---

## 5) Детализация по стратегиям

## 5.1 Если выбран путь A

### Изменить
- UI-нейминг на MoonShine Logs.
- README/позиционирование/примеры.
- Возможный новый composer package alias (по решению владельца).

### Не менять
- namespace классов;
- config file/key;
- view namespace.

### semver
- минорная версия (если без breaking).

## 5.2 Если выбран путь B

### Изменить
- package name в composer;
- namespace классов;
- при необходимости config/view namespace.

### Обязательная совместимость
- временные class aliases или deprecation слой (если возможно);
- понятные runtime warnings для старых точек расширения.

### semver
- мажорная версия.

---

## 6) Примеры артефактов

## 6.1 README header

```markdown
# MoonShine Логи
Package: tikhomirov/moon-trail
```

## 6.2 Upgrade snippet (composer)

```bash
# пример (если package name меняется)
composer remove tikhomirov/moon-trail
composer require tikhomirov/moon-trail
php artisan optimize:clear
php artisan vendor:publish --tag=<current-config-tag> --force
```

(финальные команды фиксируются после утверждения стратегии)

---

## 7) Тесты и проверки

## 7.1 Автотесты
- `composer ci` в пакете — без ошибок.
- Тесты на загрузку service provider/ресурсов проходят после ребрендинга.

## 7.2 Ручная проверка
- Открыть: `http://localhost:8000/admin/resource/moontrail-resource/index-page`.
- Проверить корректный бренд в UI (где применимо).
- Проверить, что меню и маршруты работают.

## 7.3 Логи
- `storage/logs/laravel.log` в хосте без новых ошибок namespace/config/view resolution.

---

## 8) Acceptance (DoD)

- Выбрана и зафиксирована стратегия A или B.
- Обновлены metadata, README, локализации.
- Подготовлен `UPGRADE-GUIDE-REBRANDING.md`.
- Семантическая версия релиза соответствует степени изменений.
- `composer ci` зеленый.
- UI и роуты работают в хост-приложении без ошибок в логах.

---

## 9) Что не входит в этап

- Разработка новых функциональных фич (filters/rollback/installer).
- Переписывание ядра версии/rollback.

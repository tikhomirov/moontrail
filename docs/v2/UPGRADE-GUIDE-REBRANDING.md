# Upgrade Guide — Rebranding to MoonShine Logs

## 0) Текущее состояние

Пакет перешёл на продуктовое имя **MoonShine Logs** и composer-координаты `tikhomirov/moon-trail`.
Для текущего релиза сохранена техническая совместимость по namespace/config.

---

## 1) Кому нужно обновляться

Обновление актуально для:
- текущих пользователей `tikhomirov/moon-trail`, которые обновляют пакет до релиза с ребрендингом;
- новых пользователей, которым важно понимать текущее имя продукта и старые технические идентификаторы.

Если у вас уже рабочая интеграция пакета, обновление выполняется без миграции namespace/config в стратегии A.

---

## 2) Шаги для стратегии A (совместимая, текущий релиз)

### Что меняется
- UI/документация используют бренд **MoonShine Logs**.
- Обновлены metadata и тексты локализации, связанные с названием продукта.

### Что НЕ меняется
- PHP namespace: `MoonShine\MoonTrail\...`
- Config key/file: `moontrail`
- Publish tags:
  - `moontrail-config`
  - `moontrail-views`
  - `moontrail-lang`

### Команды обновления

```bash
composer update tikhomirov/moon-trail --with-all-dependencies
php artisan optimize:clear
```

Если вы публиковали переводы/вьюхи и хотите получить новые тексты брендинга:

```bash
php artisan vendor:publish --tag=moontrail-lang --force
# опционально
php artisan vendor:publish --tag=moontrail-views --force
```

---

## 3) Шаги для стратегии B (breaking, на будущее)

> Стратегия B в текущем релизе НЕ применяется.
> Ниже — шаблон миграции, если будет принято решение о полном ребрендинге.

### План миграции
1. Перейти на новый package name (после официального анонса).
2. Обновить namespace в коде проекта (например, на `MoonShine\AdvancedAuditLog\...`).
3. Перенести config key/file и view namespace на новые имена.
4. Прогнать deprecation-слой/aliases (если предоставлен релизом).
5. Выполнить полный регресс в UI и rollback-флоу.

### Пример (шаблон)

```bash
composer update tikhomirov/moon-trail --with-all-dependencies
php artisan optimize:clear
```

---

## 4) Известные несовместимости

### Для стратегии A
- breaking changes отсутствуют по namespace/config/routes/view namespace;
- возможны расхождения UI-текстов, если в хосте опубликованы старые `lang` файлы и не выполнен `vendor:publish --force`.

### Для стратегии B (потенциально)
- несовместимость импортов/namespace;
- необходимость миграции конфигурации и publish-путей;
- возможные изменения точек расширения (IoC bindings/contracts) с deprecation-периодом.

---

## 5) Чеклист верификации после обновления

- [ ] `composer update` завершился без конфликтов зависимостей.
- [ ] Страница ресурса открывается: `/admin/resource/moontrail-resource/index-page`.
- [ ] В меню/заголовках отображается бренд **MoonShine Logs**.
- [ ] Rollback и diff UI продолжают работать как раньше.
- [ ] В логе хост-приложения нет новых ошибок namespace/config/view resolution.
- [ ] В пакете проходит `composer ci`.

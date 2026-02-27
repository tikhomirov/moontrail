# Инструкция по старту разработки `rwsite/moonshine-activity-log`

## 1) Что уже подготовлено

### Пакет
- Путь: `/home/alex/Projects/moonshine-activity-log`
- Отдельный git-репозиторий
- Базовая структура директорий создана
- `composer.json` настроен для Laravel-пакета

### Интеграция с обзорным приложением
- Приложение: `/home/alex/Projects/moonshine-app`
- В `composer.json` приложения подключен локальный пакет через `path` repository с `symlink: true`
- Пакет добавлен в `require` как `rwsite/moonshine-activity-log: @dev`
- Выполнено обновление зависимостей и очистка кешей

## 2) Где выполнять команды

### Команды пакета
Выполнять в:
```bash
/home/alex/Projects/moonshine-activity-log
```

### Команды приложения для проверки
Выполнять в:
```bash
/home/alex/Projects/moonshine-app
```

## 3) Проверка после каждого изменения в пакете

В `moonshine-app`:
```bash
composer dump-autoload
php artisan optimize:clear
```

Далее открыть:
- `http://127.0.0.1:8000/admin`

## 4) Если пакет не подхватывается

В `moonshine-app`:
```bash
composer update rwsite/moonshine-activity-log --with-all-dependencies
php artisan optimize:clear
```

## 5) Команды первого запуска окружения (если не запущено)

В `moonshine-app`:
```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan serve
```

Открыть:
- `http://127.0.0.1:8000/admin`

## 6) Что НЕ сделано специально

Реализация бизнес-логики пакета (diff/versioning/rollback) не завершалась в рамках подготовки среды.

Сделаны только стартовые инфраструктурные файлы и заглушки, чтобы:
1. пакет корректно подключался в приложение,
2. работал package-discovery,
3. можно было сразу начинать разработку по плану.

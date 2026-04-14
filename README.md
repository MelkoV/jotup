# Jotup

Backend [Jotly](https://jotly.ru)

Минималистичный PHP API-проект с собственным HTTP-ядром на PSR-7/PSR-15, PostgreSQL, Redis и консольными командами на Symfony Console.

## Стек

- PHP 8.5
- PostgreSQL 17
- Redis 7
- Yii DB
- PHPUnit 11

## Консольные команды

Все команды доступны через:

```bash
php bin/console
```

Полезные команды:

```bash
php bin/console app:about
php bin/console app:db:smoke
php bin/console app:avatar:consume
php bin/console migrate:up
php bin/console migrate:history
```

## Consumer очереди аватаров

Команда:

```bash
php bin/console app:avatar:consume
```

Полезные опции:

```bash
php bin/console app:avatar:consume --once
php bin/console app:avatar:consume --timeout=5 --retry-delay=2
```

Сейчас consumer умеет переживать временные ошибки Redis и переподключаться в следующем цикле.

Для production лучше запускать его под `supervisor` или `systemd`.

Пример `supervisor`:

```ini
[program:jotup-avatar-consumer]
command=/usr/bin/php /var/www/html/bin/console app:avatar:consume --timeout=5 --retry-delay=2
directory=/var/www/html
user=www-data
autostart=true
autorestart=true
startsecs=3
startretries=999
stopwaitsecs=10
redirect_stderr=true
stdout_logfile=/var/log/supervisor/jotup-avatar-consumer.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
```

## Тесты

Запуск всех тестов:

```bash
php vendor/bin/phpunit --configuration phpunit.xml.dist
```

Запуск одного файла:

```bash
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Http/UserApiTest.php
```

## Структура проекта

- `app` — прикладной код, сервисы, репозитории, middleware, команды
- `src` — ядро приложения, контейнер, HTTP, БД, логирование
- `config` — конфиги
- `routes` — описание маршрутов
- `database/Migrations` — миграции
- `tests` — тесты

## Замечание про Swoole

Проект уже умеет стартовать через Swoole, но перед полноценной production-эксплуатацией стоит внимательно проверить lifetime shared-сервисов, особенно соединения к PostgreSQL и Redis в long-running worker-процессе.

# Database Migrations

Миграции лежат в этой папке и загружаются через `yiisoft/db-migration` и наше `bin/console`.

## Команды

- `composer migrate:history` — история применённых миграций
- `composer migrate:new` — показать новые миграции
- `composer migrate:create -- users --command=table` — создать заготовку миграции
- `composer migrate:up` — применить новые миграции
- `composer migrate:down` — откатить последнюю миграцию
- `composer migrate:redo` — откатить и применить заново

## Соглашения

- Имена файлов и классов должны начинаться с `MYYMMDDHHMMSS...`
- Для таблиц используем `{{%table_name}}`, чтобы работал `tablePrefix`
- Новые миграции по умолчанию создаются сюда: `database/Migrations`
- В проект уже перенесены Laravel-миграции из `jotly-laravel`
- `M260411120001CreateCacheTable.php` и `M260411120002CreateJobsTable.php` оставлены как no-op, потому что в исходном Laravel-проекте их `up()` был закомментирован

## Запуск консоли напрямую

```bash
php bin/console list
php bin/console migrate:up
```

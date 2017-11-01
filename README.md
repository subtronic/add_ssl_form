# Форма добавления сертификата SSL с последующей генерацией простого nginx конфига

## Для работы необходимо
1) Прописать include для генерируемых конфигураций nginx
```
include /path/to/the/project/nginx/*;
```
2) Добавить в cron выполнение daemon каждый 5 минут.
```
*/5 * * * *	    /path/for/the/phpbin/php -f /path/to/the/project/daemon
```
3) nginx_config.tpl необходимо изменить под свое окружение.


## debug
`php -dxdebug.remote_enable=1 -dxdebug.remote_autostart=on -dxdebug.remote_mode=req -dxdebug.remote_port=9000 -dxdebug.remote_host=127.0.0.1 artisan vk:export-ads`

## тесты
`php artisan test`


## смена домена
1. прописать домены в DNS регистратора

2. заменить домен в настройках приложения в VK
https://vk.com/editapp?id=7362994&section=options

3. заменить домен в доверенных редирект URL для авторизации в Google консоли

4. заменить переменные APP_URL и GOOGLE_REDIRECT в .env на сервере

5. обновить ssl сертификат на сервера
`certbot --nginx -d $domain -d www.$domain --force-renewal` 

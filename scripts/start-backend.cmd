@echo off
cd /d "%~dp0..\backend"
php artisan serve --host=127.0.0.1 --port=8080


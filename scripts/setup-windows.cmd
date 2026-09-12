@echo off
setlocal
cd /d "%~dp0.."
where php >nul 2>nul
if errorlevel 1 (
 echo PHP is not on PATH. Add C:\xampp\php to your Windows PATH.
 exit /b 1
)
where composer >nul 2>nul
if errorlevel 1 (
 echo Install Composer for Windows first.
 exit /b 1
)
where npm >nul 2>nul
if errorlevel 1 (
 echo Install Node.js LTS first.
 exit /b 1
)
cd backend
if not exist .env copy .env.example .env
call composer install --prefer-dist
if errorlevel 1 exit /b 1
php -r "$a=parse_ini_file('.env'); exit(empty($a['APP_KEY'])?1:0);"
if errorlevel 1 (
 php artisan key:generate
 if errorlevel 1 exit /b 1
)
cd ..\frontend
call npm ci
if errorlevel 1 exit /b 1
call npm run build
if errorlevel 1 exit /b 1
echo Dependencies and frontend build are ready.
echo Next: configure backend\.env, create the commerce_desk database, then follow README.md.
endlocal


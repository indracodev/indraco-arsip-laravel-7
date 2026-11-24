@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Update Runner
color 0B
cls

cd /d "%~dp0"

:: Gunakan PHP portable dari folder evironment jika tersedia
set "PORTABLE_PHP="
for /d %%d in ("%~dp0evironment\php-8.4*") do if exist "%%d\php.exe" set "PORTABLE_PHP=%%~fd"
if defined PORTABLE_PHP (
    set "PATH=!PORTABLE_PHP!;!PATH!"
    set "PHPRC=!PORTABLE_PHP!"
)
set "LOG_FILE=%~dp0storage\logs\git-update.log"
if not exist "%~dp0storage\logs" mkdir "%~dp0storage\logs"

echo =============================================================================== > "%LOG_FILE%"
echo   PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS) >> "%LOG_FILE%"
echo   Git Pull ^& Server Auto-Restart >> "%LOG_FILE%"
echo   Timestamp: %date% %time% >> "%LOG_FILE%"
echo =============================================================================== >> "%LOG_FILE%"
echo. >> "%LOG_FILE%"

echo [*] Memberikan jeda 2 detik agar koneksi HTTP ke browser admin tuntas...
echo [*] Jeda 2 detik untuk flush HTTP response... >> "%LOG_FILE%"
timeout /t 2 /nobreak >nul

echo [*] Menjalankan git pull...
echo [*] Menjalankan git pull... >> "%LOG_FILE%"
git pull >> "%LOG_FILE%" 2>&1
set "GIT_RES=%ERRORLEVEL%"
echo [*] Git pull exit code: %GIT_RES% >> "%LOG_FILE%"
echo. >> "%LOG_FILE%"

echo [*] Memperbarui dependensi PHP (composer install --no-dev)...
echo [*] composer install --no-dev... >> "%LOG_FILE%"
if not exist "%~dp0evironment\composer.phar" (
    powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile '%~dp0evironment\composer.phar'" >> "%LOG_FILE%" 2>&1
)
if exist "%~dp0evironment\composer.phar" (
    call php "%~dp0evironment\composer.phar" install --no-dev --optimize-autoloader --no-interaction >> "%LOG_FILE%" 2>&1
)
echo. >> "%LOG_FILE%"

echo [*] Membersihkan cache aplikasi (views, routes, config)...
echo [*] Membersihkan cache aplikasi... >> "%LOG_FILE%"
call php artisan optimize:clear >> "%LOG_FILE%" 2>&1
echo. >> "%LOG_FILE%"

echo [*] Memeriksa migrasi database...
echo [*] Memeriksa migrasi database... >> "%LOG_FILE%"
call php artisan migrate --force >> "%LOG_FILE%" 2>&1
echo. >> "%LOG_FILE%"

echo [*] Menghentikan server lama di port 8000 (Caddy + PHP)...
echo [*] Menghentikan server lama di port 8000... >> "%LOG_FILE%"
call "%~dp0STOP-DMS-INDRACO.bat" >nul 2>&1
timeout /t 2 /nobreak >nul

echo [*] Menjalankan kembali server DMS PT Indraco...
echo [*] Menjalankan kembali server DMS PT Indraco... >> "%LOG_FILE%"
start "" "%~dp0START-DMS-INDRACO.bat"

echo [%date% %time%] Selesai. >> "%LOG_FILE%"
timeout /t 2 >nul
exit /b 0

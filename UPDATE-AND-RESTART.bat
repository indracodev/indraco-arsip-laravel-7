@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Update Runner
color 0B
cls

cd /d "%~dp0"
set "LOG_FILE=%~dp0storage\logs\git-update.log"
if not exist "%~dp0storage\logs" mkdir "%~dp0storage\logs"

echo =============================================================================== > "%LOG_FILE%"
echo   PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS) >> "%LOG_FILE%"
echo   Git Pull & Server Auto-Restart (Option A) >> "%LOG_FILE%"
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

echo [*] Membersihkan cache aplikasi (views, routes, config)...
echo [*] Membersihkan cache aplikasi... >> "%LOG_FILE%"
call php artisan optimize:clear >> "%LOG_FILE%" 2>&1
echo. >> "%LOG_FILE%"

echo [*] Memeriksa migrasi database...
echo [*] Memeriksa migrasi database... >> "%LOG_FILE%"
call php artisan migrate --force >> "%LOG_FILE%" 2>&1
echo. >> "%LOG_FILE%"

echo [*] Menghentikan service server lama di port 8000...
echo [*] Menghentikan service server lama di port 8000... >> "%LOG_FILE%"

taskkill /fi "WINDOWTITLE eq DMS PT INDRACO - Desktop*" /f /t >nul 2>&1
taskkill /fi "WINDOWTITLE eq DMS Indraco Server*" /f /t >nul 2>&1

for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /f /pid %%a >nul 2>&1
)

timeout /t 2 /nobreak >nul

echo [*] Menjalankan kembali server DMS PT Indraco...
echo [*] Menjalankan kembali server DMS PT Indraco... >> "%LOG_FILE%"

if exist "%~dp0START-DMS-INDRACO.bat" (
    start "" "%~dp0START-DMS-INDRACO.bat"
) else (
    start /b "" php artisan serve --host=0.0.0.0 --port=8000 > "storage\logs\http-access.log" 2>&1
)

echo [%date% %time%] Selesai. >> "%LOG_FILE%"
timeout /t 2 >nul
exit /b 0

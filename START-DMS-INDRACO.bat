@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Desktop Workstation Auto-Launcher
color 0B
cls

echo ===============================================================================
echo              PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                       Desktop Edition Auto-Launcher
echo ===============================================================================
echo.

:: 1. Pindah ke direktori project
cd /d "%~dp0"
echo [*] Memeriksa kelengkapan sistem (System Requirement Check)...
echo.

:: ===============================================================================
:: CHECK 1: PENGECEKAN RUNTIME PHP (LOCAL ENVIRONMENT / SYSTEM / LARAGON / XAMPP)
:: ===============================================================================
set "FOUND_PHP="

:: 1. Prioritas Utama: Folder internal "evironment" atau "environment" lokal project
if exist "%~dp0evironment" (
    for /d %%d in ("%~dp0evironment\php*") do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
)
if not defined FOUND_PHP if exist "%~dp0environment" (
    for /d %%d in ("%~dp0environment\php*") do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
)

:: 2. Prioritas Laragon (Prioritaskan PHP 7.4.x / 7.x / 8.0 / 8.1 yang kompatibel penuh dengan Laravel 7)
if not defined FOUND_PHP if exist "C:\laragon\bin\php" (
    for /d %%d in (C:\laragon\bin\php\php-7.4*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (C:\laragon\bin\php\php-7.*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (C:\laragon\bin\php\php-8.1*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (C:\laragon\bin\php\php-8.0*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (C:\laragon\bin\php\php-*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
)
if not defined FOUND_PHP if exist "D:\laragon\bin\php" (
    for /d %%d in (D:\laragon\bin\php\php-7.4*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (D:\laragon\bin\php\php-7.*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (D:\laragon\bin\php\php-8.1*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (D:\laragon\bin\php\php-8.0*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    if not defined FOUND_PHP for /d %%d in (D:\laragon\bin\php\php-*) do if exist "%%d\php.exe" set "FOUND_PHP=%%d"
)

:: 3. Cek XAMPP C: & D:
if not defined FOUND_PHP if exist "C:\xampp\php\php.exe" set "FOUND_PHP=C:\xampp\php"
if not defined FOUND_PHP if exist "D:\xampp\php\php.exe" set "FOUND_PHP=D:\xampp\php"

:: 4. Fallback ke System PATH
if not defined FOUND_PHP (
    where php >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        for /f "tokens=*" %%p in ('where php 2^>nul') do (
            if not defined FOUND_PHP (
                set "FOUND_PHP=%%~dp"
                set "FOUND_PHP=!FOUND_PHP:~0,-1!"
            )
        )
    )
)

:: -------------------------------------------------------------------------------
:: JIKA PHP DITEMUKAN:
:: -------------------------------------------------------------------------------
if defined FOUND_PHP (
    set "PATH=!FOUND_PHP!;!PATH!"
    echo [OK] PHP Runtime Terdeteksi: !FOUND_PHP!\php.exe
    goto :VERIFY_PHP
)

:: -------------------------------------------------------------------------------
:: JIKA PHP BELUM DITEMUKAN -> OPSI DOWNLOAD & AUTO-SETUP OTOMATIS
:: -------------------------------------------------------------------------------
color 0E
echo ===============================================================================
echo [PERHATIAN] PHP RUNTIME BELUM TERSEDIA DI SISTEM ATAU FOLDER EVIRONMENT
echo ===============================================================================
echo.
echo Aplikasi Laravel membutuhkan PHP Runtime untuk dapat dijalankan.
echo.
echo Pilihan Tindakan:
echo   [1] Download & Siapkan PHP Portable 8.2 Otomatis ke folder "evironment" (Disarankan)
echo   [2] Buka browser untuk download Laragon Full Installer
echo   [3] Keluar
echo.
set /p "CHOICE=Ketik pilihan [1/2/3] lalu tekan Enter: "

if "%CHOICE%"=="1" goto :AUTO_DOWNLOAD_PHP
if "%CHOICE%"=="2" (
    start https://laragon.org/download/
    exit /b 0
)
exit /b 1

:AUTO_DOWNLOAD_PHP
cls
color 0B
echo ===============================================================================
echo              MENGUNDUH DAN MEMPERSIAPKAN PHP RUNTIME OTOMATIS
echo ===============================================================================
echo.
echo [*] Membuat folder evironment...
if not exist "%~dp0evironment" mkdir "%~dp0evironment"

set "PHP_ZIP=%~dp0evironment\php-8.2.zip"
set "PHP_TARGET_DIR=%~dp0evironment\php-8.2.29-Win32-vs16-x64"

echo [*] Mengunduh PHP Portable 8.2 dari server resmi Windows PHP...
powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.2.29-Win32-vs16-x64.zip' -OutFile '%PHP_ZIP%'"

if not exist "%PHP_ZIP%" (
    echo [ERROR] Gagal mengunduh file PHP. Pastikan koneksi internet aktif.
    pause
    exit /b 1
)

echo [*] Mengekstrak file PHP ke folder evironment...
if not exist "%PHP_TARGET_DIR%" mkdir "%PHP_TARGET_DIR%"
powershell -NoProfile -Command "Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_TARGET_DIR%' -Force"
del /f /q "%PHP_ZIP%" >nul 2>&1

echo [*] Mengonfigurasi php.ini & mengaktifkan ekstensi yang dibutuhkan...
if exist "%PHP_TARGET_DIR%\php.ini-development" (
    copy "%PHP_TARGET_DIR%\php.ini-development" "%PHP_TARGET_DIR%\php.ini" >nul
    powershell -NoProfile -Command "$c = Get-Content '%PHP_TARGET_DIR%\php.ini'; $c = $c -replace ';extension_dir = \"ext\"', 'extension_dir = \"ext\"' -replace ';extension=curl', 'extension=curl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' -replace ';extension=sqlite3', 'extension=sqlite3' -replace ';extension=zip', 'extension=zip' -replace ';extension=gd', 'extension=gd'; Set-Content '%PHP_TARGET_DIR%\php.ini' $c"
)

set "FOUND_PHP=%PHP_TARGET_DIR%"
set "PATH=!FOUND_PHP!;!PATH!"
echo [OK] PHP 8.2 Portable siap digunakan!
echo.

:VERIFY_PHP
set "PHPRC=!FOUND_PHP!"

:: Pastikan ekstensi OpenSSL, SQLite, Fileinfo, Mbstring, Curl aktif di php.ini
if exist "!FOUND_PHP!\php.ini" (
    powershell -NoProfile -Command "$ini = '!FOUND_PHP!\php.ini'; $c = Get-Content $ini; if ($c -match ';extension=openssl' -or $c -match ';extension_dir = \"ext\"') { $c = $c -replace ';extension_dir = \"ext\"', 'extension_dir = \"ext\"' -replace ';extension=curl', 'extension=curl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' -replace ';extension=sqlite3', 'extension=sqlite3' -replace ';extension=zip', 'extension=zip' -replace ';extension=gd', 'extension=gd' -replace ';extension=intl', 'extension=intl'; Set-Content $ini $c }" >nul 2>&1
)

for /f "tokens=1,2 delims= " %%a in ('php -v 2^>nul') do (
    if not defined PHP_VERSION_INFO (
        set "PHP_VERSION_INFO=%%a %%b"
    )
)
echo [OK] Versi PHP Aktif: !PHP_VERSION_INFO!

:: ===============================================================================
:: CHECK 2: FILE KONFIGURASI ENVIRONMENT (.env)
:: ===============================================================================
if not exist ".env" (
    echo [*] File .env belum ada. Menginisialisasi dari .env.example...
    if exist ".env.example" (
        copy .env.example .env >nul
        echo [OK] File .env berhasil dibuat.
    ) else (
        echo APP_NAME="DMS PT Indraco"> .env
        echo APP_ENV=local>> .env
        echo APP_KEY=>> .env
        echo APP_DEBUG=true>> .env
        echo APP_URL=http://localhost:8000>> .env
        echo DB_CONNECTION=sqlite>> .env
        echo APP_FONT_SIZE=medium>> .env
        echo [OK] Template .env baru berhasil digenerate.
    )
)

:: ===============================================================================
:: CHECK 2B: DEPENDENSI PHP / VENDOR (vendor/autoload.php)
:: ===============================================================================
if not exist "vendor\autoload.php" (
    echo [*] Memeriksa folder vendor/autoload.php...
    where composer >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        echo [*] Menjalankan composer install untuk menginisialisasi dependensi...
        composer install --no-dev --optimize-autoloader
    ) else (
        echo [PERHATIAN] Folder vendor belum tersedia dan perintah composer tidak ditemukan.
        echo             Harap jalankan 'composer install' di direktori ini terlebih dahulu.
        pause
        exit /b 1
    )
)

:: ===============================================================================
:: CHECK 3: DATABASE SQLITE & DIREKTORI
:: ===============================================================================
if not exist "database\database.sqlite" (
    echo [*] Database SQLite belum ada. Membuat database/database.sqlite...
    type nul > "database\database.sqlite"
    echo [OK] File database.sqlite berhasil dibuat.
    
    echo [*] Menjalankan migrasi tabel database awal dan seeder master...
    php artisan migrate:fresh --seed --force
    echo [OK] Database siap digunakan [SQLite WAL mode aktif].
) else (
    echo [*] Memeriksa pembaruan skema database...
    php artisan migrate --force
)

:: ===============================================================================
:: CHECK 4: APPLICATION ENCRYPTION KEY (APP_KEY)
:: ===============================================================================
findstr /C:"APP_KEY=base64" .env >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [*] Men-generate App Encryption Key...
    php artisan key:generate --force
    echo [OK] Application key berhasil diset.
)

:: ===============================================================================
:: CHECK 5: STORAGE LINK & CACHE
:: ===============================================================================
if not exist "public\storage" (
    php artisan storage:link >nul 2>&1
)

:: ===============================================================================
:: CHECK 6: PRE-COMPILE & CACHE UNTUK PERFORMA INSTAN (AMD A9 DUAL-CORE TUNING)
:: ===============================================================================
echo [*] Memuat cache rute, konfigurasi, dan template Blade ke RAM...
php artisan config:cache >nul 2>&1
php artisan route:cache >nul 2>&1
php artisan view:cache >nul 2>&1

:: ===============================================================================
:: JALANKAN REALTIME SERVER RUNNER (ZERO-SCROLL TUI DASHBOARD)
:: ===============================================================================
echo [*] Meluncurkan Realtime Server Runner (Stationary TUI Dashboard)...

set "ROOT_PATH=%CD%"
if "%ROOT_PATH:~-1%"=="\" set "ROOT_PATH=%ROOT_PATH:~0,-1%"

powershell -NoProfile -ExecutionPolicy Bypass -File "%ROOT_PATH%\scripts\server_runner.ps1" -PhpExe "!FOUND_PHP!\php.exe" -ProjectRoot "%ROOT_PATH%" -Port 8000

if %ERRORLEVEL% NEQ 0 (
    echo [PERHATIAN] Fallback ke artisan serve standar...
    php artisan serve --host=0.0.0.0 --port=8000
)

echo.
echo [OK] Server DMS PT Indraco telah dimatikan secara bersih.
pause


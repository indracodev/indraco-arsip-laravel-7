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

:: 1. Prioritas Utama: Cek folder internal "evironment" atau "environment"
if exist "%~dp0evironment" (
    for /d %%d in ("%~dp0evironment\php-*") do (
        if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    )
    if not defined FOUND_PHP if exist "%~dp0evironment\php\php.exe" set "FOUND_PHP=%~dp0evironment\php"
)

if not defined FOUND_PHP if exist "%~dp0environment" (
    for /d %%d in ("%~dp0environment\php-*") do (
        if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    )
    if not defined FOUND_PHP if exist "%~dp0environment\php\php.exe" set "FOUND_PHP=%~dp0environment\php"
)

:: 2. Cek System PATH
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

:: 3. Cek Laragon C: & D:
if not defined FOUND_PHP if exist "C:\laragon\bin\php" (
    for /d %%d in (C:\laragon\bin\php\php-*) do (
        if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    )
)
if not defined FOUND_PHP if exist "D:\laragon\bin\php" (
    for /d %%d in (D:\laragon\bin\php\php-*) do (
        if exist "%%d\php.exe" set "FOUND_PHP=%%d"
    )
)

:: 4. Cek XAMPP C: & D:
if not defined FOUND_PHP if exist "C:\xampp\php\php.exe" set "FOUND_PHP=C:\xampp\php"
if not defined FOUND_PHP if exist "D:\xampp\php\php.exe" set "FOUND_PHP=D:\xampp\php"

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
:: CHECK 3: DATABASE SQLITE & DIREKTORI
:: ===============================================================================
if not exist "database\database.sqlite" (
    echo [*] Database SQLite belum ada. Membuat database/database.sqlite...
    type nul > "database\database.sqlite"
    echo [OK] File database.sqlite berhasil dibuat.
    
    echo [*] Menjalankan migrasi tabel database awal dan seeder master...
    php artisan migrate:fresh --seed --force
    echo [OK] Database siap digunakan.
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
:: DETEKSI IP NETWORK LOKAL (WIFI / LAN)
:: ===============================================================================
set "LOCAL_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    if "!LOCAL_IP!"=="" (
        set "temp_ip=%%a"
        set "temp_ip=!temp_ip: =!"
        if not "!temp_ip!"=="" set "LOCAL_IP=!temp_ip!"
    )
)
if "!LOCAL_IP!"=="" set "LOCAL_IP=127.0.0.1"

echo.
echo ===============================================================================
echo   SELURUH REQUIREMENT LENGKAP - MEMULAI SERVER DMS PT INDRACO
echo ===============================================================================
echo.
echo   * Direktori Project : %CD%
echo   * IP Jaringan Lokal : !LOCAL_IP!
echo.
echo   * URL Akses Komputer Ini (Localhost) :
echo     --^> http://127.0.0.1:8000  atau  http://localhost:8000
echo.
echo   * URL Akses Jaringan Komputer Lain / HP (WiFi/LAN Kantor) :
echo     --^> http://!LOCAL_IP!:8000
echo.
echo ===============================================================================
echo   PETUNJUK:
echo   - Web browser akan otomatis terbuka dalam 2 detik.
echo   - JIKA JENDELA INI DITUTUP [X] ATAU TEKAN Ctrl+C, SERVER OTOMATIS MATI TOTAL.
echo ===============================================================================
echo.

:: Trigger pembukaan web browser otomatis
start /b "" cmd /c "timeout /t 2 /nobreak >nul & start http://127.0.0.1:8000"

:: Jalankan artisan serve secara foreground
php artisan serve --host=0.0.0.0 --port=8000

echo.
echo [OK] Server DMS PT Indraco telah dimatikan.
timeout /t 2 >nul

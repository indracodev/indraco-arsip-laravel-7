@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Desktop Workstation Auto-Launcher
color 0B
cls

:: Pengaturan server ada di file .env (tetap aman saat UPDATE-AND-RESTART):
::   SERVER_LAN_IP   = IP tetap server kantor (selalu masuk sertifikat HTTPS)
::   DMS_PHP_WORKERS = jumlah worker PHP; kosong = otomatis

:: Mode silent (dipanggil START-SILENT-DESKTOP.vbs): tanpa pause dan tanpa buka browser.
set "PAUSE_CMD=pause"
if defined DMS_SILENT set "PAUSE_CMD=rem"

echo ===============================================================================
echo              PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                       Desktop Edition Auto-Launcher
echo ===============================================================================
echo.

:: 1. Pindah ke direktori project
cd /d "%~dp0"
if not exist "storage\logs" mkdir "storage\logs"
echo %date% %time% mulai> "storage\logs\startup.log"
echo [*] Memeriksa kelengkapan sistem (System Requirement Check)...
echo.

:: ===============================================================================
:: CHECK 1: RUNTIME PHP (php.exe + php-cgi.exe, PHP 8.4)
:: ===============================================================================
set "FOUND_PHP="
set "BUNDLED_PHP="

:: 1. Prioritas utama: PHP portable di folder "evironment" project
for /d %%d in ("%~dp0evironment\php-8.4*") do if exist "%%d\php-cgi.exe" set "FOUND_PHP=%%~fd"
if defined FOUND_PHP set "BUNDLED_PHP=1"
if not defined FOUND_PHP if exist "%~dp0environment" (
    for /d %%d in ("%~dp0environment\php-8.4*") do if exist "%%d\php-cgi.exe" set "FOUND_PHP=%%~fd"
)

:: 2. Laragon (PHP 8.4)
if not defined FOUND_PHP for /d %%d in (C:\laragon\bin\php\php-8.4*) do if exist "%%d\php-cgi.exe" set "FOUND_PHP=%%d"
if not defined FOUND_PHP for /d %%d in (D:\laragon\bin\php\php-8.4*) do if exist "%%d\php-cgi.exe" set "FOUND_PHP=%%d"

:: 3. XAMPP
if not defined FOUND_PHP if exist "C:\xampp\php\php-cgi.exe" set "FOUND_PHP=C:\xampp\php"
if not defined FOUND_PHP if exist "D:\xampp\php\php-cgi.exe" set "FOUND_PHP=D:\xampp\php"

:: 4. System PATH
if not defined FOUND_PHP (
    for /f "tokens=*" %%p in ('where php-cgi 2^>nul') do (
        if not defined FOUND_PHP (
            set "FOUND_PHP=%%~dpp"
            set "FOUND_PHP=!FOUND_PHP:~0,-1!"
        )
    )
)

if defined FOUND_PHP goto :VERIFY_PHP

:: -------------------------------------------------------------------------------
:: PHP BELUM ADA -> OPSI DOWNLOAD OTOMATIS
:: -------------------------------------------------------------------------------
color 0E
echo ===============================================================================
echo [PERHATIAN] PHP RUNTIME BELUM TERSEDIA DI SISTEM ATAU FOLDER EVIRONMENT
echo ===============================================================================
echo.
echo Aplikasi Laravel membutuhkan PHP Runtime untuk dapat dijalankan.
echo.
echo Pilihan Tindakan:
echo   [1] Download ^& Siapkan PHP Portable 8.4 Otomatis ke folder "evironment" (Disarankan)
echo   [2] Buka browser untuk download Laragon Full Installer
echo   [3] Keluar
echo.
if defined DMS_SILENT exit /b 1
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
if not exist "%~dp0evironment" mkdir "%~dp0evironment"

set "PHP_NAME=php-8.4.28-nts-Win32-vs17-x64"
set "PHP_SHA256=c1f9280588b7b6ad7b518969022f0b0175f106fab9d0639ad0e4ce7cd47ac1ed"
set "PHP_ZIP=%~dp0evironment\%PHP_NAME%.zip"
set "PHP_TARGET_DIR=%~dp0evironment\%PHP_NAME%"

echo [*] Mengunduh PHP Portable 8.4 dari server resmi Windows PHP...
powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; try { Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/%PHP_NAME%.zip' -OutFile '%PHP_ZIP%' } catch { Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/archives/%PHP_NAME%.zip' -OutFile '%PHP_ZIP%' }"

if not exist "%PHP_ZIP%" (
    echo [ERROR] Gagal mengunduh file PHP. Pastikan koneksi internet aktif.
    %PAUSE_CMD%
    exit /b 1
)

echo [*] Memverifikasi checksum SHA-256...
powershell -NoProfile -Command "if ((Get-FileHash '%PHP_ZIP%' -Algorithm SHA256).Hash -ne '%PHP_SHA256%') { exit 1 }"
if errorlevel 1 (
    del /f /q "%PHP_ZIP%" >nul 2>&1
    echo [ERROR] Checksum file PHP tidak cocok. File dihapus, coba lagi.
    %PAUSE_CMD%
    exit /b 1
)

echo [*] Mengekstrak file PHP ke folder evironment...
if not exist "%PHP_TARGET_DIR%" mkdir "%PHP_TARGET_DIR%"
powershell -NoProfile -Command "Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_TARGET_DIR%' -Force"
del /f /q "%PHP_ZIP%" >nul 2>&1

set "FOUND_PHP=%PHP_TARGET_DIR%"
set "BUNDLED_PHP=1"
echo [OK] PHP 8.4 Portable siap digunakan!
echo.

:VERIFY_PHP
set "PATH=!FOUND_PHP!;!PATH!"
set "PHPRC=!FOUND_PHP!"
echo [OK] PHP Runtime Terdeteksi: !FOUND_PHP!\php.exe

:: Visual C++ Runtime: dibundel di folder PHP; jika tidak ada, pasang dari Microsoft.
set "NEED_VCREDIST="
if not exist "%SystemRoot%\System32\vcruntime140.dll" set "NEED_VCREDIST=1"
if not exist "%SystemRoot%\System32\vcruntime140_1.dll" set "NEED_VCREDIST=1"
if not exist "%SystemRoot%\System32\msvcp140.dll" set "NEED_VCREDIST=1"
if exist "!FOUND_PHP!\vcruntime140.dll" if exist "!FOUND_PHP!\vcruntime140_1.dll" if exist "!FOUND_PHP!\msvcp140.dll" set "NEED_VCREDIST="
if defined NEED_VCREDIST (
    echo [*] Visual C++ Runtime belum terpasang. Mengunduh dan memasang ^(perlu izin Administrator^)...
    powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile '%TEMP%\vc_redist.x64.exe'"
    powershell -NoProfile -Command "Start-Process -FilePath '%TEMP%\vc_redist.x64.exe' -ArgumentList '/install','/passive','/norestart' -Verb RunAs -Wait"
    if not exist "%SystemRoot%\System32\vcruntime140.dll" (
        echo [ERROR] Visual C++ Runtime gagal dipasang. Install manual: https://aka.ms/vs/17/release/vc_redist.x64.exe
        %PAUSE_CMD%
        exit /b 1
    )
    echo [OK] Visual C++ Runtime terpasang.
)

:: php.ini = php.ini-production + evironment\php-dms.ini (ekstensi, OPcache, batas upload)
if defined BUNDLED_PHP if exist "%~dp0evironment\php-dms.ini" (
    copy /b /y "!FOUND_PHP!\php.ini-production"+"%~dp0evironment\php-dms.ini" "!FOUND_PHP!\php.ini" >nul
)

set "PHP_VERSION_INFO="
for /f "tokens=1,2 delims= " %%a in ('php -v 2^>nul ^| findstr /b /r /c:"PHP [0-9]"') do (
    if not defined PHP_VERSION_INFO set "PHP_VERSION_INFO=%%a %%b"
)
echo [OK] Versi PHP Aktif: !PHP_VERSION_INFO!

call :LOG_STEP "php + vc++ + php.ini"
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
        echo APP_ENV=production>> .env
        echo APP_KEY=>> .env
        echo APP_DEBUG=false>> .env
        echo APP_URL=http://localhost:8000>> .env
        echo DB_CONNECTION=sqlite>> .env
        echo SESSION_DRIVER=file>> .env
        echo CACHE_STORE=file>> .env
        echo QUEUE_CONNECTION=sync>> .env
        echo [OK] Template .env baru berhasil digenerate.
    )
)

call :LOG_STEP ".env"
:: ===============================================================================
:: CHECK 2B: DEPENDENSI PHP / VENDOR (vendor/autoload.php)
:: ===============================================================================
if not exist "vendor\autoload.php" (
    echo [*] Folder vendor belum ada. Menyiapkan dependensi PHP...
    if not exist "%~dp0evironment\composer.phar" (
        echo [*] Mengunduh composer.phar ke folder evironment...
        powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile '%~dp0evironment\composer.phar'"
    )
    if exist "%~dp0evironment\composer.phar" (
        php "%~dp0evironment\composer.phar" install --no-dev --optimize-autoloader --no-interaction
    )
    if not exist "vendor\autoload.php" (
        echo [ERROR] Folder vendor gagal disiapkan.
        echo         Pastikan koneksi internet aktif saat pertama kali dijalankan,
        echo         atau salin folder vendor dari PC lain ke direktori ini.
        %PAUSE_CMD%
        exit /b 1
    )
)

call :LOG_STEP "vendor"
:: ===============================================================================
:: CHECK 3: APPLICATION ENCRYPTION KEY (APP_KEY)
:: ===============================================================================
findstr /C:"APP_KEY=base64" .env >nul 2>&1
if errorlevel 1 (
    echo [*] Men-generate App Encryption Key...
    php artisan key:generate --force
    echo [OK] Application key berhasil diset.
)

call :LOG_STEP "app key"
:: Cache produksi dibuat ulang hanya jika ada file yang lebih baru dari cache
:: (kode, konfigurasi, .env, view, CSS/JS, dependensi). UPDATE-AND-RESTART selalu menghapus cache.
:: Harus sebelum migrate: artisan membaca konfigurasi dari cache ini.
powershell -NoProfile -Command "$c = 'bootstrap\cache\config.php'; if (-not (Test-Path $c) -or -not (Test-Path 'bootstrap\cache\routes-v7.php')) { exit 1 }; $t = (Get-Item $c).LastWriteTime; $files = @(Get-ChildItem -Path app,config,routes,resources\views,public\css,public\js -Recurse -File -ErrorAction SilentlyContinue) + @(Get-Item -Path bootstrap\app.php,bootstrap\providers.php,.env,composer.lock,vendor\composer\installed.json -ErrorAction SilentlyContinue); if ($files | Where-Object { $_.LastWriteTime -gt $t } | Select-Object -First 1) { exit 1 }" >nul 2>&1
if errorlevel 1 (
    echo [*] Memuat cache konfigurasi, rute, event, dan template Blade...
    php artisan optimize >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] Gagal membuat cache aplikasi. Jalankan: php artisan optimize
        %PAUSE_CMD%
        exit /b 1
    )
) else (
    echo [OK] Cache aplikasi masih terbaru.
)
call :LOG_STEP "cache (optimize bila perlu)"
:: ===============================================================================
:: CHECK 4: DATABASE SQLITE
:: ===============================================================================
:: Data awal (departemen, user default, layout gudang) hanya untuk database BARU.
set "SEED_FLAG="
if not exist "database\database.sqlite" (
    echo [*] Database SQLite belum ada. Membuat database/database.sqlite...
    type nul > "database\database.sqlite"
    set "SEED_FLAG=--seed"
    echo [OK] File database.sqlite berhasil dibuat.
)
echo [*] Memeriksa pembaruan skema database...
php artisan migrate --force !SEED_FLAG!
if errorlevel 1 (
    rem Database baru yang gagal disiapkan dihapus agar start berikutnya mengulang dari awal.
    if defined SEED_FLAG del /f /q "database\database.sqlite" "database\database.sqlite-wal" "database\database.sqlite-shm" >nul 2>&1
    echo [ERROR] Migrasi database gagal. Lihat pesan di atas.
    %PAUSE_CMD%
    exit /b 1
)

call :LOG_STEP "migrate"
:: ===============================================================================
:: CHECK 5: TANPA public\storage (file upload hanya lewat controller ber-login, temuan #2)
:: ===============================================================================
if not exist "storage\logs" mkdir "storage\logs"
:: Link lama dari storage:link dilepas. rmdir tanpa /s: hanya link-nya, isi storage tidak tersentuh;
:: folder nyata yang berisi file tidak dihapus (Caddy tetap menolak /storage/*).
if exist "public\storage" rmdir "public\storage" >nul 2>&1

call :LOG_STEP "lepas public\storage"
:: ===============================================================================
:: CHECK 6: PORT 8000 HARUS KOSONG
:: ===============================================================================
netstat -aon | findstr /r /c:":8000 .*LISTENING" >nul
if not errorlevel 1 (
    echo [ERROR] Port 8000 sudah dipakai ^(server DMS lain masih aktif?^).
    echo         Jalankan STOP-DMS-INDRACO.bat lalu coba lagi.
    %PAUSE_CMD%
    exit /b 1
)
:: Sisa launcher lama folder ini (loop/php-cgi yatim, dms-ready.ps1) dibersihkan dulu,
:: agar worker baru bisa memakai port 9001.. dan startup.log tidak ditulis "GAGAL" oleh
:: dms-ready lama (R18). Port 8000 kosong = tidak ada server folder ini yang sedang jalan.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0evironment\dms-stop.ps1" -Leftovers >nul 2>&1

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

:: Baca pengaturan launcher dari .env
set "PHP_WORKERS="
set "SERVER_LAN_IP="
for /f "usebackq tokens=1,* delims==" %%a in (`findstr /b /c:"DMS_PHP_WORKERS=" /c:"SERVER_LAN_IP=" ".env"`) do (
    if /i "%%a"=="DMS_PHP_WORKERS" set "PHP_WORKERS=%%b"
    if /i "%%a"=="SERVER_LAN_IP" set "SERVER_LAN_IP=%%b"
)
:: Otomatis: 2 x inti CPU, minimal 4 (agar 2-3 request lama seperti impor tidak
:: membuat user lain menunggu), maksimal 6 (hemat RAM).
if not defined PHP_WORKERS (
    set /a "PHP_WORKERS=NUMBER_OF_PROCESSORS*2"
    if !PHP_WORKERS! LSS 4 set "PHP_WORKERS=4"
    if !PHP_WORKERS! GTR 6 set "PHP_WORKERS=6"
)
set "DMS_SERVER_LAN_IP=!SERVER_LAN_IP!"

cls
echo ===============================================================================
echo   SELURUH REQUIREMENT LENGKAP - MEMULAI SERVER DMS PT INDRACO
echo ===============================================================================
echo.
echo   * Direktori Project : %CD%
echo   * Versi PHP         : !PHP_VERSION_INFO! ^(!PHP_WORKERS! worker^) + Caddy
echo   * IP Jaringan Lokal : !LOCAL_IP!
echo.
echo   * URL Akses Komputer Ini (Localhost) :
echo     --^> http://127.0.0.1:8000  atau  http://localhost:8000
echo.
echo   * URL Akses Jaringan Komputer Lain / HP (WiFi/LAN Kantor) :
echo     --^> http://!LOCAL_IP!:8000
echo     --^> https://!LOCAL_IP!:8443  ^(HTTPS / install aplikasi; klien pasang sertifikat
echo          sekali lewat INSTALL-SERTIFIKAT-KLIEN.bat atau http://!LOCAL_IP!:8000/dms-root-ca.crt^)
echo.
echo ===============================================================================
echo   STATUS: menyalakan server... tunggu tulisan "SIAP" di bawah sebelum dipakai.
echo.
echo   Catatan: Layar ini dikunci agar alamat IP di atas selalu terlihat jelas.
echo            Log akses HTTP tersimpan di: storage\logs\http-access.log
echo            JIKA JENDELA INI DITUTUP [X] ATAU TEKAN Ctrl+C, SERVER OTOMATIS MATI.
echo ===============================================================================
echo.

:: Di latar belakang: tunggu sampai PHP benar-benar menjawab, panaskan semua worker,
:: lalu tulis "SIAP" di jendela ini dan buka browser (kecuali mode silent).
set "OPEN_BROWSER=-OpenBrowser"
if defined DMS_SILENT set "OPEN_BROWSER="
start /b "" powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0evironment\dms-ready.ps1" -Workers !PHP_WORKERS! !OPEN_BROWSER!

call :LOG_STEP "port + ip + banner"
:: Jalankan pool php-cgi + Caddy di jendela ini. Output ke storage\logs agar layar tidak bergulir.
:: Berjalan sampai jendela ditutup / Ctrl+C / STOP-DMS-INDRACO.bat; semua proses ikut berhenti.
call "%~dp0evironment\dms-server.cmd" "!FOUND_PHP!" !PHP_WORKERS!
echo.
echo [OK] Server DMS PT Indraco telah dimatikan.
%PAUSE_CMD%
exit /b 0

:LOG_STEP
echo %time% %~1>> "storage\logs\startup.log"
exit /b 0

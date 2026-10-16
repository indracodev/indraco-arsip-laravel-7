@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Client Workstation Launcher
color 0B
cls

echo ===============================================================================
echo              PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                   Client Workstation Launcher (Win 7/8/10/11)
echo ===============================================================================
echo.

:: 1. Tentukan Direktori Kerja & URL Server Default
cd /d "%~dp0"
set "SERVER_URL=http://192.168.2.180:8000"

:: 2. Cek apakah ada file konfigurasi custom server (server_url.txt)
if exist "server_url.txt" (
    set /p SERVER_URL=<"server_url.txt"
    set "SERVER_URL=!SERVER_URL: =!"
)
if exist "desktop\server_url.txt" (
    set /p SERVER_URL=<"desktop\server_url.txt"
    set "SERVER_URL=!SERVER_URL: =!"
)

echo [*] Target Server DMS : !SERVER_URL!
echo [*] Memeriksa koneksi jaringan ke server...
echo.

:: 3. Uji Ping Koneksi ke Server (Menggunakan PowerShell ringan)
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri '!SERVER_URL!/api/health/ping' -TimeoutSec 2 -UseBasicParsing; if ($r.StatusCode -eq 200) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1

if !ERRORLEVEL! EQU 0 (
    echo [OK] Server DMS aktif dan siap digunakan!
    goto :LAUNCH_APP
) else (
    echo [PERHATIAN] Server pada !SERVER_URL! belum merespons.
    echo             Pastikan komputer terhubung ke jaringan LAN/WiFi kantor,
    echo             atau server utama telah dinyalakan.
    echo.
    echo Pilihan Tindakan:
    echo   [1] Coba hubungkan ulang (Retry)
    echo   [2] Ubah alamat server (misal ke localhost untuk testing)
    echo   [3] Tetap buka aplikasi
    echo   [4] Buat Shortcut Ikon di Desktop Windows
    echo   [5] Keluar
    echo.
    set /p "CHOICE=Pilihan [1/2/3/4/5]: "
    if "!CHOICE!"=="1" goto :RETRY_PING
    if "!CHOICE!"=="2" goto :CHANGE_SERVER
    if "!CHOICE!"=="3" goto :LAUNCH_APP
    if "!CHOICE!"=="4" goto :CREATE_SHORTCUT
    exit /b 0
)

:RETRY_PING
cls
echo [*] Mencoba menyambungkan kembali ke !SERVER_URL!...
goto :LAUNCH_APP

:CHANGE_SERVER
cls
echo ===============================================================================
echo                           PENGATURAN ALAMAT SERVER
echo ===============================================================================
echo.
echo Contoh:
echo   - Jaringan LAN Kantor : http://192.168.2.180:8000
echo   - Komputer Ini (Lokal): http://127.0.0.1:8000
echo.
set /p "NEW_URL=Masukkan alamat server baru: "
if not "!NEW_URL!"=="" (
    set "SERVER_URL=!NEW_URL!"
    echo !NEW_URL!> "%~dp0server_url.txt"
    echo [OK] Alamat server berhasil disimpan ke server_url.txt!
)
goto :LAUNCH_APP

:CREATE_SHORTCUT
cls
echo [*] Membuat shortcut desktop "DMS PT Indraco"...
set "EXE_TARGET=%~dp0DMS-Indraco-Client.exe"
set "BAT_TARGET=%~f0"
set "ICO_FILE=%~dp0indraco.ico"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ws = New-Object -ComObject WScript.Shell;" ^
    "$lnk = $ws.CreateShortcut([Environment]::GetFolderPath('Desktop') + '\DMS PT Indraco.lnk');" ^
    "if (Test-Path '%EXE_TARGET%') { $lnk.TargetPath = '%EXE_TARGET%' } else { $lnk.TargetPath = '%BAT_TARGET%' };" ^
    "$lnk.WorkingDirectory = '%~dp0';" ^
    "$lnk.Description = 'Sistem Manajemen Dokumen & Arsip Digital - PT Indraco';" ^
    "if (Test-Path '%ICO_FILE%') { $lnk.IconLocation = '%ICO_FILE%,0' };" ^
    "$lnk.Save()" >nul 2>&1
echo [OK] Shortcut desktop "DMS PT Indraco" dengan ikon resmi berhasil dibuat di Desktop Anda!
echo.
pause
goto :LAUNCH_APP

:LAUNCH_APP
echo.
echo [*] Membuka antarmuka DMS Indraco dalam Mode Standalone Desktop...

:: 4. Deteksi Browser untuk menjalankan Mode App (Frameless / Tanpa Address Bar)
set "BROWSER_CMD="

:: Cek Google Chrome
if not defined BROWSER_CMD if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_CMD=C:\Program Files\Google\Chrome\Application\chrome.exe"
)
if not defined BROWSER_CMD if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_CMD=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
)
if not defined BROWSER_CMD if exist "%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe" (
    set "BROWSER_CMD=%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe"
)

:: Cek Microsoft Edge
if not defined BROWSER_CMD if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    set "BROWSER_CMD=C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
)
if not defined BROWSER_CMD if exist "C:\Program Files\Microsoft\Edge\Application\msedge.exe" (
    set "BROWSER_CMD=C:\Program Files\Microsoft\Edge\Application\msedge.exe"
)

:: Cek Brave Browser
if not defined BROWSER_CMD if exist "C:\Program Files\BraveSoftware\Brave-Browser\Application\brave.exe" (
    set "BROWSER_CMD=C:\Program Files\BraveSoftware\Brave-Browser\Application\brave.exe"
)

:: Cek Mozilla Firefox
set "FIREFOX_CMD="
if exist "C:\Program Files\Mozilla Firefox\firefox.exe" set "FIREFOX_CMD=C:\Program Files\Mozilla Firefox\firefox.exe"
if not defined FIREFOX_CMD if exist "C:\Program Files (x86)\Mozilla Firefox\firefox.exe" set "FIREFOX_CMD=C:\Program Files (x86)\Mozilla Firefox\firefox.exe"

:: 5. Eksekusi Pembukaan Jendela Aplikasi Standalone
if defined BROWSER_CMD (
    echo [OK] Menggunakan Engine: !BROWSER_CMD!
    start "" "!BROWSER_CMD!" --app="!SERVER_URL!" --start-maximized
    timeout /t 1 >nul
    exit /b 0
)

if defined FIREFOX_CMD (
    echo [OK] Menggunakan Engine: !FIREFOX_CMD!
    start "" "!FIREFOX_CMD!" -new-window "!SERVER_URL!"
    timeout /t 1 >nul
    exit /b 0
)

:: Fallback Default Browser Sistem
echo [OK] Menggunakan Default System Browser...
start "" "!SERVER_URL!"
timeout /t 1 >nul
exit /b 0

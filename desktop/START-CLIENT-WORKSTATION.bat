@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Client Workstation Launcher

:: 1. Tentukan Direktori Kerja & Path File UI
cd /d "%~dp0"
set "ROOT_DIR=%~dp0"
set "ROOT_DIR_SLASH=!ROOT_DIR:\=/!"
set "UI_TARGET=file:///!ROOT_DIR_SLASH!desktop/src/index.html"
set "SERVER_URL=http://192.168.2.180:8000"

:: 2. Baca file konfigurasi jika ada
if exist "server_url.txt" (
    set /p SERVER_URL=<"server_url.txt"
    set "SERVER_URL=!SERVER_URL: =!"
)
if exist "desktop\server_url.txt" (
    set /p SERVER_URL=<"desktop\server_url.txt"
    set "SERVER_URL=!SERVER_URL: =!"
)

:: 3. Otomatis buat / perbarui shortcut desktop jika belum ada
set "DESKTOP_LNK=%USERPROFILE%\Desktop\DMS PT Indraco.lnk"
set "ICO_FILE=%~dp0indraco.ico"
set "EXE_TARGET=%~dp0DMS-Indraco-Client.exe"

if not exist "!DESKTOP_LNK!" (
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
        "$ws = New-Object -ComObject WScript.Shell;" ^
        "$lnk = $ws.CreateShortcut('%DESKTOP_LNK%');" ^
        "if (Test-Path '%EXE_TARGET%') { $lnk.TargetPath = '%EXE_TARGET%' } else { $lnk.TargetPath = '%~f0' };" ^
        "$lnk.WorkingDirectory = '%~dp0';" ^
        "$lnk.Description = 'Sistem Manajemen Dokumen & Arsip Digital - PT Indraco';" ^
        "if (Test-Path '%ICO_FILE%') { $lnk.IconLocation = '%ICO_FILE%,0' };" ^
        "$lnk.Save()" >nul 2>&1
)

:: 4. Deteksi Browser untuk menjalankan UI Client dalam App Mode (Frameless)
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

:: 5. Buka UI Standalone Desktop (Layar Reconnect & Heartbeat Matte Light)
if defined BROWSER_CMD (
    start "" "!BROWSER_CMD!" --app="!UI_TARGET!" --start-maximized
    exit /b 0
)

if defined FIREFOX_CMD (
    start "" "!FIREFOX_CMD!" -new-window "!UI_TARGET!"
    exit /b 0
)

:: Fallback Default Browser
start "" "!UI_TARGET!"
exit /b 0

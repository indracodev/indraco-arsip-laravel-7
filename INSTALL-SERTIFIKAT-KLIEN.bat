@echo off
setlocal enabledelayedexpansion
title DMS PT INDRACO - Pasang Sertifikat HTTPS (Klien)
color 0B
cls

echo ===============================================================================
echo              PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                  Pasang Sertifikat HTTPS di Komputer Klien
echo ===============================================================================
echo.
echo Sertifikat ini membuat browser mempercayai alamat https://^<server^>:8443
echo (dibutuhkan untuk memasang DMS sebagai aplikasi / PWA). Cukup sekali per user.
echo Tidak perlu hak Administrator.
echo.

cd /d "%~dp0"

:: 1. Alamat server: dari server_url.txt (mis. http://192.168.2.180:8000) atau tanya user
set "SERVER_HOST="
set "SERVER_URL="
if exist "server_url.txt" set /p SERVER_URL=<"server_url.txt"
if not defined SERVER_URL if exist "desktop\server_url.txt" set /p SERVER_URL=<"desktop\server_url.txt"
if defined SERVER_URL (
    set "SERVER_URL=!SERVER_URL: =!"
    for /f "tokens=2 delims=/" %%h in ("!SERVER_URL!") do set "SERVER_HOST=%%h"
)
if defined SERVER_HOST for /f "tokens=1 delims=:" %%h in ("!SERVER_HOST!") do set "SERVER_HOST=%%h"
if not defined SERVER_HOST (
    set /p "SERVER_HOST=Ketik IP server DMS (contoh 192.168.2.180) lalu Enter: "
)
if not defined SERVER_HOST (
    echo [ERROR] IP server belum diisi.
    pause
    exit /b 1
)

set "CERT_URL=http://!SERVER_HOST!:8000/dms-root-ca.crt"
set "CERT_FILE=%TEMP%\dms-root-ca.crt"

:: 2. Unduh sertifikat publik (root CA) dari server
echo [*] Mengunduh sertifikat dari !CERT_URL! ...
del /f /q "!CERT_FILE!" >nul 2>&1
powershell -NoProfile -Command "$ProgressPreference = 'SilentlyContinue'; try { Invoke-WebRequest -UseBasicParsing -Uri '!CERT_URL!' -OutFile '!CERT_FILE!' } catch { exit 1 }"
if errorlevel 1 (
    echo [ERROR] Gagal mengunduh. Pastikan server DMS aktif dan IP benar: !SERVER_HOST!
    pause
    exit /b 1
)
findstr /c:"BEGIN CERTIFICATE" "!CERT_FILE!" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] File yang diunduh bukan sertifikat. Periksa alamat server.
    pause
    exit /b 1
)

:: 3. Pasang ke "Trusted Root" milik user ini (tanpa admin). Windows menampilkan
::    peringatan keamanan: pastikan namanya "DMS PT Indraco Root CA", lalu klik Yes.
echo [*] Memasang sertifikat. Jika muncul peringatan Windows, periksa nama
echo     "DMS PT Indraco Root CA" lalu klik YES.
certutil -user -addstore Root "!CERT_FILE!" >nul
if errorlevel 1 (
    echo [ERROR] Sertifikat gagal dipasang ^(dibatalkan?^). Jalankan ulang file ini.
    pause
    exit /b 1
)

echo.
echo [OK] Sertifikat terpasang. Chrome dan Edge sekarang mempercayai:
echo      https://!SERVER_HOST!:8443
echo      (tutup lalu buka lagi browser jika masih muncul peringatan)
echo.
echo Pengguna Firefox:
echo   Buka about:config, cari security.enterprise_roots.enabled, ubah ke true,
echo   lalu buka ulang Firefox. Atau: Settings ^> Privacy ^& Security ^> Certificates ^>
echo   View Certificates ^> Authorities ^> Import, pilih file !CERT_FILE!
echo.
start "" "https://!SERVER_HOST!:8443"
pause

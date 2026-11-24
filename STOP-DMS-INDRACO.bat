@echo off
title Hentikan Server DMS PT Indraco
color 0C
cls

echo =====================================================================
echo           PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                       Stop Service Launcher
echo =====================================================================
echo.
echo Menghentikan seluruh proses server DMS (Caddy + PHP) di port 8000...

cd /d "%~dp0"

:: Jendela launcher folder ini (juga mode silent), loop worker, php-cgi, Caddy,
:: dms-ready.ps1, dan apa pun yang memegang port 8000 SAAT INI. Proses dipilih sekali
:: lalu ditunggu sampai benar-benar keluar (maks 15 dtk), sehingga START yang dijalankan
:: sesudahnya (atau bersamaan) tidak ikut dimatikan (temuan R18).
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0evironment\dms-stop.ps1"

echo.
echo [OK] Server DMS PT Indraco berhasil dihentikan.
ping -n 3 127.0.0.1 >nul

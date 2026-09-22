@echo off
title Hentikan Server DMS PT Indraco
color 0C
cls

echo =====================================================================
echo           PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)
echo                       Stop Service Launcher
echo =====================================================================
echo.
echo Menghentikan seluruh proses server DMS yang berjalan di port 8000...

taskkill /fi "WINDOWTITLE eq DMS Indraco Server*" /f /t >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /f /pid %%a >nul 2>&1
)

echo.
echo [OK] Server DMS PT Indraco berhasil dihentikan.
timeout /t 2 >nul

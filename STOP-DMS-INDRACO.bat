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

:: 1. Jendela launcher beserta semua proses turunannya (Caddy, php-cgi, loop worker)
taskkill /fi "WINDOWTITLE eq DMS Indraco Server*" /f /t >nul 2>&1
taskkill /fi "WINDOWTITLE eq DMS PT INDRACO - Desktop*" /f /t >nul 2>&1

:: 2. Cadangan (mode silent / jendela tersembunyi): loop worker dulu supaya tidak
::    menyalakan ulang php-cgi, lalu caddy.exe / php-cgi.exe dari folder project ini.
powershell -NoProfile -Command "$d = '%~dp0evironment'; foreach ($pass in 1..2) { $all = Get-CimInstance Win32_Process; $all | Where-Object { $_.Name -eq 'cmd.exe' -and $_.CommandLine -like ('*' + $d + '*php-cgi.exe*') } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }; $all | Where-Object { ($_.Name -eq 'caddy.exe' -or $_.Name -eq 'php-cgi.exe') -and $_.ExecutablePath -and $_.ExecutablePath.StartsWith($d, 'OrdinalIgnoreCase') } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }; Start-Sleep -Milliseconds 500 }; $deadline = (Get-Date).AddSeconds(15); while ((Get-Date) -lt $deadline -and (Get-Process caddy,php-cgi -ErrorAction SilentlyContinue | Where-Object { -not $_.HasExited -and $_.Path -and $_.Path.StartsWith($d, 'OrdinalIgnoreCase') })) { Start-Sleep -Milliseconds 250 }"

:: 3. Apa pun yang masih memegang port 8000
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /f /pid %%a >nul 2>&1
)

echo.
echo [OK] Server DMS PT Indraco berhasil dihentikan.
ping -n 3 127.0.0.1 >nul

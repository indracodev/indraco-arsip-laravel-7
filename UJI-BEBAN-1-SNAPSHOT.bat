@echo off
setlocal
title Uji Beban 1 - Snapshot Database DMS
cd /d "%~dp0"
set "PORTABLE_PHP="
for /d %%d in ("%~dp0evironment\php-8.4*") do if exist "%%d\php.exe" set "PORTABLE_PHP=%%~fd"
if not defined PORTABLE_PHP (
    echo [GAGAL] PHP portable tidak ditemukan di folder evironment\php-8.4*.
    pause
    exit /b 1
)
set "PHPRC=%PORTABLE_PHP%"
set "PHP=%PORTABLE_PHP%\php.exe"

echo =====================================================================
echo   UJI BEBAN LANGKAH 1: SNAPSHOT DATABASE + DAFTAR FILE UPLOAD
echo =====================================================================
echo  Boleh dijalankan saat server DMS sedang berjalan.
echo  Hasil: storage\app\snapshots\^<tanggal_jam^>\ (database.sqlite + manifest.json)
echo.
"%PHP%" artisan dms:snapshot
if errorlevel 1 (
    echo.
    echo [GAGAL] Snapshot tidak dibuat. JANGAN mulai uji beban tulis.
    pause
    exit /b 1
)
echo.
echo [OK] Snapshot siap. Sekarang uji beban boleh dimulai dari laptop.
pause

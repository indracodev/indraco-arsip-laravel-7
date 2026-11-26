@echo off
setlocal
title Uji Beban 2 - Verifikasi Data Uji DMS
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
echo   UJI BEBAN LANGKAH 2: VERIFIKASI DATA HASIL UJI TULIS (LOADTEST)
echo =====================================================================
echo  Ketik RUN ID yang dicetak k6 (mis. r3k9x1), atau kosongkan untuk
echo  memeriksa SEMUA run sekaligus (uji dari 2 PC).
echo.
set "RUN_ID="
set /p "RUN_ID=RUN ID (kosong = semua): "
if defined RUN_ID (
    "%PHP%" artisan dms:loadtest-verify %RUN_ID%
) else (
    "%PHP%" artisan dms:loadtest-verify
)
echo.
pause

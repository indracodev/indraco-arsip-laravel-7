@echo off
setlocal enabledelayedexpansion
title GRAFANA k6 BENCHMARK - DMS PT INDRACO

echo ========================================================================
echo          GRAFANA k6 BENCHMARK SUITE - DMS PT INDRACO
echo ========================================================================
echo.

set "K6_BIN=k6"
where k6 >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Program Files\k6\k6.exe" (
        set "K6_BIN=C:\Program Files\k6\k6.exe"
    ) else (
        echo [ERROR] Grafana k6 tidak ditemukan di sistem!
        echo Pastikan k6 sudah terinstal di C:\Program Files\k6\k6.exe
        pause
        exit /b 1
    )
)

set "DEFAULT_TARGET=http://192.168.3.163:8000"
echo Masukkan Alamat IP / URL Server Target:
echo [Tekan ENTER untuk default: %DEFAULT_TARGET%]
set /p "TARGET_URL=Target URL: "
if "%TARGET_URL%"=="" set "TARGET_URL=%DEFAULT_TARGET%"

echo.
echo Pilih Mode Pengujian:
echo  [1] Quick Test       - Super Cepat (6 Detik, 2 VU)
echo  [2] Office LAN Test  - Beban Nyata Kantor (14 Detik, 5 VU) [Rekomendasi]
echo  [3] Stress Test      - Beban Puncak (20 Detik, 12 VU)
set /p "PIL_MODE=Pilihan (1/2/3, default 1): "

set "TEST_MODE=quick"
if "%PIL_MODE%"=="2" set "TEST_MODE=office"
if "%PIL_MODE%"=="3" set "TEST_MODE=stress"

echo.
echo ========================================================================
echo Memulai Grafana k6 Benchmark ke : %TARGET_URL%
echo Mode Uji                       : %TEST_MODE%
echo ========================================================================
echo.

"%K6_BIN%" run -e TARGET_URL=%TARGET_URL% -e MODE=%TEST_MODE% "%~dp0tests\k6\load_test.js"

echo.
echo ========================================================================
echo Uji performa selesai!
echo Laporan HTML tersimpan di: %~dp0benchmark_summary.html
echo ========================================================================
echo.

if exist "%~dp0benchmark_summary.html" (
    echo Membuka laporan HTML di browser...
    start "" "%~dp0benchmark_summary.html"
)

pause

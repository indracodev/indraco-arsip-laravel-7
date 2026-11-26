@echo off
setlocal
title Uji Beban 3 - Restore Database DMS
color 0E
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
echo   UJI BEBAN LANGKAH 3: KEMBALIKAN DATABASE KE KONDISI SNAPSHOT
echo =====================================================================
echo  - Server DMS HARUS sudah dihentikan (jalankan STOP-DMS-INDRACO.bat dulu).
echo  - Semua data yang dibuat SESUDAH snapshot terakhir akan HILANG
echo    (arsip LOADTEST, file upload uji, sesi login uji).
echo  - Sebelum diganti, kondisi sekarang disalin ke
echo    storage\app\snapshots-sebelum-restore\ (bisa dikembalikan bila keliru).
echo.
set "JAWAB="
set /p "JAWAB=Ketik YA (huruf besar) untuk melanjutkan: "
if not "%JAWAB%"=="YA" (
    echo Dibatalkan. Tidak ada yang diubah.
    pause
    exit /b 1
)
"%PHP%" artisan dms:restore-snapshot --yes
if errorlevel 1 (
    echo.
    echo [GAGAL] Restore tidak selesai. Baca pesan di atas. Database tidak diganti
    echo         bila server masih berjalan atau snapshot rusak.
    pause
    exit /b 1
)
echo.
echo [OK] Database sudah kembali ke kondisi snapshot.
pause

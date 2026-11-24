@echo off
:: DMS PT Indraco - starts the php-cgi pool + Caddy in THIS console window.
:: Called by START-DMS-INDRACO.bat:  call evironment\dms-server.cmd "<php dir>" <workers>
:: Run from the repo root (Caddyfile and storage\ paths are relative).
::
:: - Each php-cgi runs inside its own tiny "cmd /c for /l" loop: cmd waits on the
::   child (blocking, 0% CPU) and restarts it if it ever exits (crash).
:: - PHP_FCGI_MAX_REQUESTS=0: workers never recycle. A recycling php-cgi resets the
::   request Caddy already queued on it (seen in the load test as a failed POST).
:: - Everything shares this console, so closing the window or Ctrl+C ends Caddy and
::   every php-cgi. STOP-DMS-INDRACO.bat kills the same process tree.
:: - Caddy runs in the foreground (http :8000 + https :8443); this script returns when Caddy stops.
setlocal enabledelayedexpansion
set "DMS_PHP=%~1"
set /a "DMS_WORKERS=%~2"
if %DMS_WORKERS% LSS 1 set "DMS_WORKERS=1"

set "PHPRC=%DMS_PHP%"
set "PHP_FCGI_MAX_REQUESTS=0"
if not exist "storage\logs\pool" mkdir "storage\logs\pool"

set "DMS_UPSTREAMS="
set /a "LAST=9000+DMS_WORKERS"
for /l %%p in (9001,1,%LAST%) do (
    set "DMS_UPSTREAMS=!DMS_UPSTREAMS! 127.0.0.1:%%p"
    type nul > "storage\logs\pool\php-cgi-%%p.log"
    start "" /b cmd /d /v:on /c "for /l %%i in (0,0,1) do ("%DMS_PHP%\php-cgi.exe" -b 127.0.0.1:%%p >nul 2>>"storage\logs\pool\php-cgi-%%p.log" & echo ^!date^! ^!time^! php-cgi %%p berhenti, dijalankan ulang>>"storage\logs\dms-server.log" & ping -n 2 127.0.0.1 >nul)"
)

:: HTTPS names for the certificate: localhost, 127.0.0.1, PC name, the fixed office IP
:: (DMS_SERVER_LAN_IP from .env) and every IPv4 address this PC has right now.
set "DMS_TLS_SITES=https://localhost:8443, https://127.0.0.1:8443, https://%COMPUTERNAME%:8443"
if defined DMS_SERVER_LAN_IP set "DMS_TLS_SITES=!DMS_TLS_SITES!, https://%DMS_SERVER_LAN_IP%:8443"
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do for /f "tokens=*" %%b in ("%%a") do (
    if /i not "%%b"=="%DMS_SERVER_LAN_IP%" set "DMS_TLS_SITES=!DMS_TLS_SITES!, https://%%b:8443"
)
if not exist "storage\caddy" mkdir "storage\caddy"

"%~dp0caddy\caddy.exe" run --config Caddyfile --adapter caddyfile >"storage\logs\caddy.out.log" 2>&1
endlocal

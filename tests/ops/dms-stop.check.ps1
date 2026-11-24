# Self-check for evironment\dms-stop.ps1 (R18). Safe next to a running DMS server: it works in
# a temp folder with its own fake "evironment" (renamed ping.exe as php-cgi.exe/caddy.exe),
# so nothing of the real server matches. Run:
#   powershell -NoProfile -ExecutionPolicy Bypass -File tests\ops\dms-stop.check.ps1
$ErrorActionPreference = 'Stop'
$root = Join-Path $env:TEMP ("dms-stop-check-" + [guid]::NewGuid().ToString('N').Substring(0, 8))
$envDir = Join-Path $root 'evironment'
New-Item -ItemType Directory -Force (Join-Path $envDir 'php') | Out-Null
Copy-Item (Join-Path $PSScriptRoot '..\..\evironment\dms-stop.ps1') $envDir
$fakeCgi = Join-Path $envDir 'php\php-cgi.exe'
$fakeCaddy = Join-Path $envDir 'caddy.exe'
Copy-Item "$env:SystemRoot\System32\PING.EXE" $fakeCgi
Copy-Item "$env:SystemRoot\System32\PING.EXE" $fakeCaddy
# Fake launcher: a php-cgi restart loop (same shape as dms-server.cmd) + "Caddy" in front.
Set-Content (Join-Path $root 'START-DMS-INDRACO.bat') -Encoding ascii -Value @"
@echo off
start "" /b cmd /d /c "for /l %%i in (0,0,1) do ("$fakeCgi" -n 600 127.0.0.1 >nul & ping -n 2 127.0.0.1 >nul)"
"$fakeCaddy" -n 600 127.0.0.1 >nul
"@
$stop = Join-Path $envDir 'dms-stop.ps1'

function Mine { @(Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -and $_.CommandLine.IndexOf($root, [StringComparison]::OrdinalIgnoreCase) -ge 0 }) }
function Start-Fake { Start-Process cmd -ArgumentList '/c', ('"' + (Join-Path $root 'START-DMS-INDRACO.bat') + '"') -WindowStyle Hidden -PassThru }
function Wait-Up([int]$min) { foreach ($i in 1..40) { if ((Mine).Count -ge $min) { return }; Start-Sleep -Milliseconds 100 }; throw "fake server did not start" }
function Assert($ok, $msg) { if (-not $ok) { throw "FAIL: $msg" }; Write-Host "ok - $msg" }

try {
    # 1. STOP kills launcher, loop, php-cgi, caddy of this folder and waits for them.
    $old = Start-Fake; Wait-Up 4
    & powershell -NoProfile -ExecutionPolicy Bypass -File $stop -Port 18999 | Out-Null
    Assert ($LASTEXITCODE -eq 0) 'stop exit code 0'
    Assert ($old.HasExited) 'old launcher window gone'
    Assert ((Mine).Count -eq 0) 'no process of the folder left'

    # 2. R18: START right after STOP survives (nothing of the new server is killed later).
    $old = Start-Fake; Wait-Up 4
    $stopJob = Start-Process powershell -ArgumentList '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $stop, '-Port', '18999' -WindowStyle Hidden -PassThru
    $stopJob.WaitForExit()
    $new = Start-Fake; Wait-Up 4
    Start-Sleep -Seconds 3
    Assert (-not $new.HasExited) 'new launcher still running 3 s after STOP'
    Assert (@(Mine | Where-Object { $_.Name -eq 'php-cgi.exe' }).Count -ge 1) 'new php-cgi alive'

    # 3. -Leftovers (from START): clears an orphaned pool but not a "Caddy" / launcher.
    $all = Mine
    $loop = $all | Where-Object { $_.Name -eq 'cmd.exe' -and $_.CommandLine -like '*php-cgi.exe*' }
    & powershell -NoProfile -ExecutionPolicy Bypass -File $stop -Leftovers -Port 18999 | Out-Null
    $after = Mine
    Assert (@($after | Where-Object { $_.Name -eq 'php-cgi.exe' -or $_.ProcessId -eq $loop.ProcessId }).Count -eq 0) '-Leftovers killed loop + php-cgi'
    Assert (@($after | Where-Object { $_.Name -eq 'caddy.exe' }).Count -eq 1) '-Leftovers kept caddy'
    Assert (-not $new.HasExited) '-Leftovers kept the launcher'
    Write-Host 'PASS'
}
finally {
    & powershell -NoProfile -ExecutionPolicy Bypass -File $stop -Port 18999 | Out-Null
    Remove-Item -LiteralPath $root -Recurse -Force -ErrorAction SilentlyContinue
}

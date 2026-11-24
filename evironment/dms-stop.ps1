# DMS PT Indraco - stops the server of THIS folder (called by STOP-DMS-INDRACO.bat).
#
# R18: the processes to stop are picked ONCE (snapshot) and held as process handles; only
# those are killed and waited for. A server started right after (or while) STOP runs is
# never touched: no second search by name/path/port, no taskkill by window title (which
# also matched other folders' windows and could hit Windows Terminal).
#
# -Leftovers (START-DMS-INDRACO.bat, after its port-8000 check): only clear php-cgi
# loops/workers and dms-ready.ps1 left by an old launcher of this folder, so the new pool
# can bind 9001.. and an old dms-ready.ps1 cannot write "GAGAL" into the new startup.log.
param([switch]$Leftovers, [int]$Port = 8000, [int]$TimeoutSec = 15)

$envDir = $PSScriptRoot
$launcher = Join-Path (Split-Path $envDir -Parent) 'START-DMS-INDRACO.bat'
function Has([string]$text, [string]$part) { $text -and $text.IndexOf($part, [StringComparison]::OrdinalIgnoreCase) -ge 0 }
function IsLoop($p) { $p.Name -eq 'cmd.exe' -and (Has $p.CommandLine 'php-cgi.exe') }

$all = @(Get-CimInstance Win32_Process)
$byPid = @{}
foreach ($p in $all) { $byPid[[int]$p.ProcessId] = $p }

$pick = @{}
foreach ($p in $all) {
    $n = $p.Name
    $mine = ((IsLoop $p) -and (Has $p.CommandLine $envDir)) -or
            ($n -eq 'php-cgi.exe' -and (Has $p.ExecutablePath $envDir)) -or
            ($n -eq 'powershell.exe' -and (Has $p.CommandLine "$envDir\dms-ready.ps1"))
    if (-not $Leftovers) {
        $mine = $mine -or ($n -eq 'caddy.exe' -and (Has $p.ExecutablePath $envDir)) -or
                ($n -eq 'cmd.exe' -and (Has $p.CommandLine $launcher))
    }
    if ($mine) { $pick[[int]$p.ProcessId] = $true }
}
if (-not $Leftovers) {
    # Whatever held the port when STOP began (e.g. an old Laravel 7 "artisan serve").
    try {
        Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction Stop |
            Where-Object { $_.OwningProcess -gt 4 } | ForEach-Object { $pick[[int]$_.OwningProcess] = $true }
    } catch { }
}
# Children of what was picked (launcher window, loops): pool from Laragon/XAMPP PHP,
# a running "php artisan migrate", conhost. Only these names, never e.g. a browser.
$childNames = 'cmd.exe', 'conhost.exe', 'php-cgi.exe', 'php.exe', 'caddy.exe', 'powershell.exe', 'ping.exe', 'timeout.exe'
do {
    $added = $false
    foreach ($p in $all) {
        $id = [int]$p.ProcessId
        if (-not $pick.ContainsKey($id) -and $pick.ContainsKey([int]$p.ParentProcessId) -and $childNames -contains $p.Name) {
            $pick[$id] = $true; $added = $true
        }
    }
} while ($added)

# Never stop ourselves or whatever started us (STOP-DMS-INDRACO.bat, UPDATE-AND-RESTART.bat).
$id = $PID
foreach ($i in 1..64) {
    $pick.Remove($id)
    if (-not $byPid.ContainsKey($id)) { break }
    $id = [int]$byPid[$id].ParentProcessId
}

# Process objects keep a handle: a PID reused by a new server can never be hit.
$procs = @()
if ($pick.Count) { $procs = @(Get-Process -Id @($pick.Keys) -ErrorAction SilentlyContinue) }
$loopIds = @($pick.Keys | Where-Object { $byPid.ContainsKey($_) -and (IsLoop $byPid[$_]) })
# Restart loops first, so none of them starts a fresh php-cgi meanwhile.
foreach ($p in ($procs | Sort-Object { if ($loopIds -contains $_.Id) { 0 } else { 1 } })) {
    try { $p.Kill() } catch { }
}
# A loop that restarted php-cgi between snapshot and kill: that child dies too.
Get-CimInstance Win32_Process -Filter "Name = 'php-cgi.exe'" |
    Where-Object { $loopIds -contains [int]$_.ParentProcessId } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }

$deadline = (Get-Date).AddSeconds($TimeoutSec)
$left = 0
foreach ($p in $procs) {
    $ms = [Math]::Max(0, [int]($deadline - (Get-Date)).TotalMilliseconds)
    if (-not $p.WaitForExit($ms)) { $left++ }
}
Write-Host ("  dihentikan: {0} proses{1}" -f ($procs.Count - $left), $(if ($left) { ", $left belum keluar" } else { '' }))
exit [int]($left -gt 0)

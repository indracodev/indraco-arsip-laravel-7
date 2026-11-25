# DMS PT Indraco - samples RAM/CPU of one server instance (Caddy + php-cgi pool) once per second.
#   pwsh tests/k6/sample-resources.ps1 -RootPid <pid of the dms-server cmd> -Seconds 130 -Out res.csv
# RootPid = the process that started the pool (dms-server.cmd's cmd.exe); every php-cgi/caddy
# below it is summed. cpu_pct = % of ONE core (200 = two cores fully busy).
# Or pass the process ids directly: -Pids 101,102,103 (a test instance started by hand).
param([int]$RootPid, [string]$Pids, [int]$Seconds = 120, [Parameter(Mandatory)][string]$Out)

function Get-PoolIds([int]$root) {
    $all = Get-CimInstance Win32_Process -Property ProcessId, ParentProcessId, Name
    $ids = @($root); $found = @()
    while ($ids.Count) {
        $kids = $all | Where-Object { $ids -contains $_.ParentProcessId }
        $found += $kids | Where-Object { $_.Name -in 'php-cgi.exe', 'caddy.exe' } | ForEach-Object ProcessId
        $ids = @($kids | ForEach-Object ProcessId)
    }
    $found
}

$ids = if ($Pids) { [int[]]($Pids -split ',') } else { Get-PoolIds $RootPid }
"t,procs,ws_mb,private_mb,cpu_pct" | Set-Content $Out
$prev = @{}
for ($i = 0; $i -le $Seconds; $i++) {
    $t0 = Get-Date
    $ps = Get-Process -Id $ids -ErrorAction SilentlyContinue
    $cpu = 0
    foreach ($p in $ps) {
        $now = $p.TotalProcessorTime.TotalMilliseconds
        if ($prev.ContainsKey($p.Id)) { $cpu += $now - $prev[$p.Id] }
        $prev[$p.Id] = $now
    }
    $ws = ($ps | Measure-Object WorkingSet64 -Sum).Sum / 1MB
    $pm = ($ps | Measure-Object PrivateMemorySize64 -Sum).Sum / 1MB
    "$i,$($ps.Count),$([int]$ws),$([int]$pm),$([int]($cpu / 10))" | Add-Content $Out
    $wait = 1000 - ((Get-Date) - $t0).TotalMilliseconds
    if ($wait -gt 0) { Start-Sleep -Milliseconds $wait }
}


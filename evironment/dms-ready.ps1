# DMS PT Indraco - waits until PHP really answers, warms every php-cgi worker,
# then reports "SIAP" in the launcher window (and opens the browser).
# Started in the background by START-DMS-INDRACO.bat; exits when done (no idle polling).
param(
    [int]$Workers = 4,
    [int]$TimeoutSec = 120,
    [switch]$OpenBrowser,
    [string]$Url = 'http://127.0.0.1:8000'
)

$log = 'storage\logs\startup.log'
Add-Type -AssemblyName System.Net.Http
$client = New-Object System.Net.Http.HttpClient
$client.Timeout = [TimeSpan]::FromSeconds(15)
$sw = [Diagnostics.Stopwatch]::StartNew()
$pids = @{}
$firstOk = $null
$extraRounds = 0

# N parallel requests per round: Caddy hands each to a different free worker, so
# every worker boots Laravel once. Stop when all N PIDs answered, or 20 rounds after
# the first answer (some workers may still be starting; they warm on first use).
while ($sw.Elapsed.TotalSeconds -lt $TimeoutSec) {
    $tasks = @(1..$Workers | ForEach-Object { $client.GetStringAsync("$Url/api/health/ready") })
    try { [Threading.Tasks.Task]::WaitAll($tasks) } catch { }
    foreach ($t in $tasks) {
        if ($t.Status -eq 'RanToCompletion' -and $t.Result -match '"pid":(\d+)') { $pids[$Matches[1]] = $true }
    }
    if ($pids.Count -gt 0 -and -not $firstOk) { $firstOk = $sw.Elapsed.TotalSeconds }
    if ($pids.Count -ge $Workers) { break }
    if ($firstOk) { $extraRounds++; if ($extraRounds -ge 20) { break } }
    Start-Sleep -Milliseconds 250
}

# R14: also render a real page on every worker (session, Blade views, settings query), so
# the first user does not pay for cold OPcache / view compilation. 2 rounds of N parallel
# requests: a busy worker is skipped by Caddy, so each round reaches every worker.
if ($pids.Count -gt 0) {
    foreach ($round in 1..2) {
        $tasks = @(1..$Workers | ForEach-Object { $client.GetStringAsync("$Url/login") })
        try { [Threading.Tasks.Task]::WaitAll($tasks) } catch { }
    }
}

$secs = '{0:N1}' -f $sw.Elapsed.TotalSeconds
if ($pids.Count -eq 0) {
    Add-Content $log ("{0:HH:mm:ss,ff} GAGAL: PHP tidak merespons dalam {1} dtk" -f (Get-Date), $TimeoutSec)
    Write-Host ''
    Write-Host "  [ERROR] PHP tidak merespons dalam $TimeoutSec detik." -ForegroundColor Red
    Write-Host '          Periksa storage\logs\pool\php-cgi-*.log dan storage\logs\laravel-*.log,' -ForegroundColor Red
    Write-Host '          lalu tutup jendela ini dan jalankan ulang START-DMS-INDRACO.bat.' -ForegroundColor Red
    exit 1
}

Add-Content $log ("{0:HH:mm:ss,ff} siap: {1}/{2} worker hangat dalam {3} dtk" -f (Get-Date), $pids.Count, $Workers, $secs)
Write-Host ''
Write-Host "  STATUS: [ SERVER AKTIF & SIAP MENERIMA KONEKSI ]  ($($pids.Count)/$Workers worker siap, $secs dtk)" -ForegroundColor Green
if ($OpenBrowser) { Start-Process $Url }

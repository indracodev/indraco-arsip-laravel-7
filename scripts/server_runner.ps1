param (
    [string]$PhpExe = "",
    [string]$ProjectRoot = "",
    [int]$Port = 8000
)

# 1. Resolve Project Root
if (-not $ProjectRoot) {
    $ProjectRoot = (Get-Item -Path "$PSScriptRoot\..").FullName
}
Set-Location -Path $ProjectRoot

# 2. Resolve PHP Executable
if (-not $PhpExe -or -not (Test-Path $PhpExe)) {
    if (Test-Path "$ProjectRoot\evironment\php-8.2.29-Win32-vs16-x64\php.exe") {
        $PhpExe = "$ProjectRoot\evironment\php-8.2.29-Win32-vs16-x64\php.exe"
    } elseif (Test-Path "$ProjectRoot\environment\php-8.2.29-Win32-vs16-x64\php.exe") {
        $PhpExe = "$ProjectRoot\environment\php-8.2.29-Win32-vs16-x64\php.exe"
    } elseif (Get-Command php -ErrorAction SilentlyContinue) {
        $PhpExe = (Get-Command php).Source
    } else {
        Write-Host "[ERROR] PHP Executable tidak ditemukan!" -ForegroundColor Red
        pause
        exit 1
    }
}

# 3. Detect LAN IP Address
$lanIp = "127.0.0.1"
try {
    $ipObj = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object {
        $_.InterfaceAlias -notmatch 'Loopback|vEthernet|Virtual' -and
        $_.IPAddress -notmatch '^127\.|^169\.254\.'
    } | Select-Object -First 1
    if ($ipObj) {
        $lanIp = $ipObj.IPAddress
    }
} catch {
    # Fallback using ipconfig
    $ipConfigOut = ipconfig | Out-String
    if ($ipConfigOut -match 'IPv4 Address[.\s]+:\s+([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)') {
        $lanIp = $matches[1]
    }
}

# 4. Check & Prepare Storage Logs
$logDir = "$ProjectRoot\storage\logs"
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}
$serverLogFile = "$logDir\php-server.log"

# 5. Start Background PHP Server Process (Redirect Output to log file so terminal doesn't scroll)
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $PhpExe
$psi.Arguments = "-S 0.0.0.0:$Port server.php"
$psi.WorkingDirectory = $ProjectRoot
$psi.UseShellExecute = $false
$psi.CreateNoWindow = $true
$psi.RedirectStandardOutput = $true
$psi.RedirectStandardError = $true

$phpProc = [System.Diagnostics.Process]::Start($psi)
if (-not $phpProc -or $phpProc.HasExited) {
    Write-Host "[ERROR] Gagal menjalankan server PHP pada port $Port!" -ForegroundColor Red
    pause
    exit 1
}

# Auto-open browser after 2 seconds
Start-Job -ScriptBlock {
    param($url)
    Start-Sleep -Seconds 2
    Start-Process $url
} -ArgumentList "http://127.0.0.1:$Port" | Out-Null

$startTime = [System.DateTime]::Now
$host.UI.RawUI.WindowTitle = "DMS PT INDRACO - Server Aktif (Port: $Port)"

# Setup clean console
Clear-Host
[Console]::CursorVisible = $false

function Draw-Stationary-Dashboard {
    param($uptimeStr, $nowStr, $lastReq)

    # Move cursor to top-left corner (0,0) - ZERO SCROLLING
    [Console]::SetCursorPosition(0, 0)

    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "            PT INDRACO - DOCUMENT MANAGEMENT SYSTEM (DMS)" -ForegroundColor White
    Write-Host "                     REALTIME SERVER DASHBOARD" -ForegroundColor DarkCyan
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "  * Status Server     : " -NoNewline; Write-Host "[ AKTIF / BERJALAN ]" -ForegroundColor Green
    Write-Host "  * Hostname Komputer : " -NoNewline; Write-Host "$env:COMPUTERNAME" -ForegroundColor Yellow
    Write-Host "  * Port Layanan      : " -NoNewline; Write-Host "$Port" -ForegroundColor Yellow
    Write-Host "  * Waktu Server      : " -NoNewline; Write-Host "$nowStr" -ForegroundColor Gray
    Write-Host "  * Durasi Uptime     : " -NoNewline; Write-Host "$uptimeStr" -ForegroundColor White
    Write-Host "-------------------------------------------------------------------------------" -ForegroundColor DarkGray
    Write-Host "  ALAMAT AKSES KOMPUTER INI (LOCALHOST):" -ForegroundColor Gray
    Write-Host "  --> " -NoNewline; Write-Host "http://127.0.0.1:$Port" -ForegroundColor Cyan; Write-Host "  atau  http://localhost:$Port" -ForegroundColor DarkCyan
    Write-Host ""
    Write-Host "  ALAMAT AKSES LAPTOP LAIN (WIFI / LAN KANTOR):" -ForegroundColor Yellow
    Write-Host "  ================================================================" -ForegroundColor Yellow
    Write-Host "   --> " -NoNewline; Write-Host "http://${lanIp}:${Port}" -ForegroundColor Green
    Write-Host "  ================================================================" -ForegroundColor Yellow
    Write-Host "  (Gunakan alamat di atas untuk membuka DMS dari laptop/komputer lain)" -ForegroundColor DarkGray
    Write-Host ""
    Write-Host "  PANEL METRIK & DIAGNOSTIK (SUPER ADMIN):" -ForegroundColor Gray
    Write-Host "  --> " -NoNewline; Write-Host "http://${lanIp}:${Port}/diagnostics" -ForegroundColor Magenta
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "  [ MONITOR TRAFIK REALTIME ]" -ForegroundColor DarkCyan
    
    # Pad last request string to clear remaining characters
    $paddedReq = "$lastReq"
    if ($paddedReq.Length -gt 75) { $paddedReq = $paddedReq.Substring(0, 75) }
    $paddedReq = $paddedReq.PadRight(75, ' ')
    Write-Host "  Aktivitas: " -NoNewline; Write-Host "$paddedReq" -ForegroundColor White
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "  PINTASAN KEYBOARD:" -ForegroundColor Gray
    Write-Host "  [B] Buka Browser | [D] Panel Diagnostik | [Q / Ctrl+C] Matikan Server" -ForegroundColor Yellow
    Write-Host "===============================================================================" -ForegroundColor Cyan
}

try {
    $lastLogLine = "Server siap menerima koneksi jaringan..."

    while (-not $phpProc.HasExited) {
        $elapsed = [System.DateTime]::Now - $startTime
        $uptime = "{0:D2}:{1:D2}:{2:D1}" -f [int]$elapsed.TotalHours, $elapsed.Minutes, $elapsed.Seconds
        $currentTime = (Get-Date).ToString("HH:mm:ss")

        # Read last non-empty line from php-server.log if available
        if (Test-Path $serverLogFile) {
            try {
                $lines = Get-Content -Path $serverLogFile -Tail 2 -ErrorAction SilentlyContinue
                if ($lines -and $lines.Count -gt 0) {
                    $lastLine = ($lines | Where-Object { $_.Trim() } | Select-Object -Last 1)
                    if ($lastLine) {
                        $lastLogLine = $lastLine
                    }
                }
            } catch {}
        }

        Draw-Stationary-Dashboard -uptimeStr $uptime -nowStr $currentTime -lastReq $lastLogLine

        # Check for keyboard input (non-blocking)
        if ([Console]::KeyAvailable) {
            $key = [Console]::ReadKey($true)
            if ($key.Key -eq [ConsoleKey]::Q) {
                break
            } elseif ($key.Key -eq [ConsoleKey]::B) {
                Start-Process "http://127.0.0.1:$Port"
            } elseif ($key.Key -eq [ConsoleKey]::D) {
                Start-Process "http://${lanIp}:${Port}/diagnostics"
            }
        }

        Start-Sleep -Milliseconds 800
    }
} finally {
    [Console]::CursorVisible = $true
    if ($phpProc -and -not $phpProc.HasExited) {
        Write-Host "`n[*] Menghentikan server PHP..." -ForegroundColor Yellow
        $phpProc.Kill()
        $phpProc.WaitForExit(3000)
    }
    Clear-Host
    Write-Host "===============================================================================" -ForegroundColor Cyan
    Write-Host "  [OK] Server DMS PT Indraco telah dimatikan secara bersih." -ForegroundColor Green
    Write-Host "===============================================================================" -ForegroundColor Cyan
}

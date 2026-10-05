Add-Type -AssemblyName System.Drawing

$projectDir = "c:\laragon\www\#Project2026\indraco-arsip-laravel-7"
$pngPath = Join-Path $projectDir "logo-indraco-est.png"
$icoPath = Join-Path $projectDir "indraco.ico"
$csPath = Join-Path $projectDir "scratch\Launcher.cs"
$exePath = Join-Path $projectDir "START-DMS-INDRACO.exe"

# 1. Generate ICO from PNG
if (Test-Path $pngPath) {
    Write-Host "[*] Creating indraco.ico from logo-indraco-est.png..."
    $bmp = [System.Drawing.Bitmap]::FromFile($pngPath)
    $thumb = New-Object System.Drawing.Bitmap 256, 256
    $g = [System.Drawing.Graphics]::FromImage($thumb)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality

    $ratio = [Math]::Min(256.0 / $bmp.Width, 256.0 / $bmp.Height)
    $w = [int]($bmp.Width * $ratio)
    $h = [int]($bmp.Height * $ratio)
    $x = [int]((256 - $w) / 2)
    $y = [int]((256 - $h) / 2)

    $g.DrawImage($bmp, $x, $y, $w, $h)
    $hIcon = $thumb.GetHicon()
    $icon = [System.Drawing.Icon]::FromHandle($hIcon)

    $stream = New-Object System.IO.FileStream($icoPath, [System.IO.FileMode]::Create)
    $icon.Save($stream)
    $stream.Close()
    $bmp.Dispose()
    $thumb.Dispose()
    $icon.Dispose()
    Write-Host "[OK] indraco.ico created."
}

# 2. Compile C# Launcher
$csc = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if (-not (Test-Path $csc)) {
    $csc = "C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe"
}

Write-Host "[*] Compiling START-DMS-INDRACO.exe using csc.exe..."
if (Test-Path $icoPath) {
    & $csc /target:exe /win32icon:"$icoPath" /out:"$exePath" "$csPath"
} else {
    & $csc /target:exe /out:"$exePath" "$csPath"
}

if (Test-Path $exePath) {
    Write-Host "[SUCCESS] START-DMS-INDRACO.exe successfully built!" -ForegroundColor Green
} else {
    Write-Host "[FAILED] Failed to build START-DMS-INDRACO.exe" -ForegroundColor Red
}

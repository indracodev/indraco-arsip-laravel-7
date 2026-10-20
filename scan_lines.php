<?php
$scanDirs = ['app', 'resources', 'routes', 'config', 'database', 'tests'];
$results = [];

foreach ($scanDirs as $sDir) {
    if (!is_dir($sDir)) continue;
    $dir = new RecursiveDirectoryIterator($sDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $it = new RecursiveIteratorIterator($dir);
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['sqlite', 'db', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'map'])) continue;
        $filePath = str_replace('\\', '/', $file->getPathname());
        $lineCount = 0;
        $handle = fopen($file->getPathname(), "r");
        if ($handle) {
            while (!feof($handle)) {
                $lineCount += substr_count(fread($handle, 8192), "\n");
            }
            fclose($handle);
        }
        if ($lineCount >= 800) {
            $results[] = ['lines' => $lineCount, 'path' => $filePath];
        }
    }
}

usort($results, function($a, $b) { return $b['lines'] <=> $a['lines']; });
foreach ($results as $r) {
    echo sprintf("%5d : %s\n", $r['lines'], $r['path']);
}
echo "Total code files >= 800: " . count($results) . "\n";

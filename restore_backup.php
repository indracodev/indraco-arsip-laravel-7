<?php

$dbPath = __DIR__ . '/database/database.sqlite';
$sqlPath = __DIR__ . '/database/backup_indraco_dms_2026_10_05_111716.sql';

if (!file_exists($sqlPath)) {
    die("Backup file not found at: {$sqlPath}\n");
}

echo "Starting restore from {$sqlPath} to {$dbPath}...\n";

// Connect PDO SQLite
$pdo = new PDO("sqlite:{$dbPath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Read SQL
$sql = file_get_contents($sqlPath);

// Convert MySQL escaped single quotes \' to standard SQL ''
// Also handle \" if any
$sql = str_replace("\\'", "''", $sql);

// Turn off foreign keys and start transaction
$pdo->exec("PRAGMA foreign_keys = OFF;");

// Split by semicolons at end of line to execute statement by statement
$statements = preg_split('/;\s*$/m', $sql);

$executedCount = 0;
$pdo->beginTransaction();
try {
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        // Ignore single-line comments or pragma if already set
        if (strpos($stmt, '--') === 0 && strpos($stmt, "\n") === false) continue;
        
        $pdo->exec($stmt);
        $executedCount++;
    }
    $pdo->commit();
    echo "Executed {$executedCount} SQL statements successfully!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error executing SQL: " . $e->getMessage() . "\nLast statement: " . substr($stmt ?? '', 0, 200) . "...\n");
}

$pdo->exec("PRAGMA foreign_keys = ON;");

echo "Database restored from SQL dump successfully!\n";

// Check archives table for 'periode' column
$stmt = $pdo->query("PRAGMA table_info(archives)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hasPeriode = false;
foreach ($columns as $col) {
    if ($col['name'] === 'periode') {
        $hasPeriode = true;
        break;
    }
}

if (!$hasPeriode) {
    $pdo->exec("ALTER TABLE archives ADD COLUMN periode VARCHAR(100) NULL;");
    echo "Added 'periode' column to archives table.\n";
} else {
    echo "'periode' column already exists in archives table.\n";
}

// Ensure migrations table exists and records our migration
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration VARCHAR NOT NULL, batch INTEGER NOT NULL);");
$stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
$stmt->execute(['2026_10_06_000001_add_periode_to_archives_table']);
if ($stmt->fetchColumn() == 0) {
    $stmtMax = $pdo->query("SELECT MAX(batch) FROM migrations");
    $maxBatch = (int)$stmtMax->fetchColumn() + 1;
    $stmtInsert = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
    $stmtInsert->execute(['2026_10_06_000001_add_periode_to_archives_table', $maxBatch]);
    echo "Recorded migration 2026_10_06_000001_add_periode_to_archives_table in migrations table.\n";
}

// Print summary of table counts
echo "\n--- Table Rows Summary ---\n";
$tables = [
    'users',
    'departments',
    'sub_departments',
    'master_archives',
    'warehouses',
    'warehouse_locations',
    'warehouse_rack_slots',
    'archives',
    'archive_items',
    'app_settings',
    'activity_logs',
    'borrowing_logs',
    'destruction_logs',
    'warehouse_entry_logs'
];

foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo str_pad($table, 25) . ": {$count} rows\n";
    } catch (Exception $e) {
        echo str_pad($table, 25) . ": [Table not found]\n";
    }
}

echo "\nRestore and migration check finished successfully.\n";

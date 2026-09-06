<?php
/**
 * Seed performance_metrics for Feb 2026 so Lab Performance Reports show data.
 * Run: php admin/modules/lis/run_seed_performance.php
 */
require_once __DIR__ . '/../../../config/config.php';

$sqlFile = __DIR__ . '/seed_performance_metrics.sql';
if (!is_readable($sqlFile)) {
    die("Error: Cannot read seed_performance_metrics.sql\n");
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

$db = $GLOBALS['db'] ?? null;
if (!$db) {
    die("Error: No database connection.\n");
}

$ok = 0;
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    try {
        $db->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage() . "\n");
    }
}

echo "Performance metrics seed done. Statements executed: $ok. Feb 2026 data added for Lab Performance Reports.\n";

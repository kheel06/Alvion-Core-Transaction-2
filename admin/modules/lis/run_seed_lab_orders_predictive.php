<?php
/**
 * Seed lab_orders for the last 7 days so the Predictive Analytics dashboard shows real data.
 * Run from project root: php admin/modules/lis/run_seed_lab_orders_predictive.php
 */
require_once __DIR__ . '/../../../config/config.php';

$sqlFile = __DIR__ . '/seed_lab_orders_predictive.sql';
if (!is_readable($sqlFile)) {
    die("Error: Cannot read seed_lab_orders_predictive.sql\n");
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

echo "Lab orders predictive seed done. Statements executed: $ok.\n";
echo "Refresh the admin dashboard to see real patient volume and lab order forecasts.\n";

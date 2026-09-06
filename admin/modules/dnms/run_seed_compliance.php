<?php
/**
 * Seed nutrition compliance data for the Nutrition Compliance Reports page.
 * Run after DNMS database_setup.sql. Execute: php admin/modules/dnms/run_seed_compliance.php
 */
require_once __DIR__ . '/../../../config/config.php';

$sqlFile = __DIR__ . '/seed_nutrition_compliance.sql';
if (!is_readable($sqlFile)) {
    die("Error: Cannot read seed_nutrition_compliance.sql\n");
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

$db = $GLOBALS['db'] ?? null;
if (!$db) {
    die("Error: No database connection. Check config/database.php.\n");
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

echo "Nutrition compliance seed done. Statements executed: $ok.\n";
echo "Open Nutrition Compliance Reports with date range Feb 1–6, 2026 to see data and chart.\n";

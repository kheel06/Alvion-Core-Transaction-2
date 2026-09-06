<?php
/**
 * Seed surgery schedule for the week Feb 2-8, 2026 (real data for calendar).
 * Run: php admin/modules/sors/run_seed_schedule.php
 */
require_once __DIR__ . '/../../../config/config.php';

$sqlFile = __DIR__ . '/seed_schedule_week.sql';
if (!is_readable($sqlFile)) {
    die("Error: Cannot read seed_schedule_week.sql\n");
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

echo "Schedule seed done. Statements executed: $ok. Week Feb 2-8, 2026 now has surgery data.\n";

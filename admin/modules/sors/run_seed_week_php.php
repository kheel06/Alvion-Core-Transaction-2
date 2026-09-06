<?php
require_once __DIR__ . '/../../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();
if (!$pdo) {
    die("No database connection.\n");
}
require_once __DIR__ . '/seed_schedule_week_php.php';
$r = seed_sors_schedule_week($pdo, '2026-02-02', '2026-02-08');
echo "Inserted: " . $r['inserted'] . "\n";
if (!empty($r['errors'])) {
    echo "Errors: " . implode("; ", $r['errors']) . "\n";
}

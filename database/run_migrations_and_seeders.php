<?php
/**
 * Run all migrations and seeders so report pages show data.
 * Lab Performance, Radiology Analytics, Pharmacy Reports, Nutrition Compliance, OR Utilization.
 *
 * Run from project root: php database/run_migrations_and_seeders.php
 * Or from browser: http://hospital-core2-system.test/database/run_migrations_and_seeders.php
 */

$isCli = php_sapi_name() === 'cli';
$baseDir = dirname(__DIR__);

require_once $baseDir . '/config/database.php';
$database = new Database();
$pdo = $database->getConnection();
if (!$pdo) {
    output('Database connection failed. Check config/database.php.', true);
    exit(1);
}

// Use mysqli for multi-statement execution (PDO does not support it natively)
$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => 'hospital-core2-system',
    'username' => 'root',
    'password' => 'petras123',
];
// Read from Database class if we could; for now use same as config
if (file_exists($baseDir . '/config/database.php')) {
    $content = file_get_contents($baseDir . '/config/database.php');
    if (preg_match('/db_name\s*=\s*["\']([^"\']+)["\']/', $content, $m)) $dbConfig['dbname'] = $m[1];
    if (preg_match('/username\s*=\s*["\']([^"\']+)["\']/', $content, $m)) $dbConfig['username'] = $m[1];
    if (preg_match('/password\s*=\s*["\']([^"\']+)["\']/', $content, $m)) $dbConfig['password'] = $m[1];
    if (preg_match('/host\s*=\s*["\']([^"\']+)["\']/', $content, $m)) $dbConfig['host'] = $m[1];
}

$mysqli = @new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['dbname']);
if ($mysqli->connect_error) {
    output('MySQLi connection failed: ' . $mysqli->connect_error, true);
    exit(1);
}
$mysqli->set_charset('utf8mb4');

function output($msg, $isError = false) {
    global $isCli;
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo '<p style="' . ($isError ? 'color:red;' : '') . '">' . htmlspecialchars($msg) . '</p>';
        if (ob_get_level()) ob_flush();
        flush();
    }
}

function runSqlFile($path, $label, $mysqli) {
    global $isCli, $baseDir;
    $fullPath = $baseDir . '/' . $path;
    if (!file_exists($fullPath)) {
        output("  [SKIP] $label – file not found: $path");
        return true;
    }
    $sql = file_get_contents($fullPath);
    $sql = trim($sql);
    if (empty($sql)) {
        output("  [OK] $label – empty file");
        return true;
    }
    if (!$mysqli->multi_query($sql)) {
        output("  [FAIL] $label – " . $mysqli->error, true);
        return false;
    }
    try {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            if ($mysqli->errno) {
                output("  [WARN] $label – " . $mysqli->error);
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
    } catch (Throwable $e) {
        output("  [WARN] $label – " . $e->getMessage());
        while ($mysqli->more_results()) {
            @$mysqli->next_result();
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        }
    }
    if (!$mysqli->errno) {
        output("  [OK] $label");
    }
    return true;
}

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Run Migrations & Seeders</title></head><body><h1>Run Migrations & Seeders</h1>';
}

output('Starting migrations and seeders...');

// 1. Core schema and seeds (optional – may already be applied)
runSqlFile('database/001_core_schema.sql', '001_core_schema.sql', $mysqli);
runSqlFile('database/002_core_seed.sql', '002_core_seed.sql', $mysqli);
runSqlFile('database/003_billing_philippines.sql', '003_billing_philippines.sql', $mysqli);
runSqlFile('database/004_doctor_portal.sql', '004_doctor_portal.sql (Doctor Portal – real data)', $mysqli);

// 2. Clinical module setups (create tables + seed data)
runSqlFile('admin/modules/lis/database_setup.sql', 'LIS database_setup.sql', $mysqli);
runSqlFile('admin/modules/ris/database_setup.sql', 'RIS database_setup.sql', $mysqli);
runSqlFile('admin/modules/pms/database_setup.sql', 'PMS database_setup.sql', $mysqli);
runSqlFile('admin/modules/sors/database_setup.sql', 'SORS database_setup.sql', $mysqli);
runSqlFile('admin/modules/dnms/database_setup.sql', 'DNMS database_setup.sql', $mysqli);

// 3. Extra seeds for current date range (Feb 2026) so reports show data
runSqlFile('admin/modules/lis/seed_performance_metrics.sql', 'LIS seed_performance_metrics.sql', $mysqli);
runSqlFile('admin/modules/dnms/seed_nutrition_compliance.sql', 'DNMS seed_nutrition_compliance.sql', $mysqli);
// SORS schedule: PHP seeder so schedule page shows data (week Feb 2–8, 2026); ties to Doctor Availability
require_once $baseDir . '/admin/modules/sors/seed_schedule_week_php.php';
$sorsSeed = seed_sors_schedule_week($pdo, '2026-02-02', '2026-02-08');
if ($sorsSeed['inserted'] > 0) {
    output('  [OK] SORS schedule week (PHP) – inserted ' . $sorsSeed['inserted'] . ' rows');
} elseif (!empty($sorsSeed['errors'])) {
    output('  [WARN] SORS schedule week – ' . implode('; ', $sorsSeed['errors']));
} else {
    output('  [OK] SORS schedule week (PHP) – no new rows (already seeded)');
}

$mysqli->close();

output('Done. Refresh Lab Performance Reports, Radiology Analytics, Pharmacy Reports, Nutrition Compliance, Surgery Schedule & Block Time, and OR Utilization & Reports to see data.');

if (!$isCli) {
    echo '<p><a href="../admin/admin-dashboard.php">Back to Dashboard</a></p></body></html>';
}

<?php
/**
 * Run SORS migration: create tables and seed Philippine data.
 * Execute from browser: /admin/modules/sors/run_migration.php
 * Or CLI: php run_migration.php
 */
require_once __DIR__ . '/../../../config/config.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$sqlFile = __DIR__ . '/database_setup.sql';
if (!is_readable($sqlFile)) {
    die("Error: Cannot read database_setup.sql\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("Error: Failed to read SQL file\n");
}

// Remove SQL comments and split into statements (handle semicolons inside INSERT values carefully)
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$statements = array_filter(
    array_map('trim',
        preg_split('/;\s*[\r\n]+/', $sql)
    )
);

$db = $GLOBALS['db'] ?? null;
if (!$db) {
    die("Error: No database connection. Check config/database.php.\n");
}

$executed = 0;
$errors = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    try {
        $db->exec($stmt);
        $executed++;
    } catch (PDOException $e) {
        // Ignore "table already exists" and "duplicate key" if we're re-running
        $code = $e->getCode();
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate entry') !== false) {
            $executed++;
            continue;
        }
        $errors[] = $msg . ' (statement #' . ($executed + 1) . ')';
    }
}

echo "SORS migration finished.\n";
echo "Statements executed: $executed\n";
if (!empty($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
} else {
    echo "OK. Tables created/updated and Philippine seed data applied.\n";
}

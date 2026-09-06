<?php
/**
 * Update existing inventory_stock so reorder_level is between 250 and 400.
 * Run once: php admin/modules/pms/update_reorder_levels.php
 */
require_once __DIR__ . '/../../../config/config.php';

$db = $GLOBALS['db'] ?? null;
if (!$db) {
    die("Error: No database connection.\n");
}

try {
    $stmt = $db->query("SELECT id, medicine_id, minimum_stock_level, reorder_level FROM inventory_stock");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $update = $db->prepare("UPDATE inventory_stock SET minimum_stock_level = ?, reorder_level = ? WHERE id = ?");
    $minRange = [50, 80, 100];
    $reorderRange = [250, 260, 275, 290, 300, 310, 320, 330, 340, 350, 360, 370, 380, 390, 400];
    $updated = 0;
    foreach ($rows as $i => $row) {
        $newMin = $minRange[$i % count($minRange)];
        $newReorder = $reorderRange[$i % count($reorderRange)];
        $update->execute([$newMin, $newReorder, $row['id']]);
        $updated++;
    }
    echo "Reorder levels updated. Rows updated: $updated (reorder_level 250-400, minimum_stock_level 50-100).\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

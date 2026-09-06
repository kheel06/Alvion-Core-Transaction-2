<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Inventory & Stock Levels - PMS';

// Handle stock update and initialization
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_stock':
                    $stmt = $db->prepare("UPDATE inventory_stock SET current_stock = ?, last_updated = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['current_stock'], $_POST['stock_id']]);
                    $message = 'Stock updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'initialize_inventory':
                    // Create inventory records for all medicines that don't have inventory yet
                    $stmt = $db->query("
                        SELECT id FROM medicine_master 
                        WHERE id NOT IN (SELECT medicine_id FROM inventory_stock)
                    ");
                    $medicinesWithoutInventory = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $count = 0;
                    foreach ($medicinesWithoutInventory as $medicineId) {
                        // Set default stock levels based on medicine type
                        $stmt = $db->prepare("
                            INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level)
                            VALUES (?, 0, 100, 300)
                        ");
                        $stmt->execute([$medicineId]);
                        $count++;
                    }
                    
                    if ($count > 0) {
                        $message = "Initialized inventory for {$count} medicine(s)";
                        $messageType = 'success';
                    } else {
                        $message = 'All medicines already have inventory records';
                        $messageType = 'info';
                    }
                    break;
                
                case 'create_inventory':
                    // Create inventory record for a specific medicine
                    $stmt = $db->prepare("
                        INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['medicine_id'],
                        $_POST['current_stock'] ?? 0,
                        $_POST['minimum_stock_level'] ?? 100,
                        $_POST['reorder_level'] ?? 300
                    ]);
                    $message = 'Inventory record created successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch inventory data
$inventory = [];
$medicinesWithoutInventory = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'inventory_stock'");
    $checkMedicineTable = $db->query("SHOW TABLES LIKE 'medicine_master'");
    
    if ($checkTable && $checkTable->rowCount() > 0 && $checkMedicineTable && $checkMedicineTable->rowCount() > 0) {
        // Check if batch_expiry table exists for expiration info
        $checkBatchTable = $db->query("SHOW TABLES LIKE 'batch_expiry'");
        $hasBatchTable = $checkBatchTable && $checkBatchTable->rowCount() > 0;
        
        // Get inventory with medicine details - sorted with items WITH stock at TOP, without stock at BOTTOM
        // Also include earliest expiry date for each medicine
        if ($hasBatchTable) {
            $stmt = $db->query("
                SELECT 
                    inv.*,
                    mm.drug_name,
                    mm.generic_name,
                    mm.dosage_form,
                    mm.strength,
                    (SELECT MIN(be.expiry_date) FROM batch_expiry be WHERE be.medicine_id = inv.medicine_id AND be.expiry_date >= CURDATE()) as earliest_expiry
                FROM inventory_stock inv
                LEFT JOIN medicine_master mm ON inv.medicine_id = mm.id
                WHERE mm.id IS NOT NULL
                ORDER BY 
                    CASE WHEN inv.current_stock > 0 THEN 0 ELSE 1 END ASC,
                    CASE WHEN inv.current_stock <= inv.minimum_stock_level THEN 0
                         WHEN inv.current_stock <= inv.reorder_level THEN 1
                         ELSE 2 END ASC,
                    earliest_expiry ASC NULLS LAST,
                    mm.drug_name ASC
            ");
        } else {
            $stmt = $db->query("
                SELECT 
                    inv.*,
                    mm.drug_name,
                    mm.generic_name,
                    mm.dosage_form,
                    mm.strength,
                    NULL as earliest_expiry
                FROM inventory_stock inv
                LEFT JOIN medicine_master mm ON inv.medicine_id = mm.id
                WHERE mm.id IS NOT NULL
                ORDER BY 
                    CASE WHEN inv.current_stock > 0 THEN 0 ELSE 1 END ASC,
                    CASE WHEN inv.current_stock <= inv.minimum_stock_level THEN 0
                         WHEN inv.current_stock <= inv.reorder_level THEN 1
                         ELSE 2 END ASC,
                    mm.drug_name ASC
            ");
        }
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get medicines without inventory records
        $stmt = $db->query("
            SELECT mm.*
            FROM medicine_master mm
            LEFT JOIN inventory_stock inv ON mm.id = inv.medicine_id
            WHERE inv.id IS NULL
            ORDER BY mm.drug_name
            LIMIT 10
        ");
        $medicinesWithoutInventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('PMS Inventory error: ' . $e->getMessage());
    // Show error in development (remove in production)
    if (isset($_GET['debug'])) {
        $message = 'Database Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

include __DIR__ . '/../../../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li><a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="hover:text-primary-600">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><span class="text-gray-700 dark:text-gray-200">Inventory & Stock Levels</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Inventory & Stock Levels
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Real-time stock levels with low stock alerts and quick update functionality.
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php 
            echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 
            ($messageType === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 
            'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'); 
        ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Debug Info (only show if debug parameter is set) -->
    <?php if (isset($_GET['debug'])): ?>
        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs">
            <strong>Debug Info:</strong><br>
            Inventory Records: <?php echo count($inventory); ?><br>
            Medicines Without Inventory: <?php echo count($medicinesWithoutInventory); ?><br>
            <?php if (!empty($inventory)): ?>
                First Record: <?php echo htmlspecialchars(json_encode($inventory[0] ?? [])); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Initialize Inventory Button -->
    <?php if (!empty($medicinesWithoutInventory)): ?>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200">Initialize Inventory</h3>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                        <?php echo count($medicinesWithoutInventory); ?> medicine(s) found without inventory records. Initialize them to start tracking stock levels.
                    </p>
                </div>
                <form method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="action" value="initialize_inventory">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Initialize All
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reorder Alerts -->
    <?php
    $lowStockCount = 0;
    $criticalStockCount = 0;
    foreach ($inventory as $item) {
        $current = (int)($item['current_stock'] ?? 0);
        $reorder = (int)($item['reorder_level'] ?? 0);
        $minimum = (int)($item['minimum_stock_level'] ?? 0);
        
        if ($current <= $minimum) {
            $criticalStockCount++;
        } elseif ($current <= $reorder) {
            $lowStockCount++;
        }
    }
    ?>
    
    <?php if ($lowStockCount > 0 || $criticalStockCount > 0): ?>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-center gap-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Stock Alerts</h3>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        <?php if ($criticalStockCount > 0): ?>
                            <span class="font-semibold text-red-600 dark:text-red-400"><?php echo $criticalStockCount; ?> critical</span>
                        <?php endif; ?>
                        <?php if ($lowStockCount > 0): ?>
                            <?php echo $criticalStockCount > 0 ? ' and ' : ''; ?>
                            <span class="font-semibold text-amber-600 dark:text-amber-400"><?php echo $lowStockCount; ?> low stock</span> items need attention.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Inventory Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Medicine Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Minimum Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reorder Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Earliest Expiry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Last Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">No inventory records found</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        <?php if (empty($medicinesWithoutInventory)): ?>
                                            Run the database setup SQL script to populate sample data, or add medicines first.
                                        <?php else: ?>
                                            Click "Initialize All" above to create inventory records for existing medicines.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): 
                            $current = (int)($item['current_stock'] ?? 0);
                            $reorder = (int)($item['reorder_level'] ?? 0);
                            $minimum = (int)($item['minimum_stock_level'] ?? 0);
                            
                            $rowClass = '';
                            if ($current <= $minimum) {
                                $rowClass = 'bg-red-50 dark:bg-red-900/20';
                            } elseif ($current <= $reorder) {
                                $rowClass = 'bg-amber-50 dark:bg-amber-900/20';
                            }
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 <?php echo $rowClass; ?>">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <div>
                                        <?php echo htmlspecialchars($item['drug_name'] ?? 'N/A'); ?>
                                        <?php if ($item['strength']): ?>
                                            <span class="text-xs text-gray-500 dark:text-gray-400"> - <?php echo htmlspecialchars($item['strength']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['generic_name']): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($item['generic_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($item['dosage_form']): ?>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            <?php echo htmlspecialchars($item['dosage_form']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold <?php echo $current <= $minimum ? 'text-red-600 dark:text-red-400' : ($current <= $reorder ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'); ?>">
                                    <?php echo number_format($current); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo number_format($minimum); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo number_format($reorder); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php 
                                    $expiryDate = $item['earliest_expiry'] ?? null;
                                    if ($expiryDate) {
                                        $expiry = new DateTime($expiryDate);
                                        $today = new DateTime();
                                        $daysUntil = (int)$today->diff($expiry)->format('%r%a');
                                        
                                        if ($daysUntil < 0) {
                                            $expiryClass = 'text-red-600 dark:text-red-400 font-medium';
                                            $expiryText = date('M d, Y', strtotime($expiryDate)) . ' (Expired)';
                                        } elseif ($daysUntil <= 30) {
                                            $expiryClass = 'text-red-600 dark:text-red-400';
                                            $expiryText = date('M d, Y', strtotime($expiryDate)) . ' (' . $daysUntil . 'd)';
                                        } elseif ($daysUntil <= 90) {
                                            $expiryClass = 'text-amber-600 dark:text-amber-400';
                                            $expiryText = date('M d, Y', strtotime($expiryDate)) . ' (' . $daysUntil . 'd)';
                                        } else {
                                            $expiryClass = 'text-gray-600 dark:text-gray-400';
                                            $expiryText = date('M d, Y', strtotime($expiryDate));
                                        }
                                    } else {
                                        $expiryClass = 'text-gray-400 dark:text-gray-500';
                                        $expiryText = 'N/A';
                                    }
                                    ?>
                                    <span class="<?php echo $expiryClass; ?>"><?php echo $expiryText; ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo $item['last_updated'] ? date('M d, Y H:i', strtotime($item['last_updated'])) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="quickUpdate(<?php echo htmlspecialchars($item['id']); ?>, '<?php echo htmlspecialchars($item['drug_name'] ?? '', ENT_QUOTES); ?>', <?php echo $current; ?>)" 
                                            class="px-3 py-1 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Quick Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Update Modal -->
<div id="updateModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeUpdateModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Stock Update</h3>
            <form method="POST" id="updateForm">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="stock_id" id="updateStockId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" id="updateMedicineName">Medicine</label>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Stock Quantity *</label>
                    <input type="number" name="current_stock" id="updateStock" required min="0"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeUpdateModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function quickUpdate(stockId, medicineName, currentStock) {
    document.getElementById('updateModal').classList.remove('hidden');
    document.getElementById('updateStockId').value = stockId;
    document.getElementById('updateMedicineName').textContent = 'Medicine: ' + medicineName;
    document.getElementById('updateStock').value = currentStock;
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Expiry & Batch Tracking - PMS';

// Handle mark for return
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'mark_return') {
            $stmt = $db->prepare("UPDATE batch_expiry SET status = 'marked_for_return' WHERE id = ?");
            $stmt->execute([$_POST['batch_id']]);
            $message = 'Batch marked for return';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch batch expiry data
$batches = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'batch_expiry'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        $stmt = $db->query("
            SELECT 
                be.*,
                mm.drug_name,
                mm.generic_name
            FROM batch_expiry be
            LEFT JOIN medicine_master mm ON be.medicine_id = mm.id
            ORDER BY be.expiry_date ASC
        ");
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('PMS Batch Expiry error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Expiry & Batch Tracking</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Expiry & Batch Tracking
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Track medicine batches by expiry date with color-coded alerts and export functionality.
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="exportExpiryReport()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export Expiry Report
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Batch Expiry Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batch Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Medicine Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expiry Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Days Until Expiry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No batch records found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $batch): 
                            $expiryDate = $batch['expiry_date'] ?? null;
                            $daysUntilExpiry = null;
                            $statusColor = '';
                            
                            if ($expiryDate) {
                                $expiry = new DateTime($expiryDate);
                                $today = new DateTime();
                                $daysUntilExpiry = $today->diff($expiry)->days;
                                
                                if ($expiry < $today) {
                                    $daysUntilExpiry = -$daysUntilExpiry;
                                    $statusColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                                } elseif ($daysUntilExpiry <= 30) {
                                    $statusColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                                } elseif ($daysUntilExpiry <= 90) {
                                    $statusColor = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
                                } else {
                                    $statusColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                                }
                            }
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($batch['batch_number'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($batch['drug_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo number_format($batch['quantity'] ?? 0); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo $expiryDate ? date('M d, Y', strtotime($expiryDate)) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($daysUntilExpiry !== null): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                            <?php 
                                            if ($daysUntilExpiry < 0) {
                                                echo abs($daysUntilExpiry) . ' days expired';
                                            } else {
                                                echo $daysUntilExpiry . ' days';
                                            }
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($batch['status'] ?? '') !== 'marked_for_return'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Mark this batch for return?')">
                                            <input type="hidden" name="action" value="mark_return">
                                            <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch['id']); ?>">
                                            <button type="submit" class="text-amber-600 hover:text-amber-700 text-sm font-medium">Mark for Return</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Marked for Return</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportExpiryReport() {
    let csv = 'Batch Expiry Report\n';
    csv += `Generated: ${new Date().toLocaleString()}\n\n`;
    csv += 'Batch Number,Medicine Name,Quantity,Expiry Date,Days Until Expiry\n';
    
    <?php foreach ($batches as $batch): 
        $expiryDate = $batch['expiry_date'] ?? null;
        $daysUntilExpiry = null;
        if ($expiryDate) {
            $expiry = new DateTime($expiryDate);
            $today = new DateTime();
            $daysUntilExpiry = $today->diff($expiry)->days;
            if ($expiry < $today) {
                $daysUntilExpiry = -$daysUntilExpiry;
            }
        }
    ?>
    csv += `<?php echo htmlspecialchars($batch['batch_number'] ?? 'N/A'); ?>,<?php echo htmlspecialchars($batch['drug_name'] ?? 'N/A'); ?>,<?php echo $batch['quantity'] ?? 0; ?>,<?php echo $expiryDate ? date('Y-m-d', strtotime($expiryDate)) : 'N/A'; ?>,<?php echo $daysUntilExpiry !== null ? $daysUntilExpiry : 'N/A'; ?>\n`;
    <?php endforeach; ?>
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `batch_expiry_report_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


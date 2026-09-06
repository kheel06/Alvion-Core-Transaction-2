<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Reference Ranges Management - LIS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_range':
                    // Check for conflicting ranges
                    $conflictCheck = $db->prepare("
                        SELECT id FROM reference_ranges 
                        WHERE test_id = ? 
                        AND age_group = ? 
                        AND gender = ?
                        AND id != ?
                    ");
                    $existingId = $_POST['range_id'] ?? 0;
                    $conflictCheck->execute([
                        $_POST['test_id'],
                        $_POST['age_group'],
                        $_POST['gender'],
                        $existingId
                    ]);
                    
                    if ($conflictCheck->rowCount() > 0) {
                        $message = 'Error: A reference range already exists for this test, age group, and gender combination.';
                        $messageType = 'error';
                    } else {
                        if ($existingId) {
                            $stmt = $db->prepare("
                                UPDATE reference_ranges 
                                SET test_id = ?, age_group = ?, gender = ?, unit_of_measure = ?, 
                                    normal_low = ?, normal_high = ?, critical_low = ?, critical_high = ?,
                                    updated_at = NOW()
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $_POST['test_id'],
                                $_POST['age_group'],
                                $_POST['gender'],
                                $_POST['unit_of_measure'],
                                $_POST['normal_low'] ?? null,
                                $_POST['normal_high'] ?? null,
                                $_POST['critical_low'] ?? null,
                                $_POST['critical_high'] ?? null,
                                $existingId
                            ]);
                            $message = 'Reference range updated successfully';
                        } else {
                            $stmt = $db->prepare("
                                INSERT INTO reference_ranges 
                                (test_id, age_group, gender, unit_of_measure, normal_low, normal_high, critical_low, critical_high)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $_POST['test_id'],
                                $_POST['age_group'],
                                $_POST['gender'],
                                $_POST['unit_of_measure'],
                                $_POST['normal_low'] ?? null,
                                $_POST['normal_high'] ?? null,
                                $_POST['critical_low'] ?? null,
                                $_POST['critical_high'] ?? null
                            ]);
                            $message = 'Reference range created successfully';
                        }
                        $messageType = 'success';
                    }
                    break;
                
                case 'delete_range':
                    $stmt = $db->prepare("DELETE FROM reference_ranges WHERE id = ?");
                    $stmt->execute([$_POST['range_id']]);
                    $message = 'Reference range deleted successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data
$ranges = [];
$tests = [];

try {
    // Get tests
    $checkTests = $db->query("SHOW TABLES LIKE 'test_catalog'");
    if ($checkTests && $checkTests->rowCount() > 0) {
        $stmt = $db->query("SELECT id, test_code, test_name FROM test_catalog WHERE status = 'active' ORDER BY test_name");
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get reference ranges
    $checkRanges = $db->query("SHOW TABLES LIKE 'reference_ranges'");
    if ($checkRanges && $checkRanges->rowCount() > 0) {
        $stmt = $db->query("
            SELECT rr.*, tc.test_name, tc.test_code
            FROM reference_ranges rr
            LEFT JOIN test_catalog tc ON rr.test_id = tc.id
            ORDER BY tc.test_name, rr.age_group, rr.gender
        ");
        $ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('LIS Reference Ranges error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Reference Ranges Management</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Reference Ranges Management
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage reference ranges with demographic selectors (age group, gender) and validation for conflicting ranges.
            </p>
        </div>
        <button onclick="openRangeModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-2" title="Add New Range"><?php echo icon_add('w-5 h-5'); ?>Add New Range</button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Reference Ranges Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Test Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Age Group</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Normal Low</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Normal High</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Critical Low</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Critical High</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($ranges)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No reference ranges found. <button onclick="openRangeModal()" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700" title="Add your first range"><?php echo icon_add('w-4 h-4'); ?>Add your first range</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ranges as $range): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($range['test_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars(ucfirst($range['age_group'] ?? 'N/A')); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars(ucfirst($range['gender'] ?? 'All')); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($range['unit_of_measure'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($range['normal_low'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($range['normal_high'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400">
                                    <?php echo htmlspecialchars($range['critical_low'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400">
                                    <?php echo htmlspecialchars($range['critical_high'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="editRange(<?php echo htmlspecialchars(json_encode($range)); ?>)" 
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this reference range?')">
                                            <input type="hidden" name="action" value="delete_range">
                                            <input type="hidden" name="range_id" value="<?php echo $range['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 text-sm font-medium" title="Delete"><?php echo icon_delete('w-4 h-4'); ?>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reference Range Modal -->
<div id="rangeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeRangeModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="rangeModalTitle">Add New Reference Range</h3>
            <form method="POST" id="rangeForm">
                <input type="hidden" name="action" value="save_range">
                <input type="hidden" name="range_id" id="rangeId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test *</label>
                        <select name="test_id" id="rangeTestId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Test</option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo $test['id']; ?>">
                                    <?php echo htmlspecialchars($test['test_name'] . ' (' . $test['test_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Age Group *</label>
                            <select name="age_group" id="rangeAgeGroup" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Select Age Group</option>
                                <option value="pediatric">Pediatric</option>
                                <option value="adult">Adult</option>
                                <option value="geriatric">Geriatric</option>
                                <option value="all">All Ages</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gender *</label>
                            <select name="gender" id="rangeGender" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unit of Measure</label>
                        <input type="text" name="unit_of_measure" id="rangeUnit" 
                               placeholder="e.g., mg/dL, mmol/L, %"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Normal Low</label>
                            <input type="number" name="normal_low" id="rangeNormalLow" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Normal High</label>
                            <input type="number" name="normal_high" id="rangeNormalHigh" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Critical Low</label>
                            <input type="number" name="critical_low" id="rangeCriticalLow" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Critical High</label>
                            <input type="number" name="critical_high" id="rangeCriticalHigh" step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeRangeModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Save Range
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRangeModal() {
    document.getElementById('rangeModal').classList.remove('hidden');
    document.getElementById('rangeModalTitle').textContent = 'Add New Reference Range';
    document.getElementById('rangeId').value = '';
    document.getElementById('rangeForm').reset();
}

function closeRangeModal() {
    document.getElementById('rangeModal').classList.add('hidden');
}

function editRange(range) {
    document.getElementById('rangeModal').classList.remove('hidden');
    document.getElementById('rangeModalTitle').textContent = 'Edit Reference Range';
    document.getElementById('rangeId').value = range.id;
    document.getElementById('rangeTestId').value = range.test_id || '';
    document.getElementById('rangeAgeGroup').value = range.age_group || '';
    document.getElementById('rangeGender').value = range.gender || '';
    document.getElementById('rangeUnit').value = range.unit_of_measure || '';
    document.getElementById('rangeNormalLow').value = range.normal_low || '';
    document.getElementById('rangeNormalHigh').value = range.normal_high || '';
    document.getElementById('rangeCriticalLow').value = range.critical_low || '';
    document.getElementById('rangeCriticalHigh').value = range.critical_high || '';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

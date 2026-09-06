<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Results Validation Settings - LIS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_rule':
                    // Check if rule exists
                    $check = $db->prepare("SELECT id FROM validation_rules WHERE test_id = ?");
                    $check->execute([$_POST['test_id']]);
                    $existing = $check->fetch();
                    
                    if ($existing) {
                        $stmt = $db->prepare("
                            UPDATE validation_rules 
                            SET auto_validate = ?, critical_low = ?, critical_high = ?, validator_user_id = ?, 
                                updated_at = NOW(), updated_by = ?
                            WHERE test_id = ?
                        ");
                        $stmt->execute([
                            isset($_POST['auto_validate']) ? 1 : 0,
                            $_POST['critical_low'] ?? null,
                            $_POST['critical_high'] ?? null,
                            $_POST['validator_user_id'] ?? null,
                            $_SESSION['user_id'],
                            $_POST['test_id']
                        ]);
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO validation_rules 
                            (test_id, auto_validate, critical_low, critical_high, validator_user_id, created_by, updated_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['test_id'],
                            isset($_POST['auto_validate']) ? 1 : 0,
                            $_POST['critical_low'] ?? null,
                            $_POST['critical_high'] ?? null,
                            $_POST['validator_user_id'] ?? null,
                            $_SESSION['user_id'],
                            $_SESSION['user_id']
                        ]);
                    }
                    $message = 'Validation rule saved successfully';
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
$tests = [];
$validationRules = [];
$history = [];
$validators = [];

try {
    // Get tests
    $checkTests = $db->query("SHOW TABLES LIKE 'test_catalog'");
    if ($checkTests && $checkTests->rowCount() > 0) {
        $stmt = $db->query("SELECT id, test_code, test_name FROM test_catalog WHERE status = 'active' ORDER BY test_name");
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get validation rules
    $checkRules = $db->query("SHOW TABLES LIKE 'validation_rules'");
    if ($checkRules && $checkRules->rowCount() > 0) {
        $stmt = $db->query("
            SELECT vr.*, tc.test_name, tc.test_code,
                   u1.username as created_by_name, u2.username as updated_by_name
            FROM validation_rules vr
            LEFT JOIN test_catalog tc ON vr.test_id = tc.id
            LEFT JOIN users u1 ON vr.created_by = u1.id
            LEFT JOIN users u2 ON vr.updated_by = u2.id
            ORDER BY vr.updated_at DESC
        ");
        $validationRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get history
        $stmt = $db->query("
            SELECT vr.*, tc.test_name, u.username as updated_by_name
            FROM validation_rules vr
            LEFT JOIN test_catalog tc ON vr.test_id = tc.id
            LEFT JOIN users u ON vr.updated_by = u.id
            WHERE vr.updated_at IS NOT NULL
            ORDER BY vr.updated_at DESC
            LIMIT 20
        ");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get validators (users with admin or pathologist role)
    $checkUsers = $db->query("SHOW TABLES LIKE 'users'");
    if ($checkUsers && $checkUsers->rowCount() > 0) {
        $stmt = $db->query("
            SELECT u.id, u.username, u.first_name, u.last_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.role_name IN ('admin', 'super admin', 'pathologist')
            ORDER BY u.first_name, u.last_name
        ");
        $validators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('LIS Validation Settings error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Results Validation Settings</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Results Validation Settings
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Configure validation rules, critical value thresholds, and assign authorized validators per test.
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Validation Rules Configuration -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Validation Rules Configuration</h2>
                
                <?php if (empty($tests)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        No active tests found. Please create tests in <a href="test_catalog_management.php" class="text-primary-600 hover:text-primary-700">Test Catalog Management</a> first.
                    </p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($tests as $test): 
                            $rule = null;
                            foreach ($validationRules as $r) {
                                if ($r['test_id'] == $test['id']) {
                                    $rule = $r;
                                    break;
                                }
                            }
                        ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($test['test_name']); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Code: <?php echo htmlspecialchars($test['test_code']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="save_rule">
                                    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                    
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Auto-Validate Normal Ranges
                                        </label>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="auto_validate" value="1" 
                                                   <?php echo ($rule && $rule['auto_validate']) ? 'checked' : ''; ?>
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                        </label>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Critical Low Threshold
                                            </label>
                                            <input type="number" name="critical_low" step="0.01"
                                                   value="<?php echo htmlspecialchars($rule['critical_low'] ?? ''); ?>"
                                                   placeholder="e.g., 2.5"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Critical High Threshold
                                            </label>
                                            <input type="number" name="critical_high" step="0.01"
                                                   value="<?php echo htmlspecialchars($rule['critical_high'] ?? ''); ?>"
                                                   placeholder="e.g., 10.0"
                                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Assign Authorized Validator
                                        </label>
                                        <select name="validator_user_id" 
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                            <option value="">No specific validator</option>
                                            <?php foreach ($validators as $validator): ?>
                                                <option value="<?php echo $validator['id']; ?>" 
                                                        <?php echo ($rule && $rule['validator_user_id'] == $validator['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(($validator['first_name'] ?? '') . ' ' . ($validator['last_name'] ?? '') . ' (' . $validator['username'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" 
                                            class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium">
                                        Save Rule
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Validation Rules History -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Validation Rules History</h2>
            
            <div class="space-y-3 max-h-[600px] overflow-y-auto">
                <?php if (empty($history)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No history available</p>
                <?php else: ?>
                    <?php foreach ($history as $item): ?>
                        <div class="border-l-4 border-primary-500 pl-3 py-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($item['test_name'] ?? 'Unknown Test'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Modified by: <?php echo htmlspecialchars($item['updated_by_name'] ?? 'System'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y H:i', strtotime($item['updated_at'] ?? 'now')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

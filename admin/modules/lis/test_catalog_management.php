<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Test Catalog Management - LIS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_test':
                    $stmt = $db->prepare("INSERT INTO test_catalog (test_code, test_name, department, specimen_type, price, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['test_code'],
                        $_POST['test_name'],
                        $_POST['department'],
                        $_POST['specimen_type'],
                        $_POST['price'] ?? 0,
                        $_POST['status'] ?? 'active'
                    ]);
                    $message = 'Test created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_test':
                    $stmt = $db->prepare("UPDATE test_catalog SET test_code = ?, test_name = ?, department = ?, specimen_type = ?, price = ?, status = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['test_code'],
                        $_POST['test_name'],
                        $_POST['department'],
                        $_POST['specimen_type'],
                        $_POST['price'] ?? 0,
                        $_POST['status'] ?? 'active',
                        $_POST['test_id']
                    ]);
                    $message = 'Test updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'deactivate_test':
                    $stmt = $db->prepare("UPDATE test_catalog SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$_POST['test_id']]);
                    $message = 'Test deactivated successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data with filters
$search = $_GET['search'] ?? '';
$departmentFilter = $_GET['department'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$tests = [];
$departments = [];

try {
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'test_catalog'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get unique departments
        $stmt = $db->query("SELECT DISTINCT department FROM test_catalog WHERE department IS NOT NULL AND department != '' ORDER BY department");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build query with filters
        $sql = "SELECT * FROM test_catalog WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (test_code LIKE ? OR test_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($departmentFilter) {
            $sql .= " AND department = ?";
            $params[] = $departmentFilter;
        }
        
        if ($statusFilter) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }
        
        $sql .= " ORDER BY test_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('LIS Test Catalog error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Test Catalog Management</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Test Catalog Management
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage laboratory test catalog with searchable table, filters, and test configuration.
            </p>
        </div>
        <button onclick="openTestModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-2" title="Add New Test"><?php echo icon_add('w-5 h-5'); ?>Add New Test</button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by code or name"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <select name="department" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Test Catalog Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Test Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Test Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Specimen Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($tests)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No tests found. <button onclick="openTestModal()" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700" title="Add your first test"><?php echo icon_add('w-4 h-4'); ?>Add your first test</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tests as $test): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($test['test_code'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($test['test_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($test['department'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($test['specimen_type'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    ₱<?php echo number_format($test['price'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status = $test['status'] ?? 'active';
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
                                    ];
                                    $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $color; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="editTestFromRow(this)" 
                                                data-test-id="<?php echo htmlspecialchars($test['id']); ?>"
                                                data-test-code="<?php echo htmlspecialchars($test['test_code'] ?? ''); ?>"
                                                data-test-name="<?php echo htmlspecialchars($test['test_name'] ?? ''); ?>"
                                                data-test-department="<?php echo htmlspecialchars($test['department'] ?? ''); ?>"
                                                data-test-specimen="<?php echo htmlspecialchars($test['specimen_type'] ?? ''); ?>"
                                                data-test-price="<?php echo htmlspecialchars($test['price'] ?? ''); ?>"
                                                data-test-status="<?php echo htmlspecialchars($test['status'] ?? 'active'); ?>"
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                                        <?php if (($test['status'] ?? 'active') === 'active'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Deactivate this test?')">
                                                <input type="hidden" name="action" value="deactivate_test">
                                                <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test['id']); ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-700 text-sm font-medium" title="Deactivate"><?php echo icon_deactivate('w-4 h-4'); ?>Deactivate</button>
                                            </form>
                                        <?php endif; ?>
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

<!-- Test Modal -->
<div id="testModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeTestModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="testModalTitle">Add New Test</h3>
            <form method="POST" id="testForm">
                <input type="hidden" name="action" id="testAction" value="create_test">
                <input type="hidden" name="test_id" id="testId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test Code *</label>
                        <input type="text" name="test_code" id="testCode" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="status" id="testStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test Name *</label>
                        <input type="text" name="test_name" id="testName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department *</label>
                        <input type="text" name="department" id="testDepartment" required list="departments"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <datalist id="departments">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Specimen Type</label>
                        <input type="text" name="specimen_type" id="testSpecimen" 
                               placeholder="e.g., Blood, Urine, Serum"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price</label>
                        <input type="number" name="price" id="testPrice" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeTestModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTestModal() {
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModalTitle').textContent = 'Add New Test';
    document.getElementById('testAction').value = 'create_test';
    document.getElementById('testId').value = '';
    document.getElementById('testForm').reset();
}

function closeTestModal() {
    document.getElementById('testModal').classList.add('hidden');
}

function editTestFromRow(button) {
    const test = {
        id: button.getAttribute('data-test-id'),
        test_code: button.getAttribute('data-test-code'),
        test_name: button.getAttribute('data-test-name'),
        department: button.getAttribute('data-test-department'),
        specimen_type: button.getAttribute('data-test-specimen'),
        price: button.getAttribute('data-test-price'),
        status: button.getAttribute('data-test-status')
    };
    
    document.getElementById('testModal').classList.remove('hidden');
    document.getElementById('testModalTitle').textContent = 'Edit Test';
    document.getElementById('testAction').value = 'update_test';
    document.getElementById('testId').value = test.id || '';
    document.getElementById('testCode').value = test.test_code || '';
    document.getElementById('testName').value = test.test_name || '';
    document.getElementById('testDepartment').value = test.department || '';
    document.getElementById('testSpecimen').value = test.specimen_type || '';
    document.getElementById('testPrice').value = test.price || '';
    document.getElementById('testStatus').value = test.status || 'active';
}

// Real-time client-side filtering
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const departmentSelect = document.querySelector('select[name="department"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const tableBody = document.querySelector('table tbody');
    const rows = tableBody ? Array.from(tableBody.querySelectorAll('tr[class*="hover"]')) : [];
    
    function filterTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const deptFilter = departmentSelect ? departmentSelect.value.toLowerCase() : '';
        const statusFilter = statusSelect ? statusSelect.value.toLowerCase() : '';
        
        let visibleCount = 0;
        
        rows.forEach(function(row) {
            const testCode = (row.querySelector('td:nth-child(1)')?.textContent || '').toLowerCase();
            const testName = (row.querySelector('td:nth-child(2)')?.textContent || '').toLowerCase();
            const department = (row.querySelector('td:nth-child(3)')?.textContent || '').toLowerCase().trim();
            const status = (row.querySelector('td:nth-child(6) span')?.textContent || '').toLowerCase().trim();
            
            const matchesSearch = !searchTerm || testCode.includes(searchTerm) || testName.includes(searchTerm);
            const matchesDept = !deptFilter || department.includes(deptFilter.toLowerCase());
            const matchesStatus = !statusFilter || status === statusFilter;
            
            if (matchesSearch && matchesDept && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide "no results" message
        const noResultsRow = tableBody.querySelector('tr[data-no-filter-results]');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noResultsRow) {
                const tr = document.createElement('tr');
                tr.setAttribute('data-no-filter-results', 'true');
                tr.innerHTML = '<td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No tests match your filters</td>';
                tableBody.appendChild(tr);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }
    
    // Attach real-time filter events
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
        searchInput.addEventListener('keyup', filterTable);
    }
    if (departmentSelect) {
        departmentSelect.addEventListener('change', filterTable);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', filterTable);
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

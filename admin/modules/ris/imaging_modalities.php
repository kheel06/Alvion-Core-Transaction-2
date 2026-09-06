<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Imaging Modalities - RIS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_modality':
                    $stmt = $db->prepare("INSERT INTO imaging_modalities (modality_name, model, location, status, last_calibration_date, scheduled_maintenance) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['modality_name'],
                        $_POST['model'],
                        $_POST['location'],
                        $_POST['status'],
                        $_POST['last_calibration_date'] ?: null,
                        $_POST['scheduled_maintenance'] ?: null
                    ]);
                    $message = 'Modality created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_modality':
                    $stmt = $db->prepare("UPDATE imaging_modalities SET modality_name = ?, model = ?, location = ?, status = ?, last_calibration_date = ?, scheduled_maintenance = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['modality_name'],
                        $_POST['model'],
                        $_POST['location'],
                        $_POST['status'],
                        $_POST['last_calibration_date'] ?: null,
                        $_POST['scheduled_maintenance'] ?: null,
                        $_POST['modality_id']
                    ]);
                    $message = 'Modality updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'delete_modality':
                    $stmt = $db->prepare("DELETE FROM imaging_modalities WHERE id = ?");
                    $stmt->execute([$_POST['modality_id']]);
                    $message = 'Modality deleted successfully';
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
$statusFilter = $_GET['status'] ?? '';
$locationFilter = $_GET['location'] ?? '';

$modalities = [];
$locations = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'imaging_modalities'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get unique locations
        $stmt = $db->query("SELECT DISTINCT location FROM imaging_modalities WHERE location IS NOT NULL AND location != '' ORDER BY location");
        $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build query with filters
        $sql = "SELECT * FROM imaging_modalities WHERE 1=1";
        $params = [];
        
        if ($statusFilter) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
        }
        
        if ($locationFilter) {
            $sql .= " AND location = ?";
            $params[] = $locationFilter;
        }
        
        $sql .= " ORDER BY modality_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $modalities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('RIS Imaging Modalities error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Imaging Modalities</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Imaging Modalities
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage imaging equipment with status tracking, calibration dates, and maintenance schedules.
            </p>
        </div>
        <button onclick="openModalityModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-2" title="Add Modality"><?php echo icon_add('w-5 h-5'); ?>Add Modality</button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <div class="flex gap-2">
                    <a href="?" class="px-4 py-2 rounded-lg <?php echo !$statusFilter ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        All
                    </a>
                    <a href="?status=operational" class="px-4 py-2 rounded-lg <?php echo $statusFilter === 'operational' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        Operational
                    </a>
                    <a href="?status=maintenance" class="px-4 py-2 rounded-lg <?php echo $statusFilter === 'maintenance' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'; ?>">
                        Maintenance
                    </a>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location</label>
                <select name="location" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $locationFilter === $loc ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Modalities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($modalities)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No modalities found. <button onclick="openModalityModal()" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700" title="Add your first modality"><?php echo icon_add('w-4 h-4'); ?>Add your first modality</button>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($modalities as $modality): ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($modality['modality_name'] ?? 'N/A'); ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <?php echo htmlspecialchars($modality['model'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <?php
                        $status = strtolower($modality['status'] ?? 'operational');
                        $statusColors = [
                            'operational' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'maintenance' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                        ];
                        $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $color; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span><?php echo htmlspecialchars($modality['location'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <?php if ($modality['last_calibration_date']): ?>
                            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>Last Calibration: <?php echo date('M d, Y', strtotime($modality['last_calibration_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($modality['scheduled_maintenance']): ?>
                            <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                <span>Maintenance: <?php echo date('M d, Y', strtotime($modality['scheduled_maintenance'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button onclick="editModality(<?php echo htmlspecialchars($modality['id']); ?>, '<?php echo htmlspecialchars($modality['modality_name'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($modality['model'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($modality['location'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($modality['status'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($modality['last_calibration_date'] ?? ''); ?>', '<?php echo htmlspecialchars($modality['scheduled_maintenance'] ?? ''); ?>')" 
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm text-primary-600 hover:text-primary-700 font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this modality?')">
                            <input type="hidden" name="action" value="delete_modality">
                            <input type="hidden" name="modality_id" value="<?php echo htmlspecialchars($modality['id']); ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-red-600 hover:text-red-700 font-medium" title="Delete"><?php echo icon_delete('w-4 h-4'); ?>Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modality Modal -->
<div id="modalityModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModalityModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="modalityModalTitle">Add Modality</h3>
            <form method="POST" id="modalityForm">
                <input type="hidden" name="action" id="modalityAction" value="create_modality">
                <input type="hidden" name="modality_id" id="modalityId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modality Name *</label>
                        <input type="text" name="modality_name" id="modalityName" required
                               placeholder="e.g., CT-Scanner, MRI, X-Ray"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Model *</label>
                        <input type="text" name="model" id="modalityModel" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location *</label>
                        <input type="text" name="location" id="modalityLocation" required
                               placeholder="e.g., Room-3A, Room-2B"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status *</label>
                        <select name="status" id="modalityStatus" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="operational">Operational</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Calibration Date</label>
                        <input type="date" name="last_calibration_date" id="modalityCalibration"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scheduled Maintenance</label>
                        <input type="date" name="scheduled_maintenance" id="modalityMaintenance"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeModalityModal()" 
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
function openModalityModal() {
    document.getElementById('modalityModal').classList.remove('hidden');
    document.getElementById('modalityModalTitle').textContent = 'Add Modality';
    document.getElementById('modalityAction').value = 'create_modality';
    document.getElementById('modalityId').value = '';
    document.getElementById('modalityForm').reset();
}

function closeModalityModal() {
    document.getElementById('modalityModal').classList.add('hidden');
}

function editModality(id, name, model, location, status, calibration, maintenance) {
    document.getElementById('modalityModal').classList.remove('hidden');
    document.getElementById('modalityModalTitle').textContent = 'Edit Modality';
    document.getElementById('modalityAction').value = 'update_modality';
    document.getElementById('modalityId').value = id;
    document.getElementById('modalityName').value = name || '';
    document.getElementById('modalityModel').value = model || '';
    document.getElementById('modalityLocation').value = location || '';
    document.getElementById('modalityStatus').value = status || 'operational';
    document.getElementById('modalityCalibration').value = calibration || '';
    document.getElementById('modalityMaintenance').value = maintenance || '';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


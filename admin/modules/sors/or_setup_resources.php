<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'OR Rooms & Equipment - OR & Surgery Management';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_equipment':
                    $stmt = $db->prepare("INSERT INTO or_equipment (or_id, equipment_name, equipment_type, serial_number, status, last_maintenance_date, next_maintenance_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['or_id'],
                        $_POST['equipment_name'],
                        $_POST['equipment_type'],
                        $_POST['serial_number'] ?? null,
                        $_POST['status'] ?? 'Available',
                        $_POST['last_maintenance_date'] ?: null,
                        $_POST['next_maintenance_date'] ?: null,
                        $_POST['notes'] ?? null
                    ]);
                    $message = 'Equipment added successfully';
                    $messageType = 'success';
                    break;
                
                case 'request_maintenance':
                    $stmt = $db->prepare("UPDATE or_equipment SET status = 'Maintenance', notes = CONCAT(IFNULL(notes, ''), '\nMaintenance requested: ', ?) WHERE id = ?");
                    $stmt->execute([$_POST['maintenance_notes'], $_POST['equipment_id']]);
                    $message = 'Maintenance request submitted';
                    $messageType = 'success';
                    break;
                
                case 'update_or_status':
                    $stmt = $db->prepare("UPDATE operating_rooms SET status = ? WHERE id = ?");
                    $stmt->execute([$_POST['status'], $_POST['or_id']]);
                    $message = 'OR status updated successfully';
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
$operatingRooms = [];
$filterSpecialty = $_GET['specialty'] ?? '';
$filterStatus = $_GET['status'] ?? '';

try {
    $query = "SELECT 
                or_rooms.*,
                COUNT(DISTINCT eq.id) as equipment_count,
                (SELECT scheduled_start_time FROM surgery_schedule WHERE or_id = or_rooms.id AND scheduled_date = CURDATE() AND status IN ('Scheduled', 'In Progress') ORDER BY scheduled_start_time ASC LIMIT 1) as next_available_slot
            FROM operating_rooms or_rooms
            LEFT JOIN or_equipment eq ON or_rooms.id = eq.or_id
            WHERE 1=1";
    
    $params = [];
    if ($filterSpecialty) {
        $query .= " AND or_rooms.room_type = ?";
        $params[] = $filterSpecialty;
    }
    if ($filterStatus) {
        $query .= " AND or_rooms.status = ?";
        $params[] = $filterStatus;
    }
    
    $query .= " GROUP BY or_rooms.id ORDER BY or_rooms.room_number";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $operatingRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get equipment for each OR
    foreach ($operatingRooms as &$or) {
        $stmt = $db->prepare("SELECT * FROM or_equipment WHERE or_id = ? ORDER BY equipment_name");
        $stmt->execute([$or['id']]);
        $or['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('SORS OR Setup error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">OR Rooms & Equipment</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                OR Rooms & Equipment
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage operating room inventory, equipment per OR, maintenance schedule, and room status (Ready / In Use / Cleaning / Maintenance) for day-to-day OR operations.
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

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Specialty</label>
                <select name="specialty" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Specialties</option>
                    <option value="General" <?php echo $filterSpecialty === 'General' ? 'selected' : ''; ?>>General</option>
                    <option value="Cardiac" <?php echo $filterSpecialty === 'Cardiac' ? 'selected' : ''; ?>>Cardiac</option>
                    <option value="Neuro" <?php echo $filterSpecialty === 'Neuro' ? 'selected' : ''; ?>>Neuro</option>
                    <option value="Orthopedic" <?php echo $filterSpecialty === 'Orthopedic' ? 'selected' : ''; ?>>Orthopedic</option>
                    <option value="Pediatric" <?php echo $filterSpecialty === 'Pediatric' ? 'selected' : ''; ?>>Pediatric</option>
                    <option value="Emergency" <?php echo $filterSpecialty === 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="Ready" <?php echo $filterStatus === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="In Use" <?php echo $filterStatus === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                    <option value="Cleaning" <?php echo $filterStatus === 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                    <option value="Maintenance" <?php echo $filterStatus === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Apply Filters
                </button>
                <a href="?" class="ml-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- OR Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($operatingRooms)): ?>
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No operating rooms found. Run the database setup SQL script to populate sample data.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($operatingRooms as $or): 
                $statusColors = [
                    'Ready' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 border-green-300 dark:border-green-700',
                    'In Use' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 border-blue-300 dark:border-blue-700',
                    'Cleaning' => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border-yellow-300 dark:border-yellow-700',
                    'Maintenance' => 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 border-red-300 dark:border-red-700'
                ];
                $statusColor = $statusColors[$or['status']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-gray-300 dark:border-gray-600';
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($or['room_number']); ?></h3>
                            <span class="px-2 py-1 text-xs font-medium rounded border <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($or['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Type:</span> <?php echo htmlspecialchars($or['room_type']); ?>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Location:</span> <?php echo htmlspecialchars($or['location'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    
                    <div class="p-5">
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Equipment (<?php echo $or['equipment_count']; ?>)</h4>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                <?php if (empty($or['equipment'])): ?>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No equipment listed</p>
                                <?php else: ?>
                                    <?php foreach (array_slice($or['equipment'], 0, 3) as $eq): ?>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($eq['equipment_name']); ?></span>
                                            <span class="px-1.5 py-0.5 rounded text-xs <?php 
                                                echo $eq['status'] === 'Available' ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 
                                                ($eq['status'] === 'In Use' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 
                                                'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300');
                                            ?>">
                                                <?php echo htmlspecialchars($eq['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($or['equipment']) > 3): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">+<?php echo count($or['equipment']) - 3; ?> more</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($or['next_available_slot']): ?>
                            <div class="mb-4 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Next Available:</span> <?php echo date('H:i', strtotime($or['next_available_slot'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2">
                            <button onclick="openAddEquipmentModal(<?php echo $or['id']; ?>, '<?php echo htmlspecialchars($or['room_number'], ENT_QUOTES); ?>')" 
                                    class="flex-1 px-3 py-2 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center justify-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Add Equipment
                            </button>
                            <button onclick="openMaintenanceModal(<?php echo $or['id']; ?>, '<?php echo htmlspecialchars($or['room_number'], ENT_QUOTES); ?>')" 
                                    class="flex-1 px-3 py-2 text-xs bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium flex items-center justify-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                                Request Maintenance
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Equipment Modal -->
<div id="addEquipmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeAddEquipmentModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Equipment</h3>
            <form method="POST" id="addEquipmentForm">
                <input type="hidden" name="action" value="add_equipment">
                <input type="hidden" name="or_id" id="addEquipmentOrId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">OR Room</label>
                    <input type="text" id="addEquipmentOrName" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Equipment Name *</label>
                    <input type="text" name="equipment_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Equipment Type</label>
                    <input type="text" name="equipment_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Serial Number</label>
                    <input type="text" name="serial_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="Available">Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Out of Service">Out of Service</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeAddEquipmentModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Maintenance Modal -->
<div id="maintenanceModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMaintenanceModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Request Maintenance</h3>
            <form method="POST" id="maintenanceForm">
                <input type="hidden" name="action" value="request_maintenance">
                <input type="hidden" name="or_id" id="maintenanceOrId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">OR Room</label>
                    <input type="text" id="maintenanceOrName" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Equipment</label>
                    <select name="equipment_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" id="maintenanceEquipmentSelect">
                        <option value="">Select Equipment</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maintenance Notes *</label>
                    <textarea name="maintenance_notes" rows="4" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeMaintenanceModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let orData = <?php echo json_encode($operatingRooms); ?>;

function openAddEquipmentModal(orId, orName) {
    document.getElementById('addEquipmentModal').classList.remove('hidden');
    document.getElementById('addEquipmentOrId').value = orId;
    document.getElementById('addEquipmentOrName').value = orName;
}

function closeAddEquipmentModal() {
    document.getElementById('addEquipmentModal').classList.add('hidden');
    document.getElementById('addEquipmentForm').reset();
}

function openMaintenanceModal(orId, orName) {
    document.getElementById('maintenanceModal').classList.remove('hidden');
    document.getElementById('maintenanceOrId').value = orId;
    document.getElementById('maintenanceOrName').value = orName;
    
    // Populate equipment dropdown
    const select = document.getElementById('maintenanceEquipmentSelect');
    select.innerHTML = '<option value="">Select Equipment</option>';
    
    const or = orData.find(r => r.id == orId);
    if (or && or.equipment) {
        or.equipment.forEach(eq => {
            const option = document.createElement('option');
            option.value = eq.id;
            option.textContent = eq.equipment_name;
            select.appendChild(option);
        });
    }
}

function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('hidden');
    document.getElementById('maintenanceForm').reset();
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Medicine Master List - PMS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_medicine':
                    $stmt = $db->prepare("INSERT INTO medicine_master (drug_name, generic_name, manufacturer, dosage_form, strength, therapeutic_class, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['drug_name'],
                        $_POST['generic_name'],
                        $_POST['manufacturer'],
                        $_POST['dosage_form'],
                        $_POST['strength'],
                        $_POST['therapeutic_class'],
                        $_POST['price']
                    ]);
                    $message = 'Medicine added successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_medicine':
                    $stmt = $db->prepare("UPDATE medicine_master SET drug_name = ?, generic_name = ?, manufacturer = ?, dosage_form = ?, strength = ?, therapeutic_class = ?, price = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['drug_name'],
                        $_POST['generic_name'],
                        $_POST['manufacturer'],
                        $_POST['dosage_form'],
                        $_POST['strength'],
                        $_POST['therapeutic_class'],
                        $_POST['price'],
                        $_POST['medicine_id']
                    ]);
                    $message = 'Medicine updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'delete_medicine':
                    $stmt = $db->prepare("DELETE FROM medicine_master WHERE id = ?");
                    $stmt->execute([$_POST['medicine_id']]);
                    $message = 'Medicine deleted successfully';
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
$classFilter = $_GET['therapeutic_class'] ?? '';
$formFilter = $_GET['dosage_form'] ?? '';

$medicines = [];
$therapeuticClasses = [];
$dosageForms = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'medicine_master'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get unique therapeutic classes
        $stmt = $db->query("SELECT DISTINCT therapeutic_class FROM medicine_master WHERE therapeutic_class IS NOT NULL AND therapeutic_class != '' ORDER BY therapeutic_class");
        $therapeuticClasses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get unique dosage forms
        $stmt = $db->query("SELECT DISTINCT dosage_form FROM medicine_master WHERE dosage_form IS NOT NULL AND dosage_form != '' ORDER BY dosage_form");
        $dosageForms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build query with filters
        $sql = "SELECT * FROM medicine_master WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (drug_name LIKE ? OR generic_name LIKE ? OR manufacturer LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($classFilter) {
            $sql .= " AND therapeutic_class = ?";
            $params[] = $classFilter;
        }
        
        if ($formFilter) {
            $sql .= " AND dosage_form = ?";
            $params[] = $formFilter;
        }
        
        $sql .= " ORDER BY drug_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('PMS Medicine Master error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Medicine Master List</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Medicine Master List
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage medicine master catalog with searchable table, filters, and drug information.
            </p>
        </div>
        <button onclick="openMedicineModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium inline-flex items-center gap-2" title="Add New Medicine"><?php echo icon_add('w-5 h-5'); ?>Add New Medicine</button>
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
                       placeholder="Search by name, generic, or manufacturer"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Therapeutic Class</label>
                <select name="therapeutic_class" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Classes</option>
                    <?php foreach ($therapeuticClasses as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $classFilter === $class ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dosage Form</label>
                <select name="dosage_form" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Forms</option>
                    <?php foreach ($dosageForms as $form): ?>
                        <option value="<?php echo htmlspecialchars($form); ?>" <?php echo $formFilter === $form ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($form); ?>
                        </option>
                    <?php endforeach; ?>
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

    <!-- Medicine Master Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Drug Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Generic Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Manufacturer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dosage Form</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Strength</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Therapeutic Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($medicines)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No medicines found. <button onclick="openMedicineModal()" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700" title="Add your first medicine"><?php echo icon_add('w-4 h-4'); ?>Add your first medicine</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicines as $medicine): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($medicine['drug_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($medicine['generic_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($medicine['manufacturer'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($medicine['dosage_form'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($medicine['strength'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($medicine['therapeutic_class'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    ₱<?php echo number_format($medicine['price'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 items-center">
                                        <button onclick="viewMedicine(<?php echo htmlspecialchars($medicine['id']); ?>)" 
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="View Details"><?php echo icon_details('w-4 h-4'); ?>View Details</button>
                                        <button onclick="editMedicine(<?php echo htmlspecialchars($medicine['id']); ?>)" 
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this medicine?')">
                                            <input type="hidden" name="action" value="delete_medicine">
                                            <input type="hidden" name="medicine_id" value="<?php echo htmlspecialchars($medicine['id']); ?>">
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

<!-- Medicine Modal -->
<div id="medicineModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMedicineModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="medicineModalTitle">Add New Medicine</h3>
            <form method="POST" id="medicineForm">
                <input type="hidden" name="action" id="medicineAction" value="create_medicine">
                <input type="hidden" name="medicine_id" id="medicineId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Drug Name *</label>
                        <input type="text" name="drug_name" id="medicineDrugName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Generic Name *</label>
                        <input type="text" name="generic_name" id="medicineGenericName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manufacturer *</label>
                        <input type="text" name="manufacturer" id="medicineManufacturer" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dosage Form *</label>
                        <select name="dosage_form" id="medicineDosageForm" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Form</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Ointment">Ointment</option>
                            <option value="Drops">Drops</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Strength</label>
                        <input type="text" name="strength" id="medicineStrength" 
                               placeholder="e.g., 500mg, 10ml"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Therapeutic Class *</label>
                        <input type="text" name="therapeutic_class" id="medicineClass" required
                               placeholder="e.g., Antibiotic, Analgesic"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Price *</label>
                        <input type="number" name="price" id="medicinePrice" required step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeMedicineModal()" 
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

<!-- View Details Modal -->
<div id="viewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeViewModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Medicine Details</h3>
            <div id="viewContent" class="space-y-3"></div>
            <div class="flex justify-end mt-6">
                <button onclick="closeViewModal()" 
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let medicinesData = <?php echo json_encode($medicines); ?>;

function openMedicineModal() {
    document.getElementById('medicineModal').classList.remove('hidden');
    document.getElementById('medicineModalTitle').textContent = 'Add New Medicine';
    document.getElementById('medicineAction').value = 'create_medicine';
    document.getElementById('medicineId').value = '';
    document.getElementById('medicineForm').reset();
}

function closeMedicineModal() {
    document.getElementById('medicineModal').classList.add('hidden');
}

function editMedicine(medicineId) {
    const medicine = medicinesData.find(m => m.id == medicineId);
    if (!medicine) return;
    
    document.getElementById('medicineModal').classList.remove('hidden');
    document.getElementById('medicineModalTitle').textContent = 'Edit Medicine';
    document.getElementById('medicineAction').value = 'update_medicine';
    document.getElementById('medicineId').value = medicine.id;
    document.getElementById('medicineDrugName').value = medicine.drug_name || '';
    document.getElementById('medicineGenericName').value = medicine.generic_name || '';
    document.getElementById('medicineManufacturer').value = medicine.manufacturer || '';
    document.getElementById('medicineDosageForm').value = medicine.dosage_form || '';
    document.getElementById('medicineStrength').value = medicine.strength || '';
    document.getElementById('medicineClass').value = medicine.therapeutic_class || '';
    document.getElementById('medicinePrice').value = medicine.price || '';
}

function viewMedicine(medicineId) {
    const medicine = medicinesData.find(m => m.id == medicineId);
    if (!medicine) return;
    
    document.getElementById('viewContent').innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div><strong>Drug Name:</strong> ${medicine.drug_name || 'N/A'}</div>
            <div><strong>Generic Name:</strong> ${medicine.generic_name || 'N/A'}</div>
            <div><strong>Manufacturer:</strong> ${medicine.manufacturer || 'N/A'}</div>
            <div><strong>Dosage Form:</strong> ${medicine.dosage_form || 'N/A'}</div>
            <div><strong>Strength:</strong> ${medicine.strength || 'N/A'}</div>
            <div><strong>Therapeutic Class:</strong> ${medicine.therapeutic_class || 'N/A'}</div>
            <div class="col-span-2"><strong>Price:</strong> ₱${parseFloat(medicine.price || 0).toFixed(2)}</div>
        </div>
    `;
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


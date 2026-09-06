<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Prescription Monitoring - PMS';

// Handle prescription actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $status = $_POST['status'];
                    if ($status === 'dispensed') {
                        $stmt = $db->prepare("UPDATE prescriptions SET status = ?, dispensed_at = NOW(), updated_at = NOW() WHERE id = ?");
                    } else {
                        $stmt = $db->prepare("UPDATE prescriptions SET status = ?, updated_at = NOW() WHERE id = ?");
                    }
                    $stmt->execute([$status, $_POST['prescription_id']]);
                    $message = 'Prescription status updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'verify':
                    $stmt = $db->prepare("UPDATE prescriptions SET status = 'ready', verified_by = ?, verified_at = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $_POST['prescription_id']]);
                    $message = 'Prescription verified';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get active tab
$activeTab = $_GET['tab'] ?? 'new';

// Fetch prescriptions
$newPrescriptions = [];
$inProcess = [];
$ready = [];
$dispensed = [];

try {
    $checkPrescriptions = $db->query("SHOW TABLES LIKE 'prescriptions'");
    $checkItems = $db->query("SHOW TABLES LIKE 'prescription_items'");
    
    if ($checkPrescriptions && $checkPrescriptions->rowCount() > 0) {
        // New prescriptions
        $stmt = $db->query("SELECT * FROM prescriptions WHERE status = 'new' ORDER BY created_at DESC");
        $newPrescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // In Process
        $stmt = $db->query("SELECT * FROM prescriptions WHERE status = 'in_process' ORDER BY created_at DESC");
        $inProcess = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ready
        $stmt = $db->query("SELECT * FROM prescriptions WHERE status = 'ready' ORDER BY verified_at DESC");
        $ready = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dispensed
        $stmt = $db->query("SELECT * FROM prescriptions WHERE status = 'dispensed' ORDER BY dispensed_at DESC LIMIT 50");
        $dispensed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get prescription items for each prescription
        if ($checkItems && $checkItems->rowCount() > 0) {
            foreach (['newPrescriptions', 'inProcess', 'ready', 'dispensed'] as $prescriptionArray) {
                $prescriptions = $$prescriptionArray;
                foreach ($prescriptions as &$prescription) {
                    $stmt = $db->prepare("
                        SELECT pi.*, mm.drug_name 
                        FROM prescription_items pi
                        LEFT JOIN medicine_master mm ON pi.medicine_id = mm.id
                        WHERE pi.prescription_id = ?
                    ");
                    $stmt->execute([$prescription['id']]);
                    $prescription['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                $$prescriptionArray = $prescriptions;
            }
        }
    }
} catch (PDOException $e) {
    error_log('PMS Prescription Monitoring error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Prescription Monitoring</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Prescription Monitoring
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Monitor prescriptions through workflow stages with barcode scanner integration and verification.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" id="barcodeInput" placeholder="Scan barcode..." 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button onclick="lookupBarcode()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                Lookup
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <a href="?tab=new" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'new' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    New (<?php echo count($newPrescriptions); ?>)
                </a>
                <a href="?tab=in_process" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'in_process' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    In Process (<?php echo count($inProcess); ?>)
                </a>
                <a href="?tab=ready" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'ready' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Ready (<?php echo count($ready); ?>)
                </a>
                <a href="?tab=dispensed" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'dispensed' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Dispensed (<?php echo count($dispensed); ?>)
                </a>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <?php
            $prescriptions = [];
            if ($activeTab === 'new') {
                $prescriptions = $newPrescriptions;
            } elseif ($activeTab === 'in_process') {
                $prescriptions = $inProcess;
            } elseif ($activeTab === 'ready') {
                $prescriptions = $ready;
            } else {
                $prescriptions = $dispensed;
            }
            ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (empty($prescriptions)): ?>
                    <div class="col-span-full text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                        No prescriptions found
                    </div>
                <?php else: ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="prescription-card bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 transition-all duration-200" data-prescription-id="<?php echo htmlspecialchars($prescription['id']); ?>">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        Patient ID: <?php echo htmlspecialchars($prescription['patient_id'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Doctor: <?php echo htmlspecialchars($prescription['doctor'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <?php
                                $status = strtolower($prescription['status'] ?? 'new');
                                $statusColors = [
                                    'new' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'in_process' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                    'ready' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'dispensed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
                                ];
                                $color = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $color; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Medicines:</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                    <?php if (!empty($prescription['items'])): ?>
                                        <?php foreach ($prescription['items'] as $item): ?>
                                            <div>• <?php echo htmlspecialchars($item['drug_name'] ?? 'N/A'); ?> - Qty: <?php echo $item['quantity'] ?? 0; ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div>No items</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <?php 
                                $timestamp = $prescription['created_at'] ?? $prescription['verified_at'] ?? $prescription['dispensed_at'] ?? 'now';
                                echo date('M d, Y H:i', strtotime($timestamp));
                                ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <?php if ($activeTab === 'new'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['id']); ?>">
                                        <input type="hidden" name="status" value="in_process">
                                        <button type="submit" class="w-full px-3 py-1 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                            Start Processing
                                        </button>
                                    </form>
                                <?php elseif ($activeTab === 'in_process'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['id']); ?>">
                                        <button type="submit" class="w-full px-3 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">
                                            Verify
                                        </button>
                                    </form>
                                <?php elseif ($activeTab === 'ready'): ?>
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['id']); ?>">
                                        <input type="hidden" name="status" value="dispensed">
                                        <button type="submit" class="w-full px-3 py-1 text-xs bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            Mark Dispensed
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Prescriptions data for barcode lookup
const prescriptionsData = <?php echo json_encode($prescriptions ?? []); ?>;

function lookupBarcode() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) {
        showToast('Please enter a barcode or prescription ID', 'warning');
        return;
    }
    
    // Search for prescription by ID, barcode, or patient name
    const searchTerm = barcode.toLowerCase();
    const found = prescriptionsData.find(p => {
        const id = String(p.id || '').toLowerCase();
        const rxNumber = String(p.rx_number || '').toLowerCase();
        const patientName = String(p.patient_name || '').toLowerCase();
        const medicine = String(p.medicine_name || '').toLowerCase();
        
        return id === searchTerm || 
               rxNumber === searchTerm || 
               rxNumber.includes(searchTerm) ||
               patientName.includes(searchTerm) ||
               medicine.includes(searchTerm);
    });
    
    if (found) {
        // Highlight the found prescription card
        const cards = document.querySelectorAll('.prescription-card');
        cards.forEach(card => {
            card.classList.remove('ring-2', 'ring-primary-500');
            if (card.dataset.prescriptionId == found.id) {
                card.classList.add('ring-2', 'ring-primary-500');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        showToast(`Found: ${found.patient_name || 'Patient'} - ${found.medicine_name || 'Prescription'}`, 'success');
    } else {
        showToast('No prescription found matching: ' + barcode, 'error');
    }
    
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeInput').focus();
}

function showToast(message, type) {
    // Use existing toast system if available, otherwise use a simple notification
    const toastContainer = document.getElementById('toast-container');
    if (toastContainer) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-amber-500';
        toast.className = `${bgColor} text-white px-4 py-2 rounded-lg shadow-lg mb-2`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    } else {
        console.log(`[${type}] ${message}`);
    }
}

// Auto-focus barcode input for scanner
document.getElementById('barcodeInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        lookupBarcode();
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


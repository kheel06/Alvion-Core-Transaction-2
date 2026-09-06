<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Radiology Orders Queue - RIS';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("UPDATE radiology_orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("UPDATE radiology_orders SET scheduled_time = ? WHERE id = ?");
        $stmt->execute([$_POST['scheduled_time'], $_POST['order_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Fetch orders with patient info
$orders = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'radiology_orders'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Check if patients table exists for joining
        $checkPatients = $db->query("SHOW TABLES LIKE 'patients'");
        $hasPatients = $checkPatients && $checkPatients->rowCount() > 0;
        
        if ($hasPatients) {
            // Join with patients table to get patient name and status
            $stmt = $db->query("
                SELECT ro.*, 
                       COALESCE(p.first_name, '') as patient_first_name,
                       COALESCE(p.last_name, '') as patient_last_name,
                       COALESCE(p.middle_name, '') as patient_middle_name,
                       COALESCE(p.status, 'active') as patient_status
                FROM radiology_orders ro
                LEFT JOIN patients p ON ro.patient_id = p.id OR ro.patient_id = p.patient_id
                WHERE p.status IS NULL OR p.status = 'active'
                ORDER BY ro.scheduled_time ASC
            ");
        } else {
            $stmt = $db->query("
                SELECT *, 'active' as patient_status FROM radiology_orders
                ORDER BY scheduled_time ASC
            ");
        }
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('RIS Orders Queue error: ' . $e->getMessage());
}

include __DIR__ . '/../../../includes/header.php';
?>

<style>
.timeline-item {
    transition: all 0.2s;
    cursor: move;
}
.timeline-item:hover {
    transform: translateX(4px);
}
.timeline-item.dragging {
    opacity: 0.5;
}
</style>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li><a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="hover:text-primary-600">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><span class="text-gray-700 dark:text-gray-200">Radiology Orders Queue</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Radiology Orders Queue
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Real-time orders queue with timeline view. Drag orders to reschedule appointments.
            </p>
        </div>
    </div>

    <!-- Timeline View -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="space-y-4">
            <?php if (empty($orders)): ?>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-8">No orders found</p>
            <?php else: ?>
                <?php 
                $currentDate = '';
                foreach ($orders as $order): 
                    $orderDate = date('Y-m-d', strtotime($order['scheduled_time'] ?? 'now'));
                    if ($currentDate !== $orderDate):
                        $currentDate = $orderDate;
                ?>
                    <div class="border-l-4 border-primary-500 pl-4 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <?php echo date('F d, Y', strtotime($orderDate)); ?>
                        </h3>
                    </div>
                <?php endif; ?>
                
                <div class="timeline-item bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 ml-4"
                     draggable="true" 
                     ondragstart="dragOrder(event)"
                     data-order-id="<?php echo htmlspecialchars($order['id']); ?>"
                     data-scheduled-time="<?php echo htmlspecialchars($order['scheduled_time'] ?? ''); ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo date('H:i', strtotime($order['scheduled_time'] ?? 'now')); ?>
                                </span>
                                <?php
                                $priority = strtolower($order['priority'] ?? 'routine');
                                $priorityColors = [
                                    'stat' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    'urgent' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                    'routine' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                                ];
                                $priorityColor = $priorityColors[$priority] ?? $priorityColors['routine'];
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $priorityColor; ?>">
                                    <?php echo strtoupper($priority); ?>
                                </span>
                                <?php
                                $status = strtolower($order['status'] ?? 'scheduled');
                                $statusColors = [
                                    'scheduled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                                    'in_progress' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                ];
                                $statusColor = $statusColors[$status] ?? $statusColors['scheduled'];
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </span>
                            </div>
                            
                            <div class="space-y-1 text-sm">
                                <?php 
                                // Build patient display name
                                $patientName = '';
                                if (!empty($order['patient_first_name']) || !empty($order['patient_last_name'])) {
                                    $patientName = trim(($order['patient_first_name'] ?? '') . ' ' . ($order['patient_middle_name'] ?? '') . ' ' . ($order['patient_last_name'] ?? ''));
                                } elseif (!empty($order['patient_name'])) {
                                    $patientName = $order['patient_name'];
                                }
                                ?>
                                <div class="text-gray-900 dark:text-white">
                                    <span class="font-medium">Patient:</span> 
                                    <?php if ($patientName): ?>
                                        <?php echo htmlspecialchars($patientName); ?>
                                        <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($order['patient_id'] ?? 'N/A'); ?>)</span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($order['patient_id'] ?? 'N/A'); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Exam Type:</span> <?php echo htmlspecialchars($order['exam_type'] ?? 'N/A'); ?>
                                </div>
                                <div class="text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Referring Doctor:</span> <?php echo htmlspecialchars($order['referring_doctor'] ?? 'N/A'); ?>
                                </div>
                                <?php if (!empty($order['patient_status']) && $order['patient_status'] !== 'active'): ?>
                                <div class="text-amber-600 dark:text-amber-400">
                                    <span class="font-medium">Status:</span> <?php echo htmlspecialchars(ucfirst($order['patient_status'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-4">
                            <button onclick="rescheduleOrder(<?php echo htmlspecialchars($order['id']); ?>)" 
                                    class="px-3 py-1 text-xs text-primary-600 hover:text-primary-700 font-medium">
                                Reschedule
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeRescheduleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reschedule Order</h3>
            <form id="rescheduleForm">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="order_id" id="rescheduleOrderId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Scheduled Time *</label>
                    <input type="datetime-local" name="scheduled_time" id="rescheduleTime" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeRescheduleModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Reschedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let draggedOrder = null;

function dragOrder(ev) {
    draggedOrder = ev.target;
    ev.target.classList.add('dragging');
    ev.dataTransfer.effectAllowed = 'move';
}

function rescheduleOrder(orderId) {
    document.getElementById('rescheduleModal').classList.remove('hidden');
    document.getElementById('rescheduleOrderId').value = orderId;
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('hidden');
}

document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error rescheduling order');
        }
    });
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


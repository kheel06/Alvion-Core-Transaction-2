<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Lab Orders Monitoring - LIS';

// Handle status update via AJAX
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    try {
        $stmt = $db->prepare("UPDATE lab_orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Fetch orders
$orders = [];
$stats = [
    'received' => 0,
    'processing' => 0,
    'verification' => 0,
    'completed' => 0
];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'lab_orders'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get statistics
        $stmt = $db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM lab_orders
            GROUP BY status
        ");
        $statsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($statsData as $stat) {
            $status = strtolower($stat['status']);
            if (isset($stats[$status])) {
                $stats[$status] = (int)$stat['count'];
            }
        }
        
        // Get all orders
        $stmt = $db->query("
            SELECT 
                id,
                patient_id,
                test_name,
                order_time,
                priority,
                status
            FROM lab_orders
            ORDER BY order_time DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('LIS Orders Monitoring error: ' . $e->getMessage());
}

include __DIR__ . '/../../../includes/header.php';
?>

<style>
.kanban-column {
    min-height: 500px;
}
.kanban-card {
    cursor: move;
    transition: all 0.2s;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.kanban-card.dragging {
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
                    <li><span class="text-gray-700 dark:text-gray-200">Lab Orders Monitoring</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Lab Orders Monitoring
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Monitor and manage laboratory orders with Kanban-style board. Drag and drop orders between status columns.
            </p>
        </div>
    </div>

    <!-- Statistics Header -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Received</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?php echo $stats['received']; ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Processing</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?php echo $stats['processing']; ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Verification</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?php echo $stats['verification']; ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Completed</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1"><?php echo $stats['completed']; ?></div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Received Column -->
        <div class="kanban-column bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Received</h3>
                <span class="px-2 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded-full">
                    <?php echo $stats['received']; ?>
                </span>
            </div>
            <div class="space-y-3" data-status="received" ondrop="drop(event)" ondragover="allowDrop(event)">
                <?php foreach ($orders as $order): ?>
                    <?php if (strtolower($order['status']) === 'received'): ?>
                        <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700" 
                             draggable="true" ondragstart="drag(event)" data-order-id="<?php echo $order['id']; ?>">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-medium text-gray-900 dark:text-white">#<?php echo htmlspecialchars($order['patient_id']); ?></span>
                                <?php
                                $priorityColors = [
                                    'high' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    'medium' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                    'low' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                ];
                                $priority = strtolower($order['priority'] ?? 'medium');
                                $priorityColor = $priorityColors[$priority] ?? $priorityColors['medium'];
                                ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($priority); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mb-1">
                                <?php echo htmlspecialchars($order['test_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y H:i', strtotime($order['order_time'] ?? 'now')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Processing Column -->
        <div class="kanban-column bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Processing</h3>
                <span class="px-2 py-1 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 rounded-full">
                    <?php echo $stats['processing']; ?>
                </span>
            </div>
            <div class="space-y-3" data-status="processing" ondrop="drop(event)" ondragover="allowDrop(event)">
                <?php foreach ($orders as $order): ?>
                    <?php if (strtolower($order['status']) === 'processing'): ?>
                        <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700" 
                             draggable="true" ondragstart="drag(event)" data-order-id="<?php echo $order['id']; ?>">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-medium text-gray-900 dark:text-white">#<?php echo htmlspecialchars($order['patient_id']); ?></span>
                                <?php
                                $priority = strtolower($order['priority'] ?? 'medium');
                                $priorityColor = $priorityColors[$priority] ?? $priorityColors['medium'];
                                ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($priority); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mb-1">
                                <?php echo htmlspecialchars($order['test_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y H:i', strtotime($order['order_time'] ?? 'now')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Verification Column -->
        <div class="kanban-column bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Verification</h3>
                <span class="px-2 py-1 text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 rounded-full">
                    <?php echo $stats['verification']; ?>
                </span>
            </div>
            <div class="space-y-3" data-status="verification" ondrop="drop(event)" ondragover="allowDrop(event)">
                <?php foreach ($orders as $order): ?>
                    <?php if (strtolower($order['status']) === 'verification'): ?>
                        <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700" 
                             draggable="true" ondragstart="drag(event)" data-order-id="<?php echo $order['id']; ?>">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-medium text-gray-900 dark:text-white">#<?php echo htmlspecialchars($order['patient_id']); ?></span>
                                <?php
                                $priority = strtolower($order['priority'] ?? 'medium');
                                $priorityColor = $priorityColors[$priority] ?? $priorityColors['medium'];
                                ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($priority); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mb-1">
                                <?php echo htmlspecialchars($order['test_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y H:i', strtotime($order['order_time'] ?? 'now')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Completed Column -->
        <div class="kanban-column bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Completed</h3>
                <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full">
                    <?php echo $stats['completed']; ?>
                </span>
            </div>
            <div class="space-y-3" data-status="completed" ondrop="drop(event)" ondragover="allowDrop(event)">
                <?php foreach ($orders as $order): ?>
                    <?php if (strtolower($order['status']) === 'completed'): ?>
                        <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700" 
                             draggable="true" ondragstart="drag(event)" data-order-id="<?php echo $order['id']; ?>">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-medium text-gray-900 dark:text-white">#<?php echo htmlspecialchars($order['patient_id']); ?></span>
                                <?php
                                $priority = strtolower($order['priority'] ?? 'medium');
                                $priorityColor = $priorityColors[$priority] ?? $priorityColors['medium'];
                                ?>
                                <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $priorityColor; ?>">
                                    <?php echo ucfirst($priority); ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mb-1">
                                <?php echo htmlspecialchars($order['test_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo date('M d, Y H:i', strtotime($order['order_time'] ?? 'now')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
let draggedElement = null;

function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev) {
    draggedElement = ev.target;
    ev.target.classList.add('dragging');
    ev.dataTransfer.effectAllowed = 'move';
    ev.dataTransfer.setData('text/html', ev.target.outerHTML);
}

function drop(ev) {
    ev.preventDefault();
    const targetColumn = ev.currentTarget;
    const newStatus = targetColumn.getAttribute('data-status');
    const orderId = draggedElement.getAttribute('data-order-id');
    
    if (!orderId) return;
    
    // Update status via AJAX
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Move card to new column
            draggedElement.classList.remove('dragging');
            targetColumn.appendChild(draggedElement);
            // Reload page to update statistics
            setTimeout(() => location.reload(), 300);
        } else {
            alert('Error updating order status');
            draggedElement.classList.remove('dragging');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        draggedElement.classList.remove('dragging');
    });
}

// Prevent default drag behavior on cards
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

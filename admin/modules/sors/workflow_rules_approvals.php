<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Surgery Request & Clearance - OR & Surgery Management';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_rule':
                    $stmt = $db->prepare("INSERT INTO surgery_rules (rule_name, rule_type, rule_description, minimum_lead_time_hours, emergency_slot_count, privilege_level_required, required_equipment, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['rule_name'],
                        $_POST['rule_type'],
                        $_POST['rule_description'],
                        $_POST['minimum_lead_time_hours'] ?: null,
                        $_POST['emergency_slot_count'] ?: null,
                        $_POST['privilege_level_required'] ?: null,
                        $_POST['required_equipment'] ?: null,
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    $message = 'Rule created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_rule':
                    $stmt = $db->prepare("UPDATE surgery_rules SET rule_name = ?, rule_type = ?, rule_description = ?, minimum_lead_time_hours = ?, emergency_slot_count = ?, privilege_level_required = ?, required_equipment = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['rule_name'],
                        $_POST['rule_type'],
                        $_POST['rule_description'],
                        $_POST['minimum_lead_time_hours'] ?: null,
                        $_POST['emergency_slot_count'] ?: null,
                        $_POST['privilege_level_required'] ?: null,
                        $_POST['required_equipment'] ?: null,
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['rule_id']
                    ]);
                    $message = 'Rule updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'approve_request':
                    $approvedBy = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 1;
                    $stmt = $db->prepare("UPDATE surgery_approvals SET status = 'Approved', approved_by = ?, responded_at = NOW() WHERE id = ?");
                    $stmt->execute([$approvedBy, $_POST['approval_id']]);
                    $message = 'Approval granted';
                    $messageType = 'success';
                    break;
                
                case 'reject_request':
                    $rejectedBy = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 1;
                    $stmt = $db->prepare("UPDATE surgery_approvals SET status = 'Rejected', approved_by = ?, rejection_reason = ?, responded_at = NOW() WHERE id = ?");
                    $stmt->execute([$rejectedBy, $_POST['rejection_reason'], $_POST['approval_id']]);
                    $message = 'Approval rejected';
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
$rules = [];
$pendingApprovals = [];
$allApprovals = [];

try {
    // Get rules
    $stmt = $db->query("SELECT * FROM surgery_rules ORDER BY rule_type, rule_name");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending approvals
    $stmt = $db->query("
        SELECT 
            sa.*,
            ss.patient_name,
            ss.patient_id,
            ss.scheduled_date,
            ss.scheduled_start_time,
            pc.procedure_name,
            s.first_name as surgeon_first_name,
            s.last_name as surgeon_last_name,
            or_rooms.room_number
        FROM surgery_approvals sa
        LEFT JOIN surgery_schedule ss ON sa.surgery_id = ss.id
        LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
        LEFT JOIN surgeons s ON ss.surgeon_id = s.id
        LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
        WHERE sa.status = 'Pending'
        ORDER BY sa.requested_at ASC
    ");
    $pendingApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all approvals
    $stmt = $db->query("
        SELECT 
            sa.*,
            ss.patient_name,
            ss.scheduled_date,
            pc.procedure_name,
            s.first_name as surgeon_first_name,
            s.last_name as surgeon_last_name
        FROM surgery_approvals sa
        LEFT JOIN surgery_schedule ss ON sa.surgery_id = ss.id
        LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
        LEFT JOIN surgeons s ON ss.surgeon_id = s.id
        ORDER BY sa.requested_at DESC
        LIMIT 50
    ");
    $allApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('SORS Workflow error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Surgery Request & Clearance</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Surgery Request & Clearance
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Surgery scheduling rules (lead time, privilege level, equipment), and clearance workflow: Department Head, Anesthesia, and Administration approvals for elective and major cases.
            </p>
        </div>
        <div>
            <button onclick="openCreateRuleModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create New Rule
            </button>
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

    <!-- Pending Approvals Queue -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Approval Queue</h2>
            <?php if (count($pendingApprovals) > 0): ?>
                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 rounded-full text-sm font-medium">
                    <?php echo count($pendingApprovals); ?> Pending
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (empty($pendingApprovals)): ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No pending approvals</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pendingApprovals as $approval): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($approval['patient_name']); ?></h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($approval['procedure_name']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Surgeon: <?php echo htmlspecialchars($approval['surgeon_first_name'] . ' ' . $approval['surgeon_last_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Scheduled: <?php echo date('M d, Y H:i', strtotime($approval['scheduled_date'] . ' ' . $approval['scheduled_start_time'])); ?>
                                </p>
                                <?php if ($approval['room_number']): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">OR: <?php echo htmlspecialchars($approval['room_number']); ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 rounded">
                                <?php echo htmlspecialchars($approval['approval_type']); ?>
                            </span>
                        </div>
                        
                        <?php if ($approval['reason']): ?>
                            <div class="mb-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Reason:</span> <?php echo htmlspecialchars($approval['reason']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="approve_request">
                                <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Approve
                                </button>
                            </form>
                            <button onclick="openRejectModal(<?php echo $approval['id']; ?>, '<?php echo htmlspecialchars($approval['patient_name'], ENT_QUOTES); ?>')" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                Reject
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scheduling Rules -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scheduling Rules</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rule Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($rules)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No rules configured</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rules as $rule): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($rule['rule_name']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($rule['rule_type']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($rule['rule_description'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded <?php echo $rule['is_active'] ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'; ?>">
                                        <?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button onclick="openEditRuleModal(<?php echo $rule['id']; ?>)" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approval History -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Approval History</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Procedure</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($allApprovals)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No approval history</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allApprovals as $approval): 
                            $statusColors = [
                                'Pending' => 'bg-amber-100 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200',
                                'Approved' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200',
                                'Rejected' => 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200'
                            ];
                            $statusColor = $statusColors[$approval['status']] ?? 'bg-gray-100 dark:bg-gray-700';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($approval['patient_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($approval['procedure_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($approval['approval_type']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded <?php echo $statusColor; ?>">
                                        <?php echo htmlspecialchars($approval['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo date('M d, Y', strtotime($approval['requested_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Rule Modal -->
<div id="ruleModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeRuleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="ruleModalTitle">Create New Rule</h3>
            <form method="POST" id="ruleForm">
                <input type="hidden" name="action" id="ruleAction" value="create_rule">
                <input type="hidden" name="rule_id" id="ruleId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rule Name *</label>
                    <input type="text" name="rule_name" id="ruleName" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rule Type *</label>
                    <select name="rule_type" id="ruleType" required onchange="updateRuleFields()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="Lead Time">Lead Time</option>
                        <option value="Emergency Slot">Emergency Slot</option>
                        <option value="Privilege Level">Privilege Level</option>
                        <option value="Equipment Requirement">Equipment Requirement</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description *</label>
                    <textarea name="rule_description" id="ruleDescription" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div id="leadTimeField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Lead Time (hours)</label>
                        <input type="number" name="minimum_lead_time_hours" id="leadTime" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div id="emergencySlotField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Emergency Slot Count</label>
                        <input type="number" name="emergency_slot_count" id="emergencySlot" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div id="privilegeField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Required Privilege Level</label>
                        <select name="privilege_level_required" id="privilegeLevel" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Level</option>
                            <option value="Level 1">Level 1</option>
                            <option value="Level 2">Level 2</option>
                            <option value="Level 3">Level 3</option>
                            <option value="Level 4">Level 4</option>
                        </select>
                    </div>
                    
                    <div id="equipmentField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Required Equipment</label>
                        <input type="text" name="required_equipment" id="requiredEquipment" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Comma-separated list">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="isActive" checked class="rounded border-gray-300 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                    </label>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeRuleModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Approval Modal -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeRejectModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reject Approval</h3>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="action" value="reject_request">
                <input type="hidden" name="approval_id" id="rejectApprovalId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Patient</label>
                    <input type="text" id="rejectPatientName" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rejection Reason *</label>
                    <textarea name="rejection_reason" rows="4" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeRejectModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateRuleModal() {
    document.getElementById('ruleModal').classList.remove('hidden');
    document.getElementById('ruleModalTitle').textContent = 'Create New Rule';
    document.getElementById('ruleAction').value = 'create_rule';
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleId').value = '';
    updateRuleFields();
}

function openEditRuleModal(ruleId) {
    // Fetch rule data from the rules array
    const rulesData = <?php echo json_encode($rules); ?>;
    const rule = rulesData.find(r => r.id == ruleId);
    if (!rule) {
        alert('Rule not found');
        return;
    }
    
    document.getElementById('ruleModal').classList.remove('hidden');
    document.getElementById('ruleModalTitle').textContent = 'Edit Rule';
    document.getElementById('ruleAction').value = 'update_rule';
    document.getElementById('ruleId').value = rule.id;
    document.getElementById('ruleName').value = rule.rule_name || '';
    document.getElementById('ruleType').value = rule.rule_type || 'Lead Time';
    document.getElementById('ruleDescription').value = rule.rule_description || '';
    document.getElementById('leadTime').value = rule.minimum_lead_time_hours || '';
    document.getElementById('emergencySlot').value = rule.emergency_slot_count || '';
    document.getElementById('privilegeLevel').value = rule.privilege_level_required || '';
    document.getElementById('requiredEquipment').value = rule.required_equipment || '';
    document.getElementById('isActive').checked = rule.is_active == 1;
    
    updateRuleFields();
}

function closeRuleModal() {
    document.getElementById('ruleModal').classList.add('hidden');
    document.getElementById('ruleForm').reset();
}

function updateRuleFields() {
    const ruleType = document.getElementById('ruleType').value;
    document.getElementById('leadTimeField').style.display = ruleType === 'Lead Time' ? 'block' : 'none';
    document.getElementById('emergencySlotField').style.display = ruleType === 'Emergency Slot' ? 'block' : 'none';
    document.getElementById('privilegeField').style.display = ruleType === 'Privilege Level' ? 'block' : 'none';
    document.getElementById('equipmentField').style.display = ruleType === 'Equipment Requirement' ? 'block' : 'none';
}

function openRejectModal(approvalId, patientName) {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectApprovalId').value = approvalId;
    document.getElementById('rejectPatientName').value = patientName;
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

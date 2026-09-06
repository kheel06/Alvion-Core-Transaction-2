<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Patient Diet Assignments - DNMS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'assign_diet':
                    $stmt = $db->prepare("INSERT INTO patient_diet_assignments (patient_id, diet_plan_id, assigned_by, reason, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $_POST['patient_id'],
                        $_POST['diet_plan_id'],
                        $_SESSION['user_id'] ?? 1,
                        $_POST['reason']
                    ]);
                    $assignmentId = $db->lastInsertId();
                    
                    // Create meal schedule
                    if (isset($_POST['meal_schedule']) && is_array($_POST['meal_schedule'])) {
                        foreach ($_POST['meal_schedule'] as $mealId) {
                            $stmt = $db->prepare("INSERT INTO patient_meal_schedule (assignment_id, meal_template_id, scheduled_date) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$assignmentId, $mealId]);
                        }
                    }
                    
                    $message = 'Diet assignment created successfully (pending dietitian review)';
                    $messageType = 'success';
                    break;
                
                case 'update_diet':
                    $stmt = $db->prepare("UPDATE patient_diet_assignments SET diet_plan_id = ?, reason = ?, status = 'pending' WHERE id = ?");
                    $stmt->execute([
                        $_POST['diet_plan_id'],
                        $_POST['reason'],
                        $_POST['assignment_id']
                    ]);
                    $message = 'Diet assignment updated successfully (pending review)';
                    $messageType = 'success';
                    break;
                
                case 'approve_diet':
                    $stmt = $db->prepare("UPDATE patient_diet_assignments SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $_SESSION['user_id'] ?? 1,
                        $_POST['assignment_id']
                    ]);
                    $message = 'Diet assignment approved';
                    $messageType = 'success';
                    break;
                
                case 'change_diet':
                    $stmt = $db->prepare("UPDATE patient_diet_assignments SET status = 'changed', change_reason = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['change_reason'],
                        $_POST['assignment_id']
                    ]);
                    
                    // Create new assignment
                    $stmt = $db->prepare("INSERT INTO patient_diet_assignments (patient_id, diet_plan_id, assigned_by, reason, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $_POST['patient_id'],
                        $_POST['new_diet_plan_id'],
                        $_SESSION['user_id'] ?? 1,
                        $_POST['change_reason']
                    ]);
                    $message = 'Diet change request submitted (pending review)';
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
$search = $_GET['search'] ?? '';
$selectedPatientId = $_GET['patient_id'] ?? null;

$patients = [];
$selectedPatient = null;
$currentAssignment = null;
$mealSchedule = [];
$dietPlans = [];
$dietitians = [];

try {
    // Get diet plans
    $checkPlans = $db->query("SHOW TABLES LIKE 'diet_plans'");
    if ($checkPlans && $checkPlans->rowCount() > 0) {
        $stmt = $db->query("SELECT * FROM diet_plans ORDER BY plan_name");
        $dietPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get dietitians
    $checkDietitians = $db->query("SHOW TABLES LIKE 'dietitians'");
    if ($checkDietitians && $checkDietitians->rowCount() > 0) {
        $stmt = $db->query("SELECT * FROM dietitians WHERE status = 'active' ORDER BY name");
        $dietitians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get patients from patients table with their diet assignments
    $checkPatients = $db->query("SHOW TABLES LIKE 'patients'");
    if ($checkPatients && $checkPatients->rowCount() > 0) {
        // First check if patients table has data
        $countCheck = $db->query("SELECT COUNT(*) FROM patients");
        $patientCount = $countCheck->fetchColumn();
        
        if ($patientCount > 0) {
            $sql = "SELECT p.id as patient_id, p.hospital_id, p.first_name, p.last_name
                    FROM patients p
                    WHERE 1=1";
            if ($search) {
                $sql .= " AND (p.id LIKE ? OR p.hospital_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
            }
            $sql .= " ORDER BY p.last_name, p.first_name LIMIT 50";
            
            $stmt = $db->prepare($sql);
            if ($search) {
                $searchParam = "%$search%";
                $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
            } else {
                $stmt->execute();
            }
            $patientRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($patientRows as $row) {
                $patients[] = [
                    'id' => $row['patient_id'],
                    'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Patient ' . $row['patient_id'],
                    'room' => $row['hospital_id'] ? 'ID: ' . $row['hospital_id'] : ''
                ];
            }
        }
    }
    
    // Fallback: If no patients found from patients table, check assignments table
    if (empty($patients)) {
        // Fallback: Get patients from assignments if patients table doesn't exist
        $checkAssignments = $db->query("SHOW TABLES LIKE 'patient_diet_assignments'");
        if ($checkAssignments && $checkAssignments->rowCount() > 0) {
            $sql = "SELECT DISTINCT patient_id FROM patient_diet_assignments";
            if ($search) {
                $sql .= " WHERE patient_id LIKE ?";
            }
            $sql .= " ORDER BY patient_id LIMIT 50";
            
            $stmt = $db->prepare($sql);
            if ($search) {
                $stmt->execute(["%$search%"]);
            } else {
                $stmt->execute();
            }
            $patientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($patientIds as $index => $pid) {
                $patients[] = [
                    'id' => $pid,
                    'name' => 'Patient ' . $pid,
                    'room' => 'Room ' . (100 + ($index % 50))
                ];
            }
        }
    }
    
    // Get selected patient details
    if ($selectedPatientId) {
        // First get patient info from patients table
        $stmt = $db->prepare("SELECT id, hospital_id, first_name, last_name FROM patients WHERE id = ?");
        $stmt->execute([$selectedPatientId]);
        $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patientInfo) {
            $selectedPatient = [
                'id' => $patientInfo['id'],
                'name' => trim(($patientInfo['first_name'] ?? '') . ' ' . ($patientInfo['last_name'] ?? '')) ?: 'Patient ' . $patientInfo['id'],
                'room' => $patientInfo['hospital_id'] ? 'ID: ' . $patientInfo['hospital_id'] : ''
            ];
        } else {
            // Fallback if patient not found in patients table
            $selectedPatient = [
                'id' => $selectedPatientId,
                'name' => 'Patient ' . $selectedPatientId,
                'room' => ''
            ];
        }
        
        $stmt = $db->prepare("SELECT * FROM patient_diet_assignments WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$selectedPatientId]);
        $currentAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentAssignment) {
            // Get meal schedule
            $stmt = $db->prepare("SELECT pms.*, mt.meal_name, mt.meal_type, mt.scheduled_time 
                                  FROM patient_meal_schedule pms
                                  LEFT JOIN meal_templates mt ON pms.meal_template_id = mt.id
                                  WHERE pms.assignment_id = ?
                                  ORDER BY mt.meal_type, mt.scheduled_time");
            $stmt->execute([$currentAssignment['id']]);
            $mealSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log('DNMS Patient Assignments error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Patient Diet Assignments</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Patient Diet Assignments
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Assign and manage patient diet plans with meal schedules and dietary restrictions.
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Patient List -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Search Patients</h2>
            
            <form method="GET" class="mb-4">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by name or ID..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </form>
            
            <div class="space-y-2 max-h-[600px] overflow-y-auto">
                <?php if (empty($patients)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No patients found</p>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                        <a href="?patient_id=<?php echo htmlspecialchars($patient['id']); ?>&search=<?php echo urlencode($search); ?>" 
                           class="block p-3 rounded-lg border <?php echo $selectedPatientId == $patient['id'] ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500' : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                            <div class="font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($patient['name']); ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ID: <?php echo htmlspecialchars($patient['id']); ?> | <?php echo htmlspecialchars($patient['room']); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Patient Details & Assignment -->
        <div class="lg:col-span-2 space-y-6">
            <?php if (!$selectedPatient): ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">Select a patient from the list to view or assign diet plan</p>
                </div>
            <?php else: ?>
                <!-- Current Assignment -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Patient: <?php echo htmlspecialchars($selectedPatient['name']); ?>
                        </h2>
                        <?php if ($currentAssignment): ?>
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php 
                                echo $currentAssignment['status'] === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : 
                                ($currentAssignment['status'] === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : 
                                'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'); 
                            ?>">
                                <?php echo strtoupper($currentAssignment['status'] ?? 'N/A'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($currentAssignment): ?>
                        <?php 
                        $assignedPlan = null;
                        foreach ($dietPlans as $plan) {
                            if ($plan['id'] == $currentAssignment['diet_plan_id']) {
                                $assignedPlan = $plan;
                                break;
                            }
                        }
                        ?>
                        
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Current Diet Plan:</span>
                                <div class="text-sm text-gray-900 dark:text-white mt-1">
                                    <?php echo htmlspecialchars($assignedPlan['plan_name'] ?? 'N/A'); ?> 
                                    (<?php echo htmlspecialchars($assignedPlan['category'] ?? 'N/A'); ?>)
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Calorie Range:</span>
                                <div class="text-sm text-gray-900 dark:text-white mt-1">
                                    <?php echo number_format($assignedPlan['calorie_range_low'] ?? 0); ?> - 
                                    <?php echo number_format($assignedPlan['calorie_range_high'] ?? 0); ?> kcal
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dietary Restrictions:</span>
                                <div class="text-sm text-gray-900 dark:text-white mt-1">
                                    <?php echo htmlspecialchars($assignedPlan['restrictions'] ?? 'None'); ?>
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reason:</span>
                                <div class="text-sm text-gray-900 dark:text-white mt-1">
                                    <?php echo htmlspecialchars($currentAssignment['reason'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            
                            <?php if ($currentAssignment['status'] === 'pending'): ?>
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="action" value="approve_diet">
                                    <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($currentAssignment['id']); ?>">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Approve Assignment
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No active diet assignment</p>
                    <?php endif; ?>
                </div>
                
                <!-- Meal Schedule -->
                <?php if (!empty($mealSchedule)): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Meal Schedule</h2>
                        <div class="space-y-3">
                            <?php 
                            $groupedMeals = [];
                            foreach ($mealSchedule as $meal) {
                                $type = $meal['meal_type'] ?? 'Other';
                                if (!isset($groupedMeals[$type])) {
                                    $groupedMeals[$type] = [];
                                }
                                $groupedMeals[$type][] = $meal;
                            }
                            ?>
                            <?php foreach ($groupedMeals as $type => $meals): ?>
                                <div class="border-l-4 border-primary-500 pl-4">
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($type); ?></h3>
                                    <?php foreach ($meals as $meal): ?>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <?php echo htmlspecialchars($meal['meal_name'] ?? 'N/A'); ?> 
                                            (<?php echo htmlspecialchars($meal['scheduled_time'] ?? 'N/A'); ?>)
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Assign/Change Diet Form -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <?php echo $currentAssignment ? 'Change Diet Plan' : 'Assign New Diet Plan'; ?>
                    </h2>
                    <form method="POST" id="dietAssignmentForm">
                        <input type="hidden" name="action" value="<?php echo $currentAssignment ? 'change_diet' : 'assign_diet'; ?>">
                        <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($selectedPatientId); ?>">
                        <?php if ($currentAssignment): ?>
                            <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($currentAssignment['id']); ?>">
                        <?php endif; ?>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Diet Plan *</label>
                                <select name="<?php echo $currentAssignment ? 'new_diet_plan_id' : 'diet_plan_id'; ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">Select Diet Plan</option>
                                    <?php foreach ($dietPlans as $plan): ?>
                                        <option value="<?php echo htmlspecialchars($plan['id']); ?>">
                                            <?php echo htmlspecialchars($plan['plan_name']); ?> (<?php echo htmlspecialchars($plan['category']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason *</label>
                                <select name="<?php echo $currentAssignment ? 'change_reason' : 'reason'; ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">Select Reason</option>
                                    <option value="Medical">Medical</option>
                                    <option value="Preference">Preference</option>
                                    <option value="Compliance">Compliance</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Notes</label>
                                <textarea name="notes" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 justify-end mt-6">
                            <button type="submit" 
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                <?php echo $currentAssignment ? 'Submit Change Request' : 'Assign Diet Plan'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


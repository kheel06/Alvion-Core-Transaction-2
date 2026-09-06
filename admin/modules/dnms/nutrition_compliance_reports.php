<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Nutrition Compliance Reports - DNMS';

$message = '';
$messageType = '';

// Handle fetch meal logs (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_meal_logs') {
    header('Content-Type: application/json');
    try {
        $patientId = $_GET['patient_id'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("
            SELECT 
                mcl.*,
                mt.meal_name,
                mt.meal_type
            FROM meal_consumption_log mcl
            LEFT JOIN meal_templates mt ON mcl.meal_template_id = mt.id
            WHERE mcl.patient_id = ? AND mcl.consumed_date BETWEEN ? AND ?
            ORDER BY mcl.consumed_date DESC, mcl.consumed_time DESC
        ");
        $stmt->execute([$patientId, $dateFrom, $dateTo]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="nutrition_compliance_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Patient ID', 'Patient Name', 'Assigned Plan', 'Meals Consumed', 'Meals Prescribed', 'Calorie Intake', 'Target Calories', 'Compliance Score %']);
    
    try {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("
            SELECT 
                nc.patient_id,
                nc.assigned_plan,
                nc.meals_consumed,
                nc.meals_prescribed,
                nc.calorie_intake,
                nc.target_calories,
                nc.compliance_score
            FROM nutrition_compliance nc
            WHERE nc.report_date BETWEEN ? AND ?
            ORDER BY nc.compliance_score ASC, nc.patient_id
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['patient_id'],
                'Patient ' . $row['patient_id'],
                $row['assigned_plan'],
                $row['meals_consumed'],
                $row['meals_prescribed'],
                $row['calorie_intake'],
                $row['target_calories'],
                number_format($row['compliance_score'], 1) . '%'
            ]);
        }
    } catch (PDOException $e) {
        error_log('Export error: ' . $e->getMessage());
    }
    
    fclose($output);
    exit;
}

// Handle generate compliance data
if (isset($_POST['action']) && $_POST['action'] === 'generate_compliance') {
    try {
        $generateDate = $_POST['generate_date'] ?? date('Y-m-d');
        
        // Get all active assignments
        $stmt = $db->query("
            SELECT pda.id, pda.patient_id, pda.diet_plan_id, dp.plan_name, dp.calorie_range_low, dp.calorie_range_high
            FROM patient_diet_assignments pda
            LEFT JOIN diet_plans dp ON pda.diet_plan_id = dp.id
            WHERE pda.status = 'active'
        ");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($assignments as $assignment) {
            // Get meal schedule for this assignment
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_meals
                FROM patient_meal_schedule
                WHERE assignment_id = ? AND scheduled_date = ?
            ");
            $stmt->execute([$assignment['id'], $generateDate]);
            $scheduleData = $stmt->fetch(PDO::FETCH_ASSOC);
            $mealsPrescribed = $scheduleData['total_meals'] ?? 0;
            
            // Get consumed meals for this date
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as consumed_count,
                    COALESCE(SUM(actual_calories), 0) as total_calories,
                    COALESCE(SUM(actual_protein), 0) as total_protein
                FROM meal_consumption_log
                WHERE assignment_id = ? AND consumed_date = ?
            ");
            $stmt->execute([$assignment['id'], $generateDate]);
            $consumptionData = $stmt->fetch(PDO::FETCH_ASSOC);
            $mealsConsumed = $consumptionData['consumed_count'] ?? 0;
            $calorieIntake = $consumptionData['total_calories'] ?? 0;
            $proteinIntake = $consumptionData['total_protein'] ?? 0;
            
            // Calculate target calories (average of range)
            $targetCalories = ($assignment['calorie_range_low'] + $assignment['calorie_range_high']) / 2;
            $targetProtein = $targetCalories * 0.15 / 4; // 15% of calories from protein
            
            // Calculate compliance score (weighted: 50% meal adherence, 50% calorie adherence)
            $mealAdherence = $mealsPrescribed > 0 ? ($mealsConsumed / $mealsPrescribed) * 100 : 0;
            $calorieAdherence = $targetCalories > 0 ? min(100, ($calorieIntake / $targetCalories) * 100) : 0;
            $complianceScore = ($mealAdherence * 0.5) + ($calorieAdherence * 0.5);
            
            // Check if record exists
            $stmt = $db->prepare("SELECT id FROM nutrition_compliance WHERE assignment_id = ? AND report_date = ?");
            $stmt->execute([$assignment['id'], $generateDate]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing
                $stmt = $db->prepare("
                    UPDATE nutrition_compliance SET
                        meals_consumed = ?,
                        meals_prescribed = ?,
                        calorie_intake = ?,
                        target_calories = ?,
                        protein_intake = ?,
                        target_protein = ?,
                        compliance_score = ?,
                        adherence_percentage = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $mealsConsumed, $mealsPrescribed, $calorieIntake, $targetCalories,
                    $proteinIntake, $targetProtein, $complianceScore, $mealAdherence,
                    $existing['id']
                ]);
            } else {
                // Insert new
                $stmt = $db->prepare("
                    INSERT INTO nutrition_compliance 
                    (patient_id, assignment_id, report_date, assigned_plan, meals_consumed, meals_prescribed, 
                     calorie_intake, target_calories, protein_intake, target_protein, compliance_score, adherence_percentage)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $assignment['patient_id'], $assignment['id'], $generateDate, $assignment['plan_name'],
                    $mealsConsumed, $mealsPrescribed, $calorieIntake, $targetCalories,
                    $proteinIntake, $targetProtein, $complianceScore, $mealAdherence
                ]);
            }
        }
        
        $message = 'Compliance data generated successfully';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error generating compliance data: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'compliance';

$complianceData = [];
$summaryStats = [
    'total_patients' => 0,
    'avg_compliance' => 0,
    'high_compliance' => 0,
    'low_compliance' => 0
];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'nutrition_compliance'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get compliance data - fix the query to properly get patient_id
        $stmt = $db->prepare("
            SELECT 
                nc.*,
                COALESCE(nc.patient_id, pda.patient_id) as patient_id
            FROM nutrition_compliance nc
            LEFT JOIN patient_diet_assignments pda ON nc.assignment_id = pda.id
            WHERE nc.report_date BETWEEN ? AND ?
            ORDER BY nc.compliance_score ASC, nc.patient_id
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $complianceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary stats
        if (!empty($complianceData)) {
            $summaryStats['total_patients'] = count($complianceData);
            $totalCompliance = array_sum(array_column($complianceData, 'compliance_score'));
            $summaryStats['avg_compliance'] = $totalCompliance / count($complianceData);
            $summaryStats['high_compliance'] = count(array_filter($complianceData, function($d) { return $d['compliance_score'] >= 80; }));
            $summaryStats['low_compliance'] = count(array_filter($complianceData, function($d) { return $d['compliance_score'] < 60; }));
        }
    }
} catch (PDOException $e) {
    error_log('DNMS Compliance Reports error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Nutrition Compliance Reports</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Nutrition Compliance Reports
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Monitor patient adherence to diet plans with compliance metrics and meal-by-meal logs.
            </p>
        </div>
        <a href="?export=csv&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
        </a>
    </div>

    <?php if (isset($message) && $message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Generate Compliance Data Button -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200">Generate Compliance Data</h3>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">Generate compliance reports from meal consumption logs</p>
            </div>
            <form method="POST" class="flex items-center gap-2">
                <input type="hidden" name="action" value="generate_compliance">
                <input type="date" name="generate_date" value="<?php echo date('Y-m-d'); ?>" 
                       class="px-3 py-2 border border-blue-300 dark:border-blue-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Generate
                </button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                       class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                       class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Report Type</label>
                <select name="report_type" 
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="compliance" <?php echo $reportType === 'compliance' ? 'selected' : ''; ?>>Compliance Overview</option>
                    <option value="detailed" <?php echo $reportType === 'detailed' ? 'selected' : ''; ?>>Detailed Logs</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Total Patients</div>
            <div class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                <?php echo number_format($summaryStats['total_patients']); ?>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Avg Compliance</div>
            <div class="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
                <?php echo number_format($summaryStats['avg_compliance'], 1); ?>%
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">High Compliance (≥80%)</div>
            <div class="text-2xl font-semibold text-green-600 dark:text-green-400 mt-1">
                <?php echo number_format($summaryStats['high_compliance']); ?>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Low Compliance (<60%)</div>
            <div class="text-2xl font-semibold text-red-600 dark:text-red-400 mt-1">
                <?php echo number_format($summaryStats['low_compliance']); ?>
            </div>
        </div>
    </div>

    <!-- Compliance Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Compliance Distribution</h2>
        <canvas id="complianceChart" height="80"></canvas>
    </div>

    <!-- Compliance Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Patient Compliance Details</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Assigned Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Meals</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Calories</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Compliance Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($complianceData)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No compliance data found for the selected date range
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($complianceData as $row): 
                            $complianceScore = floatval($row['compliance_score'] ?? 0);
                            $scoreColor = $complianceScore >= 80 ? 'text-green-600 dark:text-green-400' : 
                                         ($complianceScore >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 
                                         'text-red-600 dark:text-red-400');
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        Patient <?php echo htmlspecialchars($row['patient_id'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($row['assigned_plan'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo number_format($row['meals_consumed'] ?? 0); ?> / <?php echo number_format($row['meals_prescribed'] ?? 0); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $row['meals_prescribed'] > 0 ? number_format(($row['meals_consumed'] / $row['meals_prescribed']) * 100, 1) : 0; ?>% consumed
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo number_format($row['calorie_intake'] ?? 0); ?> / <?php echo number_format($row['target_calories'] ?? 0); ?> kcal
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $row['target_calories'] > 0 ? number_format(($row['calorie_intake'] / $row['target_calories']) * 100, 1) : 0; ?>% of target
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-1">
                                            <div class="text-sm font-semibold <?php echo $scoreColor; ?>">
                                                <?php echo number_format($complianceScore, 1); ?>%
                                            </div>
                                        </div>
                                        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full ml-2">
                                            <div class="h-2 rounded-full <?php 
                                                echo $complianceScore >= 80 ? 'bg-green-500' : 
                                                ($complianceScore >= 60 ? 'bg-yellow-500' : 'bg-red-500'); 
                                            ?>" style="width: <?php echo min(100, $complianceScore); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button type="button" onclick="viewMealLogs('<?php echo htmlspecialchars($row['patient_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-green-600 bg-green-600 text-white hover:bg-green-700 hover:border-green-700 font-medium text-sm" title="View meal logs"><?php echo icon_view('w-4 h-4'); ?>View Logs</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Meal Logs Modal -->
<div id="mealLogsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMealLogsModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Meal-by-Meal Logs</h3>
            <div id="mealLogsContent">
                <p class="text-gray-500 dark:text-gray-400">Loading meal logs...</p>
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="closeMealLogsModal()" 
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Compliance Chart
const ctx = document.getElementById('complianceChart');
if (ctx) {
    const complianceData = <?php echo json_encode(array_column($complianceData, 'compliance_score')); ?>;
    
    // Create distribution buckets
    const buckets = {
        '0-40%': 0,
        '41-60%': 0,
        '61-80%': 0,
        '81-100%': 0
    };
    
    complianceData.forEach(score => {
        if (score < 41) buckets['0-40%']++;
        else if (score < 61) buckets['41-60%']++;
        else if (score < 81) buckets['61-80%']++;
        else buckets['81-100%']++;
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(buckets),
            datasets: [{
                label: 'Number of Patients',
                data: Object.values(buckets),
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)'
                ],
                borderColor: [
                    'rgb(239, 68, 68)',
                    'rgb(234, 179, 8)',
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function viewMealLogs(patientId) {
    document.getElementById('mealLogsModal').classList.remove('hidden');
    document.getElementById('mealLogsContent').innerHTML = '<p class="text-gray-500 dark:text-gray-400">Loading meal logs for Patient ' + patientId + '...</p>';
    
    // Fetch meal logs from server
    fetch('?action=fetch_meal_logs&patient_id=' + encodeURIComponent(patientId) + '&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.error) {
                document.getElementById('mealLogsContent').innerHTML = '<p class="text-red-500">' + (data.error || 'Failed to load logs.') + '</p>';
                return;
            }
            
            const logs = data.logs || [];
            if (logs.length === 0) {
                document.getElementById('mealLogsContent').innerHTML = '<p class="text-gray-500 dark:text-gray-400">No meal logs found for this patient.</p>';
                return;
            }
            
            let html = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">Patient ${patientId} - Meal Consumption Logs</h4>
                        <span class="text-sm text-gray-500 dark:text-gray-400">${logs.length} meals logged</span>
                    </div>
            `;
            
            logs.forEach(log => {
                const statusColor = log.consumption_status === 'full' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' :
                                  log.consumption_status === 'partial' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' :
                                  'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400';
                
                html += `
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">${log.consumed_date} at ${log.consumed_time || 'N/A'}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${log.food_items_consumed || 'N/A'}</div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor}">
                                ${log.consumption_status || 'N/A'}
                            </span>
                        </div>
                        <div class="grid grid-cols-4 gap-3 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 text-sm">
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">Calories</div>
                                <div class="font-medium text-gray-900 dark:text-white">${parseFloat(log.actual_calories || 0).toFixed(0)}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">Protein</div>
                                <div class="font-medium text-gray-900 dark:text-white">${parseFloat(log.actual_protein || 0).toFixed(1)}g</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">Carbs</div>
                                <div class="font-medium text-gray-900 dark:text-white">${parseFloat(log.actual_carbs || 0).toFixed(1)}g</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">Fats</div>
                                <div class="font-medium text-gray-900 dark:text-white">${parseFloat(log.actual_fats || 0).toFixed(1)}g</div>
                            </div>
                        </div>
                        ${log.notes ? `<div class="text-xs text-gray-600 dark:text-gray-400 mt-2 italic">${log.notes}</div>` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('mealLogsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('mealLogsContent').innerHTML = '<p class="text-red-500">Error loading meal logs: ' + error + '</p>';
        });
}

function closeMealLogsModal() {
    document.getElementById('mealLogsModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>


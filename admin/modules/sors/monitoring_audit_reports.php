<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'OR Utilization & Reports - OR & Surgery Management';

$message = '';
$messageType = '';

// Handle CSV export (before any HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $currentSurgeries = [];
        $auditLog = [];
        $reports = [];
        $stmt = $db->query("
            SELECT ss.patient_name, ss.scheduled_date, ss.scheduled_start_time, ss.scheduled_end_time, ss.status,
                   pc.procedure_name, s.first_name as surgeon_first_name, s.last_name as surgeon_last_name, or_rooms.room_number
            FROM surgery_schedule ss
            LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
            LEFT JOIN surgeons s ON ss.surgeon_id = s.id
            LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
            WHERE ss.scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ss.scheduled_date DESC, ss.scheduled_start_time
            LIMIT 200
        ");
        $currentSurgeries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->query("
            SELECT sal.action_type, sal.action_description, sal.created_at, ss.patient_name, ss.scheduled_date
            FROM surgery_audit_log sal
            LEFT JOIN surgery_schedule ss ON sal.surgery_id = ss.id
            ORDER BY sal.created_at DESC
            LIMIT 200
        ");
        $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->query("
            SELECT sr.report_type, sr.report_date, sr.metrics, sr.created_at, or_rooms.room_number, s.first_name as surgeon_first_name, s.last_name as surgeon_last_name
            FROM surgery_reports sr
            LEFT JOIN operating_rooms or_rooms ON sr.or_id = or_rooms.id
            LEFT JOIN surgeons s ON sr.surgeon_id = s.id
            ORDER BY sr.created_at DESC
            LIMIT 100
        ");
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $currentSurgeries = [];
        $auditLog = [];
        $reports = [];
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="or_utilization_reports_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM
    // Section: Surgery Schedule (last 30 days)
    fputcsv($out, ['Surgery Schedule (last 30 days)']);
    fputcsv($out, ['Patient', 'Procedure', 'Surgeon', 'OR', 'Date', 'Start', 'End', 'Status']);
    foreach ($currentSurgeries as $s) {
        fputcsv($out, [
            $s['patient_name'] ?? '',
            $s['procedure_name'] ?? '',
            trim(($s['surgeon_first_name'] ?? '') . ' ' . ($s['surgeon_last_name'] ?? '')),
            $s['room_number'] ?? '',
            $s['scheduled_date'] ?? '',
            $s['scheduled_start_time'] ?? '',
            $s['scheduled_end_time'] ?? '',
            $s['status'] ?? ''
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Audit Log']);
    fputcsv($out, ['Timestamp', 'Action', 'Patient', 'Description']);
    foreach ($auditLog as $log) {
        fputcsv($out, [
            $log['created_at'] ?? '',
            $log['action_type'] ?? '',
            $log['patient_name'] ?? '',
            $log['action_description'] ?? ''
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Recent Reports']);
    fputcsv($out, ['Report Type', 'Report Date', 'OR/Surgeon', 'Generated At', 'Metrics (summary)']);
    foreach ($reports as $r) {
        $orSurgeon = $r['room_number'] ?: trim(($r['surgeon_first_name'] ?? '') . ' ' . ($r['surgeon_last_name'] ?? '')) ?: 'All';
        $metrics = $r['metrics'] ? json_decode($r['metrics'], true) : [];
        $summary = is_array($metrics) ? count($metrics) . ' metric(s)' : substr($r['metrics'], 0, 80);
        fputcsv($out, [
            $r['report_type'] ?? '',
            $r['report_date'] ?? '',
            $orSurgeon,
            $r['created_at'] ?? '',
            $summary
        ]);
    }
    fclose($out);
    exit;
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_report') {
    try {
        $reportType = $_POST['report_type'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $orId = $_POST['or_id'] ?? null;
        $surgeonId = $_POST['surgeon_id'] ?? null;
        
        // Calculate metrics based on report type
        $metrics = [];
        
        if ($reportType === 'Utilization') {
            $stmt = $db->prepare("
                SELECT 
                    or_rooms.room_number,
                    COUNT(DISTINCT ss.id) as total_surgeries,
                    SUM(TIMESTAMPDIFF(MINUTE, CONCAT(ss.scheduled_date, ' ', ss.scheduled_start_time), CONCAT(ss.scheduled_date, ' ', ss.scheduled_end_time))) as total_minutes,
                    COUNT(DISTINCT DATE(ss.scheduled_date)) as days_used
                FROM surgery_schedule ss
                LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
                WHERE ss.scheduled_date BETWEEN ? AND ?
                " . ($orId ? "AND ss.or_id = ?" : "") . "
                GROUP BY ss.or_id
            ");
            $params = [$startDate, $endDate];
            if ($orId) $params[] = $orId;
            $stmt->execute($params);
            $utilization = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($utilization as $util) {
                $availableHours = $util['days_used'] * 8; // Assuming 8 hours per day
                $usedHours = $util['total_minutes'] / 60;
                $utilizationRate = $availableHours > 0 ? ($usedHours / $availableHours) * 100 : 0;
                
                $metrics[] = [
                    'or' => $util['room_number'],
                    'utilization_rate' => round($utilizationRate, 2),
                    'total_surgeries' => $util['total_surgeries'],
                    'total_hours' => round($usedHours, 2)
                ];
            }
        } elseif ($reportType === 'Surgeon Productivity') {
            $stmt = $db->prepare("
                SELECT 
                    s.first_name,
                    s.last_name,
                    COUNT(ss.id) as surgeries_completed,
                    AVG(TIMESTAMPDIFF(MINUTE, CONCAT(ss.scheduled_date, ' ', ss.scheduled_start_time), CONCAT(ss.scheduled_date, ' ', ss.scheduled_end_time))) as avg_duration
                FROM surgery_schedule ss
                LEFT JOIN surgeons s ON ss.surgeon_id = s.id
                WHERE ss.scheduled_date BETWEEN ? AND ? AND ss.status = 'Completed'
                " . ($surgeonId ? "AND ss.surgeon_id = ?" : "") . "
                GROUP BY ss.surgeon_id
            ");
            $params = [$startDate, $endDate];
            if ($surgeonId) $params[] = $surgeonId;
            $stmt->execute($params);
            $productivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($productivity as $prod) {
                $metrics[] = [
                    'surgeon' => $prod['first_name'] . ' ' . $prod['last_name'],
                    'surgeries_completed' => $prod['surgeries_completed'],
                    'average_duration' => round($prod['avg_duration'], 2)
                ];
            }
        } elseif ($reportType === 'Turnaround Time') {
            $stmt = $db->prepare("
                SELECT 
                    or_rooms.room_number,
                    AVG(TIMESTAMPDIFF(MINUTE, 
                        (SELECT actual_end_time FROM surgery_schedule ss2 WHERE ss2.or_id = ss.or_id AND ss2.scheduled_date = ss.scheduled_date AND ss2.id < ss.id ORDER BY ss2.scheduled_end_time DESC LIMIT 1),
                        CONCAT(ss.scheduled_date, ' ', ss.scheduled_start_time)
                    )) as avg_turnaround
                FROM surgery_schedule ss
                LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
                WHERE ss.scheduled_date BETWEEN ? AND ? AND ss.status = 'Completed'
                " . ($orId ? "AND ss.or_id = ?" : "") . "
                GROUP BY ss.or_id
            ");
            $params = [$startDate, $endDate];
            if ($orId) $params[] = $orId;
            $stmt->execute($params);
            $turnaround = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($turnaround as $turn) {
                $metrics[] = [
                    'or' => $turn['room_number'],
                    'average_turnaround' => round($turn['avg_turnaround'] ?? 0, 2)
                ];
            }
        }
        
        // Save report
        $stmt = $db->prepare("INSERT INTO surgery_reports (report_type, report_date, or_id, surgeon_id, metrics, generated_by) VALUES (?, CURDATE(), ?, ?, ?, ?)");
        $stmt->execute([$reportType, $orId, $surgeonId, json_encode($metrics), $_SESSION['user_id']]);
        
        $message = 'Report generated successfully';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch current surgeries for monitoring
$currentSurgeries = [];
$monitoringData = [];

try {
    // Get in-progress surgeries
    $stmt = $db->query("
        SELECT 
            ss.*,
            pc.procedure_name,
            s.first_name as surgeon_first_name,
            s.last_name as surgeon_last_name,
            or_rooms.room_number,
            (SELECT progress_percentage FROM surgery_monitoring WHERE surgery_id = ss.id ORDER BY recorded_at DESC LIMIT 1) as progress,
            (SELECT vital_signs FROM surgery_monitoring WHERE surgery_id = ss.id ORDER BY recorded_at DESC LIMIT 1) as vital_signs
        FROM surgery_schedule ss
        LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
        LEFT JOIN surgeons s ON ss.surgeon_id = s.id
        LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
        WHERE ss.status = 'In Progress'
        ORDER BY ss.scheduled_start_time
    ");
    $currentSurgeries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get audit log
    $auditLog = [];
    $stmt = $db->query("
        SELECT 
            sal.*,
            ss.patient_name,
            ss.scheduled_date
        FROM surgery_audit_log sal
        LEFT JOIN surgery_schedule ss ON sal.surgery_id = ss.id
        ORDER BY sal.created_at DESC
        LIMIT 100
    ");
    $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent reports
    $reports = [];
    $stmt = $db->query("
        SELECT 
            sr.*,
            or_rooms.room_number,
            s.first_name as surgeon_first_name,
            s.last_name as surgeon_last_name
        FROM surgery_reports sr
        LEFT JOIN operating_rooms or_rooms ON sr.or_id = or_rooms.id
        LEFT JOIN surgeons s ON sr.surgeon_id = s.id
        ORDER BY sr.created_at DESC
        LIMIT 20
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get supporting data
    $stmt = $db->query("SELECT * FROM operating_rooms ORDER BY room_number");
    $operatingRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT * FROM surgeons WHERE status = 'active' ORDER BY last_name, first_name");
    $surgeons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('SORS Monitoring error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">OR Utilization & Reports</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                OR Utilization & Reports
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Live OR status, surgery audit trail, and reports: OR utilization, surgeon productivity, turnaround time for performance review and planning.
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

    <!-- Real-time Monitoring Dashboard -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Surgeries</h2>
        
        <?php if (empty($currentSurgeries)): ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No surgeries currently in progress</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($currentSurgeries as $surgery): 
                    $progress = $surgery['progress'] ?? 0;
                    $vitals = $surgery['vital_signs'] ? json_decode($surgery['vital_signs'], true) : null;
                ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($surgery['patient_name']); ?></h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($surgery['procedure_name']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">OR: <?php echo htmlspecialchars($surgery['room_number']); ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded">
                                In Progress
                            </span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                                <span>Progress</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Vitals -->
                        <?php if ($vitals): ?>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">BP:</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($vitals['bp'] ?? 'N/A'); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">HR:</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($vitals['heart_rate'] ?? 'N/A'); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">O2 Sat:</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($vitals['oxygen_sat'] ?? 'N/A'); ?>%</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Temp:</span>
                                    <span class="text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($vitals['temperature'] ?? 'N/A'); ?>°C</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            Surgeon: <?php echo htmlspecialchars($surgery['surgeon_first_name'] . ' ' . $surgery['surgeon_last_name']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Report Generator -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Generate Report</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="generate_report">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Report Type *</label>
                    <select name="report_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="Utilization">OR Utilization Rates</option>
                        <option value="Surgeon Productivity">Surgeon Productivity</option>
                        <option value="Turnaround Time">Turnaround Time</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operating Room</label>
                    <select name="or_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">All ORs</option>
                        <?php foreach ($operatingRooms as $or): ?>
                            <option value="<?php echo $or['id']; ?>"><?php echo htmlspecialchars($or['room_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                    <input type="date" name="start_date" required value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date *</label>
                    <input type="date" name="end_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Surgeon (for Productivity Report)</label>
                <select name="surgeon_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Surgeons</option>
                    <?php foreach ($surgeons as $surgeon): ?>
                        <option value="<?php echo $surgeon['id']; ?>">
                            <?php echo htmlspecialchars($surgeon['first_name'] . ' ' . $surgeon['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Generate Report
                </button>
                <button type="button" onclick="exportReport()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Export CSV
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Reports -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Reports</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Report Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">OR / Surgeon</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No reports generated yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): 
                            $metrics = json_decode($report['metrics'], true);
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($report['report_type']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php 
                                    if ($report['room_number']) {
                                        echo htmlspecialchars($report['room_number']);
                                    } elseif ($report['surgeon_first_name']) {
                                        echo htmlspecialchars($report['surgeon_first_name'] . ' ' . $report['surgeon_last_name']);
                                    } else {
                                        echo 'All';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <button onclick="viewReport(<?php echo $report['id']; ?>)" class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="View Details"><?php echo icon_view('w-4 h-4'); ?>View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit Log -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Audit Log</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($auditLog)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No audit log entries</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditLog as $log): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($log['action_type']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($log['patient_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($log['action_description'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Report Modal -->
<div id="viewReportModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeViewReportModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Report Details</h3>
            <div id="viewReportContent" class="text-gray-700 dark:text-gray-300"></div>
            <div class="flex justify-end mt-6">
                <button onclick="closeViewReportModal()" 
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Reports data for viewing
const reportsData = <?php echo json_encode($reports ?? []); ?>;

function viewReport(reportId) {
    const report = reportsData.find(r => r.id == reportId);
    if (!report) {
        // If report not found in data, show basic info
        const modal = document.getElementById('viewReportModal');
        if (modal) {
            document.getElementById('viewReportContent').innerHTML = `
                <div class="text-center py-4">
                    <p class="text-gray-500 dark:text-gray-400">Report ID: ${reportId}</p>
                    <p class="text-sm text-gray-400 mt-2">Full report details not available in current view.</p>
                </div>
            `;
            modal.classList.remove('hidden');
        }
        return;
    }
    
    const modal = document.getElementById('viewReportModal');
    if (modal) {
        let metricsHtml = '';
        if (report.metrics) {
            try {
                const metrics = typeof report.metrics === 'string' ? JSON.parse(report.metrics) : report.metrics;
                metricsHtml = '<div class="grid grid-cols-2 gap-4 mt-4">';
                for (const [key, value] of Object.entries(metrics)) {
                    metricsHtml += `<div><strong>${key.replace(/_/g, ' ')}:</strong> ${value}</div>`;
                }
                metricsHtml += '</div>';
            } catch (e) {
                metricsHtml = `<div class="mt-4"><strong>Metrics:</strong> ${report.metrics}</div>`;
            }
        }
        
        document.getElementById('viewReportContent').innerHTML = `
            <div class="space-y-3">
                <div><strong>Report Type:</strong> ${report.report_type || 'N/A'}</div>
                <div><strong>Report Date:</strong> ${report.report_date || 'N/A'}</div>
                <div><strong>OR Room:</strong> ${report.room_number || 'All'}</div>
                <div><strong>Surgeon:</strong> ${report.surgeon_first_name ? report.surgeon_first_name + ' ' + report.surgeon_last_name : 'All'}</div>
                <div><strong>Generated:</strong> ${report.created_at || 'N/A'}</div>
                ${metricsHtml}
            </div>
        `;
        modal.classList.remove('hidden');
    }
}

function closeViewReportModal() {
    document.getElementById('viewReportModal').classList.add('hidden');
}

// Auto-refresh monitoring every 30 seconds
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 30000);
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Surgery Schedule & Block Time - OR & Surgery Management';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_schedule':
                    $stmt = $db->prepare("INSERT INTO surgery_schedule (patient_id, patient_name, procedure_id, surgeon_id, anesthesiologist_id, or_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, priority, estimated_duration, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['patient_id'],
                        $_POST['patient_name'],
                        $_POST['procedure_id'],
                        $_POST['surgeon_id'],
                        $_POST['anesthesiologist_id'] ?: null,
                        $_POST['or_id'],
                        $_POST['scheduled_date'],
                        $_POST['scheduled_start_time'],
                        $_POST['scheduled_end_time'],
                        'Scheduled',
                        $_POST['priority'] ?? 'Routine',
                        $_POST['estimated_duration'],
                        $_POST['notes'] ?? null,
                        $_SESSION['user_id']
                    ]);
                    $message = 'Surgery scheduled successfully';
                    $messageType = 'success';
                    break;
                
                case 'create_block':
                    // Database expects DATETIME format, so use placeholder date with time
                    $startTime = $_POST['start_time'];
                    $endTime = $_POST['end_time'];
                    
                    // Convert time to full datetime using a placeholder date (2000-01-01)
                    if (strpos($startTime, 'T') !== false) {
                        // Already has date component
                        $startTime = date('Y-m-d H:i:s', strtotime($startTime));
                    } else {
                        // Time only - add placeholder date
                        $startTime = '2000-01-01 ' . $startTime . ':00';
                    }
                    
                    if (strpos($endTime, 'T') !== false) {
                        $endTime = date('Y-m-d H:i:s', strtotime($endTime));
                    } else {
                        $endTime = '2000-01-01 ' . $endTime . ':00';
                    }
                    
                    $stmt = $db->prepare("INSERT INTO surgery_blocks (block_name, surgeon_id, or_id, start_time, end_time, day_of_week, is_recurring, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['block_name'],
                        $_POST['surgeon_id'],
                        $_POST['or_id'],
                        $startTime,
                        $endTime,
                        $_POST['day_of_week'],
                        isset($_POST['is_recurring']) ? 1 : 0,
                        'Active'
                    ]);
                    $message = 'Surgery block created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_schedule':
                    $stmt = $db->prepare("UPDATE surgery_schedule SET scheduled_date = ?, scheduled_start_time = ?, scheduled_end_time = ?, or_id = ?, notes = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['scheduled_date'],
                        $_POST['scheduled_start_time'],
                        $_POST['scheduled_end_time'],
                        $_POST['or_id'],
                        $_POST['notes'] ?? null,
                        $_POST['schedule_id']
                    ]);
                    $message = 'Schedule updated successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get view parameters (and optional assign from Doctor Availability page)
$view = $_GET['view'] ?? 'week';
$currentDate = $_GET['date'] ?? date('Y-m-d');
$assignSurgeonId = isset($_GET['assign_surgeon_id']) ? (int)$_GET['assign_surgeon_id'] : null;
$assignDate = isset($_GET['date']) ? $_GET['date'] : null;
$startDate = $view === 'week' ? date('Y-m-d', strtotime('monday this week', strtotime($currentDate))) : $currentDate;
$endDate = $view === 'week' ? date('Y-m-d', strtotime('sunday this week', strtotime($currentDate))) : $currentDate;

// Fetch schedules
$schedules = [];
$blocks = [];
$conflicts = [];
$procedures = [];
$surgeons = [];
$anesthesiologists = [];
$operatingRooms = [];

try {
    // Get supporting data first (for forms and for seed when schedule is empty)
    try {
        $stmt = $db->query("SELECT * FROM procedure_catalog ORDER BY procedure_name");
        $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $procedures = []; }
    try {
        $stmt = $db->query("SELECT * FROM surgeons WHERE status = 'active' ORDER BY last_name, first_name");
        $surgeons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $surgeons = []; }
    try {
        $stmt = $db->query("SELECT * FROM anesthesiologists WHERE status = 'active' ORDER BY last_name, first_name");
        $anesthesiologists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $anesthesiologists = []; }
    try {
        $stmt = $db->query("SELECT * FROM operating_rooms WHERE status IN ('Ready', 'In Use') ORDER BY room_number");
        $operatingRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $operatingRooms = []; }

    // Get schedules for the week
    try {
        $stmt = $db->prepare("
            SELECT 
                ss.*,
                pc.procedure_name,
                pc.procedure_code,
                s.first_name as surgeon_first_name,
                s.last_name as surgeon_last_name,
                s.specialization,
                a.first_name as anes_first_name,
                a.last_name as anes_last_name,
                or_rooms.room_number,
                or_rooms.room_type
            FROM surgery_schedule ss
            LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
            LEFT JOIN surgeons s ON ss.surgeon_id = s.id
            LEFT JOIN anesthesiologists a ON ss.anesthesiologist_id = a.id
            LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
            WHERE ss.scheduled_date BETWEEN ? AND ?
            ORDER BY ss.scheduled_date, ss.scheduled_start_time
        ");
        $stmt->execute([$startDate, $endDate]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $schedules = [];
    }

    // If no schedules for this week but we have surgeons/procedures/ORs (Philippine HMS), seed sample data for the displayed week
    if (empty($schedules) && !empty($surgeons) && !empty($procedures) && !empty($operatingRooms)) {
        $phPatients = [
            'Rosa Almario', 'Emilio Santos Jr.', 'Lorna Dimaguiba', 'Gregorio Villanueva', 'Cecilia Bautista',
            'Arturo Reyes', 'Imelda Cruz', 'Rodrigo Mendoza', 'Aurora Santiago', 'Benito Lopez',
            'Corazon Abad', 'Felipe Navarro', 'Gloria Estrella', 'Hector dela Rosa', 'Irene Tan',
            'Jose Maria Flores', 'Kristina Morales', 'Leonardo Gutierrez', 'Maria Clara Reyes', 'Pedro Bautista',
        ];
        $insertSchedule = $db->prepare("
            INSERT INTO surgery_schedule (patient_id, patient_name, procedure_id, surgeon_id, anesthesiologist_id, or_id, block_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, priority, estimated_duration, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'Scheduled', ?, ?, NULL, ?)
        ");
        $userId = $_SESSION['user_id'] ?? 1;
        $anesId = !empty($anesthesiologists) ? (int)$anesthesiologists[0]['id'] : null;
        $dayStart = strtotime($startDate);
        $dayEnd = strtotime($endDate);
        $idx = 0;
        for ($d = $dayStart; $d <= $dayEnd; $d += 86400) {
            $dateStr = date('Y-m-d', $d);
            $dayOfWeek = date('w', $d); // 0 Sun .. 6 Sat; skip Sunday 0 or reduce slots
            $slots = $dayOfWeek == 0 ? 1 : ($dayOfWeek == 6 ? 2 : 3); // Sun 1, Sat 2, Weekdays 3
            for ($s = 0; $s < $slots; $s++) {
                $hour = 8 + ($s * 3);
                $dur = [60, 90, 120][$s % 3];
                $proc = $procedures[$idx % count($procedures)];
                $surgeon = $surgeons[$idx % count($surgeons)];
                $or = $operatingRooms[$idx % count($operatingRooms)];
                $patientName = $phPatients[$idx % count($phPatients)];
                $patientId = 'P-' . date('Y', $d) . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
                $startTime = sprintf('%02d:00:00', $hour);
                $endHour = $hour + (int)ceil($dur / 60);
                $endTime = sprintf('%02d:00:00', $endHour);
                $insertSchedule->execute([
                    $patientId, $patientName, $proc['id'], $surgeon['id'], $anesId, $or['id'],
                    $dateStr, $startTime, $endTime, $dur <= 60 ? 'Routine' : 'Routine', $dur, $userId
                ]);
                $idx++;
            }
        }
        $stmt = $db->prepare("
            SELECT ss.*, pc.procedure_name, pc.procedure_code, s.first_name as surgeon_first_name, s.last_name as surgeon_last_name, s.specialization,
                   a.first_name as anes_first_name, a.last_name as anes_last_name, or_rooms.room_number, or_rooms.room_type
            FROM surgery_schedule ss
            LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
            LEFT JOIN surgeons s ON ss.surgeon_id = s.id
            LEFT JOIN anesthesiologists a ON ss.anesthesiologist_id = a.id
            LEFT JOIN operating_rooms or_rooms ON ss.or_id = or_rooms.id
            WHERE ss.scheduled_date BETWEEN ? AND ?
            ORDER BY ss.scheduled_date, ss.scheduled_start_time
        ");
        $stmt->execute([$startDate, $endDate]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get blocks - wrap in separate try-catch
    try {
        $stmt = $db->query("
            SELECT 
                sb.*,
                s.first_name as surgeon_first_name,
                s.last_name as surgeon_last_name,
                or_rooms.room_number
            FROM surgery_blocks sb
            LEFT JOIN surgeons s ON sb.surgeon_id = s.id
            LEFT JOIN operating_rooms or_rooms ON sb.or_id = or_rooms.id
            WHERE sb.status = 'Active'
        ");
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Blocks query error: ' . $e->getMessage());
        $blocks = [];
    }
    
    // Detect conflicts
    foreach ($schedules as $i => $schedule1) {
        foreach ($schedules as $j => $schedule2) {
            if ($i < $j && $schedule1['or_id'] == $schedule2['or_id'] && $schedule1['scheduled_date'] == $schedule2['scheduled_date']) {
                $start1 = strtotime($schedule1['scheduled_date'] . ' ' . $schedule1['scheduled_start_time']);
                $end1 = strtotime($schedule1['scheduled_date'] . ' ' . $schedule1['scheduled_end_time']);
                $start2 = strtotime($schedule2['scheduled_date'] . ' ' . $schedule2['scheduled_start_time']);
                $end2 = strtotime($schedule2['scheduled_date'] . ' ' . $schedule2['scheduled_end_time']);
                
                if (($start1 < $end2 && $end1 > $start2)) {
                    $conflicts[] = [
                        'schedule1' => $schedule1,
                        'schedule2' => $schedule2
                    ];
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('SORS Scheduling error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Surgery Schedule & Block Time</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Surgery Schedule & Block Time
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Book elective and emergency surgeries, assign surgeon and anesthesiologist, assign OR and block time. View by day or week with conflict checks.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/modules/sors/doctor_availability.php?view=<?php echo urlencode($view); ?>&date=<?php echo urlencode($currentDate); ?>"
               class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Doctor Availability
            </a>
            <button onclick="openCreateScheduleModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create New Block
            </button>
        </div>
    </div>

    <?php if (empty($surgeons) && empty($schedules)): ?>
        <div class="rounded-md p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
            <p class="text-sm text-amber-800 dark:text-amber-200">
                No surgery data yet. Run the SORS migration and seed to load surgeons, procedures, and ORs: 
                <a href="<?php echo BASE_URL; ?>/database/run_migrations_and_seeders.php" class="underline font-medium">Run migrations &amp; seeders</a>.
                After that, refresh this page to see the schedule or create new blocks.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php 
            echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 
            ($messageType === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 
            'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'); 
        ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Conflict Alerts -->
    <?php if (!empty($conflicts)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">Scheduling Conflicts Detected</h3>
            </div>
            <div class="space-y-2">
                <?php foreach ($conflicts as $conflict): ?>
                    <p class="text-sm text-red-700 dark:text-red-300">
                        Conflict in <?php echo htmlspecialchars($conflict['schedule1']['room_number']); ?> on <?php echo date('M d, Y', strtotime($conflict['schedule1']['scheduled_date'])); ?>: 
                        <?php echo htmlspecialchars($conflict['schedule1']['patient_name']); ?> (<?php echo date('H:i', strtotime($conflict['schedule1']['scheduled_start_time'])); ?>) 
                        overlaps with 
                        <?php echo htmlspecialchars($conflict['schedule2']['patient_name']); ?> (<?php echo date('H:i', strtotime($conflict['schedule2']['scheduled_start_time'])); ?>)
                    </p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- View Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-2">
                <?php
                $prevDate = $view === 'week' ? date('Y-m-d', strtotime($currentDate . ' -7 days')) : date('Y-m-d', strtotime($currentDate . ' -1 day'));
                $nextDate = $view === 'week' ? date('Y-m-d', strtotime($currentDate . ' +7 days')) : date('Y-m-d', strtotime($currentDate . ' +1 day'));
                ?>
                <a href="?view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Prev
                </a>
                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Today
                </a>
                <a href="?view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center gap-1">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
            <div class="flex gap-2">
                <a href="?view=day&date=<?php echo $currentDate; ?>" class="px-4 py-2 <?php echo $view === 'day' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> rounded-lg hover:bg-primary-700">
                    Day
                </a>
                <a href="?view=week&date=<?php echo $currentDate; ?>" class="px-4 py-2 <?php echo $view === 'week' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> rounded-lg hover:bg-primary-700">
                    Week
                </a>
            </div>
            <div>
                <input type="date" value="<?php echo $currentDate; ?>" onchange="window.location.href='?view=<?php echo $view; ?>&date=' + this.value" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 mb-4 text-xs">
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-purple-100 dark:bg-purple-900/20 border border-purple-300 dark:border-purple-700"></span>
            <span class="text-gray-600 dark:text-gray-400">Block Time</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700"></span>
            <span class="text-gray-600 dark:text-gray-400">Scheduled Surgery</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-green-100 dark:bg-green-900/20 border border-green-300 dark:border-green-700"></span>
            <span class="text-gray-600 dark:text-gray-400">In Progress</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></span>
            <span class="text-gray-600 dark:text-gray-400">Completed</span>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if ($view === 'week'): ?>
            <!-- Week View -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                            <?php 
                            $current = strtotime($startDate);
                            $end = strtotime($endDate);
                            while ($current <= $end): 
                            ?>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    <?php echo date('D M d', $current); ?>
                                </th>
                            <?php 
                                $current = strtotime('+1 day', $current);
                            endwhile; 
                            ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php for ($hour = 6; $hour < 20; $hour++): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"><?php echo str_pad($hour, 2, '0', STR_PAD_LEFT); ?>:00</td>
                                <?php 
                                $current = strtotime($startDate);
                                $end = strtotime($endDate);
                                while ($current <= $end): 
                                    $dateStr = date('Y-m-d', $current);
                                    $timeStr = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
                                ?>
                                    <td class="px-2 py-1 min-h-[60px]">
                                        <?php 
                                        // Show blocks for this day/time
                                        $dayName = date('l', $current); // Monday, Tuesday, etc.
                                        $cellTime = strtotime($dateStr . ' ' . $timeStr);
                                        $cellHour = $hour;
                                        
                                        // Filter blocks that match this day and overlap this hour
                                        $dayBlocks = array_filter($blocks, function($b) use ($dayName, $cellHour) {
                                            if ($b['day_of_week'] !== $dayName) return false;
                                            
                                            // Extract hour from start_time and end_time (handles DATETIME format like '2000-01-01 08:00:00')
                                            $blockStartHour = (int)date('H', strtotime($b['start_time']));
                                            $blockEndHour = (int)date('H', strtotime($b['end_time']));
                                            
                                            // Show block in all cells it spans
                                            return $cellHour >= $blockStartHour && $cellHour < $blockEndHour;
                                        });
                                        
                                        foreach ($dayBlocks as $block):
                                            // Only show the block info in the first cell (start hour)
                                            $blockStartHour = (int)date('H', strtotime($block['start_time']));
                                            if ($cellHour == $blockStartHour):
                                        ?>
                                            <div class="mb-1 p-2 rounded border text-xs bg-purple-100 dark:bg-purple-900/20 border-purple-300 dark:border-purple-700 cursor-pointer hover:opacity-80">
                                                <div class="font-semibold text-purple-800 dark:text-purple-300">
                                                    <?php echo htmlspecialchars($block['block_name']); ?>
                                                </div>
                                                <div class="text-purple-600 dark:text-purple-400">
                                                    <?php echo htmlspecialchars(($block['surgeon_first_name'] ?? '') . ' ' . ($block['surgeon_last_name'] ?? '')); ?>
                                                </div>
                                                <div class="text-purple-500 dark:text-purple-500">
                                                    OR: <?php echo htmlspecialchars($block['room_number'] ?? 'N/A'); ?>
                                                </div>
                                                <div class="text-purple-500 dark:text-purple-500">
                                                    <?php echo date('H:i', strtotime($block['start_time'])) . ' - ' . date('H:i', strtotime($block['end_time'])); ?>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        
                                        // Show scheduled surgeries
                                        $daySchedules = array_filter($schedules, function($s) use ($dateStr, $timeStr) {
                                            $scheduleTime = strtotime($s['scheduled_date'] . ' ' . $s['scheduled_start_time']);
                                            $cellTime = strtotime($dateStr . ' ' . $timeStr);
                                            return $s['scheduled_date'] === $dateStr && 
                                                   $scheduleTime >= $cellTime && 
                                                   $scheduleTime < strtotime('+1 hour', $cellTime);
                                        });
                                        foreach ($daySchedules as $schedule):
                                            $statusColors = [
                                                'Scheduled' => 'bg-blue-100 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700',
                                                'In Progress' => 'bg-green-100 dark:bg-green-900/20 border-green-300 dark:border-green-700',
                                                'Completed' => 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600'
                                            ];
                                            $color = $statusColors[$schedule['status']] ?? 'bg-gray-100 dark:bg-gray-700';
                                        ?>
                                            <div class="mb-1 p-2 rounded border text-xs <?php echo $color; ?> cursor-pointer hover:opacity-80" onclick="openEditScheduleModal(<?php echo $schedule['id']; ?>)">
                                                <div class="font-semibold text-gray-900 dark:text-white">
                                                    <?php echo htmlspecialchars($schedule['patient_name']); ?>
                                                    <span class="font-normal text-gray-500">(<?php echo htmlspecialchars($schedule['patient_id'] ?? 'N/A'); ?>)</span>
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($schedule['procedure_name']); ?></div>
                                                <div class="text-gray-500 dark:text-gray-500"><?php echo htmlspecialchars($schedule['surgeon_first_name'] . ' ' . $schedule['surgeon_last_name']); ?></div>
                                                <div class="text-gray-500 dark:text-gray-500"><?php echo date('H:i', strtotime($schedule['scheduled_start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['scheduled_end_time'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php 
                                    $current = strtotime('+1 day', $current);
                                endwhile; 
                                ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Day View -->
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <?php echo date('l, F d, Y', strtotime($currentDate)); ?>
                </h3>
                
                <?php 
                // Get blocks for this day of week
                $currentDayName = date('l', strtotime($currentDate));
                $dayBlocks = array_filter($blocks, function($b) use ($currentDayName) {
                    return $b['day_of_week'] === $currentDayName;
                });
                
                // Show blocks first
                if (!empty($dayBlocks)): 
                ?>
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-purple-700 dark:text-purple-400 mb-2">Block Times</h4>
                    <div class="space-y-2">
                        <?php foreach ($dayBlocks as $block): ?>
                            <div class="p-3 rounded-lg border bg-purple-100 dark:bg-purple-900/20 border-purple-300 dark:border-purple-700">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-purple-800 dark:text-purple-300"><?php echo htmlspecialchars($block['block_name']); ?></h4>
                                        <p class="text-sm text-purple-600 dark:text-purple-400">
                                            Surgeon: <?php echo htmlspecialchars(($block['surgeon_first_name'] ?? '') . ' ' . ($block['surgeon_last_name'] ?? '')); ?>
                                        </p>
                                        <p class="text-sm text-purple-600 dark:text-purple-400">
                                            OR: <?php echo htmlspecialchars($block['room_number'] ?? 'N/A'); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-purple-800 dark:text-purple-300">
                                            <?php echo date('H:i', strtotime($block['start_time'])); ?> - <?php echo date('H:i', strtotime($block['end_time'])); ?>
                                        </p>
                                        <span class="px-2 py-1 text-xs rounded bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200">
                                            Block Time
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="space-y-4">
                    <?php 
                    $daySchedules = array_filter($schedules, function($s) use ($currentDate) {
                        return $s['scheduled_date'] === $currentDate;
                    });
                    if (empty($daySchedules) && empty($dayBlocks)): 
                    ?>
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">No surgeries or blocks scheduled for this day</p>
                    <?php elseif (!empty($daySchedules)): ?>
                        <?php foreach ($daySchedules as $schedule): 
                            $statusColors = [
                                'Scheduled' => 'bg-blue-100 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700',
                                'In Progress' => 'bg-green-100 dark:bg-green-900/20 border-green-300 dark:border-green-700',
                                'Completed' => 'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600'
                            ];
                            $color = $statusColors[$schedule['status']] ?? 'bg-gray-100 dark:bg-gray-700';
                        ?>
                            <div class="p-4 rounded-lg border <?php echo $color; ?>">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($schedule['patient_name']); ?>
                                            <span class="font-normal text-sm text-gray-500">(<?php echo htmlspecialchars($schedule['patient_id'] ?? 'N/A'); ?>)</span>
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($schedule['procedure_name']); ?></p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Surgeon: <?php echo htmlspecialchars($schedule['surgeon_first_name'] . ' ' . $schedule['surgeon_last_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            OR: <?php echo htmlspecialchars($schedule['room_number']); ?> (<?php echo htmlspecialchars($schedule['room_type']); ?>)
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo date('H:i', strtotime($schedule['scheduled_start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($schedule['scheduled_end_time'])); ?>
                                        </p>
                                        <span class="px-2 py-1 text-xs rounded <?php echo $color; ?>">
                                            <?php echo htmlspecialchars($schedule['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Schedule Modal -->
<div id="createScheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeCreateScheduleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Surgery Block</h3>
            <form method="POST" id="createScheduleForm">
                <input type="hidden" name="action" value="create_block">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Block Name *</label>
                        <input type="text" name="block_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Day of Week *</label>
                        <select name="day_of_week" id="blockDayOfWeek" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <?php 
                            $assignDayName = $assignDate ? date('l', strtotime($assignDate)) : '';
                            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                            foreach ($days as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo ($assignDayName === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Surgeon *</label>
                        <select name="surgeon_id" id="blockSurgeonId" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Surgeon</option>
                            <?php foreach ($surgeons as $surgeon): ?>
                                <option value="<?php echo $surgeon['id']; ?>" <?php echo ($assignSurgeonId && $assignSurgeonId === (int)$surgeon['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($surgeon['first_name'] . ' ' . $surgeon['last_name'] . ' - ' . $surgeon['specialization']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operating Room *</label>
                        <select name="or_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select OR</option>
                            <?php foreach ($operatingRooms as $or): ?>
                                <option value="<?php echo $or['id']; ?>">
                                    <?php echo htmlspecialchars($or['room_number'] . ' - ' . $or['room_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Time *</label>
                        <input type="time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Time *</label>
                        <input type="time" name="end_time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_recurring" class="rounded border-gray-300 dark:border-gray-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Recurring Block</span>
                    </label>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeCreateScheduleModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Create Block
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editScheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeEditScheduleModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Surgery Schedule</h3>
            <form method="POST" id="editScheduleForm">
                <input type="hidden" name="action" value="update_schedule">
                <input type="hidden" name="schedule_id" id="editScheduleId">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Patient Name</label>
                        <input type="text" id="editPatientName" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Patient ID</label>
                        <input type="text" id="editPatientId" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scheduled Date *</label>
                        <input type="date" name="scheduled_date" id="editScheduledDate" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Time *</label>
                        <input type="time" name="scheduled_start_time" id="editScheduledStartTime" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Time *</label>
                        <input type="time" name="scheduled_end_time" id="editScheduledEndTime" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operating Room *</label>
                    <select name="or_id" id="editOrId" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Select OR</option>
                        <?php foreach ($operatingRooms as $or): ?>
                            <option value="<?php echo $or['id']; ?>">
                                <?php echo htmlspecialchars($or['room_number'] . ' - ' . $or['room_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea name="notes" id="editNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeEditScheduleModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Schedule data for editing
const schedulesData = <?php echo json_encode($schedules); ?>;

function openCreateScheduleModal() {
    document.getElementById('createScheduleModal').classList.remove('hidden');
}

function closeCreateScheduleModal() {
    document.getElementById('createScheduleModal').classList.add('hidden');
    document.getElementById('createScheduleForm').reset();
    // Clear assign params from URL so refresh doesn't reopen
    if (window.location.search && window.location.search.indexOf('assign_surgeon_id') !== -1) {
        const u = new URL(window.location.href);
        u.searchParams.delete('assign_surgeon_id');
        window.history.replaceState({}, '', u.toString());
    }
}

<?php if ($assignSurgeonId && $assignDate): ?>
document.addEventListener('DOMContentLoaded', function() {
    openCreateScheduleModal();
    var startInput = document.querySelector('input[name="start_time"]');
    var endInput = document.querySelector('input[name="end_time"]');
    if (startInput && endInput) {
        startInput.value = '<?php echo $assignDate; ?>T08:00';
        endInput.value = '<?php echo $assignDate; ?>T09:00';
    }
});
<?php endif; ?>

function openEditScheduleModal(scheduleId) {
    const schedule = schedulesData.find(s => s.id == scheduleId);
    if (!schedule) {
        alert('Schedule not found');
        return;
    }
    
    const modal = document.getElementById('editScheduleModal');
    if (!modal) return;
    
    document.getElementById('editScheduleId').value = schedule.id;
    document.getElementById('editPatientName').value = schedule.patient_name || '';
    document.getElementById('editPatientId').value = schedule.patient_id || '';
    document.getElementById('editScheduledDate').value = schedule.scheduled_date || '';
    document.getElementById('editScheduledStartTime').value = schedule.scheduled_start_time ? schedule.scheduled_start_time.substring(0,5) : '';
    document.getElementById('editScheduledEndTime').value = schedule.scheduled_end_time ? schedule.scheduled_end_time.substring(0,5) : '';
    document.getElementById('editOrId').value = schedule.or_id || '';
    document.getElementById('editNotes').value = schedule.notes || '';
    
    modal.classList.remove('hidden');
}

function closeEditScheduleModal() {
    document.getElementById('editScheduleModal').classList.add('hidden');
}

// Real-time refresh every 30 seconds
let refreshInterval;
function startAutoRefresh() {
    refreshInterval = setInterval(function() {
        if (document.visibilityState === 'visible' && 
            document.getElementById('createScheduleModal').classList.contains('hidden') &&
            document.getElementById('editScheduleModal').classList.contains('hidden')) {
            // Only refresh if no modals are open
            const indicator = document.createElement('div');
            indicator.className = 'fixed top-4 right-4 bg-primary-600 text-white px-3 py-1 rounded-full text-xs z-50';
            indicator.textContent = 'Refreshing...';
            indicator.id = 'refreshIndicator';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    }, 30000);
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', startAutoRefresh);
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

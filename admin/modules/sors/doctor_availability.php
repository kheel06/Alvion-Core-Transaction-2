<?php
/**
 * Real-time Doctor (Surgeon) Availability
 * Shows which surgeons are available vs busy (in surgery schedule). If not in a block, they can be assigned.
 */
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Doctor Availability - OR & Surgery Management';

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_availability') {
    header('Content-Type: application/json');
    // Return availability data as JSON for real-time updates
    $view = $_GET['view'] ?? 'week';
    $currentDate = $_GET['date'] ?? date('Y-m-d');
    $startDate = $view === 'week' ? date('Y-m-d', strtotime('monday this week', strtotime($currentDate))) : $currentDate;
    $endDate = $view === 'week' ? date('Y-m-d', strtotime('sunday this week', strtotime($currentDate))) : $currentDate;
    
    try {
        $surgeons = $db->query("SELECT id, first_name, last_name, specialization FROM surgeons WHERE status = 'active' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
        $busyData = [];
        
        $stmt = $db->prepare("SELECT surgeon_id, scheduled_date, scheduled_start_time, scheduled_end_time, patient_name FROM surgery_schedule WHERE scheduled_date BETWEEN ? AND ? AND status != 'Cancelled'");
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $busyData[$row['surgeon_id']][] = ['date' => $row['scheduled_date'], 'start' => substr($row['scheduled_start_time'], 0, 5), 'end' => substr($row['scheduled_end_time'], 0, 5), 'label' => $row['patient_name']];
        }
        
        $blocks = $db->query("SELECT surgeon_id, day_of_week, start_time, end_time, block_name FROM surgery_blocks WHERE status = 'Active' AND is_recurring = 1")->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'surgeons' => $surgeons, 'busy' => $busyData, 'blocks' => $blocks, 'startDate' => $startDate, 'endDate' => $endDate]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle POST - Quick Schedule Surgery
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'quick_schedule') {
            // Get user ID as integer (created_by expects INT)
            $createdBy = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 1;
            
            $stmt = $db->prepare("INSERT INTO surgery_schedule (patient_id, patient_name, procedure_id, surgeon_id, anesthesiologist_id, or_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, priority, estimated_duration, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled', ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['patient_id'],
                $_POST['patient_name'],
                $_POST['procedure_id'],
                $_POST['surgeon_id'],
                $_POST['anesthesiologist_id'] ?: null,
                $_POST['or_id'],
                $_POST['scheduled_date'],
                $_POST['start_time'] . ':00',
                $_POST['end_time'] . ':00',
                $_POST['priority'] ?? 'Routine',
                $_POST['duration'] ?? 60,
                $_POST['notes'] ?? null,
                $createdBy
            ]);
            $message = 'Surgery scheduled successfully!';
            $messageType = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// View params (same as schedule page)
$view = $_GET['view'] ?? 'week';
$currentDate = $_GET['date'] ?? date('Y-m-d');
$startDate = $view === 'week'
    ? date('Y-m-d', strtotime('monday this week', strtotime($currentDate)))
    : $currentDate;
$endDate = $view === 'week'
    ? date('Y-m-d', strtotime('sunday this week', strtotime($currentDate)))
    : $currentDate;

$surgeons = [];
$busyBySurgeon = [];
$blocksBySurgeon = [];
$procedures = [];
$anesthesiologists = [];
$operatingRooms = [];

try {
    $stmt = $db->query("SELECT id, employee_id, first_name, last_name, specialization FROM surgeons WHERE status = 'active' ORDER BY last_name, first_name");
    $surgeons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($surgeons as $s) {
        $busyBySurgeon[$s['id']] = [];
        $blocksBySurgeon[$s['id']] = [];
    }

    // Busy = in surgery_schedule (exclude Cancelled)
    $stmt = $db->prepare("
        SELECT ss.surgeon_id, ss.scheduled_date, ss.scheduled_start_time, ss.scheduled_end_time, ss.patient_name, pc.procedure_name
        FROM surgery_schedule ss
        LEFT JOIN procedure_catalog pc ON ss.procedure_id = pc.id
        WHERE ss.scheduled_date BETWEEN ? AND ?
        AND ss.status != 'Cancelled'
        ORDER BY ss.scheduled_date, ss.scheduled_start_time
    ");
    $stmt->execute([$startDate, $endDate]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sid = $row['surgeon_id'];
        if (!isset($busyBySurgeon[$sid])) $busyBySurgeon[$sid] = [];
        $busyBySurgeon[$sid][] = [
            'date' => $row['scheduled_date'],
            'start' => $row['scheduled_start_time'],
            'end' => $row['scheduled_end_time'],
            'procedure_name' => $row['procedure_name'] ?? 'Surgery',
            'patient_name' => $row['patient_name'] ?? '',
        ];
    }

    // Recurring blocks from Surgery Schedule & Block Time: surgeon busy during block time on matching day
    $stmt = $db->query("
        SELECT surgeon_id, block_name, day_of_week, start_time, end_time
        FROM surgery_blocks
        WHERE status = 'Active' AND is_recurring = 1
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sid = $row['surgeon_id'];
        if (!isset($blocksBySurgeon[$sid])) $blocksBySurgeon[$sid] = [];
        $blocksBySurgeon[$sid][] = [
            'day_of_week' => $row['day_of_week'],
            'start' => date('H:i', strtotime($row['start_time'])),
            'end' => date('H:i', strtotime($row['end_time'])),
            'block_name' => $row['block_name'] ?? 'Block time',
        ];
    }
    // Get supporting data for booking form
    try {
        $procedures = $db->query("SELECT id, procedure_name FROM procedure_catalog ORDER BY procedure_name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $procedures = []; }
    
    try {
        $anesthesiologists = $db->query("SELECT id, first_name, last_name FROM anesthesiologists WHERE status = 'active' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $anesthesiologists = []; }
    
    try {
        $operatingRooms = $db->query("SELECT id, room_number, room_type FROM operating_rooms WHERE status IN ('Ready', 'In Use') ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $operatingRooms = []; }
    
} catch (PDOException $e) {
    error_log('Doctor Availability error: ' . $e->getMessage());
}

// Helper: is surgeon busy on this date? (schedule or recurring block)
function isSurgeonBusyOnDate($surgeonId, $dateYmd, $busyBySurgeon, $blocksBySurgeon) {
    $dayName = date('l', strtotime($dateYmd)); // Monday, Tuesday, ...
    $slots = [];

    foreach ($busyBySurgeon[$surgeonId] ?? [] as $b) {
        if ($b['date'] === $dateYmd) {
            $slots[] = ['start' => substr($b['start'], 0, 5), 'end' => substr($b['end'], 0, 5), 'label' => $b['procedure_name'] . ' (' . $b['patient_name'] . ')' ];
        }
    }
    foreach ($blocksBySurgeon[$surgeonId] ?? [] as $blk) {
        if ($blk['day_of_week'] === $dayName) {
            $slots[] = ['start' => $blk['start'], 'end' => $blk['end'], 'label' => $blk['block_name'] ?? 'Block time' ];
        }
    }
    return $slots;
}

$weekDays = [];
for ($t = strtotime($startDate); $t <= strtotime($endDate); $t += 86400) {
    $weekDays[] = date('Y-m-d', $t);
}

include __DIR__ . '/../../../includes/header.php';
?>

<div class="space-y-6">
    <?php if ($message): ?>
        <div class="p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li><a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="hover:text-primary-600">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php" class="hover:text-primary-600">Surgery Schedule & Block Time</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><span class="text-gray-700 dark:text-gray-200">Doctor Availability</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Doctor Availability</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Real-time surgeon availability. Doctors not in a surgery block are available and can be assigned. Data refreshes when you load or refresh the page.
            </p>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php?view=<?php echo urlencode($view); ?>&date=<?php echo urlencode($currentDate); ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Open Schedule
        </a>
    </div>

    <!-- Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex gap-2">
                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($currentDate . ' -7 days')); ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Prev
                </a>
                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Today</a>
                <a href="?view=<?php echo $view; ?>&date=<?php echo date('Y-m-d', strtotime($currentDate . ' +7 days')); ?>" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center gap-1">
                    Next <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
            <div class="flex gap-2 items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">Week of <?php echo date('M j', strtotime($startDate)); ?> – <?php echo date('M j, Y', strtotime($endDate)); ?></span>
                <input type="date" value="<?php echo $currentDate; ?>" onchange="window.location.href='?view=<?php echo $view; ?>&date=' + this.value" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <a href="?view=week&date=<?php echo urlencode($currentDate); ?>" class="px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600">Refresh</a>
            </div>
        </div>
    </div>

    <!-- Legend and blocks link -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-4 text-sm">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-green-500"></span> Available – can be assigned
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-amber-500"></span> Busy – scheduled surgery or <strong>block time</strong> (from Surgery Schedule &amp; Block Time)
            </span>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php?view=week&date=<?php echo urlencode($currentDate); ?>"
           class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">Manage blocks &amp; schedule →</a>
    </div>

    <!-- Availability grid -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">Surgeon</th>
                        <?php foreach ($weekDays as $d): ?>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                <?php echo strtoupper(substr(date('D', strtotime($d)), 0, 2)); ?> <?php echo date('M j', strtotime($d)); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($surgeons as $surgeon):
                        $name = htmlspecialchars(trim($surgeon['first_name'] . ' ' . $surgeon['last_name']));
                        $spec = htmlspecialchars($surgeon['specialization'] ?? '');
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 sticky left-0 bg-white dark:bg-gray-800 z-10 border-r border-gray-200 dark:border-gray-700">
                                <div class="font-medium text-gray-900 dark:text-white"><?php echo $name; ?></div>
                                <?php if ($spec): ?>
                                    <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $spec; ?></div>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($weekDays as $day): 
                                $busySlots = isSurgeonBusyOnDate($surgeon['id'], $day, $busyBySurgeon, $blocksBySurgeon);
                                $isAvailable = empty($busySlots);
                            ?>
                                <td class="px-3 py-2 align-top">
                                    <?php if ($isAvailable): ?>
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded">Available</span>
                                            <button type="button" onclick="openBookingModal(<?php echo (int)$surgeon['id']; ?>, '<?php echo htmlspecialchars($surgeon['first_name'] . ' ' . $surgeon['last_name']); ?>', '<?php echo $day; ?>')"
                                               class="text-xs text-primary-600 dark:text-primary-400 hover:underline text-left cursor-pointer">Book on Schedule →</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-0.5">
                                            <?php foreach ($busySlots as $slot): ?>
                                                <div class="text-xs text-amber-800 dark:text-amber-200 bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded" title="<?php echo htmlspecialchars($slot['label']); ?>">
                                                    Busy <?php echo $slot['start']; ?>–<?php echo $slot['end']; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <button type="button" onclick="openBookingModal(<?php echo (int)$surgeon['id']; ?>, '<?php echo htmlspecialchars($surgeon['first_name'] . ' ' . $surgeon['last_name']); ?>', '<?php echo $day; ?>')"
                                               class="text-xs text-primary-600 dark:text-primary-400 hover:underline text-left cursor-pointer">Book on Schedule →</button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($surgeons)): ?>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm text-amber-800 dark:text-amber-200">
            No active surgeons found. Run the migration to load Philippine surgeons and blocks:
            <a href="<?php echo BASE_URL; ?>/database/run_migrations_and_seeders.php" class="underline font-medium">Run migrations &amp; seeders</a>.
            Then add or edit surgeons in OR Rooms &amp; Equipment and manage blocks in Surgery Schedule &amp; Block Time.
        </div>
    <?php endif; ?>
</div>

<!-- Quick Booking Modal -->
<div id="bookingModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeBookingModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Schedule Surgery</h3>
                <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            
            <div id="bookingSurgeonInfo" class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                <span class="text-sm text-primary-800 dark:text-primary-200">Scheduling for: <strong id="bookingSurgeonName"></strong> on <strong id="bookingDate"></strong></span>
            </div>
            
            <form method="POST" id="bookingForm">
                <input type="hidden" name="action" value="quick_schedule">
                <input type="hidden" name="surgeon_id" id="bookingSurgeonId">
                <input type="hidden" name="scheduled_date" id="bookingScheduledDate">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Patient ID *</label>
                        <input type="text" name="patient_id" required placeholder="e.g. P-2026-001" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Patient Name *</label>
                        <input type="text" name="patient_name" required placeholder="Full name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Procedure *</label>
                        <select name="procedure_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Procedure</option>
                            <?php foreach ($procedures as $proc): ?>
                                <option value="<?php echo $proc['id']; ?>"><?php echo htmlspecialchars($proc['procedure_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Operating Room *</label>
                        <select name="or_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select OR</option>
                            <?php foreach ($operatingRooms as $or): ?>
                                <option value="<?php echo $or['id']; ?>"><?php echo htmlspecialchars($or['room_number'] . ' - ' . $or['room_type']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Time *</label>
                        <input type="time" name="start_time" required value="08:00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Time *</label>
                        <input type="time" name="end_time" required value="10:00" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (min)</label>
                        <input type="number" name="duration" value="120" min="15" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anesthesiologist</label>
                        <select name="anesthesiologist_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select (Optional)</option>
                            <?php foreach ($anesthesiologists as $anes): ?>
                                <option value="<?php echo $anes['id']; ?>"><?php echo htmlspecialchars($anes['first_name'] . ' ' . $anes['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="Routine">Routine</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Additional notes..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeBookingModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Schedule Surgery
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Booking modal functions
function openBookingModal(surgeonId, surgeonName, date) {
    document.getElementById('bookingSurgeonId').value = surgeonId;
    document.getElementById('bookingScheduledDate').value = date;
    document.getElementById('bookingSurgeonName').textContent = surgeonName;
    document.getElementById('bookingDate').textContent = new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('bookingModal').classList.remove('hidden');
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingForm').reset();
}

// Real-time refresh every 30 seconds
let refreshInterval;
function startAutoRefresh() {
    refreshInterval = setInterval(function() {
        if (document.visibilityState === 'visible') {
            // Show subtle refresh indicator
            const indicator = document.createElement('div');
            indicator.className = 'fixed top-4 right-4 bg-primary-600 text-white px-3 py-1 rounded-full text-xs z-50';
            indicator.textContent = 'Refreshing...';
            document.body.appendChild(indicator);
            
            setTimeout(() => {
                location.reload();
            }, 500);
        }
    }, 30000);
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', startAutoRefresh);

// Manual refresh button
function manualRefresh() {
    location.reload();
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

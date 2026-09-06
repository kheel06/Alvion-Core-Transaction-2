<?php
require_once __DIR__ . '/../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../includes/doctor_portal/doctor_data.php';
require_once __DIR__ . '/../includes/doctor_portal/rbac.php';

$patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'summary';

if ($patient_id === '') {
    header('Location: ' . BASE_URL . '/doctor/patients/patient_list.php');
    exit;
}

$patient = doctorPortal_getPatient($patient_id);
if (!$patient) {
    $page_title = 'Patient not found';
    include __DIR__ . '/../includes/header.php';
    echo '<p class="text-gray-600 dark:text-gray-400">Patient not found. <a href="' . BASE_URL . '/doctor/patients/patient_list.php" class="text-[#008080] hover:underline">Back to list</a>.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$can_access = doctorPortal_canAccessPatient($patient_id);
$break_glass_key = 'doctor_break_glass_' . $patient_id;

if (!$can_access) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['break_glass_reason'])) {
        doctorPortal_logBreakGlassAccess($patient_id, $_POST['break_glass_reason']);
        $_SESSION[$break_glass_key] = true;
        header('Location: ' . BASE_URL . '/doctor/chart.php?patient_id=' . urlencode($patient_id) . '&tab=' . urlencode($tab));
        exit;
    }
    if (empty($_SESSION[$break_glass_key])) {
        $page_title = 'Break-glass access';
        include __DIR__ . '/../includes/header.php';
        ?>
        <div class="max-w-md mx-auto mt-8 p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">Patient not assigned to you</h2>
            <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">Accessing this chart requires a reason. This will be logged for audit.</p>
            <form method="post">
                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason</label>
                <select name="break_glass_reason" required class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm mb-3">
                    <option value="">Select reason…</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Consult">Consult</option>
                    <option value="Coverage">Coverage</option>
                    <option value="Other">Other</option>
                </select>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note (optional)</label>
                <textarea name="break_glass_note" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm mb-4"></textarea>
                <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Continue to chart</button>
                <a href="<?php echo BASE_URL; ?>/doctor/patients/patient_list.php" class="ml-2 text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
            </form>
        </div>
        <?php
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

$chart = doctorPortal_getPatientChart($patient_id);
$page_title = 'Chart – ' . $patient['last_name'] . ', ' . $patient['first_name'];

include __DIR__ . '/../includes/header.php';

include __DIR__ . '/../includes/doctor_portal/patient_header.php';
include __DIR__ . '/../includes/doctor_portal/chart_tabs.php';

$valid_tabs = ['summary','vitals','problems','allergies','medications','orders','labs','imaging','pathology','notes','care-team','documents','timeline'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'summary';
?>

<div class="chart-tab-content">
    <?php
    switch ($tab) {
        case 'summary':
            $p = $chart['problems'];
            $meds = $chart['medications'];
            $all = $chart['allergies'];
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Active problems</h3>
                    <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <?php foreach (array_filter($p, function ($x) { return ($x['status'] ?? '') === 'Active'; }) as $x): ?>
                        <li><?php echo htmlspecialchars($x['description'] . ' (' . $x['icd'] . ')'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Allergies</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><?php echo empty($all) ? 'None' : implode(', ', array_column($all, 'agent')); ?></p>
                </div>
                <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Active medications</h3>
                    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <?php foreach (array_filter($meds, function ($x) { return ($x['status'] ?? '') === 'Active'; }) as $x): ?>
                        <li><?php echo htmlspecialchars($x['drug'] . ' – ' . $x['dose']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php
            break;
        case 'vitals':
            $v = $chart['vitals'];
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Time</th><th class="px-4 py-2">BP</th><th class="px-4 py-2">HR</th><th class="px-4 py-2">Temp</th><th class="px-4 py-2">RR</th><th class="px-4 py-2">SpO2</th></tr></thead>
                    <tbody>
                        <?php foreach ($v as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['time']); ?></td><td class="px-4 py-2"><?php echo $r['bp']; ?></td><td class="px-4 py-2"><?php echo $r['hr']; ?></td><td class="px-4 py-2"><?php echo $r['temp']; ?></td><td class="px-4 py-2"><?php echo $r['rr']; ?></td><td class="px-4 py-2"><?php echo $r['spo2']; ?>%</td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'problems':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">ICD</th><th class="px-4 py-2">Description</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Onset</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['problems'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['icd']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['description']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['status']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['onset']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'allergies':
            $all = $chart['allergies'];
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <?php if (empty($all)): ?>
                <p class="text-gray-600 dark:text-gray-400">No known allergies.</p>
                <?php else: ?>
                <table class="w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Agent</th><th class="px-4 py-2">Reaction</th><th class="px-4 py-2">Severity</th></tr></thead><tbody>
                <?php foreach ($all as $r): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['agent']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['reaction']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['severity']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
                <?php endif; ?>
            </div>
            <?php
            break;
        case 'medications':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Drug</th><th class="px-4 py-2">Dose</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Start</th><th class="px-4 py-2">Prescriber</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['medications'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['drug']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['dose']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['status']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['start']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['prescriber']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'orders':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Type</th><th class="px-4 py-2">Order</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['orders'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['type']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['order']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['status']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['date']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'labs':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Panel</th><th class="px-4 py-2">Result</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Collected</th><th class="px-4 py-2">Reported</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['labs'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['panel']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['result']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['status']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['collected']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['reported']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'imaging':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Modality</th><th class="px-4 py-2">Description</th><th class="px-4 py-2">Result</th><th class="px-4 py-2">Performed</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['imaging'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['modality']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['description']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['result']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['performed']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'pathology':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Specimen</th><th class="px-4 py-2">Result</th><th class="px-4 py-2">Signed</th></tr></thead>
                    <tbody>
                        <?php foreach ($chart['pathology'] as $r): ?>
                        <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2"><?php echo htmlspecialchars($r['specimen']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['result']); ?></td><td class="px-4 py-2"><?php echo htmlspecialchars($r['signed']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            break;
        case 'notes':
            ?>
            <div class="space-y-3">
                <?php foreach ($chart['notes'] as $r): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($r['type'] . ' · ' . $r['author'] . ' · ' . $r['date']); ?></p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($r['snippet']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php
            break;
        case 'care-team':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <table class="w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-2">Role</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Contact</th></tr></thead><tbody>
                <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2">Attending</td><td class="px-4 py-2">Dr. Reyes</td><td class="px-4 py-2">—</td></tr>
                <tr class="border-t border-gray-200 dark:border-gray-700"><td class="px-4 py-2">Nurse</td><td class="px-4 py-2">RN Santos</td><td class="px-4 py-2">Ward</td></tr>
                </tbody></table>
            </div>
            <?php
            break;
        case 'documents':
            ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">Consent forms, advance directives, and uploaded documents appear here.</p>
                <ul class="mt-2 text-sm text-gray-700 dark:text-gray-300 space-y-1">
                    <li>Admission consent – signed <?php echo date('M j, Y', strtotime('-5 days')); ?></li>
                    <li>Blood product consent – on file</li>
                </ul>
            </div>
            <?php
            break;
        case 'timeline':
            ?>
            <div class="space-y-2">
                <?php
                $events = [
                    ['date' => date('Y-m-d H:i', strtotime('-1 day')), 'text' => 'Progress note – Dr. Reyes'],
                    ['date' => date('Y-m-d H:i', strtotime('-2 days')), 'text' => 'Cardiology consult – echo ordered'],
                    ['date' => date('Y-m-d', strtotime('-3 days')) . ' 08:00', 'text' => 'CBC, CMP collected'],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'text' => 'Admission – Medical 2'],
                ];
                foreach ($events as $e):
                ?>
                <div class="flex gap-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400 w-36 flex-shrink-0"><?php echo htmlspecialchars($e['date']); ?></span>
                    <span class="text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($e['text']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php
            break;
        default:
            echo '<p class="text-gray-500 dark:text-gray-400">Select a tab above.</p>';
    }
    ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

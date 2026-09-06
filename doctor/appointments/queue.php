<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Visit Queue';
$appointments = array_filter(doctorPortal_seedAppointments(), function ($a) { return ($a['status'] ?? '') === 'Checked in'; });

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Visit Queue</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Patients checked in and waiting</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($appointments)): ?>
    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No patients in queue.</div>
    <?php else: ?>
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">MRN</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $a): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['time']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['patient']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['mrn']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=1" class="text-[#008080] hover:underline">Start visit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

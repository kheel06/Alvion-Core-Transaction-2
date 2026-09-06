<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Clinic Schedule';
$appointments = doctorPortal_seedAppointments();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Clinic Schedule</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo date('l, F j, Y'); ?></p>
    </div>
    <input type="text" placeholder="Search…" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 w-48">
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">MRN</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $a): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['time']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=1" class="text-[#008080] hover:underline"><?php echo htmlspecialchars($a['patient']); ?></a></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['mrn']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['type']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($a['status']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=1" class="text-[#008080] hover:underline">Open chart</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'OR Schedule';
$cases = doctorPortal_seedORSchedule();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">OR Schedule</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo date('l, F j, Y'); ?></p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">Procedure</th>
                <th class="px-4 py-3">Room</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cases as $c): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($c['time']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($c['patient']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($c['procedure']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($c['room']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($c['status']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

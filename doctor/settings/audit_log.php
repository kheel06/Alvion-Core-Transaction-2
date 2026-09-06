<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Audit Log (My Activity)';
$log = doctorPortal_getAuditLog();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Log – My Activity</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chart access and break-glass events</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Patient ID</th>
                <th class="px-4 py-3">Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($log, 0, 20) as $entry): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td class="px-4 py-3"><?php echo htmlspecialchars($entry['at'] ?? ''); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($entry['action'] ?? ''); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($entry['patient_id'] ?? ''); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($entry['reason'] ?? '—'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

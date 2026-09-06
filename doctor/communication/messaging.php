<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Secure Messaging';
$threads = doctorPortal_seedMessagingThreads();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Secure Messaging</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Team and staff messages</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">From</th>
                <th class="px-4 py-3">Subject</th>
                <th class="px-4 py-3">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($threads as $t): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($t['from']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($t['subject']); ?><?php if (!empty($t['unread'])): ?> <span class="text-[#008080]">•</span><?php endif; ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($t['date']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

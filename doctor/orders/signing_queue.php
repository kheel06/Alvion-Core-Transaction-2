<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Order Signing Queue';
$queue = doctorPortal_seedSigningQueue();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order Signing Queue</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Orders pending your signature</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (empty($queue)): ?>
    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No orders pending signature.</div>
    <?php else: ?>
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Ordered at</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($queue as $q): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($q['patient']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($q['type']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($q['order']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($q['ordered_at']); ?></td>
                <td class="px-4 py-3"><button type="button" class="text-[#008080] hover:underline">Sign</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

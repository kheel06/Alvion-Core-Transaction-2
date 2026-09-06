<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Signature & Templates';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Signature &amp; Templates</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">E-signature and note signing preferences</p>
</div>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Your signature is on file for electronic signing of orders and notes.</p>
    <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Signature block: [On file]
    </div>
    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">To update your signature or signing preferences, contact Medical Affairs.</p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

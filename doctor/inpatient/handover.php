<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Handover / Sign-out';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Handover / Sign-out</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Patient handover notes for on-call</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Document active issues, to-dos, and contact info for the covering team.</p>
    <textarea rows="8" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Handover note…"></textarea>
    <button type="button" class="mt-3 rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Save handover</button>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

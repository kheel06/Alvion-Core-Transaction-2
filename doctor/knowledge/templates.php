<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Forms & Templates';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Forms &amp; Templates</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Note templates and document forms</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Template</th>
                <th class="px-4 py-3">Type</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">Progress note – Medicine</td><td class="px-4 py-3">Note</td></tr>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">Discharge summary</td><td class="px-4 py-3">Note</td></tr>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">Consult request</td><td class="px-4 py-3">Form</td></tr>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

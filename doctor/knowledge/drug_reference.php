<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Drug Reference';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Drug Reference</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dosing, interactions, and formulary</p>
</div>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search drug</label>
    <input type="text" placeholder="Generic or brand name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm mb-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">Results will show dosing, contraindications, and hospital formulary status.</p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

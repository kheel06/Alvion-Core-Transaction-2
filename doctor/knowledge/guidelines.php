<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Clinical Guidelines';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Clinical Guidelines</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hospital-approved protocols and pathways</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Guideline</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Updated</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">Sepsis bundle</td><td class="px-4 py-3">Critical care</td><td class="px-4 py-3"><?php echo date('Y-m-d', strtotime('-2 months')); ?></td></tr>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">VTE prophylaxis</td><td class="px-4 py-3">Inpatient</td><td class="px-4 py-3"><?php echo date('Y-m-d', strtotime('-1 month')); ?></td></tr>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

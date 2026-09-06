<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Calculators';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Calculators</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clinical calculators and scores</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="#" class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-[#008080] transition-colors">
        <p class="font-medium text-gray-900 dark:text-white">BMI / BSA</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Body mass index and body surface area</p>
    </a>
    <a href="#" class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-[#008080] transition-colors">
        <p class="font-medium text-gray-900 dark:text-white">eGFR (CKD-EPI)</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Glomerular filtration rate</p>
    </a>
    <a href="#" class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-[#008080] transition-colors">
        <p class="font-medium text-gray-900 dark:text-white">CHADS₂-VASc</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Stroke risk in atrial fibrillation</p>
    </a>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

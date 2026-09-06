<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Patient Messages (Clinic)';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Patient Messages</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Clinic patient portal messages</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No patient messages requiring response.</p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

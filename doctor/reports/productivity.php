<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/report_data.php';

$page_title = 'My Productivity';
$stats = getDoctorProductivityFromDb();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Productivity</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Encounters and visits from hospital system (MTD: <?php echo date('F Y'); ?>)</p>
    <?php if (($stats['source'] ?? '') === 'database'): ?>
    <p class="mt-1 text-xs text-green-600 dark:text-green-400">Real-time data from appointments</p>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Encounters this month</p>
        <p class="text-2xl font-semibold text-[#008080]"><?php echo (int)($stats['encounters_this_month'] ?? 0); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Notes signed</p>
        <p class="text-2xl font-semibold text-[#008080]"><?php echo (int)($stats['notes_signed'] ?? 0); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">RVUs (MTD)</p>
        <p class="text-2xl font-semibold text-[#008080]"><?php echo (int)($stats['rvus_mtd'] ?? 0); ?></p>
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Outpatient visits</p>
        <p class="text-2xl font-semibold text-[#008080]"><?php echo (int)($stats['outpatient_visits'] ?? 0); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Inpatient days</p>
        <p class="text-2xl font-semibold text-[#008080]"><?php echo (int)($stats['inpatient_days'] ?? 0); ?></p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <p class="text-sm text-gray-500 dark:text-gray-400">Data is from the hospital management system. Notes signed and RVUs require EMR/LIS integration. Detailed reports are available in the admin Reports section.</p>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

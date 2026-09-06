<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Recently Viewed';
$recent = array_slice(doctorPortal_seedPatients(), 0, 4);

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recently Viewed</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quick access to recently opened charts</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($recent as $p): ?>
    <a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($p['id']); ?>" class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-[#008080] dark:hover:border-teal-500 transition-colors">
        <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($p['mrn']); ?> · <?php echo htmlspecialchars($p['ward'] ?? 'Outpatient'); ?></p>
    </a>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Rounding List';
$patients = array_filter(doctorPortal_getPatientsForDoctor(), function ($p) { return !empty($p['ward']); });

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rounding List</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Assigned inpatients for rounds</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Ward / Bed</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">MRN</th>
                <th class="px-4 py-3">Allergies</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patients)): ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No inpatients on your rounding list.</td></tr>
            <?php else: ?>
            <?php foreach ($patients as $p): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars(($p['ward'] ?? '') . ' ' . ($p['bed'] ?? '')); ?></td>
                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($p['mrn']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($p['allergies']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($p['id']); ?>" class="text-[#008080] hover:underline">Chart</a></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

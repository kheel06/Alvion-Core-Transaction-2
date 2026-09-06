<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/rbac.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Discharge Planning';
$can_finalize = doctorPortal_canFinalizeDischarge();
$discharge_list = doctorPortal_seedDischargeList();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Discharge Planning</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Discharge summaries and planning</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">MRN</th>
                <th class="px-4 py-3">Ward / Bed</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($discharge_list)): ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No inpatients in discharge planning.</td></tr>
            <?php else: ?>
            <?php foreach ($discharge_list as $d): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($d['patient_id']); ?>" class="text-[#008080] hover:underline"><?php echo htmlspecialchars($d['patient']); ?></a></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($d['mrn']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($d['ward'] . ($d['bed'] ? ' / ' . $d['bed'] : '')); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($d['status']); ?></td>
                <td class="px-4 py-3">
                    <?php if ($can_finalize): ?>
                    <a href="#" class="text-[#008080] hover:underline">Finalize discharge</a>
                    <?php else: ?>
                    <span class="text-gray-500" title="Attending or authorized role required">Finalize (restricted)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

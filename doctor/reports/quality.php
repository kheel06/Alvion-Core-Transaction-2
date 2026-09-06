<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/report_data.php';

$page_title = 'Clinical Quality';
$result = getDoctorQualityFromDb();
$metrics = $result['metrics'] ?? [];

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Clinical Quality</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quality metrics from hospital system</p>
    <?php if (($result['source'] ?? '') === 'database'): ?>
    <p class="mt-1 text-xs text-green-600 dark:text-green-400">Real-time data</p>
    <?php endif; ?>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <?php if (count($metrics) > 0): ?>
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Metric</th>
                <th class="px-4 py-3">Your rate</th>
                <th class="px-4 py-3">Target</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($metrics as $m): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($m['metric']); ?></td>
                <td class="px-4 py-3"><?php echo (int)$m['rate']; ?>%</td>
                <td class="px-4 py-3"><?php echo (int)$m['target']; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="p-6 text-center">
        <p class="text-gray-500 dark:text-gray-400">No quality metrics on file for your account. Data will appear when the hospital has configured quality tracking (e.g. documentation completeness, discharge summary timeliness).</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

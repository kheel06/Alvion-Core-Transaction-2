<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Referrals';
$referrals = doctorPortal_seedReferrals();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Referrals</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Incoming and outgoing referrals</p>
    </div>
    <select class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2">
        <option>All</option>
        <option>Incoming</option>
        <option>Outgoing</option>
    </select>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">From</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">Reason</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($referrals as $r): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($r['from']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($r['patient_id'] ?? ''); ?>" class="text-[#008080] hover:underline"><?php echo htmlspecialchars($r['patient']); ?></a></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($r['reason']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($r['date']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($r['status']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

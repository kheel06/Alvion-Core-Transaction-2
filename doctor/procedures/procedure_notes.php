<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Procedure Notes';
$notes = doctorPortal_seedProcedureNotes();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Procedure Notes</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Document and view procedure notes</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Recent procedure notes you authored or are responsible for.</p>
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Patient</th>
                <th class="px-4 py-3">Procedure</th>
                <th class="px-4 py-3">Surgeon</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($notes)): ?>
            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No procedure notes found.</td></tr>
            <?php else: ?>
            <?php foreach ($notes as $n): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3"><?php echo htmlspecialchars($n['date']); ?></td>
                <td class="px-4 py-3"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($n['patient_id'] ?? ''); ?>" class="text-[#008080] hover:underline"><?php echo htmlspecialchars($n['patient']); ?></a></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($n['procedure']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($n['surgeon']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

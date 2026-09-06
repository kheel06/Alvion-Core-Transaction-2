<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/doctor_data.php';

$page_title = 'Order Sets';
$sets = doctorPortal_seedOrderSets();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order Sets</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Predefined order bundles</p>
    </div>
    <input type="text" placeholder="Search order sets…" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 w-56">
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Last updated</th>
                <th class="px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sets as $s): ?>
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($s['name']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($s['category']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($s['updated']); ?></td>
                <td class="px-4 py-3"><button type="button" class="text-[#008080] hover:underline">Apply</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

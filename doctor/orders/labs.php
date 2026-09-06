<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Lab Orders';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lab Orders</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Order laboratory tests</p>
</div>

<div class="max-w-2xl bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <form class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient</label>
            <input type="text" placeholder="MRN or patient name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tests / panels</label>
            <select multiple class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" size="6">
                <option>CBC</option>
                <option>CMP</option>
                <option>HbA1c</option>
                <option>Troponin I</option>
                <option>Blood culture x2</option>
                <option>Urinalysis</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
            <select class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option>Routine</option>
                <option>Stat</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Order</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

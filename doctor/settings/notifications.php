<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Notification Preferences';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notification Preferences</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Alerts, reminders, and messages</p>
</div>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <form class="space-y-4">
        <label class="flex items-center gap-3">
            <input type="checkbox" checked class="rounded border-gray-300 dark:border-gray-600 text-[#008080]">
            <span class="text-sm text-gray-700 dark:text-gray-300">Critical result alerts</span>
        </label>
        <label class="flex items-center gap-3">
            <input type="checkbox" checked class="rounded border-gray-300 dark:border-gray-600 text-[#008080]">
            <span class="text-sm text-gray-700 dark:text-gray-300">Order signing queue reminders</span>
        </label>
        <label class="flex items-center gap-3">
            <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-[#008080]">
            <span class="text-sm text-gray-700 dark:text-gray-300">Secure messaging</span>
        </label>
        <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Save</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

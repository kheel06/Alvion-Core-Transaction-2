<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'My Profile';
$name = $_SESSION['first_name'] ?? '' . ' ' . $_SESSION['last_name'] ?? '';

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Personal and professional details</p>
</div>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <form class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full name</label>
            <input type="text" value="<?php echo htmlspecialchars(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" readonly>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <input type="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? $_SESSION['username'] ?? ''); ?>" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preferred name / credentials</label>
            <input type="text" placeholder="e.g. Dr. Reyes, MD" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Save changes</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

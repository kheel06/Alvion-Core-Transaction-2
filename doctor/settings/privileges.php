<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/rbac.php';

$page_title = 'Role & Privileges';
$role = doctorPortal_currentRole();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Role &amp; Privileges</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your current role and capability summary</p>
</div>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Current role</p>
    <p class="mt-1 text-lg text-[#008080]"><?php echo htmlspecialchars(ucfirst($role)); ?></p>
    <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
        <li>• Notes: <?php echo doctorPortal_noteRequiresCosign() ? 'Require co-sign' : 'Can sign independently'; ?></li>
        <li>• Controlled medications: <?php echo doctorPortal_canPrescribeControlled() ? 'Yes' : 'No (attending/consultant required)'; ?></li>
        <li>• Discharge finalization: <?php echo doctorPortal_canFinalizeDischarge() ? 'Yes' : 'No'; ?></li>
    </ul>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

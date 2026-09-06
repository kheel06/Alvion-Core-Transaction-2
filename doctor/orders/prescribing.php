<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../../includes/doctor_portal/rbac.php';

$page_title = 'Medication Prescribing (eRx)';
$patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';
$can_controlled = doctorPortal_canPrescribeControlled();

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Medication Prescribing (eRx)</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">New prescription or refill</p>
</div>

<div class="max-w-2xl bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <form class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient MRN</label>
            <input type="text" value="<?php echo htmlspecialchars($patient_id); ?>" placeholder="MRN or search patient" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Medication</label>
            <input type="text" placeholder="Search drug name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dose</label>
                <input type="text" placeholder="e.g. 5 mg" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Route / frequency</label>
                <input type="text" placeholder="e.g. PO daily" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>
        <?php if (!$can_controlled): ?>
        <p class="text-sm text-amber-600 dark:text-amber-400">Controlled substances require attending or consultant role.</p>
        <?php endif; ?>
        <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Sign &amp; send</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

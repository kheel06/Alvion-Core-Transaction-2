<?php
require_once __DIR__ . '/../../includes/doctor_portal/guard.php';

$page_title = 'Add Patient to My List';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mrn = trim($_POST['mrn'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    if ($mrn && $reason) {
        $message = 'Request submitted for MRN ' . htmlspecialchars($mrn) . '. Pending approval.';
    } else {
        $message = 'Please provide MRN and reason.';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Patient to My List (Request Access)</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Request to add a patient to your assigned list</p>
</div>

<?php if ($message): ?>
<div class="mb-4 p-4 rounded-lg bg-teal-50 dark:bg-teal-900/20 text-teal-800 dark:text-teal-200 border border-teal-200 dark:border-teal-800">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<div class="max-w-lg bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <form method="post" class="space-y-4">
        <div>
            <label for="mrn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Patient MRN</label>
            <input type="text" id="mrn" name="mrn" required placeholder="e.g. MRN-2024-001" class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason for request</label>
            <select id="reason" name="reason" required class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                <option value="">Select…</option>
                <option value="Consult">Consult</option>
                <option value="Coverage">Coverage</option>
                <option value="Follow-up">Follow-up</option>
                <option value="Procedure">Procedure</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
        </div>
        <button type="submit" class="rounded-lg bg-[#008080] text-white px-4 py-2 text-sm font-medium hover:bg-teal-700">Submit request</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

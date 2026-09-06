<?php
/**
 * Sticky patient header for chart. Expects $patient (from mock) and $patient_id.
 */
if (empty($patient)) return;
$name = htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']);
$mrn = htmlspecialchars($patient['mrn']);
$dob = htmlspecialchars($patient['dob'] ?? '');
$sex = htmlspecialchars($patient['sex'] ?? '');
$loc = trim(($patient['ward'] ?? '') . ($patient['bed'] ? ' / ' . $patient['bed'] : ''));
$allergies = htmlspecialchars($patient['allergies'] ?? 'None');
$code = htmlspecialchars($patient['code_status'] ?? 'Full Code');
?>
<div class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm mb-4 -mx-6 px-6 py-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo $name; ?></h1>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo $mrn; ?></span>
            <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo $dob; ?> · <?php echo $sex; ?></span>
            <?php if ($loc): ?>
            <span class="text-sm text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($loc); ?></span>
            <?php endif; ?>
            <span class="text-sm <?php echo $allergies !== 'None' ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'; ?>">Allergies: <?php echo $allergies; ?></span>
            <span class="text-sm text-gray-500 dark:text-gray-400">Code: <?php echo $code; ?></span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="#" class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">Message Team</a>
            <a href="<?php echo BASE_URL; ?>/doctor/orders/prescribing.php?patient_id=<?php echo urlencode($patient_id); ?>" class="inline-flex items-center rounded-lg bg-[#008080] text-white px-3 py-2 text-sm font-medium hover:bg-teal-700">Order</a>
            <a href="#" class="inline-flex items-center rounded-lg bg-[#008080] text-white px-3 py-2 text-sm font-medium hover:bg-teal-700">Write Note</a>
            <?php if (function_exists('doctorPortal_canFinalizeDischarge') && doctorPortal_canFinalizeDischarge()): ?>
            <a href="<?php echo BASE_URL; ?>/doctor/inpatient/discharge.php?patient_id=<?php echo urlencode($patient_id); ?>" class="inline-flex items-center rounded-lg border border-amber-500 text-amber-700 dark:text-amber-400 px-3 py-2 text-sm font-medium hover:bg-amber-50 dark:hover:bg-amber-900/20">Discharge</a>
            <?php else: ?>
            <span class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 px-3 py-2 text-sm text-gray-500 dark:text-gray-400" title="Attending or authorized role required">Discharge</span>
            <?php endif; ?>
        </div>
    </div>
</div>

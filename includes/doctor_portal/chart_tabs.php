<?php
/**
 * Chart tab navigation. Expects $patient_id and $tab (current tab key).
 */
$doctor_base = defined('BASE_URL') ? BASE_URL . '/doctor' : '';
$tabs = [
    'summary' => 'Summary',
    'vitals' => 'Vitals & Flowsheets',
    'problems' => 'Problems & Diagnoses',
    'allergies' => 'Allergies',
    'medications' => 'Medications',
    'orders' => 'Orders',
    'labs' => 'Results – Labs',
    'imaging' => 'Results – Imaging',
    'pathology' => 'Results – Pathology',
    'notes' => 'Notes',
    'care-team' => 'Care Team',
    'documents' => 'Documents',
    'timeline' => 'Clinical Timeline',
];
$current = $tab ?? 'summary';
?>
<nav class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700 mb-4 -mx-6 px-6 overflow-x-auto">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=<?php echo urlencode($patient_id); ?>&tab=<?php echo urlencode($key); ?>"
       class="px-3 py-2 text-sm font-medium rounded-t-lg whitespace-nowrap <?php echo $current === $key ? 'bg-[#008080] text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
        <?php echo htmlspecialchars($label); ?>
    </a>
    <?php endforeach; ?>
</nav>

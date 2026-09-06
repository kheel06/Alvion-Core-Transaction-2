<?php
require_once __DIR__ . '/../includes/doctor_portal/guard.php';
require_once __DIR__ . '/../includes/doctor_portal/doctor_data.php';

$page_title = 'Doctor Portal – Overview';
$patients = doctorPortal_getPatientsForDoctor();
$appointments = doctorPortal_seedAppointments();
$signing = doctorPortal_seedSigningQueue();
$alerts = doctorPortal_seedCriticalAlerts();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Overview Dashboard</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Today’s summary and quick actions</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">My assigned patients</p>
        <p class="text-2xl font-semibold text-[#008080] dark:text-teal-400"><?php echo count($patients); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Clinic appointments today</p>
        <p class="text-2xl font-semibold text-[#008080] dark:text-teal-400"><?php echo count($appointments); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Orders in signing queue</p>
        <p class="text-2xl font-semibold text-[#008080] dark:text-teal-400"><?php echo count($signing); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <p class="text-sm text-gray-500 dark:text-gray-400">Critical result alerts</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo count($alerts); ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Today’s schedule</h2>
            <a href="<?php echo BASE_URL; ?>/doctor/schedule.php" class="text-sm text-[#008080] hover:underline">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2">Time</th>
                        <th class="px-4 py-2">Patient</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $a): ?>
                    <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($a['time']); ?></td>
                        <td class="px-4 py-2"><a href="<?php echo BASE_URL; ?>/doctor/chart.php?patient_id=<?php echo urlencode($a['patient_id'] ?? '1'); ?>" class="text-[#008080] hover:underline"><?php echo htmlspecialchars($a['patient']); ?></a></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($a['type']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($a['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Order signing queue</h2>
            <a href="<?php echo BASE_URL; ?>/doctor/orders/signing_queue.php" class="text-sm text-[#008080] hover:underline">Sign orders</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2">Patient</th>
                        <th class="px-4 py-2">Order</th>
                        <th class="px-4 py-2">Ordered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signing as $s): ?>
                    <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($s['patient']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($s['type'] . ' – ' . $s['order']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($s['ordered_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

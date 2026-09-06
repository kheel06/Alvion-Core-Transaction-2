<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Clinical Operations Dashboard';

require_once __DIR__ . '/../includes/azure_ml_helper.php';
$predictive_data = getPredictiveAnalyticsData();

// Simulate dashboard data (replace with actual database queries in production)
$dashboard_data = [
    'kpi' => [
        'active_patients' => 247,
        'pending_lab_tests' => 83,
        'pending_radiology' => 29,
        'active_prescriptions' => 156,
        'scheduled_surgeries' => 12,
        'active_diet_orders' => 94,
    ],
    'lis' => [
        'tests_ordered' => 124,
        'pending_validation' => 31,
        'critical_results' => 7,
        'avg_tat' => '2.4 hrs',
    ],
    'ris' => [
        'exams_scheduled' => 45,
        'pending_interpretation' => 18,
        'awaiting_approval' => 9,
        'equipment_utilization' => 78,
    ],
    'pms' => [
        'active_prescriptions' => 156,
        'low_stock_alerts' => 23,
        'expiring_30' => 12,
        'expiring_60' => 28,
        'expiring_90' => 47,
        'daily_dispensed' => 342,
    ],
    'sors' => [
        'surgeries_today' => 12,
        'or_utilization' => 85,
        'cancelled_cases' => 2,
        'avg_duration' => '3.2 hrs',
    ],
    'dnms' => [
        'special_diets' => 67,
        'diet_orders_today' => 41,
        'missed_meals' => 3,
        'dietitian_assignments' => 12,
    ],
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
    
    .dashboard-container {
        font-family: 'Outfit', system-ui, sans-serif;
    }
    
    .kpi-card {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent-start), var(--accent-end));
    }
    
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }
    
    .kpi-card .kpi-icon {
        transition: transform 0.3s ease;
    }
    
    .kpi-card:hover .kpi-icon {
        transform: scale(1.1);
    }
    
    .kpi-card-link {
        display: block;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }
    
    .kpi-card-link:hover {
        color: inherit;
    }
    
    .module-panel-link {
        display: block;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }
    
    .module-panel-link:hover {
        color: inherit;
    }
    
    .quick-action-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        background: rgba(255,255,255,0.05);
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: background 0.2s;
        width: 100%;
        border: none;
        cursor: pointer;
        text-align: left;
    }
    
    .quick-action-link:hover {
        background: rgba(255,255,255,0.1);
        color: white;
    }
    
    .alert-item-link {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .alert-item-link:hover {
        background: rgba(0,0,0,0.05);
    }
    
    .dark .alert-item-link:hover {
        background: rgba(255,255,255,0.05);
    }
    
    .module-panel {
        position: relative;
        border-radius: 1rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .module-panel::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1rem;
        padding: 1px;
        background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }
    
    .module-panel:hover {
        transform: translateY(-2px);
    }
    
    .metric-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }
    
    .metric-item:last-child {
        border-bottom: none;
    }
    
    .metric-value {
        font-weight: 600;
        font-size: 1.125rem;
        font-variant-numeric: tabular-nums;
    }
    
    .chart-container {
        position: relative;
        height: 280px;
    }
    
    .section-title {
        position: relative;
        display: inline-block;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, #0d9488, #14b8a6);
        border-radius: 2px;
    }
    
    /* Staggered animation */
    .animate-stagger {
        opacity: 0;
        animation: fadeSlideUp 0.5s ease forwards;
    }
    
    @keyframes fadeSlideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: pulse-animation 2s infinite;
    }
    
    @keyframes pulse-animation {
        0% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(20, 184, 166, 0); }
        100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0); }
    }
    
    .stat-ring {
        position: relative;
        width: 60px;
        height: 60px;
    }
    
    .stat-ring svg {
        transform: rotate(-90deg);
    }
    
    .stat-ring-bg {
        fill: none;
        stroke: currentColor;
        opacity: 0.1;
    }
    
    .stat-ring-fill {
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        transition: stroke-dashoffset 1s ease;
    }
</style>

<div class="dashboard-container">
    <!-- Header Section -->
    <div class="mb-8 animate-stagger" style="animation-delay: 0.05s;">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                    Clinical Operations Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <span class="pulse-dot bg-teal-500"></span>
                    Real-time overview of hospital clinical operations
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 dark:text-gray-500">Last updated:</span>
                <span id="lastUpdated" class="text-sm font-medium text-gray-600 dark:text-gray-300"></span>
                <button onclick="refreshDashboard()" class="p-2 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Top Summary KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Active Patients -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #0d9488; --accent-end: #14b8a6; animation-delay: 0.1s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="text-xs font-medium text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30 px-2 py-1 rounded-full">Today</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['active_patients']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Active Patients</div>
        </a>

        <!-- Pending Lab Tests -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #7c3aed; --accent-end: #a78bfa; animation-delay: 0.15s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M8 18v-1"/><path d="M16 18v-3"/></svg>
                </div>
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">Pending</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['pending_lab_tests']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Pending Lab Tests</div>
        </a>

        <!-- Pending Radiology -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/radiology_orders_queue.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #0891b2; --accent-end: #22d3ee; animation-delay: 0.2s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M3 12h3"/><path d="M18 12h3"/><path d="M12 3v3"/><path d="M12 18v3"/></svg>
                </div>
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">Pending</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['pending_radiology']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Pending Radiology</div>
        </a>

        <!-- Active Prescriptions -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/prescription_monitoring.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #059669; --accent-end: #34d399; animation-delay: 0.25s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                </div>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">Active</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['active_prescriptions']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Active Prescriptions</div>
        </a>

        <!-- Scheduled Surgeries -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #dc2626; --accent-end: #f87171; animation-delay: 0.3s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </div>
                <span class="text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">Scheduled</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['scheduled_surgeries']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Surgeries Today</div>
        </a>

        <!-- Active Diet Orders -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/patient_diet_assignments.php" class="kpi-card-link kpi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger" style="--accent-start: #ea580c; --accent-end: #fb923c; animation-delay: 0.35s;">
            <div class="flex items-center justify-between mb-3">
                <div class="kpi-icon w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
                </div>
                <span class="text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30 px-2 py-1 rounded-full">Active</span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo number_format($dashboard_data['kpi']['active_diet_orders']); ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Diet Orders</div>
        </a>
    </div>

    <!-- Section Title -->
    <div class="mb-6 animate-stagger" style="animation-delay: 0.4s;">
        <h2 class="section-title text-lg font-semibold text-gray-900 dark:text-white">Core Transaction Modules</h2>
    </div>

    <!-- Module Panels Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        
        <!-- LIS Panel -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="module-panel-link module-panel bg-white dark:bg-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.45s;">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 2v17.5A2.5 2.5 0 0 1 6.5 22v0A2.5 2.5 0 0 1 4 19.5V2"/><path d="M20 2v17.5a2.5 2.5 0 0 1-2.5 2.5v0a2.5 2.5 0 0 1-2.5-2.5V2"/><path d="M3 2h7"/><path d="M14 2h7"/><path d="M9 12h6"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Laboratory (LIS)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Laboratory Information System</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Tests Ordered Today</span>
                    <span class="metric-value text-violet-600 dark:text-violet-400"><?php echo $dashboard_data['lis']['tests_ordered']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Pending Validation</span>
                    <span class="metric-value text-amber-600 dark:text-amber-400"><?php echo $dashboard_data['lis']['pending_validation']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Critical Results</span>
                    <span class="metric-value text-red-600 dark:text-red-400"><?php echo $dashboard_data['lis']['critical_results']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Avg Turnaround Time</span>
                    <span class="metric-value text-gray-900 dark:text-white"><?php echo $dashboard_data['lis']['avg_tat']; ?></span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <span class="text-sm font-medium text-violet-600 dark:text-violet-400 hover:underline">View LIS module →</span>
            </div>
        </a>

        <!-- RIS Panel -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/radiology_orders_queue.php" class="module-panel-link module-panel bg-white dark:bg-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.5s;">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M12 3v3"/><path d="M12 18v3"/><path d="M3 12h3"/><path d="M18 12h3"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Radiology (RIS)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Radiology Information System</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Exams Scheduled Today</span>
                    <span class="metric-value text-cyan-600 dark:text-cyan-400"><?php echo $dashboard_data['ris']['exams_scheduled']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Pending Interpretation</span>
                    <span class="metric-value text-amber-600 dark:text-amber-400"><?php echo $dashboard_data['ris']['pending_interpretation']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Awaiting Approval</span>
                    <span class="metric-value text-orange-600 dark:text-orange-400"><?php echo $dashboard_data['ris']['awaiting_approval']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">Equipment Utilization
                        <div class="stat-ring text-cyan-500">
                            <svg viewBox="0 0 36 36" class="w-10 h-10">
                                <circle class="stat-ring-bg" cx="18" cy="18" r="16" stroke-width="3"/>
                                <circle class="stat-ring-fill" cx="18" cy="18" r="16" stroke-width="3" 
                                    stroke-dasharray="<?php echo ($dashboard_data['ris']['equipment_utilization'] * 100.53) / 100; ?> 100.53"/>
                            </svg>
                        </div>
                    </span>
                    <span class="metric-value text-cyan-600 dark:text-cyan-400"><?php echo $dashboard_data['ris']['equipment_utilization']; ?>%</span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <span class="text-sm font-medium text-cyan-600 dark:text-cyan-400 hover:underline">View RIS module →</span>
            </div>
        </a>

        <!-- PMS Panel -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/prescription_monitoring.php" class="module-panel-link module-panel bg-white dark:bg-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.55s;">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Pharmacy (PMS)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pharmacy Management System</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Active Prescriptions</span>
                    <span class="metric-value text-emerald-600 dark:text-emerald-400"><?php echo $dashboard_data['pms']['active_prescriptions']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Low Stock Alerts</span>
                    <span class="metric-value text-red-600 dark:text-red-400"><?php echo $dashboard_data['pms']['low_stock_alerts']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Expiring (30/60/90 days)</span>
                    <span class="text-sm font-semibold">
                        <span class="text-red-500"><?php echo $dashboard_data['pms']['expiring_30']; ?></span> / 
                        <span class="text-orange-500"><?php echo $dashboard_data['pms']['expiring_60']; ?></span> / 
                        <span class="text-amber-500"><?php echo $dashboard_data['pms']['expiring_90']; ?></span>
                    </span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Daily Dispensed</span>
                    <span class="metric-value text-gray-900 dark:text-white"><?php echo $dashboard_data['pms']['daily_dispensed']; ?></span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline">View PMS module →</span>
            </div>
        </a>

        <!-- SORS Panel -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php" class="module-panel-link module-panel bg-white dark:bg-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.6s;">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-500/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/><circle cx="12" cy="12" r="10"/><line x1="14.31" y1="8" x2="20.05" y2="17.94"/><line x1="9.69" y1="8" x2="21.17" y2="8"/><line x1="7.38" y1="12" x2="13.12" y2="2.06"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Surgery (SORS)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Operating Room Scheduler</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Surgeries Today</span>
                    <span class="metric-value text-red-600 dark:text-red-400"><?php echo $dashboard_data['sors']['surgeries_today']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">OR Utilization Rate
                        <div class="stat-ring text-red-500">
                            <svg viewBox="0 0 36 36" class="w-10 h-10">
                                <circle class="stat-ring-bg" cx="18" cy="18" r="16" stroke-width="3"/>
                                <circle class="stat-ring-fill" cx="18" cy="18" r="16" stroke-width="3" 
                                    stroke-dasharray="<?php echo ($dashboard_data['sors']['or_utilization'] * 100.53) / 100; ?> 100.53"/>
                            </svg>
                        </div>
                    </span>
                    <span class="metric-value text-red-600 dark:text-red-400"><?php echo $dashboard_data['sors']['or_utilization']; ?>%</span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Cancelled/Rescheduled</span>
                    <span class="metric-value text-amber-600 dark:text-amber-400"><?php echo $dashboard_data['sors']['cancelled_cases']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Avg Surgery Duration</span>
                    <span class="metric-value text-gray-900 dark:text-white"><?php echo $dashboard_data['sors']['avg_duration']; ?></span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <span class="text-sm font-medium text-red-600 dark:text-red-400 hover:underline">View SORS module →</span>
            </div>
        </a>

        <!-- DNMS Panel -->
        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/patient_diet_assignments.php" class="module-panel-link module-panel bg-white dark:bg-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.65s;">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-orange-500/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nutrition (DNMS)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Diet & Nutrition Management</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Patients on Special Diets</span>
                    <span class="metric-value text-orange-600 dark:text-orange-400"><?php echo $dashboard_data['dnms']['special_diets']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Diet Orders Today</span>
                    <span class="metric-value text-amber-600 dark:text-amber-400"><?php echo $dashboard_data['dnms']['diet_orders_today']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Missed Meal Alerts</span>
                    <span class="metric-value text-red-600 dark:text-red-400"><?php echo $dashboard_data['dnms']['missed_meals']; ?></span>
                </div>
                <div class="metric-item">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Dietitian Assignments</span>
                    <span class="metric-value text-gray-900 dark:text-white"><?php echo $dashboard_data['dnms']['dietitian_assignments']; ?></span>
                </div>
            </div>
            <div class="px-5 pb-5">
                <span class="text-sm font-medium text-orange-600 dark:text-orange-400 hover:underline">View DNMS module →</span>
            </div>
        </a>

        <!-- Quick Actions Panel -->
        <div class="module-panel bg-gradient-to-br from-gray-800 to-gray-900 dark:from-gray-700 dark:to-gray-800 shadow-sm animate-stagger" style="animation-delay: 0.7s;">
            <div class="p-5 border-b border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Quick Actions</h3>
                        <p class="text-xs text-gray-400">Common operations</p>
                    </div>
                </div>
            </div>
            <div class="p-5 space-y-3">
                <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="quick-action-link">
                    <svg class="w-5 h-5 text-teal-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    New Patient / Lab Order
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="quick-action-link">
                    <svg class="w-5 h-5 text-violet-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Order Lab Test
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php" class="quick-action-link">
                    <svg class="w-5 h-5 text-cyan-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Schedule Surgery
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/modules/pms/prescription_monitoring.php" class="quick-action-link">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/></svg>
                    New Prescription
                </a>
            </div>
        </div>
    </div>

    <!-- Predictive Analytics (Real-time) Section -->
    <?php
    $pred_source = $predictive_data['source'] ?? 'demo';
    $pred_badge = $pred_source === 'azure_ml' ? 'AI Forecast' : ($pred_source === 'database' ? 'Real-time' : 'Sample');
    $pred_badge_class = $pred_source === 'azure_ml' ? 'text-indigo-600 dark:text-indigo-400' : ($pred_source === 'database' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400');
    $pred_updated = isset($predictive_data['last_updated']) ? date('M j, Y g:i A', strtotime($predictive_data['last_updated'])) : '';
    ?>
    <div class="mb-6 animate-stagger" style="animation-delay: 0.72s;">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="section-title text-lg font-semibold text-gray-900 dark:text-white">Predictive Analytics</h2>
            <?php if ($pred_updated): ?>
                <span class="text-xs text-gray-500 dark:text-gray-400">Updated <?php echo $pred_updated; ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger border border-indigo-100 dark:border-indigo-900/30" style="animation-delay: 0.74s;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Predicted Patient Volume (7d)</span>
                <span class="text-xs font-medium <?php echo $pred_badge_class; ?>"><?php echo $pred_badge; ?></span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($predictive_data['summary']['avg_patients_7d']); ?></div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg. daily census next 7 days</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger border border-indigo-100 dark:border-indigo-900/30" style="animation-delay: 0.76s;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Predicted Lab Orders (7d)</span>
                <span class="text-xs font-medium <?php echo $pred_badge_class; ?>"><?php echo $pred_badge; ?></span>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($predictive_data['summary']['avg_lab_orders_7d']); ?></div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg. daily lab orders next 7 days</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 animate-stagger border border-indigo-100 dark:border-indigo-900/30" style="animation-delay: 0.78s;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Trend</span>
                <span class="text-xs font-medium <?php echo $predictive_data['summary']['trend'] === 'up' ? 'text-emerald-600 dark:text-emerald-400' : ($predictive_data['summary']['trend'] === 'down' ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400'); ?>">
                    <?php echo ucfirst($predictive_data['summary']['trend']); ?>
                </span>
            </div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white capitalize"><?php echo $predictive_data['summary']['trend']; ?></div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Expected vs. current period</p>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-8 animate-stagger" style="animation-delay: 0.8s;">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-white">Historical vs. Predicted – Patient Volume &amp; Lab Orders</h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">Last 7 days + next 7 days forecast · Live from LIS</span>
        </div>
        <div class="chart-container">
            <canvas id="predictiveChart"></canvas>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="mb-6 animate-stagger" style="animation-delay: 0.75s;">
        <h2 class="section-title text-lg font-semibold text-gray-900 dark:text-white">Operational Analytics</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        <!-- Lab Test Volume Trends -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 0.8s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Lab Test Volume Trends</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Last 7 days</span>
            </div>
            <div class="chart-container">
                <canvas id="labTestChart"></canvas>
            </div>
        </div>

        <!-- Radiology Exam Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 0.85s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Radiology Exam Distribution</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">This month</span>
            </div>
            <div class="chart-container">
                <canvas id="radiologyChart"></canvas>
            </div>
        </div>

        <!-- Medicine Consumption Trends -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 0.9s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Medicine Consumption</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Last 7 days</span>
            </div>
            <div class="chart-container">
                <canvas id="medicineChart"></canvas>
            </div>
        </div>

        <!-- OR Utilization by Room -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 0.95s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">OR Utilization by Room</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Today</span>
            </div>
            <div class="chart-container">
                <canvas id="orUtilizationChart"></canvas>
            </div>
        </div>

        <!-- Diet Plan Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 1s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Diet Plan Distribution</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Active patients</span>
            </div>
            <div class="chart-container">
                <canvas id="dietChart"></canvas>
            </div>
        </div>

        <!-- Critical Alerts Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 animate-stagger" style="animation-delay: 1.05s;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">Critical Alerts</h3>
                <span class="px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full">3 Active</span>
            </div>
            <div class="space-y-3">
                <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="alert-item-link flex items-start gap-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30">
                    <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">Critical Lab Result</p>
                        <p class="text-xs text-red-600 dark:text-red-400 truncate">Patient #2847 - Potassium level critical</p>
                    </div>
                    <span class="text-xs text-red-500 flex-shrink-0">2m ago</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/modules/pms/inventory_stock_levels.php" class="alert-item-link flex items-start gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/30">
                    <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Low Stock Alert</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 truncate">Amoxicillin 500mg - 12 units remaining</p>
                    </div>
                    <span class="text-xs text-amber-500 flex-shrink-0">15m ago</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/patient_diet_assignments.php" class="alert-item-link flex items-start gap-3 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-900/30">
                    <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-orange-800 dark:text-orange-300">Missed Meal</p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 truncate">Room 305 - Lunch not delivered</p>
                    </div>
                    <span class="text-xs text-orange-500 flex-shrink-0">32m ago</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Update last updated timestamp
function updateTimestamp() {
    const now = new Date();
    const options = { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit',
        hour12: true 
    };
    document.getElementById('lastUpdated').textContent = now.toLocaleTimeString('en-US', options);
}
updateTimestamp();

function refreshDashboard() {
    // Add refresh animation
    const btn = event.currentTarget;
    btn.querySelector('svg').style.animation = 'spin 1s linear';
    setTimeout(() => {
        btn.querySelector('svg').style.animation = '';
        updateTimestamp();
    }, 1000);
}

// Chart.js configuration
const isDarkMode = document.documentElement.classList.contains('dark');
const gridColor = isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
const textColor = isDarkMode ? '#9ca3af' : '#6b7280';

Chart.defaults.font.family = "'Outfit', system-ui, sans-serif";
Chart.defaults.color = textColor;

// Predictive Analytics (Azure ML) – Historical + Forecast
const predictiveData = <?php echo json_encode([
    'labels_historical' => $predictive_data['labels_historical'],
    'labels_forecast'   => $predictive_data['labels_forecast'],
    'historical_patient_volume' => $predictive_data['historical_patient_volume'],
    'forecast_patient_volume'  => $predictive_data['forecast_patient_volume'],
    'historical_lab_orders'    => $predictive_data['historical_lab_orders'],
    'forecast_lab_orders'      => $predictive_data['forecast_lab_orders'],
]); ?>;
const allLabels = [...predictiveData.labels_historical, ...predictiveData.labels_forecast];
const historicalPatient = [...predictiveData.historical_patient_volume];
const forecastPatient = [...predictiveData.forecast_patient_volume];
const historicalLab = [...predictiveData.historical_lab_orders];
const forecastLab = [...predictiveData.forecast_lab_orders];
const predictiveEl = document.getElementById('predictiveChart');
if (predictiveEl && typeof Chart !== 'undefined') {
const predictiveCtx = predictiveEl.getContext('2d');
    new Chart(predictiveCtx, {
        type: 'line',
        data: {
            labels: allLabels,
            datasets: [
                {
                    label: 'Patient volume (actual)',
                    data: [...historicalPatient, ...Array(7).fill(null)],
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d9488',
                },
                {
                    label: 'Patient volume (predicted)',
                    data: [...Array(7).fill(null), ...forecastPatient],
                    borderColor: '#6366f1',
                    borderDash: [6, 4],
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
                },
                {
                    label: 'Lab orders (actual)',
                    data: [...historicalLab, ...Array(7).fill(null)],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#8b5cf6',
                },
                {
                    label: 'Lab orders (predicted)',
                    data: [...Array(7).fill(null), ...forecastLab],
                    borderColor: '#ec4899',
                    borderDash: [6, 4],
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ec4899',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 16 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        callback: function(_, i) {
                            if (i === 6) return this.getLabelForValue(i) + ' (today)';
                            if (i === 7) return this.getLabelForValue(i) + ' (fc)';
                            return this.getLabelForValue(i);
                        }
                    }
                }
            }
        }
    });
}

// Lab Test Volume Trends Chart
const labTestEl = document.getElementById('labTestChart');
if (labTestEl) {
const labTestCtx = labTestEl.getContext('2d');
new Chart(labTestCtx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Tests Completed',
            data: [85, 92, 78, 110, 124, 95, 88],
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#8b5cf6',
        }, {
            label: 'Tests Ordered',
            data: [98, 105, 89, 120, 135, 108, 95],
            borderColor: '#14b8a6',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#14b8a6',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { usePointStyle: true, padding: 20 }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                border: { display: false }
            },
            x: {
                grid: { display: false },
                border: { display: false }
            }
        }
    }
});
}

// Radiology Exam Distribution Chart
const radiologyEl = document.getElementById('radiologyChart');
if (radiologyEl) {
const radiologyCtx = radiologyEl.getContext('2d');
new Chart(radiologyCtx, {
    type: 'doughnut',
    data: {
        labels: ['X-Ray', 'CT Scan', 'MRI', 'Ultrasound', 'Mammography'],
        datasets: [{
            data: [35, 25, 18, 15, 7],
            backgroundColor: [
                '#06b6d4',
                '#0ea5e9',
                '#3b82f6',
                '#6366f1',
                '#8b5cf6'
            ],
            borderWidth: 0,
            spacing: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { usePointStyle: true, padding: 15 }
            }
        }
    }
});

// Medicine Consumption Chart
const medicineCtx = document.getElementById('medicineChart').getContext('2d');
new Chart(medicineCtx, {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Medications Dispensed',
            data: [312, 345, 298, 367, 342, 289, 254],
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                border: { display: false }
            },
            x: {
                grid: { display: false },
                border: { display: false }
            }
        }
    }
});
}

// OR Utilization Chart
const orEl = document.getElementById('orUtilizationChart');
if (orEl) {
const orCtx = orEl.getContext('2d');
new Chart(orCtx, {
    type: 'bar',
    data: {
        labels: ['OR 1', 'OR 2', 'OR 3', 'OR 4', 'OR 5'],
        datasets: [{
            label: 'Utilization %',
            data: [92, 78, 85, 65, 88],
            backgroundColor: function(context) {
                const value = context.dataset.data[context.dataIndex];
                if (value >= 85) return 'rgba(239, 68, 68, 0.8)';
                if (value >= 70) return 'rgba(245, 158, 11, 0.8)';
                return 'rgba(16, 185, 129, 0.8)';
            },
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                grid: { color: gridColor },
                border: { display: false },
                ticks: { callback: value => value + '%' }
            },
            y: {
                grid: { display: false },
                border: { display: false }
            }
        }
    }
});

// Diet Plan Distribution Chart
const dietCtx = document.getElementById('dietChart').getContext('2d');
new Chart(dietCtx, {
    type: 'polarArea',
    data: {
        labels: ['Regular', 'Diabetic', 'Low Sodium', 'Renal', 'Cardiac', 'Soft/Liquid'],
        datasets: [{
            data: [45, 22, 18, 12, 15, 8],
            backgroundColor: [
                'rgba(251, 146, 60, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)'
            ],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { usePointStyle: true, padding: 12, font: { size: 11 } }
            }
        },
        scales: {
            r: {
                ticks: { display: false },
                grid: { color: gridColor }
            }
        }
    }
});
}

// Add spin animation
const style = document.createElement('style');
style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(style);

// Listen for dark mode changes
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            // Reload page to update chart colors (or implement chart color update logic)
        }
    });
});
observer.observe(document.documentElement, { attributes: true });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

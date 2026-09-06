<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

$role_name = $_SESSION['role_name'] ?? $_SESSION['user_role'] ?? 'employee';
$role_id = $_SESSION['role_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function for active menu item
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : '';
}

// Helper function to check if current page is in a directory
function isActiveDir($dir) {
    global $current_page;
    $script_path = $_SERVER['PHP_SELF'] ?? '';
    return strpos($script_path, $dir) !== false ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : '';
}

// Helper: is current page in this module (for auto-expand and parent highlight)
function isInModule($pages) {
    global $current_page;
    return in_array($current_page, $pages, true);
}
?>
<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden sidebar-transition"></div>

<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 bg-white dark:bg-gray-800 shadow-xl sidebar-transition transform -translate-x-full lg:translate-x-0 transition-all duration-300">
    <div class="flex flex-col h-full">
        <!-- Logo/Branding -->
        <div class="flex items-center justify-center px-4 py-5 border-b border-gray-200 dark:border-gray-700 transition-all duration-300">
            <div class="flex items-center justify-center sidebar-logo-full w-full">
                <img src="<?php echo BASE_URL; ?>/assets/img/alvion-logo-removebg.png" alt="Alvion" class="h-8 object-contain transition-opacity duration-200">
            </div>
            <div class="flex items-center justify-center sidebar-logo-collapsed hidden w-full">
                <img src="<?php echo BASE_URL; ?>/assets/img/alvion-logo-removebg.png" alt="Alvion" class="h-8 object-contain transition-opacity duration-200">
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-2.5 py-3 space-y-0.5 overflow-y-auto overflow-x-hidden">
            <!-- Dashboard -->
            <a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200 <?php echo $current_page === 'admin-dashboard.php' || strpos($current_page, 'dashboard') !== false ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>
                <span class="sidebar-text text-sm">Dashboard</span>
            </a>

            <!-- CLINICAL OPERATIONS MODULES -->
            <div class="mb-3">
                <h3 class="sidebar-section-title uppercase tracking-wider sidebar-text transition-colors duration-200">
                    Clinical Operations
                </h3>

                <?php $lis_pages = ['test_catalog_management.php','lab_orders_monitoring.php','results_validation_settings.php','reference_ranges.php','lab_performance_reports.php']; $lis_open = isInModule($lis_pages); ?>
                <!-- Laboratory Information System (dropdown) -->
                <button
                    type="button"
                    class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200"
                    data-collapse-target="lis-submenu"
                    aria-expanded="<?php echo $lis_open ? 'true' : 'false'; ?>"
                    aria-controls="lis-submenu"
                >
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="M8 18v-1"></path><path d="M16 18v-3"></path></svg>
                        <span class="sidebar-text">Laboratory Information System</span>
                    </span>
                    <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="lis-submenu" class="sidebar-submenu-wrapper <?php echo $lis_open ? 'is-open' : ''; ?>">
                    <div class="sidebar-submenu-inner space-y-0.5">
                        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/test_catalog_management.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('test_catalog_management.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h8"></path></svg>
                            <span class="sidebar-text">Test Catalog Management</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_orders_monitoring.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('lab_orders_monitoring.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line></svg>
                            <span class="sidebar-text">Lab Orders Monitoring</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/results_validation_settings.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('results_validation_settings.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                            <span class="sidebar-text">Results Validation Settings</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/reference_ranges.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('reference_ranges.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            <span class="sidebar-text">Reference Ranges</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/lis/lab_performance_reports.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('lab_performance_reports.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                            <span class="sidebar-text">Lab Performance Reports</span>
                        </a>
                    </div>
                </div>

                <?php $ris_pages = ['imaging_modalities.php','exam_types_templates.php','radiology_orders_queue.php','report_approval_workflow.php','radiology_analytics.php']; $ris_open = isInModule($ris_pages); ?>
                <!-- Radiology & Imaging System (dropdown) -->
                <button
                    type="button"
                    class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200"
                    data-collapse-target="ris-submenu"
                    aria-expanded="<?php echo $ris_open ? 'true' : 'false'; ?>"
                    aria-controls="ris-submenu"
                >
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M3 12h3"></path><path d="M18 12h3"></path><path d="M12 3v3"></path><path d="M12 18v3"></path></svg>
                        <span class="sidebar-text text-sm">Radiology &amp; Imaging System</span>
                    </span>
                    <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="ris-submenu" class="sidebar-submenu-wrapper <?php echo $ris_open ? 'is-open' : ''; ?>">
                    <div class="sidebar-submenu-inner space-y-0.5">
                        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/imaging_modalities.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('imaging_modalities.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            <span class="sidebar-text">Imaging Modalities</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/exam_types_templates.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('exam_types_templates.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                            <span class="sidebar-text">Exam Types &amp; Templates</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/radiology_orders_queue.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('radiology_orders_queue.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            <span class="sidebar-text">Radiology Orders Queue</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/report_approval_workflow.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('report_approval_workflow.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            <span class="sidebar-text">Report Approval Workflow</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/ris/radiology_analytics.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('radiology_analytics.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                            <span class="sidebar-text">Radiology Analytics</span>
                        </a>
                    </div>
                </div>

                <?php $pms_pages = ['medicine_master_list.php','inventory_stock_levels.php','expiry_batch_tracking.php','prescription_monitoring.php','pharmacy_reports.php']; $pms_open = isInModule($pms_pages); ?>
                <!-- Pharmacy Management System (dropdown) -->
                <button
                    type="button"
                    class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200"
                    data-collapse-target="pms-submenu"
                    aria-expanded="<?php echo $pms_open ? 'true' : 'false'; ?>"
                    aria-controls="pms-submenu"
                >
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
                        <span class="sidebar-text">Pharmacy Management System</span>
                    </span>
                    <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="pms-submenu" class="sidebar-submenu-wrapper <?php echo $pms_open ? 'is-open' : ''; ?>">
                    <div class="sidebar-submenu-inner space-y-0.5">
                        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/medicine_master_list.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('medicine_master_list.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            <span class="sidebar-text">Medicine Master List</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/inventory_stock_levels.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('inventory_stock_levels.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                            <span class="sidebar-text">Inventory &amp; Stock Levels</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/expiry_batch_tracking.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('expiry_batch_tracking.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span class="sidebar-text">Expiry &amp; Batch Tracking</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/prescription_monitoring.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('prescription_monitoring.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                            <span class="sidebar-text">Prescription Monitoring</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/pms/pharmacy_reports.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('pharmacy_reports.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                            <span class="sidebar-text">Pharmacy Reports</span>
                        </a>
                    </div>
                </div>

                <?php $sors_pages = ['or_setup_resources.php','surgery_scheduling_blocks.php','doctor_availability.php','workflow_rules_approvals.php','monitoring_audit_reports.php']; $sors_open = isInModule($sors_pages); ?>
                <!-- OR & Surgery Management (Philippine hospital process) -->
                <button
                    type="button"
                    class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200"
                    data-collapse-target="sors-submenu"
                    aria-expanded="<?php echo $sors_open ? 'true' : 'false'; ?>"
                    aria-controls="sors-submenu"
                >
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path></svg>
                        <span class="sidebar-text text-sm">OR &amp; Surgery Management</span>
                    </span>
                    <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="sors-submenu" class="sidebar-submenu-wrapper <?php echo $sors_open ? 'is-open' : ''; ?>">
                    <div class="sidebar-submenu-inner space-y-0.5">
                        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/or_setup_resources.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('or_setup_resources.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v2"></path><path d="M12 21v2"></path><path d="M4.22 4.22l1.42 1.42"></path><path d="M18.36 18.36l1.42 1.42"></path><path d="M1 12h2"></path><path d="M21 12h2"></path><path d="M4.22 19.78l1.42-1.42"></path><path d="M18.36 5.64l1.42-1.42"></path></svg>
                            <span class="sidebar-text">OR Rooms &amp; Equipment</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/surgery_scheduling_blocks.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('surgery_scheduling_blocks.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span class="sidebar-text">Surgery Schedule &amp; Block Time</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/doctor_availability.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('doctor_availability.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="m9 12 2 2 4-4"></path></svg>
                            <span class="sidebar-text">Doctor Availability</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/workflow_rules_approvals.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('workflow_rules_approvals.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            <span class="sidebar-text">Surgery Request &amp; Clearance</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/sors/monitoring_audit_reports.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('monitoring_audit_reports.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                            <span class="sidebar-text">OR Utilization &amp; Reports</span>
                        </a>
                    </div>
                </div>

                <?php $dnms_pages = ['diet_plans_categories.php','meal_schedules.php','patient_diet_assignments.php','nutrition_compliance_reports.php']; $dnms_open = isInModule($dnms_pages); ?>
                <!-- Diet & Nutrition Management System (dropdown) -->
                <button
                    type="button"
                    class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item transition-all duration-200"
                    data-collapse-target="dnms-submenu"
                    aria-expanded="<?php echo $dnms_open ? 'true' : 'false'; ?>"
                    aria-controls="dnms-submenu"
                >
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 mr-3 flex-shrink-0"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path><path d="M7 2v20"></path><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path></svg>
                        <span class="sidebar-text">Diet &amp; Nutrition Management System</span>
                    </span>
                    <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div id="dnms-submenu" class="sidebar-submenu-wrapper <?php echo $dnms_open ? 'is-open' : ''; ?>">
                    <div class="sidebar-submenu-inner space-y-0.5">
                        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/diet_plans_categories.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('diet_plans_categories.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M12 7v6"></path><path d="M8 10h8"></path></svg>
                            <span class="sidebar-text">Diet Plans &amp; Categories</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/meal_schedules.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('meal_schedules.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span class="sidebar-text">Meal Schedules</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/patient_diet_assignments.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('patient_diet_assignments.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="m16 12 2 2 4-4"></path></svg>
                            <span class="sidebar-text">Patient Diet Assignments</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/modules/dnms/nutrition_compliance_reports.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('nutrition_compliance_reports.php') ? 'is-active' : ''; ?>">
                            <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                            <span class="sidebar-text">Nutrition Compliance Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer - Empty for spacing -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700"></div>
    </div>
</aside>

<script>
    // Professional admin sidebar: smooth dropdown with chevron and aria
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#sidebar [data-collapse-target]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var targetId = btn.getAttribute('data-collapse-target');
                if (!targetId) return;
                var submenu = document.getElementById(targetId);
                if (!submenu) return;
                var isOpen = submenu.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    });
</script>


<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

$role_name = $_SESSION['role_name'] ?? $_SESSION['user_role'] ?? 'doctor';
$current_page = basename($_SERVER['PHP_SELF']);
$script_path = $_SERVER['PHP_SELF'] ?? '';

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : '';
}

function isActiveDir($pathPart) {
    global $script_path;
    return strpos($script_path, $pathPart) !== false ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : '';
}

function isInModule($pages) {
    global $current_page;
    return in_array($current_page, $pages, true);
}

$doctor_base = BASE_URL . '/doctor';
?>
<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden sidebar-transition"></div>

<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 bg-white dark:bg-gray-800 shadow-xl sidebar-transition transform -translate-x-full lg:translate-x-0 transition-all duration-300 w-64">
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-center px-4 py-5 border-b border-gray-200 dark:border-gray-700">
            <div class="sidebar-logo-full w-full text-center">
                <span class="sidebar-text font-semibold text-[#008080]">Doctor Portal</span>
            </div>
            <div class="sidebar-logo-collapsed hidden w-full text-center">
                <span class="sidebar-text font-semibold text-[#008080]">MD</span>
            </div>
        </div>

        <nav class="flex-1 px-2.5 py-3 space-y-0.5 overflow-y-auto overflow-x-hidden">
            <!-- 1) Home -->
            <?php $home_pages = ['doctor-dashboard.php', 'index.php', 'schedule.php']; $home_open = isInModule($home_pages) || $current_page === 'doctor-dashboard.php'; ?>
            <div class="mb-2">
                <h3 class="sidebar-section-title uppercase tracking-wider sidebar-text text-xs px-3 py-1.5 text-gray-500 dark:text-gray-400">Home</h3>
                <a href="<?php echo $doctor_base; ?>/doctor-dashboard.php" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item <?php echo $current_page === 'doctor-dashboard.php' || $current_page === 'index.php' ? 'bg-gray-100 dark:bg-gray-700' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>
                    <span class="sidebar-text text-sm">Overview Dashboard</span>
                </a>
                <a href="<?php echo $doctor_base; ?>/schedule.php" class="flex items-center px-3 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item <?php echo isActive('schedule.php'); ?>">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span class="sidebar-text text-sm">Today's Schedule</span>
                </a>
            </div>

            <!-- 2) My Patients -->
            <?php $patients_pages = ['patient_list.php', 'inpatients.php', 'clinic.php', 'recent.php', 'request_access.php']; $patients_open = isInModule($patients_pages) || strpos($script_path, 'doctor/patients') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-patients" aria-expanded="<?php echo $patients_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span class="sidebar-text text-sm">My Patients</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-patients" class="sidebar-submenu-wrapper <?php echo $patients_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/patients/patient_list.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('patient_list.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        <span class="sidebar-text">Patient List</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/patients/inpatients.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('inpatients.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span class="sidebar-text">Assigned Inpatients</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/patients/clinic.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('clinic.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span class="sidebar-text">Clinic Patients</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/patients/recent.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('recent.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span class="sidebar-text">Recently Viewed</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/patients/request_access.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('request_access.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                        <span class="sidebar-text">Add Patient to My List</span>
                    </a>
                </div>
            </div>

            <!-- 3) Patient Chart (context) - link to first patient as example -->
            <?php $chart_open = strpos($script_path, 'chart') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-chart" aria-expanded="<?php echo $chart_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                    <span class="sidebar-text text-sm">Patient Chart</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-chart" class="sidebar-submenu-wrapper <?php echo $chart_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=summary" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Summary</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=vitals" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                        <span class="sidebar-text">Vitals &amp; Flowsheets</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=problems" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span class="sidebar-text">Problems &amp; Diagnoses</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=allergies" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span class="sidebar-text">Allergies</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=medications" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
                        <span class="sidebar-text">Medications</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=orders" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line></svg>
                        <span class="sidebar-text">Orders</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=labs" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="M8 18v-1"></path><path d="M16 18v-3"></path></svg>
                        <span class="sidebar-text">Results – Labs</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=imaging" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M3 12h3"></path><path d="M18 12h3"></path><path d="M12 3v3"></path><path d="M12 18v3"></path></svg>
                        <span class="sidebar-text">Results – Imaging</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=pathology" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h8"></path></svg>
                        <span class="sidebar-text">Results – Pathology</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=notes" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Notes</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=care-team" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span class="sidebar-text">Care Team</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=documents" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Documents</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/chart.php?patient_id=1&tab=timeline" class="sidebar-submenu-link text-gray-600 dark:text-gray-400">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span class="sidebar-text">Clinical Timeline</span>
                    </a>
                </div>
            </div>

            <!-- 4) Appointments & Clinic -->
            <?php $appt_pages = ['appointments.php', 'queue.php', 'referrals.php']; $appt_open = isInModule($appt_pages) || strpos($script_path, 'doctor/appointments') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-appointments" aria-expanded="<?php echo $appt_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span class="sidebar-text text-sm">Appointments &amp; Clinic</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-appointments" class="sidebar-submenu-wrapper <?php echo $appt_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/appointments/appointments.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('appointments.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span class="sidebar-text">Clinic Schedule</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/appointments/queue.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('queue.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        <span class="sidebar-text">Visit Queue</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/appointments/referrals.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('referrals.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="m15 11-2 2 2 2"></path><path d="m9 11 2 2-2 2"></path></svg>
                        <span class="sidebar-text">Referrals</span>
                    </a>
                </div>
            </div>

            <!-- 5) Orders & Prescribing -->
            <?php $orders_pages = ['orders.php', 'order_sets.php', 'prescribing.php', 'labs.php', 'imaging.php', 'signing_queue.php']; $orders_open = isInModule($orders_pages) || strpos($script_path, 'doctor/orders') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-orders" aria-expanded="<?php echo $orders_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                    <span class="sidebar-text text-sm">Orders &amp; Prescribing</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-orders" class="sidebar-submenu-wrapper <?php echo $orders_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/orders/order_sets.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('order_sets.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h8"></path></svg>
                        <span class="sidebar-text">Order Sets</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/orders/prescribing.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('prescribing.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
                        <span class="sidebar-text">Medication Prescribing (eRx)</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/orders/labs.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('labs.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="M8 18v-1"></path><path d="M16 18v-3"></path></svg>
                        <span class="sidebar-text">Lab Orders</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/orders/imaging.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('imaging.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M3 12h3"></path><path d="M18 12h3"></path><path d="M12 3v3"></path><path d="M12 18v3"></path></svg>
                        <span class="sidebar-text">Imaging Requests</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/orders/signing_queue.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('signing_queue.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <span class="sidebar-text">Order Signing Queue</span>
                    </a>
                </div>
            </div>

            <!-- 6) Inpatient Workflow -->
            <?php $inp_pages = ['rounding.php', 'tasks.php', 'consults.php', 'discharge.php', 'handover.php']; $inp_open = isInModule($inp_pages) || strpos($script_path, 'doctor/inpatient') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-inpatient" aria-expanded="<?php echo $inp_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span class="sidebar-text text-sm">Inpatient Workflow</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-inpatient" class="sidebar-submenu-wrapper <?php echo $inp_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/inpatient/rounding.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('rounding.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span class="sidebar-text">Rounding List</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/inpatient/tasks.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('tasks.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <span class="sidebar-text">Tasks &amp; To-Dos</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/inpatient/consults.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('consults.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span class="sidebar-text">Consults</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/inpatient/discharge.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('discharge.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        <span class="sidebar-text">Discharge Planning</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/inpatient/handover.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('handover.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 11-3 3 3 3"></path><path d="m8 11 3 3-3 3"></path><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path></svg>
                        <span class="sidebar-text">Handover / Sign-out</span>
                    </a>
                </div>
            </div>

            <!-- 7) Results & Monitoring -->
            <?php $res_pages = ['alerts.php', 'pending.php']; $res_open = isInModule($res_pages) || strpos($script_path, 'doctor/results') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-results" aria-expanded="<?php echo $res_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M12 18v-6"></path><path d="M8 18v-1"></path><path d="M16 18v-3"></path></svg>
                    <span class="sidebar-text text-sm">Results &amp; Monitoring</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-results" class="sidebar-submenu-wrapper <?php echo $res_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/results/alerts.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('alerts.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span class="sidebar-text">Critical Alerts</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/results/pending.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('pending.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span class="sidebar-text">Pending Results</span>
                    </a>
                </div>
            </div>

            <!-- 8) Communication -->
            <?php $comm_pages = ['messaging.php', 'patient_messages.php', 'announcements.php']; $comm_open = isInModule($comm_pages) || strpos($script_path, 'doctor/communication') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-communication" aria-expanded="<?php echo $comm_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span class="sidebar-text text-sm">Communication</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-communication" class="sidebar-submenu-wrapper <?php echo $comm_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/communication/messaging.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('messaging.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span class="sidebar-text">Secure Messaging</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/communication/patient_messages.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('patient_messages.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span class="sidebar-text">Patient Messages</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/communication/announcements.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('announcements.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2l-2-2H9L7 7H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2z"></path><path d="M12 19v4"></path><path d="M8 23h8"></path></svg>
                        <span class="sidebar-text">Announcements</span>
                    </a>
                </div>
            </div>

            <!-- 9) Procedures & Theatre -->
            <?php $proc_pages = ['or_schedule.php', 'procedure_notes.php']; $proc_open = isInModule($proc_pages) || strpos($script_path, 'doctor/procedures') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-procedures" aria-expanded="<?php echo $proc_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span class="sidebar-text text-sm">Procedures &amp; Theatre</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-procedures" class="sidebar-submenu-wrapper <?php echo $proc_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/procedures/or_schedule.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('or_schedule.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span class="sidebar-text">OR Schedule</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/procedures/procedure_notes.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('procedure_notes.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Procedure Notes</span>
                    </a>
                </div>
            </div>

            <!-- 10) Reports & Analytics -->
            <?php $rpt_pages = ['productivity.php', 'quality.php']; $rpt_open = isInModule($rpt_pages) || strpos($script_path, 'doctor/reports') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-reports" aria-expanded="<?php echo $rpt_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                    <span class="sidebar-text text-sm">Reports &amp; Analytics</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-reports" class="sidebar-submenu-wrapper <?php echo $rpt_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/reports/productivity.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('productivity.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path></svg>
                        <span class="sidebar-text">My Productivity</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/reports/quality.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('quality.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span class="sidebar-text">Clinical Quality</span>
                    </a>
                </div>
            </div>

            <!-- 11) Knowledge & Tools -->
            <?php $know_pages = ['guidelines.php', 'drug_reference.php', 'calculators.php', 'templates.php']; $know_open = isInModule($know_pages) || strpos($script_path, 'doctor/knowledge') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-knowledge" aria-expanded="<?php echo $know_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h8"></path></svg>
                    <span class="sidebar-text text-sm">Knowledge &amp; Tools</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-knowledge" class="sidebar-submenu-wrapper <?php echo $know_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/knowledge/guidelines.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('guidelines.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h8"></path></svg>
                        <span class="sidebar-text">Clinical Guidelines</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/knowledge/drug_reference.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('drug_reference.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path><path d="m8.5 8.5 7 7"></path></svg>
                        <span class="sidebar-text">Drug Reference</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/knowledge/calculators.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('calculators.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"></rect><path d="M8 6h8"></path><path d="M8 10h8"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>
                        <span class="sidebar-text">Calculators</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/knowledge/templates.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('templates.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Forms &amp; Templates</span>
                    </a>
                </div>
            </div>

            <!-- 12) Account & Settings -->
            <?php $set_pages = ['profile.php', 'notifications.php', 'signature.php', 'privileges.php', 'audit_log.php']; $set_open = isInModule($set_pages) || strpos($script_path, 'doctor/settings') !== false; ?>
            <button type="button" class="sidebar-module-btn w-full flex items-center justify-between px-3 py-2 mt-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 sidebar-item" data-collapse-target="doctor-settings" aria-expanded="<?php echo $set_open ? 'true' : 'false'; ?>">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span class="sidebar-text text-sm">Account &amp; Settings</span>
                </span>
                <svg class="sidebar-chevron w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div id="doctor-settings" class="sidebar-submenu-wrapper <?php echo $set_open ? 'is-open' : ''; ?>">
                <div class="sidebar-submenu-inner space-y-0.5">
                    <a href="<?php echo $doctor_base; ?>/settings/profile.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('profile.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span class="sidebar-text">My Profile</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/settings/privileges.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('privileges.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span class="sidebar-text">Role &amp; Privileges</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/settings/notifications.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('notifications.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path></svg>
                        <span class="sidebar-text">Notification Preferences</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/settings/signature.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('signature.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        <span class="sidebar-text">Signature &amp; Templates</span>
                    </a>
                    <a href="<?php echo $doctor_base; ?>/settings/audit_log.php" class="sidebar-submenu-link text-gray-600 dark:text-gray-400 <?php echo isActive('audit_log.php') ? 'is-active' : ''; ?>">
                        <svg class="sidebar-submenu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>
                        <span class="sidebar-text">Audit Log (My Activity)</span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="p-3 border-t border-gray-200 dark:border-gray-700"></div>
    </div>
</aside>

<script>
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

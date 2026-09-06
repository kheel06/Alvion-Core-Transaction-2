<?php
require_once __DIR__ . '/../includes/doctor_portal/guard.php';
header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/doctor/doctor-dashboard.php');
exit;

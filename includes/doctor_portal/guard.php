<?php
/**
 * Doctor Portal route guard. Include after config.php.
 */
if (!function_exists('requireAuth') || !function_exists('checkRole')) {
    require_once __DIR__ . '/../../config/config.php';
}
requireAuth();
checkRole(['doctor', 'attending', 'resident', 'consultant', 'admin', 'super admin']);

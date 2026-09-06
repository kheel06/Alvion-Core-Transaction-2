<?php
/**
 * Session timeout for admin: update last activity and optionally redirect if expired.
 * Call this only on admin pages when user is logged in.
 *
 * @return bool True if session is still valid (and last_activity was updated), false if expired.
 */
function checkAdminSessionTimeout() {
    if (!defined('SESSION_TIMEOUT_SECONDS')) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    $now = time();
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
        return true;
    }
    $elapsed = $now - $_SESSION['last_activity'];
    if ($elapsed >= SESSION_TIMEOUT_SECONDS) {
        return false;
    }
    $_SESSION['last_activity'] = $now;
    return true;
}

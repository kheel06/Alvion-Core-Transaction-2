<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
define('SITE_NAME', 'Alvion');
define('SITE_SHORT_NAME', 'ALVION');
define('SITE_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/hospital-core2');

// Theme Colors
define('PRIMARY_COLOR', 'teal');
define('SECONDARY_COLOR', 'sky');
define('ACCENT_COLOR', 'emerald');
define('NEUTRAL_COLOR', 'slate');

// Session timeout (admin): idle time in seconds before logout. Warning shown before this.
if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 120); // 2 minutes
}
if (!defined('SESSION_WARNING_BEFORE_SECONDS')) {
    define('SESSION_WARNING_BEFORE_SECONDS', 30); // warn 30 seconds before logout
}

// reCAPTCHA Configuration
// Get your keys from: https://www.google.com/recaptcha/admin
// For testing, Google provides test keys that always pass: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI (Site Key)
define('RECAPTCHA_SITE_KEY', '6LcBwPQrAAAAAFyq70T-0JYC31_4egSWyI3ijlVf'); // Replace with your actual site key (using test key for development)
define('RECAPTCHA_SECRET_KEY', '6LcBwPQrAAAAAJBf746ZZFevjCSRMAEPB8w7JZ53'); // Replace with your actual secret key (using test key for development)
define('RECAPTCHA_ENABLED', true); // Set to false to disable reCAPTCHA

// Database Configuration
require_once 'database.php';
$database = new Database();
$db = $database->getConnection();

// Include helper functions
require_once __DIR__ . '/../includes/functions.php';

// Include permissions system
require_once __DIR__ . '/permissions.php';

// Ensure the core roles exist in the database
if (function_exists('ensureCoreRoles')) {
    ensureCoreRoles($db);
}

// Include role-based helpers
require_once __DIR__ . '/../includes/role_helpers.php';

// Check if user is logged in for protected pages
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Check user role - supports both role_id (array of integers) and role_name (array of strings)
function checkRole($allowed_roles) {
    $user_role_id = $_SESSION['role_id'] ?? null;
    $user_role_name = $_SESSION['role_name'] ?? $_SESSION['user_role'] ?? null;
    $normalized_role_name = $user_role_name ? normalizeRoleName($user_role_name) : null;
    
    // Super Admin bypasses role gate checks
    if ($normalized_role_name === 'super admin') {
        return;
    }
    
    // Check if allowed_roles contains integers (role_ids) or strings (role_names)
    $first_allowed = $allowed_roles[0] ?? null;
    $is_role_id_check = !empty($allowed_roles) && is_numeric($first_allowed);
    
    if ($is_role_id_check) {
        // Check using role_id
        if (!$user_role_id || !in_array($user_role_id, $allowed_roles)) {
            $_SESSION['error'] = "You don't have permission to access this page.";
            header("Location: ../index.php");
            exit();
        }
        return;
    }
    
    // Check using role_name (backward compatibility)
    $normalized_allowed = [];
    foreach ($allowed_roles as $role) {
        if (is_string($role)) {
            $normalized_allowed[] = normalizeRoleName($role);
        }
    }
    
    if (!$normalized_role_name || (!empty($normalized_allowed) && !in_array($normalized_role_name, $normalized_allowed, true))) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: ../index.php");
        exit();
    }
}

/**
 * Verify reCAPTCHA response
 * @param string $recaptcha_response The reCAPTCHA response token from the form
 * @return bool True if verification succeeds, false otherwise
 */
function verifyRecaptcha($recaptcha_response) {
    if (!defined('RECAPTCHA_ENABLED') || !RECAPTCHA_ENABLED) {
        return true; // Skip verification if disabled
    }
    
    if (empty($recaptcha_response)) {
        return false;
    }
    
    // Allow test keys to pass (Google's test keys always pass verification)
    $isTestKey = (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
    
    if (!defined('RECAPTCHA_SECRET_KEY') || empty(RECAPTCHA_SECRET_KEY)) {
        error_log('reCAPTCHA secret key not configured');
        return true; // Allow login if not configured (for development)
    }
    
    // Test keys always pass
    if ($isTestKey) {
        return true;
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        error_log('reCAPTCHA verification request failed');
        return false;
    }
    
    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}

// Azure Machine Learning – Predictive Analytics
// Set these after deploying a real-time endpoint in Azure ML Studio
define('AZURE_ML_ENDPOINT', '');   // e.g. https://<your-endpoint>.<region>.inference.ml.azure.com/score
define('AZURE_ML_API_KEY', '');    // Primary or secondary key from the endpoint
?>
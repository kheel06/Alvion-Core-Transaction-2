<?php
require_once '../config/config.php';
require_once '../includes/email_helper.php';

if (!empty($_GET['session_expired'])) {
    $_SESSION['error'] = 'Your session expired due to inactivity. Please log in again.';
}

if (!function_exists('maskEmail')) {
    function maskEmail($email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$localPart, $domainPart] = explode('@', $email, 2);

        $maskSegment = function ($segment) {
            $length = strlen($segment);
            if ($length <= 2) {
                return substr($segment, 0, 1) . str_repeat('*', max(0, $length - 1));
            }
            return substr($segment, 0, 1) . str_repeat('*', $length - 2) . substr($segment, -1);
        };

        $domainParts = explode('.', $domainPart);
        $domainName = array_shift($domainParts);
        $maskedDomainName = $maskSegment($domainName);
        $maskedDomain = $maskedDomainName . (!empty($domainParts) ? '.' . implode('.', $domainParts) : '');

        return $maskSegment($localPart) . '@' . $maskedDomain;
    }
}

function initializeLoginController(PDO $db, array $config): array {
    $defaults = [
        'context' => 'standard',
        'redirect' => 'login.php',
        'identifier_field' => 'username',
        'empty_identifier_message' => 'Please enter your credentials.',
        'invalid_credentials_message' => 'Invalid credentials.',
        'inactive_error_message' => 'Your account is inactive. Please contact an administrator.',
        'role_mapping' => [
            // Primary HR/Operations roles
            'super admin' => 1,
            'admin' => 2,
            'staff' => 3,
            'employee' => 4,
            // Legacy mappings kept for backward compatibility
            'doctor' => 5,
            'attending' => 11,
            'resident' => 12,
            'consultant' => 13,
            'nurse' => 6,
            'receptionist' => 7,
            'appointment_coordinator' => 8,
            'billing_staff' => 9,
            'patient' => 10
        ],
        'default_role' => 'employee',
        'default_role_id' => 4,
        'fetch_user' => null,
        'source_table' => 'users',
        'source_identifier_field' => 'id',
    ];

    $config = array_merge($defaults, $config);

    if (!is_callable($config['fetch_user'])) {
        throw new InvalidArgumentException('fetch_user callback is required.');
    }

    if (isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }

    if (isset($_POST['resend_otp'])) {
        handleResendOtpRequest($config['redirect']);
    }

    if (isset($_POST['verify_code'])) {
        handleOtpVerificationSubmission($db, $config['redirect']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verify_code']) && !isset($_POST['resend_otp'])) {
        processPrimaryLoginAttempt($db, $config);
    }

    $resendCountdownShouldStart = isset($_SESSION['resend_cooldown_active']);
    $resendCountdownStartTime = $_SESSION['resend_cooldown_start'] ?? null;
    if ($resendCountdownShouldStart) {
        unset($_SESSION['resend_cooldown_active']);
    }

    // Login lockout state for countdown on button
    $lockoutUntil = (int)($_SESSION['login_lockout_until'] ?? 0);
    $isLoginLocked = $lockoutUntil > 0 && time() < $lockoutUntil;
    $loginLockoutRemaining = $isLoginLocked ? max(0, (int)ceil($lockoutUntil - time())) : 0;

    return [
        'resendCountdownShouldStart' => $resendCountdownShouldStart,
        'resendCountdownStartTime' => $resendCountdownStartTime,
        'loginLocked' => $isLoginLocked,
        'loginLockoutRemaining' => $loginLockoutRemaining,
        'loginLockoutUntil' => $lockoutUntil,
    ];
}

// Login lockout constants: 3 wrong attempts = 30 second lockout
const LOGIN_MAX_ATTEMPTS = 3;
const LOGIN_LOCKOUT_SECONDS = 30;

function recordFailedLoginAttempt(string $invalidMessage): void {
    $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;

    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_lockout_until'] = time() + LOGIN_LOCKOUT_SECONDS;
        unset($_SESSION['login_attempts']);
        $_SESSION['error'] = 'Too many failed login attempts. Account locked for ' . LOGIN_LOCKOUT_SECONDS . ' seconds. Please try again later.';
    } else {
        $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
        $_SESSION['error'] = $invalidMessage . " ({$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining)";
    }
}

function processPrimaryLoginAttempt(PDO $db, array $config): void {
    // Check if currently locked out (3 wrong password attempts)
    $lockoutUntil = $_SESSION['login_lockout_until'] ?? 0;
    if ($lockoutUntil > 0 && time() < $lockoutUntil) {
        $remaining = (int)ceil($lockoutUntil - time());
        $_SESSION['error'] = "Too many failed attempts. Please try again in {$remaining} seconds.";
        return;
    }
    // Clear lockout if expired
    if ($lockoutUntil > 0 && time() >= $lockoutUntil) {
        unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);
    }

    // Verify reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (function_exists('verifyRecaptcha') && !verifyRecaptcha($recaptcha_response)) {
        $_SESSION['error'] = 'Please complete the reCAPTCHA verification.';
        return;
    }
    
    $identifier = sanitizeInput($_POST[$config['identifier_field']] ?? '');
    $password = isset($_POST['password']) ? trim((string)$_POST['password']) : '';

    if (empty($identifier) || empty($password)) {
        $_SESSION['error'] = $config['empty_identifier_message'];
        return;
    }

    $user = call_user_func($config['fetch_user'], $db, $identifier);
    if (!$user) {
        recordFailedLoginAttempt($config['invalid_credentials_message']);
        return;
    }

    $userStatus = $user['status'] ?? null;
    if ($userStatus !== null && $userStatus !== 'active') {
        $_SESSION['error'] = $config['inactive_error_message'];
        return;
    }

    if (!verifyUserPassword($password, $user['password'] ?? null)) {
        recordFailedLoginAttempt($config['invalid_credentials_message']);
        return;
    }

    // Successful password verification - reset attempts
    unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

    $roleMeta = resolveUserRoleMetadata($db, $user, $config);
    $sourceIdentifierField = $config['source_identifier_field'] ?? 'id';
    $sourceIdentifierValue = $user[$sourceIdentifierField] ?? ($user['id'] ?? null);

    $_SESSION['pending_user'] = [
        'id' => $user['id'] ?? $sourceIdentifierValue,
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'] ?? '',
        'role_id' => $roleMeta['role_id'],
        'role_name' => $roleMeta['role_name'],
        'employee_id' => $user['employee_id'] ?? null,
        'login_context' => $config['context'] ?? 'standard',
        'source_table' => $config['source_table'] ?? 'users',
        'source_identifier_field' => $sourceIdentifierField,
        'source_identifier_value' => $sourceIdentifierValue,
    ];

    triggerOtpForPendingUser($user);
}

function resolveUserRoleMetadata(PDO $db, array $user, array $config): array {
    $role_id = $user['role_id'] ?? null;
    $role_name = $user['role'] ?? $config['default_role'];
    
    if (!empty($user['role_id'])) {
        try {
            $role_query = "SELECT role_name FROM roles WHERE id = :role_id";
            $role_stmt = $db->prepare($role_query);
            $role_stmt->bindParam(':role_id', $user['role_id']);
            $role_stmt->execute();
            $role_data = $role_stmt->fetch();
            if ($role_data) {
                $role_name = $role_data['role_name'];
            }
            $role_id = $user['role_id'];
        } catch (PDOException $e) {
            error_log("Roles lookup failed: " . $e->getMessage());
        }
    }
    
    $normalized_role_name = function_exists('normalizeRoleName')
        ? normalizeRoleName($role_name)
        : strtolower((string)$role_name);
    
    if (!$role_id && !empty($normalized_role_name)) {
        try {
            $lookup_stmt = $db->prepare("SELECT id FROM roles WHERE role_name = :role_name LIMIT 1");
            $lookup_stmt->bindValue(':role_name', $normalized_role_name);
            $lookup_stmt->execute();
            $lookup_role = $lookup_stmt->fetch(PDO::FETCH_ASSOC);
            if ($lookup_role) {
                $role_id = (int)$lookup_role['id'];
            }
        } catch (PDOException $e) {
            error_log("Role lookup by name failed: " . $e->getMessage());
        }
    }
    
    if (!$role_id) {
        $role_id = $config['role_mapping'][$normalized_role_name] ?? $config['default_role_id'];
    }
    
    return [
        'role_id' => $role_id,
        'role_name' => $normalized_role_name ?: $config['default_role'],
    ];
}

if (!function_exists('saveOtpToDatabase')) {
    function saveOtpToDatabase(PDO $db, string $code, string $destination, string $sourceTable, int|string|null $userId = null, int|string|null $departmentAccountId = null, string $purpose = 'login'): ?int {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes from now
            
            $stmt = $db->prepare("
                INSERT INTO otp_codes (code, user_id, department_account_id, source_table, destination, purpose, expires_at, attempts, created_at)
                VALUES (:code, :user_id, :department_account_id, :source_table, :destination, :purpose, :expires_at, 0, NOW())
            ");
            
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            
            // Handle user_id - can be int or null
            if ($userId !== null) {
                $stmt->bindValue(':user_id', is_numeric($userId) ? (int)$userId : $userId, is_numeric($userId) ? PDO::PARAM_INT : PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            }
            
            // Handle department_account_id - can be int, string, or null
            if ($departmentAccountId !== null) {
                $stmt->bindValue(':department_account_id', is_numeric($departmentAccountId) ? (int)$departmentAccountId : $departmentAccountId, is_numeric($departmentAccountId) ? PDO::PARAM_INT : PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':department_account_id', null, PDO::PARAM_NULL);
            }
            
            $stmt->bindValue(':source_table', $sourceTable, PDO::PARAM_STR);
            $stmt->bindValue(':destination', $destination, PDO::PARAM_STR);
            $stmt->bindValue(':purpose', $purpose, PDO::PARAM_STR);
            $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return (int)$db->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("Failed to save OTP to database: " . $e->getMessage());
        }
        return null;
    }
}

if (!function_exists('markOtpAsConsumed')) {
    function markOtpAsConsumed(PDO $db, string $code, string $destination): bool {
        try {
            $stmt = $db->prepare("
                UPDATE otp_codes 
                SET consumed_at = NOW() 
                WHERE code = :code 
                AND destination = :destination 
                AND consumed_at IS NULL 
                AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->bindValue(':code', $code, PDO::PARAM_STR);
            $stmt->bindValue(':destination', $destination, PDO::PARAM_STR);
            
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to mark OTP as consumed: " . $e->getMessage());
            return false;
        }
    }
}

function triggerOtpForPendingUser(array $user): void {
    global $db;
    
    $verification_code = generateVerificationCode();
    $code_expiry = time() + (10 * 60);

    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['verification_code_expiry'] = $code_expiry;
    $_SESSION['otp_last_sent'] = time();
    $_SESSION['show_verification_modal'] = true;

    $pending_user = $_SESSION['pending_user'] ?? [];
    $user_email = $pending_user['email'] ?? '';
    $user_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'User');
    $fallbackMessage = null;
    
    // Determine source table and IDs
    $sourceTable = $pending_user['source_table'] ?? 'department_accounts';
    $userId = null;
    $departmentAccountId = null;
    
    if ($sourceTable === 'users') {
        $userId = $pending_user['id'] ?? null;
    } else {
        $departmentAccountId = $pending_user['source_identifier_value'] ?? $pending_user['employee_id'] ?? null;
    }

    if (!empty($user_email)) {
        // Save OTP to database
        saveOtpToDatabase($db, $verification_code, $user_email, $sourceTable, $userId, $departmentAccountId, 'login');
        
        $email_result = sendVerificationCodeEmail($user_email, $user_name, $verification_code);
        if (!$email_result['success']) {
            error_log("Failed to send verification email: " . $email_result['message']);
            $_SESSION['error'] = "Failed to send verification code. Please contact support.";
            $_SESSION['show_verification_modal'] = true;
            $fallbackMessage = "Email delivery failed. Use this one-time code to continue.";
        } else {
            $_SESSION['verification_email'] = $user_email;
            unset($_SESSION['otp_fallback_code'], $_SESSION['otp_fallback_message']);
        }
    } else {
        $_SESSION['error'] = "No email address found for your account. Please contact support.";
        $_SESSION['show_verification_modal'] = true;
        $fallbackMessage = "No email on file. Use this one-time code to continue.";
    }

    if ($fallbackMessage !== null) {
        $_SESSION['otp_fallback_code'] = $verification_code;
        $_SESSION['otp_fallback_message'] = $fallbackMessage;
    }
}

function handleResendOtpRequest(string $redirectPath): void {
    global $db;
    
    if (!isset($_SESSION['pending_user'])) {
        $_SESSION['error'] = "Session expired. Please login again.";
        $_SESSION['show_verification_modal'] = true;
        header("Location: {$redirectPath}");
        exit();
    }

    unset($_SESSION['error']);

    $verification_code = generateVerificationCode();
    $code_expiry = time() + (10 * 60);
    $current_time = time();

    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['verification_code_expiry'] = $code_expiry;
    $_SESSION['otp_last_sent'] = $current_time;
    $_SESSION['resend_cooldown_start'] = $current_time;
    $_SESSION['resend_cooldown_active'] = true;

    $pending_user = $_SESSION['pending_user'];
    $user_email = $pending_user['email'] ?? '';
    $user_name = trim(($pending_user['first_name'] ?? '') . ' ' . ($pending_user['last_name'] ?? '')) ?: ($pending_user['username'] ?? 'User');
    
    // Determine source table and IDs
    $sourceTable = $pending_user['source_table'] ?? 'department_accounts';
    $userId = null;
    $departmentAccountId = null;
    
    if ($sourceTable === 'users') {
        $userId = $pending_user['id'] ?? null;
    } else {
        $departmentAccountId = $pending_user['source_identifier_value'] ?? $pending_user['employee_id'] ?? null;
    }

    if (!empty($user_email)) {
        // Save OTP to database
        saveOtpToDatabase($db, $verification_code, $user_email, $sourceTable, $userId, $departmentAccountId, 'login');
        
        $email_result = sendVerificationCodeEmail($user_email, $user_name, $verification_code);
        if (!$email_result['success']) {
            error_log("Failed to resend verification email: " . $email_result['message']);
            $_SESSION['error'] = "Failed to resend verification code. Please try again.";
            $_SESSION['show_verification_modal'] = true;
        } else {
            $_SESSION['success_message'] = "A new verification code has been sent to your email.";
            $_SESSION['show_verification_modal'] = true;
            $_SESSION['verification_email'] = $user_email;
        }
    } else {
        $_SESSION['error'] = "No email address found for your account. Please contact support.";
        $_SESSION['show_verification_modal'] = true;
    }

    header("Location: {$redirectPath}");
    exit();
}

function handleOtpVerificationSubmission(PDO $db, string $redirectPath): void {
    $entered_code = '';
    if (isset($_POST['code1'], $_POST['code2'], $_POST['code3'], $_POST['code4'], $_POST['code5'], $_POST['code6'])) {
        $entered_code = sanitizeInput($_POST['code1'] . $_POST['code2'] . $_POST['code3'] . $_POST['code4'] . $_POST['code5'] . $_POST['code6']);
    } elseif (isset($_POST['verification_code'])) {
        $entered_code = sanitizeInput($_POST['verification_code']);
    }

    $entered_code = trim((string)$entered_code);
    $stored_code = isset($_SESSION['verification_code']) ? trim((string)$_SESSION['verification_code']) : null;
    $code_expiry = $_SESSION['verification_code_expiry'] ?? null;

    if (empty($entered_code)) {
        $_SESSION['error'] = "Please enter the verification code.";
        $_SESSION['show_verification_modal'] = true;
    } elseif (!$stored_code) {
        $_SESSION['error'] = "No verification code found. Please login again.";
        $_SESSION['show_verification_modal'] = true;
        clearPendingVerificationState();
    } elseif ($code_expiry === null || time() > $code_expiry) {
        $_SESSION['error'] = "Verification code has expired. Please login again.";
        $_SESSION['show_verification_modal'] = true;
        clearPendingVerificationState();
    } elseif ($entered_code !== $stored_code) {
        $_SESSION['error'] = "Wrong OTP. Please try again.";
        $_SESSION['show_verification_modal'] = true;
    } else {
        // Mark OTP as consumed in database
        $pending_user = $_SESSION['pending_user'] ?? [];
        $user_email = $pending_user['email'] ?? '';
        if (!empty($user_email)) {
            markOtpAsConsumed($db, $entered_code, $user_email);
        }
        
        finalizeUserLogin($db);
        return;
    }

    header("Location: {$redirectPath}");
    exit();
}

function clearPendingVerificationState(): void {
    unset($_SESSION['pending_user'], $_SESSION['verification_code'], $_SESSION['verification_code_expiry']);
}

function finalizeUserLogin(PDO $db): void {
    $pending_user = $_SESSION['pending_user'];
    $loginContext = $pending_user['login_context'] ?? 'standard';
    $_SESSION['login_context'] = $loginContext;

    $_SESSION['user_id'] = $pending_user['id'];
    $_SESSION['username'] = $pending_user['username'];
    $_SESSION['first_name'] = $pending_user['first_name'];
    $_SESSION['last_name'] = $pending_user['last_name'];
    $_SESSION['role_id'] = $pending_user['role_id'];
    $resolvedRoleName = $pending_user['role_name'] ?? 'employee';
    if (function_exists('normalizeRoleName')) {
        $resolvedRoleName = normalizeRoleName($resolvedRoleName);
    } else {
        $resolvedRoleName = strtolower((string)$resolvedRoleName);
    }
    $_SESSION['role_name'] = $resolvedRoleName;
    $_SESSION['user_role'] = $resolvedRoleName;
    if (function_exists('getRoleDisplayName')) {
        $_SESSION['role_display_name'] = getRoleDisplayName($resolvedRoleName);
    }

    if (isset($pending_user['employee_id'])) {
        $_SESSION['employee_id'] = $pending_user['employee_id'];
    }

    $sourceTable = $pending_user['source_table'] ?? 'users';
    $sourceField = $pending_user['source_identifier_field'] ?? 'id';
    $sourceValue = $pending_user['source_identifier_value'] ?? $pending_user['id'];

    updateSourceLastLogin($db, $sourceTable, $sourceField, $sourceValue);

    unset($_SESSION['pending_user'], $_SESSION['verification_code'], $_SESSION['verification_code_expiry'], $_SESSION['otp_last_sent']);

    $userFullName = trim(($pending_user['first_name'] ?? '') . ' ' . ($pending_user['last_name'] ?? '')) ?: ($pending_user['username'] ?? 'there');
    $_SESSION['success'] = "Welcome back! 🎉 " . $userFullName . "! You have successfully logged in.";
    header("Location: ../index.php");
    exit();
}

function verifyUserPassword(string $inputPassword, ?string $storedPassword): bool {
    if ($storedPassword === null || $storedPassword === '') {
        return false;
    }

    $storedPassword = trim((string)$storedPassword);
    $isPasswordHash = preg_match('/^\$(2y|2a|2b|argon2i|argon2id|argon2|P)\$/', $storedPassword) === 1;

    if ($isPasswordHash) {
        return password_verify($inputPassword, $storedPassword);
    }

    return hash_equals($storedPassword, $inputPassword);
}

function tableHasColumn(PDO $db, string $table, string $column): bool {
    static $tableColumnCache = [];
    $sanitizedTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $cacheKey = "{$sanitizedTable}.{$column}";

    if (array_key_exists($cacheKey, $tableColumnCache)) {
        return $tableColumnCache[$cacheKey];
    }

    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `{$sanitizedTable}` LIKE :column_name");
        $stmt->bindParam(':column_name', $column);
        $stmt->execute();
        $tableColumnCache[$cacheKey] = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Failed to inspect {$sanitizedTable}.{$column}: " . $e->getMessage());
        $tableColumnCache[$cacheKey] = false;
    }

    return $tableColumnCache[$cacheKey];
}

function userTableHasColumn(PDO $db, string $column): bool {
    return tableHasColumn($db, 'users', $column);
}

function updateSourceLastLogin(PDO $db, string $table, string $field, $value): void {
    if ($value === null) {
        return;
    }

    $sanitizedTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $sanitizedField = preg_replace('/[^A-Za-z0-9_]/', '', $field);

    try {
        $query = "UPDATE `{$sanitizedTable}` SET last_login = NOW() WHERE `{$sanitizedField}` = :identifier";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $value);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Failed to update {$sanitizedTable} last_login: " . $e->getMessage());
    }
}

$departmentTable = 'department_accounts';
$departmentTableSafe = preg_replace('/[^A-Za-z0-9_]/', '', $departmentTable);
$departmentIdentifierColumns = [];

if (tableHasColumn($db, $departmentTable, 'employee_id')) {
    $departmentIdentifierColumns[] = 'employee_id';
}
if (tableHasColumn($db, $departmentTable, 'employee_email')) {
    $departmentIdentifierColumns[] = 'employee_email';
}
if (tableHasColumn($db, $departmentTable, 'email')) {
    $departmentIdentifierColumns[] = 'email';
}
if (tableHasColumn($db, $departmentTable, 'username')) {
    $departmentIdentifierColumns[] = 'username';
}

if (empty($departmentIdentifierColumns)) {
    $departmentIdentifierColumns[] = 'id';
}

$departmentRoleMapping = [
    'admin' => 1,
    'doctor' => 2,
    'nurse' => 3,
    'staff' => 3,
    'employee' => 3,
    'receptionist' => 4,
    'appointment_coordinator' => 5,
    'billing_staff' => 6,
    'finance_staff' => 6,
    'finance staff' => 6,
    'patient' => 7,
];

$loginControllerState = initializeLoginController($db, [
    'context' => 'employee',
    'redirect' => 'employee-login.php',
    'identifier_field' => 'employee_id',
    'empty_identifier_message' => "Please enter both employee ID and password.",
    'invalid_credentials_message' => "Invalid employee ID or password.",
    'role_mapping' => $departmentRoleMapping,
    'default_role' => 'employee',
    'default_role_id' => 3,
    'source_table' => $departmentTableSafe,
    'source_identifier_field' => 'employee_id',
    'fetch_user' => function(PDO $db, string $identifier) use ($departmentTableSafe, $departmentIdentifierColumns) {
        $conditions = array_map(fn($column) => "{$column} = :identifier", $departmentIdentifierColumns);
        $whereClause = implode(' OR ', $conditions);
        $query = "SELECT * FROM `{$departmentTableSafe}` WHERE {$whereClause} LIMIT 1";

        try {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                $record['id'] = $record['id'] ?? $record['employee_id'] ?? ($record['staff_id'] ?? null);
                $record['username'] = $record['username']
                    ?? $record['employee_email']
                    ?? $record['email']
                    ?? $record['employee_id']
                    ?? ($record['id'] ?? null);

                $record['email'] = $record['employee_email']
                    ?? $record['email']
                    ?? ($record['work_email'] ?? $record['company_email'] ?? null);

                $record['first_name'] = $record['first_name']
                    ?? $record['employee_fname']
                    ?? $record['fname']
                    ?? '';

                $record['last_name'] = $record['last_name']
                    ?? $record['employee_lname']
                    ?? $record['lname']
                    ?? '';

                if ((empty($record['first_name']) || empty($record['last_name'])) && !empty($record['full_name'])) {
                    $nameParts = preg_split('/\s+/', trim($record['full_name']), 2);
                    $record['first_name'] = $record['first_name'] ?: ($nameParts[0] ?? '');
                    if (count($nameParts) === 2) {
                        $record['last_name'] = $record['last_name'] ?: $nameParts[1];
                    }
                }

                $record['role'] = $record['role']
                    ?? $record['role_name']
                    ?? $record['account_type']
                    ?? 'employee';

                $record['role_name'] = $record['role_name'] ?? $record['role'];

                if (!isset($record['status'])) {
                    if (isset($record['is_active'])) {
                        $record['status'] = ((int)$record['is_active'] === 1) ? 'active' : 'inactive';
                    } elseif (isset($record['account_status'])) {
                        $record['status'] = $record['account_status'];
                    }
                }

                $record['employee_id'] = $record['employee_id'] ?? ($record['staff_id'] ?? $record['id'] ?? null);

                if (isset($record['password'])) {
                    $record['password'] = trim($record['password']);
                }
            }

            return $record;
        } catch (PDOException $e) {
            error_log("Department account lookup failed: " . $e->getMessage());
            return null;
        }
    },
]);

$resendCountdownShouldStart = $loginControllerState['resendCountdownShouldStart'];
$resendCountdownStartTime = $loginControllerState['resendCountdownStartTime'];
$loginLocked = $loginControllerState['loginLocked'];
$loginLockoutRemaining = $loginControllerState['loginLockoutRemaining'];
$loginLockoutUntil = $loginControllerState['loginLockoutUntil'];
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Employee Login - <?php echo SITE_NAME; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/toast.js"></script>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/alvion-emblem-removebg.png">

    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED && defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>

    <style>
        :root {
            --ink: #0b1026;
            --muted: #6e7892;
            --line: #e4e8f0;
            --surface: #ffffff;
            --purple: #5542f5;
            --purple-dark: #25245d;
            --cyan: #6c7cff;
            --coral: #ec6b62;
            --gold: #f5bf72;
            --soft: #f7f8fc;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            min-height: 100%;
            margin: 0;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 6% 92%, rgba(109, 122, 255, 0.12) 0 7rem, transparent 7.1rem),
                radial-gradient(circle at 96% 14%, rgba(235, 166, 121, 0.14) 0 8rem, transparent 8.1rem),
                linear-gradient(135deg, #eef0fb 0%, #f8f6fb 48%, #fbf2ea 100%);
        }

        button, input { font: inherit; }

        .page-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px 18px;
        }

        .login-card {
            width: min(1180px, 100%);
            min-height: 760px;
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(360px, 0.72fr);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.72);
            border-radius: 32px;
            background: #fff;
            box-shadow:
                0 38px 90px rgba(31, 35, 78, .14),
                0 12px 32px rgba(31, 35, 78, .08);
        }

        .brand-panel {
            position: relative;
            isolation: isolate;
            min-height: 760px;
            padding: 18px;
            overflow: hidden;
            background:
                radial-gradient(circle at 86% 92%, rgba(99, 102, 241, .48), transparent 20rem),
                linear-gradient(145deg, #161b37 0%, #342d78 46%, #5247eb 100%);
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -3;
            background-image: url('<?php echo BASE_URL; ?>/assets/img/alvion-building.png');
            background-size: cover;
            background-position: center;
            opacity: .11;
            filter: saturate(.65) contrast(1.08);
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            background: linear-gradient(145deg, rgba(15,18,52,.92) 0%, rgba(45,35,112,.88) 48%, rgba(79,67,227,.84) 100%);
        }

        .brand-inner {
            position: relative;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 30px;
            padding: 34px;
            overflow: hidden;
        }

        .brand-inner::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -140px;
            bottom: -125px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 0 0 36px rgba(255,255,255,.035), 0 0 0 72px rgba(255,255,255,.022);
        }

        .portal-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            align-self: flex-start;
            min-height: 38px;
            padding: 0 15px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.12);
            color: rgba(255,255,255,.94);
            font-size: 13px;
            letter-spacing: .01em;
            backdrop-filter: blur(12px);
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--gold);
            box-shadow: 0 0 0 4px rgba(245,191,114,.10);
        }

        .brand-icon {
            width: 66px;
            height: 66px;
            display: grid;
            place-items: center;
            margin-top: 38px;
            border-radius: 18px;
            border: 1px solid rgba(98,154,255,.6);
            background: rgba(21,25,55,.24);
            color: #ffd597;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
        }

        .eyebrow {
            margin-top: 26px;
            color: #eec793;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .28em;
            text-transform: uppercase;
        }

        .hero-title {
            max-width: 620px;
            margin: 18px 0 0;
            color: #fff;
            font-size: clamp(46px, 5vw, 68px);
            line-height: .97;
            letter-spacing: -.045em;
            font-weight: 800;
        }

        .hero-copy {
            max-width: 590px;
            margin-top: 24px;
            color: rgba(255,255,255,.82);
            font-size: 17px;
            line-height: 1.65;
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: auto;
            padding-top: 58px;
        }

        .security-card {
            min-height: 98px;
            padding: 18px 20px;
            border: 1px solid rgba(255,255,255,.17);
            border-radius: 20px;
            background: rgba(255,255,255,.105);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
            backdrop-filter: blur(14px);
        }

        .security-value {
            color: #fff;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .security-label {
            margin-top: 6px;
            color: rgba(255,255,255,.78);
            font-size: 12px;
            line-height: 1.45;
        }

        .brand-footer {
            margin-top: 24px;
            color: rgba(255,255,255,.55);
            font-size: 11px;
        }

        .form-panel {
            position: relative;
            padding: 52px clamp(28px, 4vw, 54px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .form-panel-inner {
            width: min(100%, 390px);
            margin-inline: auto;
        }

        .brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .brand-lockup-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #f1f0ff 0%, #fff1ec 100%);
            color: #4c3ef0;
            box-shadow: 0 8px 20px rgba(83,68,240,.12);
        }

        .brand-lockup-eyebrow {
            color: #5946ff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .24em;
            text-transform: uppercase;
        }

        .brand-lockup-name {
            margin-top: 2px;
            color: var(--ink);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .form-title {
            margin-top: 40px;
            color: var(--ink);
            font-size: clamp(34px, 3.1vw, 44px);
            line-height: 1.04;
            letter-spacing: -.045em;
            font-weight: 800;
        }

        .form-subtitle {
            margin-top: 13px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
        }

        .login-form {
            margin-top: 34px;
        }

        .field-group + .field-group { margin-top: 22px; }

        .field-label-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 9px;
        }

        .field-label {
            color: #46516a;
            font-size: 13px;
            font-weight: 700;
        }

        .field-meta {
            color: #8fa0bb;
            font-size: 12px;
            font-weight: 600;
        }

        .input-wrap { position: relative; }

        .login-input {
            width: 100%;
            min-height: 58px;
            padding: 0 48px 0 50px;
            border: 1px solid #dbe1ec;
            border-radius: 17px;
            outline: none;
            background: #fff;
            color: var(--ink);
            font-size: 14px;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease, background .2s ease;
            box-shadow: 0 1px 2px rgba(20,26,54,.02);
        }

        .login-input::placeholder { color: #a7afc2; }
        .login-input:hover { border-color: #cfd7e6; }
        .login-input:focus {
            border-color: #6657f6;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(101,85,246,.09), 0 12px 28px rgba(81,68,219,.06);
        }
        .login-input:disabled { background: #f5f6fa; cursor: not-allowed; }

        .input-icon {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            color: #5f53f4;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 12px;
            color: #8590a7;
            background: transparent;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .password-toggle:hover {
            background: #f4f3ff;
            color: #4f42df;
        }

        .recaptcha-wrap {
            margin-top: 22px;
            display: flex;
            justify-content: center;
            padding: 12px;
            border: 1px solid #eef0f5;
            border-radius: 18px;
            background: #fafbfe;
            overflow-x: auto;
        }

        .login-button,
        .verify-button {
            width: 100%;
            min-height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
            border: 0;
            border-radius: 17px;
            color: #fff;
            background: linear-gradient(105deg, #5437ef 0%, #7b3ee8 55%, #dc655f 100%);
            box-shadow: 0 16px 28px rgba(84, 56, 232, .20);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -.01em;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        .login-button:hover:not(:disabled),
        .verify-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(84, 56, 232, .24);
            filter: saturate(1.04);
        }

        .login-button:active:not(:disabled),
        .verify-button:active:not(:disabled) { transform: translateY(0); }
        .login-button:disabled,
        .verify-button:disabled { opacity: .7; cursor: not-allowed; }

        .security-note {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 14px 15px;
            border: 1px solid #dce4ff;
            border-radius: 17px;
            color: #56627e;
            background: linear-gradient(180deg, #f6f7ff 0%, #f4f6ff 100%);
            font-size: 12px;
            line-height: 1.55;
        }

        .security-note-icon {
            flex: 0 0 auto;
            width: 24px;
            height: 24px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: #e8eaff;
            color: #5849ef;
        }

        /* OTP modal */
        .otp-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            padding: 18px;
            background: rgba(10, 13, 31, .56);
            backdrop-filter: blur(11px);
        }

        .otp-modal.is-hidden { display: none; }
        .otp-dialog {
            position: relative;
            width: min(100%, 520px);
            max-height: min(760px, calc(100vh - 36px));
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,.8);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 32px 80px rgba(8, 10, 27, .28);
            animation: otpIn .24s ease-out;
        }

        @keyframes otpIn {
            from { opacity: 0; transform: translateY(14px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .otp-topbar {
            height: 8px;
            background: linear-gradient(90deg, #5437ef 0%, #7b3ee8 55%, #dc655f 100%);
        }

        .otp-content { padding: 28px; }

        .otp-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border: 1px solid #e8eaf1;
            border-radius: 12px;
            color: #8b93a8;
            background: rgba(255,255,255,.9);
            cursor: pointer;
            transition: all .18s ease;
        }

        .otp-close:hover { color: #3b355b; background: #f7f8fc; }

        .otp-header { text-align: left; padding-right: 42px; }

        .otp-icon {
            width: 60px;
            height: 60px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            color: #5948f3;
            background: linear-gradient(145deg, #f0efff 0%, #fff0ea 100%);
        }

        .otp-kicker {
            margin-top: 20px;
            color: #5b4aff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .2em;
            text-transform: uppercase;
        }

        .otp-title {
            margin-top: 7px;
            color: var(--ink);
            font-size: 30px;
            line-height: 1.06;
            letter-spacing: -.04em;
            font-weight: 800;
        }

        .otp-description {
            margin-top: 11px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .otp-email {
            color: #4637c6;
            font-weight: 800;
            word-break: break-word;
        }

        .otp-alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-top: 20px;
            padding: 12px 14px;
            border-radius: 15px;
            font-size: 12px;
            line-height: 1.5;
        }

        .otp-alert.error {
            border: 1px solid #ffd7dc;
            color: #a63d4a;
            background: #fff6f7;
        }
        .otp-alert.success {
            border: 1px solid #d8efdf;
            color: #2f7650;
            background: #f3fbf6;
        }
        .otp-alert.manual {
            border: 1px solid #f6dfae;
            color: #855e1f;
            background: #fffaf0;
        }

        .manual-code {
            margin-top: 6px;
            color: #5d4617;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: .27em;
        }

        .otp-code-row {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-top: 22px;
        }

        .code-input {
            width: 100%;
            aspect-ratio: 1 / 1;
            min-width: 0;
            border: 1px solid #d9deea;
            border-radius: 16px;
            outline: none;
            background: #fafbfe;
            color: var(--ink);
            text-align: center;
            font-size: 26px;
            font-weight: 800;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease, transform .2s ease;
        }

        .code-input:hover { border-color: #cbd2e0; }
        .code-input:focus {
            border-color: #6255f6;
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 0 0 4px rgba(98,85,246,.09), 0 12px 24px rgba(79,66,220,.08);
        }

        .otp-helper {
            margin-top: 13px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            color: #8a93a8;
            font-size: 11px;
            line-height: 1.5;
        }

        .otp-helper strong { color: #58627c; }

        .resend-row {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: #8a93a8;
            font-size: 12px;
        }

        .resend-button {
            border: 0;
            padding: 0;
            color: #4f42dc;
            background: transparent;
            font-weight: 800;
            cursor: pointer;
        }
        .resend-button:disabled { color: #a8aec0; cursor: not-allowed; }

        .otp-footer-note {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #edf0f5;
            color: #97a0b3;
            text-align: center;
            font-size: 11px;
            line-height: 1.55;
        }

        .auto-dismiss {
            transition: opacity .3s ease, transform .3s ease;
        }

        @media (max-width: 1000px) {
            .login-card {
                grid-template-columns: 1fr;
                max-width: 740px;
            }
            .brand-panel {
                min-height: auto;
            }
            .brand-inner {
                min-height: 420px;
            }
            .security-grid { margin-top: 48px; }
            .form-panel { min-height: 620px; }
        }

        @media (max-width: 640px) {
            .page-shell { padding: 12px; align-items: stretch; }
            .login-card { border-radius: 24px; min-height: auto; }
            .brand-panel { padding: 10px; border-radius: 24px 24px 0 0; }
            .brand-inner { min-height: 0; border-radius: 20px; padding: 22px; }
            .portal-pill { min-height: 34px; padding-inline: 12px; font-size: 11px; }
            .brand-icon { width: 56px; height: 56px; margin-top: 25px; }
            .eyebrow { margin-top: 20px; font-size: 11px; }
            .hero-title { margin-top: 12px; font-size: clamp(34px, 11vw, 48px); }
            .hero-copy { margin-top: 15px; font-size: 13px; line-height: 1.6; }
            .security-grid { grid-template-columns: 1fr; gap: 10px; margin-top: 28px; padding-top: 0; }
            .security-card { min-height: 74px; display: flex; align-items: center; gap: 12px; padding: 13px 15px; }
            .security-value { font-size: 18px; min-width: 52px; }
            .security-label { margin-top: 0; }
            .brand-footer { margin-top: 18px; }

            .form-panel { padding: 34px 22px 28px; min-height: auto; }
            .form-title { margin-top: 30px; font-size: 34px; }
            .form-subtitle { font-size: 13px; }
            .login-form { margin-top: 28px; }
            .login-input { min-height: 55px; }

            .otp-modal { padding: 10px; align-items: end; }
            .otp-dialog {
                width: 100%;
                max-height: calc(100vh - 20px);
                border-radius: 24px 24px 20px 20px;
            }
            .otp-content { padding: 22px 18px 20px; }
            .otp-close { top: 14px; right: 14px; width: 38px; height: 38px; }
            .otp-header { padding-right: 34px; }
            .otp-icon { width: 54px; height: 54px; border-radius: 16px; }
            .otp-title { font-size: 26px; }
            .otp-description { font-size: 12px; }
            .otp-code-row { gap: 7px; }
            .code-input { border-radius: 13px; font-size: 22px; }
            .otp-helper { flex-direction: column; align-items: flex-start; }
            .verify-button { min-height: 54px; }
        }

        @media (max-width: 390px) {
            .otp-code-row { gap: 5px; }
            .code-input { border-radius: 11px; font-size: 20px; }
        }

        /* =========================================================
           Compact one-screen layout + reCAPTCHA overflow fix
           ========================================================= */
        html, body { height: 100%; }
        body { overflow: hidden; }

        .page-shell {
            min-height: 100svh;
            height: 100svh;
            padding: 16px;
            overflow: hidden;
        }

        .login-card {
            height: min(760px, calc(100svh - 32px));
            min-height: 0;
            max-height: calc(100svh - 32px);
        }

        .brand-panel { min-height: 0; }
        .brand-inner { min-height: 0; padding: 28px; }
        .brand-icon { margin-top: 30px; }
        .eyebrow { margin-top: 20px; }
        .hero-title { margin-top: 14px; font-size: clamp(42px, 4.6vw, 62px); }
        .hero-copy { margin-top: 18px; font-size: 15px; line-height: 1.55; }
        .security-grid { padding-top: 28px; }
        .security-card { min-height: 86px; padding: 15px 17px; }
        .brand-footer { margin-top: 14px; }

        .form-panel {
            min-height: 0;
            padding: 30px clamp(24px, 3.2vw, 46px);
            overflow: hidden;
        }

        .form-title { margin-top: 28px; font-size: clamp(32px, 3vw, 40px); }
        .form-subtitle { margin-top: 9px; font-size: 13px; line-height: 1.6; }
        .login-form { margin-top: 24px; }
        .field-group + .field-group { margin-top: 16px; }
        .field-label-row { margin-bottom: 7px; }
        .login-input { min-height: 54px; }

        /* The Google reCAPTCHA iframe is wider than the inner box on some
           browsers/scales. Never allow a horizontal scrollbar to appear. */
        .recaptcha-wrap {
            width: 100%;
            margin-top: 16px;
            min-height: 78px;
            padding: 8px;
            overflow: hidden !important;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .recaptcha-wrap .g-recaptcha {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 100%;
            overflow: hidden;
        }

        .recaptcha-wrap iframe {
            display: block;
            max-width: 100%;
        }

        .login-button,
        .verify-button {
            min-height: 52px;
            margin-top: 16px;
        }

        .security-note {
            margin-top: 12px;
            padding: 11px 12px;
            font-size: 11px;
        }

        /* Keep the OTP dialog compact as well, so it never creates a
           page-level scrollbar. The dialog itself can scroll only when
           absolutely necessary on very small devices. */
        .otp-modal {
            padding: 12px;
            overflow: hidden;
        }

        .otp-dialog {
            max-height: calc(100svh - 24px);
            overflow-x: hidden;
            overflow-y: auto;
        }

        @media (max-width: 1000px) {
            body { overflow: auto; }
            .page-shell {
                height: auto;
                min-height: 100svh;
                padding: 16px;
                overflow: visible;
            }
            .login-card {
                height: auto;
                max-height: none;
                min-height: 0;
            }
            .brand-panel { min-height: auto; }
            .form-panel { min-height: auto; }
        }

        @media (max-width: 640px) {
            .page-shell { padding: 10px; }
            .login-card { max-height: none; }
            .brand-inner { padding: 20px; }
            .brand-icon { margin-top: 22px; }
            .hero-title { font-size: clamp(34px, 10.5vw, 46px); }
            .hero-copy { font-size: 13px; }
            .security-grid { padding-top: 20px; }
            .form-panel { padding: 28px 20px 24px; }
            .form-title { margin-top: 24px; font-size: 32px; }
            .login-form { margin-top: 22px; }

            .recaptcha-wrap {
                min-height: 76px;
                padding: 6px 4px;
            }

            /* Scale the 304px reCAPTCHA widget slightly on narrow screens
               instead of allowing it to force horizontal overflow. */
            .recaptcha-wrap .g-recaptcha {
                transform: scale(.92);
                transform-origin: center center;
            }
        }

        @media (max-width: 370px) {
            .recaptcha-wrap .g-recaptcha { transform: scale(.82); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordField = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const passwordEyeIcon = document.getElementById('passwordEyeIcon');

            if (togglePasswordBtn && passwordField && passwordEyeIcon) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPassword = passwordField.getAttribute('type') === 'password';
                    const nextType = isPassword ? 'text' : 'password';
                    passwordField.setAttribute('type', nextType);
                    togglePasswordBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    togglePasswordBtn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');

                    if (isPassword) {
                        passwordEyeIcon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" y1="2" x2="22" y2="22"></line>';
                    } else {
                        passwordEyeIcon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>';
                    }
                });
            }

            let codeInputsSetup = false;

            function syncFullCode() {
                const codeInputs = document.querySelectorAll('.code-input');
                const code = Array.from(codeInputs).map(function (input) { return input.value || ''; }).join('');
                const fullCode = document.getElementById('fullCode');
                if (fullCode) fullCode.value = code;
                return code;
            }

            function setupCodeInputs() {
                if (codeInputsSetup) return;
                codeInputsSetup = true;

                const codeInputs = document.querySelectorAll('.code-input');
                if (!codeInputs.length) return;

                <?php if (isset($_SESSION['error']) && isset($_SESSION['show_verification_modal'])): ?>
                    codeInputs.forEach(function (input) { input.value = ''; });
                <?php endif; ?>

                codeInputs.forEach(function (input, index) {
                    if (input.dataset.listenerAdded === 'true') return;
                    input.dataset.listenerAdded = 'true';

                    input.addEventListener('input', function (event) {
                        const value = (event.target.value || '').replace(/[^0-9]/g, '');
                        event.target.value = value.slice(-1);
                        syncFullCode();

                        if (event.target.value && index < codeInputs.length - 1) {
                            codeInputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('paste', function (event) {
                        event.preventDefault();
                        const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, codeInputs.length);
                        if (!pasted) return;

                        pasted.split('').forEach(function (digit, digitIndex) {
                            if (codeInputs[digitIndex]) codeInputs[digitIndex].value = digit;
                        });

                        syncFullCode();
                        const focusIndex = Math.min(pasted.length, codeInputs.length) - 1;
                        if (focusIndex >= 0 && codeInputs[focusIndex]) codeInputs[focusIndex].focus();
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Backspace' && !event.target.value && index > 0) {
                            codeInputs[index - 1].focus();
                            codeInputs[index - 1].select();
                        } else if (event.key === 'ArrowLeft' && index > 0) {
                            codeInputs[index - 1].focus();
                        } else if (event.key === 'ArrowRight' && index < codeInputs.length - 1) {
                            codeInputs[index + 1].focus();
                        } else if (event.key === 'Enter') {
                            const code = syncFullCode();
                            if (code.length === codeInputs.length) {
                                event.preventDefault();
                                document.getElementById('verificationForm')?.requestSubmit();
                            }
                        }
                    });
                });

                codeInputs[0].focus();
            }

            <?php if (isset($_SESSION['show_verification_modal']) && $_SESSION['show_verification_modal']): ?>
                const modal = document.getElementById('verificationModal');
                if (modal) {
                    modal.classList.remove('is-hidden');
                    document.body.classList.add('overflow-hidden');
                    setTimeout(setupCodeInputs, 100);
                }
            <?php
                $error_message = $_SESSION['error'] ?? '';
                $is_otp_error = stripos($error_message, 'OTP') !== false ||
                                stripos($error_message, 'verification') !== false ||
                                stripos($error_message, 'code') !== false;

                if (!isset($_SESSION['error']) || !$is_otp_error) {
                    unset($_SESSION['show_verification_modal']);
                }
            endif; ?>

            setupCodeInputs();

            const verificationModal = document.getElementById('verificationModal');
            const closeOtpButtons = document.querySelectorAll('[data-close-otp]');
            closeOtpButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (verificationModal) verificationModal.classList.add('is-hidden');
                    document.body.classList.remove('overflow-hidden');
                });
            });

            verificationModal?.addEventListener('click', function (event) {
                if (event.target === verificationModal) {
                    verificationModal.classList.add('is-hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && verificationModal && !verificationModal.classList.contains('is-hidden')) {
                    verificationModal.classList.add('is-hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            <?php if (isset($_SESSION['error']) && !isset($_SESSION['show_verification_modal'])): ?>
                showToast('error', <?php echo json_encode($_SESSION['error']); ?>);
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 pointer-events-none"></div>

    <main class="page-shell">
        <section class="login-card" aria-label="Employee secure login">
            <div class="brand-panel">
                <div class="brand-inner">
                    <div class="portal-pill">
                        <span class="status-dot" aria-hidden="true"></span>
                        Secure Employee Portal
                    </div>

                    <div class="brand-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 4.8 6.2v5.3c0 4.4 2.7 7.8 7.2 9.5 4.5-1.7 7.2-5.1 7.2-9.5V6.2L12 3Z"></path>
                            <path d="m9.2 12.3 1.9 1.9 3.9-4.1"></path>
                        </svg>
                    </div>

                    <div class="eyebrow">Alvion Workforce</div>
                    <h1 class="hero-title">Built for fast, secure employee access.</h1>
                    <p class="hero-copy">
                        Sign in to manage your daily workflow, view dashboard updates, and continue protected hospital operations with a refined two-step login experience.
                    </p>

                    <div class="security-grid" aria-label="Security highlights">
                        <div class="security-card">
                            <div class="security-value">2FA</div>
                            <div class="security-label">Email code verification</div>
                        </div>
                        <div class="security-card">
                            <div class="security-value">30s</div>
                            <div class="security-label">Smart login lockout</div>
                        </div>
                        <div class="security-card">
                            <div class="security-value">24/7</div>
                            <div class="security-label">Protected staff access</div>
                        </div>
                    </div>

                    <div class="brand-footer">
                        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                    </div>
                </div>
            </div>

            <div class="form-panel">
                <div class="form-panel-inner">
                    <div class="brand-lockup">
                        <div class="brand-lockup-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18"></path>
                                <path d="M5 21V7.5L12 3l7 4.5V21"></path>
                                <path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01M8.5 14h.01M12 14h.01M15.5 14h.01"></path>
                                <path d="M10 21v-3h4v3"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="brand-lockup-eyebrow">Employee Login</div>
                            <div class="brand-lockup-name">Alvion</div>
                        </div>
                    </div>

                    <h2 class="form-title">Welcome back</h2>
                    <p class="form-subtitle">Enter your employee credentials to continue to the secure staff dashboard.</p>

                    <form id="employeeLoginForm" class="login-form" method="POST" novalidate>
                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="employee_id" class="field-label">Employee ID</label>
                            </div>
                            <div class="input-wrap">
                                <span class="input-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                        <path d="M8 10h8M8 14h4"></path>
                                    </svg>
                                </span>
                                <input
                                    id="employee_id"
                                    name="employee_id"
                                    type="text"
                                    required
                                    autocomplete="username"
                                    autocapitalize="off"
                                    spellcheck="false"
                                    class="login-input"
                                    placeholder="Enter your employee ID"
                                    value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>"
                                    <?php if ($loginLocked): ?>disabled<?php endif; ?>
                                >
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="password" class="field-label">Password</label>
                                <span class="field-meta">Protected access</span>
                            </div>
                            <div class="input-wrap">
                                <span class="input-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3.5" y="10.5" width="17" height="10" rx="2"></rect>
                                        <path d="M7.5 10.5V7a4.5 4.5 0 0 1 9 0v3.5"></path>
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    class="login-input"
                                    placeholder="Enter your account password"
                                    <?php if ($loginLocked): ?>disabled<?php endif; ?>
                                >
                                <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password" title="Show password">
                                    <svg id="passwordEyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED && defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)): ?>
                        <div class="recaptcha-wrap">
                            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>" data-theme="light"></div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" id="loginButton" class="login-button" <?php if ($loginLocked): ?>disabled<?php endif; ?>>
                            <?php if ($loginLocked): ?>
                                <span>Locked — Try again in <strong id="loginCountdown"><?php echo $loginLockoutRemaining; ?></strong>s</span>
                            <?php else: ?>
                                <span>Sign in securely</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path>
                                </svg>
                            <?php endif; ?>
                        </button>
                    </form>

                    <div class="security-note">
                        <span class="security-note-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3 4.8 6.2v5.3c0 4.4 2.7 7.8 7.2 9.5 4.5-1.7 7.2-5.1 7.2-9.5V6.2L12 3Z"></path>
                                <path d="m9.2 12.3 1.9 1.9 3.9-4.1"></path>
                            </svg>
                        </span>
                        <span>Your session uses password verification, email OTP, and temporary lockout protection.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Verification Modal -->
    <div id="verificationModal" class="otp-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="otpTitle" aria-describedby="otpDescription">
        <div class="otp-dialog">
            <div class="otp-topbar" aria-hidden="true"></div>

            <button type="button" class="otp-close" data-close-otp aria-label="Close verification dialog" title="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>

            <div class="otp-content">
                <div class="otp-header">
                    <div class="otp-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="m3 7 7.8 5.1a2.3 2.3 0 0 0 2.4 0L21 7"></path>
                        </svg>
                    </div>
                    <div class="otp-kicker">Two-step verification</div>
                    <h3 id="otpTitle" class="otp-title">Enter your 6-digit code</h3>
                    <p id="otpDescription" class="otp-description">
                        We sent a one-time verification code to
                        <span class="otp-email">
                            <?php
                            if (isset($_SESSION['verification_email']) && !empty($_SESSION['verification_email'])) {
                                echo htmlspecialchars(maskEmail($_SESSION['verification_email']));
                            } else {
                                echo 'your email';
                            }
                            ?>
                        </span>.
                    </p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="otp-alert error auto-dismiss" role="alert" data-auto-dismiss="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>
                        </svg>
                        <span><?php $error_msg = $_SESSION['error']; echo htmlspecialchars($error_msg); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="otp-alert success auto-dismiss" role="status" data-auto-dismiss="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m5 12 4 4L19 6"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['otp_fallback_code'])): ?>
                    <div class="otp-alert manual">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3 4.8 6.2v5.3c0 4.4 2.7 7.8 7.2 9.5 4.5-1.7 7.2-5.1 7.2-9.5V6.2L12 3Z"></path>
                            <path d="M12 8v4M12 16h.01"></path>
                        </svg>
                        <div>
                            <strong>Manual verification code</strong>
                            <div><?php echo htmlspecialchars($_SESSION['otp_fallback_message'] ?? 'Use this one-time code to continue.'); ?></div>
                            <div class="manual-code"><?php echo htmlspecialchars($_SESSION['otp_fallback_code']); ?></div>
                        </div>
                    </div>
                    <?php unset($_SESSION['otp_fallback_code'], $_SESSION['otp_fallback_message']); ?>
                <?php endif; ?>

                <form method="POST" id="verificationForm">
                    <div class="otp-code-row" aria-label="Verification code">
                        <input type="text" name="code1" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" aria-label="Digit 1" required>
                        <input type="text" name="code2" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" aria-label="Digit 2" required>
                        <input type="text" name="code3" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" aria-label="Digit 3" required>
                        <input type="text" name="code4" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" aria-label="Digit 4" required>
                        <input type="text" name="code5" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" aria-label="Digit 5" required>
                        <input type="text" name="code6" class="code-input" maxlength="1" pattern="[0-9]" inputmode="numeric" aria-label="Digit 6" required>
                    </div>

                    <div class="otp-helper">
                        <span>Code expires in <strong>10 minutes</strong>.</span>
                        <span>Enter all six digits to continue.</span>
                    </div>

                    <input type="hidden" name="verification_code" id="fullCode">
                    <input type="hidden" name="verify_code" value="1">

                    <button type="submit" id="verifyButton" class="verify-button">
                        Verify and continue
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path>
                        </svg>
                    </button>
                </form>

                <div class="resend-row">
                    <span>Didn't receive the code?</span>
                    <form method="POST" id="resendOtpForm">
                        <input type="hidden" name="resend_otp" value="1">
                        <button type="submit" id="resendOtpBtn" class="resend-button">Resend code</button>
                    </form>
                </div>

                <div class="otp-footer-note">
                    For your security, never share this verification code with anyone.
                </div>
            </div>
        </div>
    </div>

    <script>
        function setButtonLoading(button, text) {
            if (!button || button.dataset.loading === 'true') return;
            button.dataset.loading = 'true';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = `
                <span class="inline-flex items-center justify-center gap-2">
                    <svg class="animate-spin" style="width:18px;height:18px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <span>${text}</span>
                </span>
            `;
        }

        const verificationForm = document.getElementById('verificationForm');
        const verifyButton = document.getElementById('verifyButton');

        if (verificationForm) {
            verificationForm.addEventListener('submit', function (event) {
                const codeInputs = document.querySelectorAll('.code-input');
                const fullCode = Array.from(codeInputs).map(function (input) { return input.value || ''; }).join('');

                if (fullCode.length !== 6) {
                    event.preventDefault();
                    if (typeof showErrorAlert === 'function') {
                        showErrorAlert('Verification Code', 'Please enter all 6 digits of the verification code.');
                    } else if (typeof showToast === 'function') {
                        showToast('error', 'Please enter all 6 digits of the verification code.');
                    }
                    return false;
                }

                document.getElementById('fullCode').value = fullCode;
                setButtonLoading(verifyButton, 'Verifying...');
            });
        }

        const loginForm = document.getElementById('employeeLoginForm');
        const loginButton = document.getElementById('loginButton');

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function (event) {
                if (loginButton.disabled) {
                    event.preventDefault();
                    return false;
                }

                if (!loginForm.checkValidity()) {
                    event.preventDefault();
                    loginForm.reportValidity();
                    return false;
                }

                <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED && defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)): ?>
                if (typeof grecaptcha !== 'undefined') {
                    const recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        event.preventDefault();
                        showToast('error', 'Please complete the reCAPTCHA verification.');
                        return false;
                    }
                }
                <?php endif; ?>

                setButtonLoading(loginButton, 'Authenticating...');
            });
        }

        (function () {
            const loginBtn = document.getElementById('loginButton');
            const countdownEl = document.getElementById('loginCountdown');
            const employeeInput = document.getElementById('employee_id');
            const passwordInput = document.getElementById('password');
            let remaining = <?php echo $loginLocked ? (int)$loginLockoutRemaining : 0; ?>;
            const lockoutUntil = <?php echo (int)$loginLockoutUntil; ?>;

            if (!loginBtn || !loginBtn.disabled) return;

            function unlockForm() {
                clearInterval(window.loginLockoutInterval);
                loginBtn.disabled = false;
                loginBtn.dataset.loading = 'false';
                loginBtn.innerHTML = `
                    <span>Sign in securely</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path>
                    </svg>
                `;
                if (employeeInput) employeeInput.disabled = false;
                if (passwordInput) passwordInput.disabled = false;
            }

            function updateCountdown() {
                const now = Math.floor(Date.now() / 1000);
                remaining = Math.max(0, lockoutUntil - now);
                if (countdownEl) countdownEl.textContent = remaining;
                if (remaining <= 0) unlockForm();
            }

            updateCountdown();
            if (remaining > 0) {
                window.loginLockoutInterval = setInterval(updateCountdown, 1000);
            }
        })();

        (function () {
            const resendBtn = document.getElementById('resendOtpBtn');
            const resendForm = document.getElementById('resendOtpForm');
            const cooldown = 60;
            let remaining = 0;
            let countdownInterval = null;
            const originalText = resendBtn ? resendBtn.textContent.trim() : 'Resend code';

            function setButtonText(text) {
                if (resendBtn) resendBtn.textContent = text;
            }

            function resetButton() {
                if (!resendBtn) return;
                resendBtn.disabled = false;
                setButtonText(originalText || 'Resend code');
            }

            function startCountdown(seconds) {
                if (!resendBtn) return;
                remaining = seconds;
                resendBtn.disabled = true;
                setButtonText(`Resend in ${remaining}s`);

                if (countdownInterval) clearInterval(countdownInterval);

                countdownInterval = setInterval(function () {
                    remaining--;
                    if (remaining > 0) {
                        setButtonText(`Resend in ${remaining}s`);
                    } else {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        resetButton();
                    }
                }, 1000);
            }

            const resumeCountdown = <?php echo $resendCountdownShouldStart && $resendCountdownStartTime ? 'true' : 'false'; ?>;
            const serverStartTime = <?php echo $resendCountdownStartTime ? (int)$resendCountdownStartTime : 'null'; ?>;
            const serverNow = <?php echo time(); ?>;

            if (resumeCountdown && serverStartTime) {
                const elapsed = serverNow - serverStartTime;
                const remainingTime = Math.max(0, cooldown - elapsed);
                if (remainingTime > 0) startCountdown(remainingTime);
                else resetButton();
            }

            resendForm?.addEventListener('submit', function () {
                startCountdown(cooldown);
            });
        })();

        // Fade session alerts in the OTP dialog after a few seconds.
        document.querySelectorAll('[data-auto-dismiss="true"]').forEach(function (alert) {
            setTimeout(function () {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-4px)';
                setTimeout(function () { alert.remove(); }, 300);
            }, 5000);
        });
    </script>
</body>
</html>

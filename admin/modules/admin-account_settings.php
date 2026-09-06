<?php
require_once __DIR__ . '/../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Account Settings';

// Handle form submissions
$errors = [];
$success = null;

// Setup upload directory
$upload_dir = __DIR__ . '/../../assets/uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Determine if user is from department_accounts or users table
$isDepartmentAccount = false;
$employeeId = $_SESSION['employee_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

// Check if user is from department_accounts
// First check session, then check by email/username
if ($employeeId) {
    try {
        $checkStmt = $db->prepare("SELECT * FROM department_accounts WHERE employee_id = ? LIMIT 1");
        $checkStmt->execute([$employeeId]);
        $deptAccount = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($deptAccount) {
            $isDepartmentAccount = true;
        }
    } catch (PDOException $e) {
        error_log('Error checking department_accounts: ' . $e->getMessage());
    }
}

// If not found by employee_id, try to find by email or username
if (!$isDepartmentAccount && isset($_SESSION['email'])) {
    try {
        $checkStmt = $db->prepare("SELECT * FROM department_accounts WHERE employee_email = ? LIMIT 1");
        $checkStmt->execute([$_SESSION['email']]);
        $deptAccount = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($deptAccount) {
            $isDepartmentAccount = true;
            $employeeId = $deptAccount['employee_id'];
            $_SESSION['employee_id'] = $employeeId;
        }
    } catch (PDOException $e) {
        error_log('Error checking department_accounts by email: ' . $e->getMessage());
    }
}

// Fetch current user information
$userInfo = null;
function fetchUserInfo($db, $userId, $employeeId = null, $isDepartmentAccount = false) {
    try {
        if ($isDepartmentAccount && $employeeId) {
            // Fetch from department_accounts
            $stmt = $db->prepare("
                SELECT 
                    employee_id as id,
                    employee_id,
                    employee_email as email,
                    employee_fname as first_name,
                    employee_lname as last_name,
                    role_name,
                    profile_picture,
                    status,
                    created_at,
                    updated_at
                FROM department_accounts
                WHERE employee_id = ?
            ");
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $result['username'] = $result['employee_id'];
                $result['role_name'] = $result['role_name'] ?? 'admin';
            }
            return $result;
        } else {
            // Fetch from users table
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.status,
                    u.profile_picture,
                    u.created_at,
                    u.updated_at,
                    r.role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Account settings fetch error: ' . $e->getMessage());
        return null;
    }
}

$userInfo = fetchUserInfo($db, $userId, $employeeId, $isDepartmentAccount);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_profile' && isset($db)) {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $email     = trim($_POST['email'] ?? '');

            if ($firstName === '' || $lastName === '') {
                $errors[] = 'First name and last name are required.';
            } elseif ($email === '') {
                $errors[] = 'Email is required.';
            } else {
                if ($isDepartmentAccount && $employeeId) {
                    // Check if email is already taken by another department account
                    $checkStmt = $db->prepare("SELECT employee_id FROM department_accounts WHERE employee_email = ? AND employee_id != ?");
                    $checkStmt->execute([$email, $employeeId]);
                    if ($checkStmt->fetch()) {
                        $errors[] = 'Email is already taken by another user.';
                    } else {
                        $stmt = $db->prepare("
                            UPDATE department_accounts
                            SET employee_fname = ?,
                                employee_lname = ?,
                                employee_email = ?,
                                updated_at = NOW()
                            WHERE employee_id = ?
                        ");
                        $stmt->execute([$firstName, $lastName, $email, $employeeId]);

                        // Update session
                        $_SESSION['first_name'] = $firstName;
                        $_SESSION['last_name']  = $lastName;
                        $_SESSION['email']      = $email;

                        $success = 'Profile updated successfully.';
                        
                        // Refresh user info
                        $userInfo = fetchUserInfo($db, $userId, $employeeId, $isDepartmentAccount);
                    }
                } else {
                    // Check if email is already taken by another user
                    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $checkStmt->execute([$email, $userId]);
                    if ($checkStmt->fetch()) {
                        $errors[] = 'Email is already taken by another user.';
                    } else {
                        $stmt = $db->prepare("
                            UPDATE users
                            SET first_name = ?,
                                last_name  = ?,
                                email      = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$firstName, $lastName, $email, $userId]);

                        // Update session
                        $_SESSION['first_name'] = $firstName;
                        $_SESSION['last_name']  = $lastName;
                        $_SESSION['email']      = $email;

                        $success = 'Profile updated successfully.';
                        
                        // Refresh user info
                        $userInfo = fetchUserInfo($db, $userId, $employeeId, $isDepartmentAccount);
                    }
                }
            }
        }

        if ($action === 'update_password' && isset($db)) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errors[] = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            } else {
                if ($isDepartmentAccount && $employeeId) {
                    // Verify current password from department_accounts
                    $stmt = $db->prepare("SELECT password FROM department_accounts WHERE employee_id = ?");
                    $stmt->execute([$employeeId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $passwordField = $user['password'] ?? null;
                    // Check if password is hashed or plain text
                    if ($passwordField && (password_verify($currentPassword, $passwordField) || $passwordField === $currentPassword)) {
                        // Update password (hash it)
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            UPDATE department_accounts
                            SET password = ?,
                                updated_at = NOW()
                            WHERE employee_id = ?
                        ");
                        $stmt->execute([$hash, $employeeId]);
                        $success = 'Password updated successfully.';
                    } else {
                        $errors[] = 'Current password is incorrect.';
                    }
                } else {
                    // Verify current password from users table
                    $stmt = $db->prepare("SELECT password, password_hash FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $passwordField = $user['password_hash'] ?? $user['password'] ?? null;
                    if (!$passwordField || !password_verify($currentPassword, $passwordField)) {
                        $errors[] = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("
                            UPDATE users
                            SET password_hash = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$hash, $userId]);
                        $success = 'Password updated successfully.';
                    }
                }
            }
        }

        if ($action === 'upload_profile_picture' && isset($db)) {
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $file_name = $file['name'];
                $file_tmp = $file['tmp_name'];
                $file_size = $file['size'];
                $file_type = $file['type'];

                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
                } elseif ($file_size > 2097152) { // 2MB
                    $errors[] = 'File size too large. Maximum size is 2MB.';
                } else {
                    // Get current profile picture to delete old one
                    $currentPic = $userInfo['profile_picture'] ?? null;
                    if ($currentPic && file_exists(__DIR__ . '/../../' . $currentPic)) {
                        $old_file = __DIR__ . '/../../' . $currentPic;
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }

                    // Generate new filename
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $identifier = $isDepartmentAccount ? $employeeId : $userId;
                    $new_filename = 'admin_' . $identifier . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $relative_path = 'assets/uploads/profile_pictures/' . $new_filename;
                        
                        try {
                            if ($isDepartmentAccount && $employeeId) {
                                // Update department_accounts table
                                $stmt = $db->prepare("
                                    UPDATE department_accounts
                                    SET profile_picture = ?,
                                        updated_at = NOW()
                                    WHERE employee_id = ?
                                ");
                                $stmt->execute([$relative_path, $employeeId]);
                                
                                if ($stmt->rowCount() === 0) {
                                    throw new Exception("No rows updated in department_accounts. Employee ID: " . $employeeId);
                                }
                            } else {
                                // Update users table
                                try {
                                    $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
                                    if ($check_column->rowCount() === 0) {
                                        $db->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL");
                                    }
                                } catch (PDOException $e) {
                                    // Column might already exist, continue
                                }
                                
                                $stmt = $db->prepare("
                                    UPDATE users
                                    SET profile_picture = ?,
                                        updated_at = NOW()
                                    WHERE id = ?
                                ");
                                $stmt->execute([$relative_path, $userId]);
                                
                                if ($stmt->rowCount() === 0) {
                                    throw new Exception("No rows updated in users. User ID: " . $userId);
                                }
                            }

                            // Update session for real-time header update
                            $_SESSION['profile_picture'] = $relative_path;

                            $success = 'Profile picture updated successfully.';
                            
                            // Refresh user info
                            $userInfo = fetchUserInfo($db, $userId, $employeeId, $isDepartmentAccount);
                        } catch (Exception $e) {
                            error_log('Profile picture update error: ' . $e->getMessage());
                            $errors[] = 'Failed to update profile picture in database: ' . $e->getMessage();
                            // Delete uploaded file if database update failed
                            if (file_exists($upload_path)) {
                                unlink($upload_path);
                            }
                        }
                    } else {
                        $errors[] = 'Failed to upload profile picture. Please check file permissions.';
                    }
                }
            } else {
                $errors[] = 'Please select a valid image file.';
            }
        }

        if ($action === 'remove_profile_picture' && isset($db)) {
            $currentPic = $userInfo['profile_picture'] ?? null;
            
            // Delete physical file
            if ($currentPic && file_exists(__DIR__ . '/../../' . $currentPic)) {
                $old_file = __DIR__ . '/../../' . $currentPic;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }

            // Update database
            try {
                if ($isDepartmentAccount && $employeeId) {
                    // Update department_accounts table
                    $stmt = $db->prepare("
                        UPDATE department_accounts
                        SET profile_picture = NULL,
                            updated_at = NOW()
                        WHERE employee_id = ?
                    ");
                    $stmt->execute([$employeeId]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("No rows updated in department_accounts. Employee ID: " . $employeeId);
                    }
                } else {
                    // Update users table
                    $stmt = $db->prepare("
                        UPDATE users
                        SET profile_picture = NULL,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$userId]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("No rows updated in users. User ID: " . $userId);
                    }
                }

                // Clear session for real-time header update
                unset($_SESSION['profile_picture']);

                $success = 'Profile picture removed successfully.';
                
                // Refresh user info
                $userInfo = fetchUserInfo($db, $userId, $employeeId, $isDepartmentAccount);
            } catch (Exception $e) {
                error_log('Profile picture remove error: ' . $e->getMessage());
                $errors[] = 'Failed to remove profile picture: ' . $e->getMessage();
            }
        }
    } catch (PDOException $e) {
        error_log('Admin account settings error: ' . $e->getMessage());
        $errors[] = 'An unexpected error occurred. Please try again.';
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Page Header -->
    <div>
        <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li><a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="hover:text-primary-600">Dashboard</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span class="text-gray-700 dark:text-gray-200">Account Settings</span></li>
            </ol>
        </nav>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Account Settings
        </h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage your profile information, security settings, and account preferences.
        </p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="rounded-md p-4 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="rounded-md p-4 bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-medium mb-1">Please fix the following errors:</h3>
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Account Information Display -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Account Information</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Your account details and login credentials.
                </p>
            </div>
        </header>
        <div class="px-6 py-5">
            <?php if ($userInfo): ?>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400"><?php echo $isDepartmentAccount ? 'Employee ID' : 'Username'; ?></dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                            <?php echo htmlspecialchars($userInfo['username'] ?? $userInfo['employee_id'] ?? 'N/A'); ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($userInfo['email'] ?? $userInfo['employee_email'] ?? 'N/A'); ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?php 
                            $firstName = $userInfo['first_name'] ?? $userInfo['employee_fname'] ?? '';
                            $lastName = $userInfo['last_name'] ?? $userInfo['employee_lname'] ?? '';
                            echo htmlspecialchars(trim($firstName . ' ' . $lastName)); 
                            ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $userInfo['role_name'] ?? 'N/A'))); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($userInfo['status'] ?? '') === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'; ?>">
                                <?php echo htmlspecialchars(ucfirst($userInfo['status'] ?? 'N/A')); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?php echo $userInfo['created_at'] ? date('M d, Y', strtotime($userInfo['created_at'])) : 'N/A'; ?>
                        </dd>
                    </div>
                </dl>
            <?php endif; ?>
        </div>
    </section>

    <!-- Profile Picture Management -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Picture</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Upload or remove your profile picture. Changes will be reflected immediately in the header.
                </p>
            </div>
        </header>
        <div class="px-6 py-5">
            <div class="flex flex-col items-center space-y-4">
                <div class="relative">
                    <?php 
                    $profilePic = $userInfo['profile_picture'] ?? $_SESSION['profile_picture'] ?? null;
                    $firstName = $userInfo['first_name'] ?? $_SESSION['first_name'] ?? '';
                    $lastName = $userInfo['last_name'] ?? $_SESSION['last_name'] ?? '';
                    $firstInitial = strtoupper(substr($firstName, 0, 1));
                    $lastInitial = strtoupper(substr($lastName, 0, 1));
                    $initials = trim($firstInitial . $lastInitial) ?: 'AD';
                    ?>
                    <?php if (!empty($profilePic) && file_exists(__DIR__ . '/../../' . $profilePic)): ?>
                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($profilePic); ?>" 
                             alt="Profile Picture"
                             id="profilePicturePreview"
                             class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700 shadow-lg">
                    <?php else: ?>
                        <div id="profilePicturePreview" class="w-32 h-32 bg-purple-500 rounded-full flex items-center justify-center text-white text-4xl font-semibold border-4 border-gray-200 dark:border-gray-700 shadow-lg">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                    <?php endif; ?>
                    <div id="imagePreviewContainer" class="hidden mt-2">
                        <img id="imagePreview" src="" alt="Preview" class="w-32 h-32 rounded-full object-cover border-4 border-primary-500 shadow-lg">
                    </div>
                </div>

                <div class="w-full max-w-md space-y-4">
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_profile_picture">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Upload New Picture
                            </label>
                            <input
                                type="file"
                                name="profile_picture"
                                id="profilePictureInput"
                                accept="image/jpeg,image/jpg,image/png,image/gif"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                onchange="previewImage(this)"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Accepted formats: JPEG, PNG, GIF. Maximum size: 2MB
                            </p>
                        </div>
                        <button
                            type="submit"
                            class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            Upload Picture
                        </button>
                    </form>
                    
                    <?php if (!empty($profilePic)): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove your profile picture?');">
                            <input type="hidden" name="action" value="remove_profile_picture">
                            <button
                                type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium flex items-center justify-center gap-2"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                                Remove Profile Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Management -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Update your display name and email address.
                </p>
            </div>
        </header>
        <div class="px-6 py-5">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            First Name *
                        </label>
                        <input
                            type="text"
                            name="first_name"
                            value="<?php echo htmlspecialchars($userInfo['first_name'] ?? $userInfo['employee_fname'] ?? $_SESSION['first_name'] ?? ''); ?>"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Last Name *
                        </label>
                        <input
                            type="text"
                            name="last_name"
                            value="<?php echo htmlspecialchars($userInfo['last_name'] ?? $userInfo['employee_lname'] ?? $_SESSION['last_name'] ?? ''); ?>"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Address *
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($userInfo['email'] ?? $userInfo['employee_email'] ?? $_SESSION['email'] ?? ''); ?>"
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                </div>
                
                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Profile
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Security Settings -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Update your password to keep your account secure. Use a strong password with at least 8 characters.
                </p>
            </div>
        </header>
        <div class="px-6 py-5">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_password">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Current Password *
                    </label>
                    <input
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Password *
                        </label>
                        <input
                            type="password"
                            name="new_password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum 8 characters</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm New Password *
                        </label>
                        <input
                            type="password"
                            name="confirm_password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>
                </div>
                
                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
function previewImage(input) {
    const previewContainer = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const currentPreview = document.getElementById('profilePicturePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            if (currentPreview && currentPreview.tagName === 'IMG') {
                currentPreview.style.display = 'none';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.classList.add('hidden');
        if (currentPreview && currentPreview.tagName === 'IMG') {
            currentPreview.style.display = 'block';
        }
    }
}

// Reload page after successful profile picture update/remove to refresh header
<?php if ($success && (isset($_POST['action']) && ($_POST['action'] === 'upload_profile_picture' || $_POST['action'] === 'remove_profile_picture'))): ?>
    setTimeout(function() {
        window.location.reload();
    }, 800);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

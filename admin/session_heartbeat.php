<?php
/**
 * Session heartbeat: touch session to extend timeout. Used by the session timeout warning modal.
 */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['ok' => true]);

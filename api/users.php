<?php
/**
 * FIXED - Users API Helper
 * Save as: api/users.php
 */

// Prevent any output before headers
ob_start();

// Include config
$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Config file not found']);
    exit;
}

require_once $configPath;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Get all active users
$query = "SELECT id, name, role, email, phone, branch_id FROM users WHERE status = 'active' ORDER BY name ASC";
$result = $conn->query($query);

if (!$result) {
    respond(false, 'Database error: ' . $conn->error, null, 500);
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

respond(true, 'Users retrieved', ['users' => $users]);
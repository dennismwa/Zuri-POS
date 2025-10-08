<?php
/**
 * Permissions Management API
 * api/permissions.php
 */

ob_start();
require_once dirname(__DIR__) . '/config.php';
requireOwner();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== GET ALL PERMISSIONS ====================
if ($action === 'get_permissions') {
    $query = "SELECT * FROM permissions ORDER BY category, name";
    $result = $conn->query($query);
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    respond(true, 'Permissions retrieved', ['permissions' => $permissions]);
}

// ==================== GET USER PERMISSIONS ====================
if ($action === 'get_user_permissions') {
    $userId = intval($_GET['user_id']);
    
    $permissions = getUserPermissions($userId);
    respond(true, 'User permissions retrieved', ['permissions' => $permissions]);
}

// ==================== UPDATE USER PERMISSIONS ====================
if ($action === 'update_user_permissions') {
    $userId = intval($_POST['user_id']);
    $permissionIds = isset($_POST['permission_ids']) ? json_decode($_POST['permission_ids'], true) : [];
    
    if (!is_array($permissionIds)) {
        respond(false, 'Invalid permission data', null, 400);
    }
    
    $conn->begin_transaction();
    
    try {
        // Get old permissions for audit log
        $oldPerms = getUserPermissions($userId);
        
        // Delete existing permissions
        $conn->query("DELETE FROM user_permissions WHERE user_id = $userId");
        
        // Insert new permissions
        $grantedBy = $_SESSION['user_id'];
        foreach ($permissionIds as $permId) {
            $permId = intval($permId);
            $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $userId, $permId, $grantedBy);
            $stmt->execute();
            $stmt->close();
        }
        
        // Get user info for logging
        $userInfo = $conn->query("SELECT name FROM users WHERE id = $userId")->fetch_assoc();
        
        // Log audit trail
        logAudit('PERMISSIONS_UPDATED', 'admin', "Updated permissions for user: " . $userInfo['name'], 'user_permissions', $userId, 
                ['permissions' => $oldPerms], 
                ['permissions' => getUserPermissions($userId)]);
        
        $conn->commit();
        respond(true, 'Permissions updated successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        respond(false, 'Failed to update permissions: ' . $e->getMessage(), null, 500);
    }
}

// ==================== GET USERS WITH PERMISSIONS ====================
if ($action === 'get_users_permissions') {
    $query = "SELECT u.id, u.name, u.email, u.role, u.status,
              COUNT(up.id) as permission_count
              FROM users u
              LEFT JOIN user_permissions up ON u.id = up.user_id
              WHERE u.status = 'active'
              GROUP BY u.id
              ORDER BY u.name";
    
    $result = $conn->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    respond(true, 'Users retrieved', ['users' => $users]);
}

respond(false, 'Invalid action', null, 400);
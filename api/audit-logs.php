<?php
/**
 * Audit Logs API
 * api/audit-logs.php
 */

ob_start();
require_once dirname(__DIR__) . '/config.php';
requireAuth();

// Check permission
if (!hasPermission('can_view_audit_logs')) {
    http_response_code(403);
    respond(false, 'You do not have permission to view audit logs', null, 403);
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ==================== GET AUDIT LOGS ====================
if ($action === 'get_logs') {
    $dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $actionType = isset($_GET['action_type']) ? sanitize($_GET['action_type']) : '';
    $actionCategory = isset($_GET['action_category']) ? sanitize($_GET['action_category']) : '';
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    
    $where = ["DATE(al.created_at) BETWEEN '$dateFrom' AND '$dateTo'"];
    
    if ($userId > 0) {
        $where[] = "al.user_id = $userId";
    }
    
    if ($actionType) {
        $where[] = "al.action_type = '$actionType'";
    }
    
    if ($actionCategory) {
        $where[] = "al.action_category = '$actionCategory'";
    }
    
    if ($search) {
        $where[] = "(al.description LIKE '%$search%' OR al.action_type LIKE '%$search%')";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countQuery = $conn->query("SELECT COUNT(*) as total FROM audit_logs al WHERE $whereClause");
    $totalLogs = $countQuery->fetch_assoc()['total'];
    
    // Get logs
    $query = "SELECT al.*, u.name as user_name, u.email as user_email
              FROM audit_logs al
              LEFT JOIN users u ON al.user_id = u.id
              WHERE $whereClause
              ORDER BY al.created_at DESC
              LIMIT $perPage OFFSET $offset";
    
    $result = $conn->query($query);
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON values
        $row['old_values'] = $row['old_values'] ? json_decode($row['old_values'], true) : null;
        $row['new_values'] = $row['new_values'] ? json_decode($row['new_values'], true) : null;
        $logs[] = $row;
    }
    
    respond(true, 'Logs retrieved', [
        'logs' => $logs,
        'total' => $totalLogs,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($totalLogs / $perPage)
    ]);
}

// ==================== GET ACTION TYPES ====================
if ($action === 'get_action_types') {
    $query = "SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type";
    $result = $conn->query($query);
    
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['action_type'];
    }
    
    respond(true, 'Action types retrieved', ['types' => $types]);
}

// ==================== GET ACTION CATEGORIES ====================
if ($action === 'get_action_categories') {
    $query = "SELECT DISTINCT action_category FROM audit_logs ORDER BY action_category";
    $result = $conn->query($query);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['action_category'];
    }
    
    respond(true, 'Categories retrieved', ['categories' => $categories]);
}

// ==================== EXPORT LOGS TO CSV ====================
if ($action === 'export_csv') {
    $dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $actionType = isset($_GET['action_type']) ? sanitize($_GET['action_type']) : '';
    $actionCategory = isset($_GET['action_category']) ? sanitize($_GET['action_category']) : '';
    
    $where = ["DATE(al.created_at) BETWEEN '$dateFrom' AND '$dateTo'"];
    
    if ($userId > 0) $where[] = "al.user_id = $userId";
    if ($actionType) $where[] = "al.action_type = '$actionType'";
    if ($actionCategory) $where[] = "al.action_category = '$actionCategory'";
    
    $whereClause = implode(' AND ', $where);
    
    $query = "SELECT al.id, al.created_at, u.name as user_name, u.email, 
              al.action_type, al.action_category, al.description, 
              al.table_name, al.record_id, al.ip_address
              FROM audit_logs al
              LEFT JOIN users u ON al.user_id = u.id
              WHERE $whereClause
              ORDER BY al.created_at DESC
              LIMIT 5000";
    
    $result = $conn->query($query);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
    $headers = ['ID', 'Date/Time', 'User', 'Email', 'Action Type', 'Category', 'Description', 'Table', 'Record ID', 'IP Address'];
    
    exportToCSV($data, $filename, $headers);
}

respond(false, 'Invalid action', null, 400);
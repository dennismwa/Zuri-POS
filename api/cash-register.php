<?php
/**
 * Cash Register Management API
 * api/cash-register.php
 */

ob_start();

require_once dirname(__DIR__) . '/config.php';
requireAuth();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$isOwner = $_SESSION['role'] === 'owner';

// ==================== GET ACTIVE SESSION ====================
if ($action === 'get_active_session') {
    $stmt = $conn->prepare("SELECT crs.*, u.name as user_name 
                           FROM cash_register_sessions crs
                           JOIN users u ON crs.user_id = u.id
                           WHERE crs.user_id = ? AND crs.status = 'open'
                           ORDER BY crs.opened_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $session = $result->fetch_assoc();
        
        // Get transactions for this session
        $transStmt = $conn->prepare("SELECT * FROM cash_register_transactions 
                                     WHERE session_id = ? ORDER BY created_at DESC");
        $transStmt->bind_param("i", $session['id']);
        $transStmt->execute();
        $transResult = $transStmt->get_result();
        
        $transactions = [];
        while ($trans = $transResult->fetch_assoc()) {
            $transactions[] = $trans;
        }
        $transStmt->close();
        
        $session['transactions'] = $transactions;
        respond(true, 'Active session found', ['session' => $session]);
    } else {
        respond(true, 'No active session', ['session' => null]);
    }
    $stmt->close();
}

// ==================== OPEN REGISTER ====================
if ($action === 'open_register') {
    // Check if already open
    $check = $conn->prepare("SELECT id FROM cash_register_sessions 
                            WHERE user_id = ? AND status = 'open'");
    $check->bind_param("i", $userId);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        respond(false, 'You already have an open register session', null, 400);
    }
    $check->close();
    
    $openingFloat = floatval($_POST['opening_float']);
    $branchId = isset($_POST['branch_id']) && !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    if ($openingFloat < 0) {
        respond(false, 'Opening float cannot be negative', null, 400);
    }
    
    $stmt = $conn->prepare("INSERT INTO cash_register_sessions 
                           (user_id, branch_id, opening_float, opened_at, notes) 
                           VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("iids", $userId, $branchId, $openingFloat, $notes);
    
    if ($stmt->execute()) {
        $sessionId = $conn->insert_id;
        logActivity('REGISTER_OPENED', "Opened register with float: " . formatCurrency($openingFloat));
        respond(true, 'Register opened successfully', ['session_id' => $sessionId]);
    } else {
        respond(false, 'Failed to open register', null, 500);
    }
    $stmt->close();
}

// ==================== CLOSE REGISTER ====================
if ($action === 'close_register') {
    $sessionId = intval($_POST['session_id']);
    $actualCash = floatval($_POST['actual_cash']);
    $closingNotes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    // Verify session belongs to user
    $check = $conn->prepare("SELECT * FROM cash_register_sessions 
                            WHERE id = ? AND user_id = ? AND status = 'open'");
    $check->bind_param("ii", $sessionId, $userId);
    $check->execute();
    $session = $check->get_result()->fetch_assoc();
    $check->close();
    
    if (!$session) {
        respond(false, 'Session not found or already closed', null, 404);
    }
    
    // Calculate expected cash
    $salesStmt = $conn->prepare("SELECT 
                                 COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) as cash_sales,
                                 COUNT(*) as sales_count
                                 FROM sales 
                                 WHERE register_session_id = ?");
    $salesStmt->bind_param("i", $sessionId);
    $salesStmt->execute();
    $salesData = $salesStmt->get_result()->fetch_assoc();
    $salesStmt->close();
    
    // Get cash in/out transactions
    $transStmt = $conn->prepare("SELECT 
                                 COALESCE(SUM(CASE WHEN transaction_type = 'in' THEN amount ELSE 0 END), 0) as cash_in,
                                 COALESCE(SUM(CASE WHEN transaction_type = 'out' THEN amount ELSE 0 END), 0) as cash_out
                                 FROM cash_register_transactions 
                                 WHERE session_id = ?");
    $transStmt->bind_param("i", $sessionId);
    $transStmt->execute();
    $transData = $transStmt->get_result()->fetch_assoc();
    $transStmt->close();
    
    $expectedCash = $session['opening_float'] + $salesData['cash_sales'] + $transData['cash_in'] - $transData['cash_out'];
    $variance = $actualCash - $expectedCash;
    
    // Get all sales data
    $allSalesStmt = $conn->prepare("SELECT 
                                    COALESCE(SUM(total_amount), 0) as total_sales,
                                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) as cash_sales,
                                    COALESCE(SUM(CASE WHEN payment_method IN ('mpesa', 'mpesa_till') THEN total_amount ELSE 0 END), 0) as mpesa_sales,
                                    COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END), 0) as card_sales,
                                    COUNT(*) as sales_count
                                    FROM sales 
                                    WHERE register_session_id = ?");
    $allSalesStmt->bind_param("i", $sessionId);
    $allSalesStmt->execute();
    $allSalesData = $allSalesStmt->get_result()->fetch_assoc();
    $allSalesStmt->close();
    
    // Update session
    $updateStmt = $conn->prepare("UPDATE cash_register_sessions 
                                  SET closing_float = ?, 
                                      expected_cash = ?,
                                      actual_cash = ?,
                                      variance = ?,
                                      total_sales = ?,
                                      total_cash_sales = ?,
                                      total_mpesa_sales = ?,
                                      total_card_sales = ?,
                                      sales_count = ?,
                                      closed_at = NOW(),
                                      status = 'closed',
                                      notes = CONCAT(COALESCE(notes, ''), '\n\nClosing Notes: ', ?)
                                  WHERE id = ?");
    
    $closingFloat = $actualCash;
    $updateStmt->bind_param("dddddddisi", 
        $closingFloat,
        $expectedCash, 
        $actualCash, 
        $variance,
        $allSalesData['total_sales'],
        $allSalesData['cash_sales'],
        $allSalesData['mpesa_sales'],
        $allSalesData['card_sales'],
        $allSalesData['sales_count'],
        $closingNotes,
        $sessionId
    );
    
    if ($updateStmt->execute()) {
        logActivity('REGISTER_CLOSED', "Closed register - Expected: " . formatCurrency($expectedCash) . ", Actual: " . formatCurrency($actualCash) . ", Variance: " . formatCurrency($variance));
        
        respond(true, 'Register closed successfully', [
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'variance' => $variance
        ]);
    } else {
        respond(false, 'Failed to close register', null, 500);
    }
    $updateStmt->close();
}

// ==================== ADD CASH TRANSACTION ====================
if ($action === 'add_transaction') {
    $sessionId = intval($_POST['session_id']);
    $type = sanitize($_POST['type']);
    $amount = floatval($_POST['amount']);
    $reason = sanitize($_POST['reason']);
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    if (!in_array($type, ['in', 'out'])) {
        respond(false, 'Invalid transaction type', null, 400);
    }
    
    if ($amount <= 0) {
        respond(false, 'Amount must be greater than zero', null, 400);
    }
    
    if (empty($reason)) {
        respond(false, 'Reason is required', null, 400);
    }
    
    // Verify session is open and belongs to user
    $check = $conn->prepare("SELECT id FROM cash_register_sessions 
                            WHERE id = ? AND user_id = ? AND status = 'open'");
    $check->bind_param("ii", $sessionId, $userId);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        respond(false, 'Session not found or not open', null, 404);
    }
    $check->close();
    
    $stmt = $conn->prepare("INSERT INTO cash_register_transactions 
                           (session_id, transaction_type, amount, reason, notes, created_by) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdssi", $sessionId, $type, $amount, $reason, $notes, $userId);
    
    if ($stmt->execute()) {
        logActivity('CASH_' . strtoupper($type), "$reason - " . formatCurrency($amount));
        respond(true, 'Transaction added successfully');
    } else {
        respond(false, 'Failed to add transaction', null, 500);
    }
    $stmt->close();
}

// ==================== GET SHIFT REPORT ====================
if ($action === 'get_shift_report') {
    $sessionId = intval($_GET['session_id']);
    
    // Get session details
    $stmt = $conn->prepare("SELECT crs.*, u.name as user_name, u.role,
                           b.name as branch_name
                           FROM cash_register_sessions crs
                           JOIN users u ON crs.user_id = u.id
                           LEFT JOIN branches b ON crs.branch_id = b.id
                           WHERE crs.id = ? AND (crs.user_id = ? OR ? = 1)");
    $stmt->bind_param("iii", $sessionId, $userId, $isOwner);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$session) {
        respond(false, 'Session not found', null, 404);
    }
    
    // Get sales
    $salesStmt = $conn->prepare("SELECT * FROM sales WHERE register_session_id = ? ORDER BY sale_date DESC");
    $salesStmt->bind_param("i", $sessionId);
    $salesStmt->execute();
    $salesResult = $salesStmt->get_result();
    
    $sales = [];
    while ($sale = $salesResult->fetch_assoc()) {
        $sales[] = $sale;
    }
    $salesStmt->close();
    
    // Get transactions
    $transStmt = $conn->prepare("SELECT * FROM cash_register_transactions WHERE session_id = ? ORDER BY created_at DESC");
    $transStmt->bind_param("i", $sessionId);
    $transStmt->execute();
    $transResult = $transStmt->get_result();
    
    $transactions = [];
    while ($trans = $transResult->fetch_assoc()) {
        $transactions[] = $trans;
    }
    $transStmt->close();
    
    respond(true, 'Report retrieved', [
        'session' => $session,
        'sales' => $sales,
        'transactions' => $transactions
    ]);
}

// ==================== GET SESSIONS HISTORY (Owner only) ====================
if ($action === 'get_sessions') {
    if (!$isOwner) {
        respond(false, 'Access denied', null, 403);
    }
    
    $dateFrom = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-01');
    $dateTo = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d');
    $userFilter = isset($_GET['user']) ? intval($_GET['user']) : 0;
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    
    $where = ["DATE(crs.opened_at) BETWEEN '$dateFrom' AND '$dateTo'"];
    
    if ($userFilter > 0) {
        $where[] = "crs.user_id = $userFilter";
    }
    
    if ($status) {
        $where[] = "crs.status = '$status'";
    }
    
    $whereClause = implode(' AND ', $where);
    
    $query = "SELECT crs.*, u.name as user_name, u.role,
              b.name as branch_name
              FROM cash_register_sessions crs
              JOIN users u ON crs.user_id = u.id
              LEFT JOIN branches b ON crs.branch_id = b.id
              WHERE $whereClause
              ORDER BY crs.opened_at DESC
              LIMIT 100";
    
    $result = $conn->query($query);
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    respond(true, 'Sessions retrieved', ['sessions' => $sessions]);
}

respond(false, 'Invalid action: ' . $action, null, 400);
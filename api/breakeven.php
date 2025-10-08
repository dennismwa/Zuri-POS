<?php
/**
 * Break-Even Analysis API - FIXED AUDIT LOGGING
 * api/breakeven.php
 */

ob_start();
require_once dirname(__DIR__) . '/config.php';
requireOwner();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== GET BREAK-EVEN DATA ====================
if ($action === 'get_breakeven') {
    $month = isset($_GET['month']) ? sanitize($_GET['month']) : date('Y-m-01');
    
    $data = calculateBreakEven($month);
    respond(true, 'Break-even data retrieved', $data);
}

// ==================== GET FIXED COSTS ====================
if ($action === 'get_fixed_costs') {
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : 'active';
    
    $where = $status ? "WHERE status = '$status'" : '';
    
    $query = "SELECT fc.*, u.name as created_by_name
              FROM fixed_costs fc
              LEFT JOIN users u ON fc.created_by = u.id
              $where
              ORDER BY fc.created_at DESC";
    
    $result = $conn->query($query);
    
    $costs = [];
    while ($row = $result->fetch_assoc()) {
        $costs[] = $row;
    }
    
    respond(true, 'Fixed costs retrieved', ['costs' => $costs]);
}

// ==================== ADD/UPDATE FIXED COST ====================
if ($action === 'save_fixed_cost') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $costName = sanitize($_POST['cost_name']);
    $category = sanitize($_POST['category']);
    $monthlyAmount = floatval($_POST['monthly_amount']);
    $description = sanitize($_POST['description']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
    
    if (empty($costName) || $monthlyAmount <= 0 || empty($startDate)) {
        respond(false, 'Cost name, amount, and start date are required', null, 400);
    }
    
    if ($id > 0) {
        // Update - Get old values first
        $oldCost = $conn->query("SELECT * FROM fixed_costs WHERE id = $id")->fetch_assoc();
        
        $stmt = $conn->prepare("UPDATE fixed_costs SET 
            cost_name=?, category=?, monthly_amount=?, description=?, 
            start_date=?, end_date=?, status=?
            WHERE id=?");
        $stmt->bind_param("ssdssssi", $costName, $category, $monthlyAmount, $description, 
                         $startDate, $endDate, $status, $id);
        
        if ($stmt->execute()) {
            // Get new values after update
            $newCost = $conn->query("SELECT * FROM fixed_costs WHERE id = $id")->fetch_assoc();
            
            // Prepare clean old and new values for audit
            $oldValues = [
                'cost_name' => $oldCost['cost_name'],
                'category' => $oldCost['category'],
                'monthly_amount' => $oldCost['monthly_amount'],
                'description' => $oldCost['description'],
                'start_date' => $oldCost['start_date'],
                'end_date' => $oldCost['end_date'],
                'status' => $oldCost['status']
            ];
            
            $newValues = [
                'cost_name' => $newCost['cost_name'],
                'category' => $newCost['category'],
                'monthly_amount' => $newCost['monthly_amount'],
                'description' => $newCost['description'],
                'start_date' => $newCost['start_date'],
                'end_date' => $newCost['end_date'],
                'status' => $newCost['status']
            ];
            
            logAudit('FIXED_COST_UPDATED', 'finance', "Updated fixed cost: $costName", 
                    'fixed_costs', $id, $oldValues, $newValues);
            
            respond(true, 'Fixed cost updated successfully');
        } else {
            respond(false, 'Failed to update fixed cost', null, 500);
        }
        $stmt->close();
    } else {
        // Create
        $createdBy = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO fixed_costs 
            (cost_name, category, monthly_amount, description, start_date, end_date, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssi", $costName, $category, $monthlyAmount, $description, 
                         $startDate, $endDate, $status, $createdBy);
        
        if ($stmt->execute()) {
            $costId = $conn->insert_id;
            
            // Get the created record
            $newCost = $conn->query("SELECT * FROM fixed_costs WHERE id = $costId")->fetch_assoc();
            
            // Prepare clean new values for audit
            $newValues = [
                'cost_name' => $newCost['cost_name'],
                'category' => $newCost['category'],
                'monthly_amount' => $newCost['monthly_amount'],
                'description' => $newCost['description'],
                'start_date' => $newCost['start_date'],
                'end_date' => $newCost['end_date'],
                'status' => $newCost['status']
            ];
            
            logAudit('FIXED_COST_CREATED', 'finance', "Created fixed cost: $costName", 
                    'fixed_costs', $costId, null, $newValues);
            
            respond(true, 'Fixed cost added successfully', ['cost_id' => $costId]);
        } else {
            respond(false, 'Failed to add fixed cost', null, 500);
        }
        $stmt->close();
    }
}

// ==================== DELETE FIXED COST ====================
if ($action === 'delete_fixed_cost') {
    $id = intval($_POST['id']);
    
    $cost = $conn->query("SELECT * FROM fixed_costs WHERE id = $id")->fetch_assoc();
    
    if (!$cost) {
        respond(false, 'Fixed cost not found', null, 404);
    }
    
    // Prepare old values for audit
    $oldValues = [
        'cost_name' => $cost['cost_name'],
        'category' => $cost['category'],
        'monthly_amount' => $cost['monthly_amount'],
        'description' => $cost['description'],
        'start_date' => $cost['start_date'],
        'end_date' => $cost['end_date'],
        'status' => $cost['status']
    ];
    
    $stmt = $conn->prepare("DELETE FROM fixed_costs WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAudit('FIXED_COST_DELETED', 'finance', "Deleted fixed cost: " . $cost['cost_name'], 
                'fixed_costs', $id, $oldValues, null);
        respond(true, 'Fixed cost deleted successfully');
    } else {
        respond(false, 'Failed to delete fixed cost', null, 500);
    }
    $stmt->close();
}

// ==================== GET HISTORICAL DATA ====================
if ($action === 'get_historical') {
    $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
    
    $query = "SELECT * FROM breakeven_snapshots 
              ORDER BY period_month DESC 
              LIMIT $months";
    
    $result = $conn->query($query);
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    respond(true, 'Historical data retrieved', ['history' => array_reverse($history)]);
}

// ==================== SAVE SNAPSHOT ====================
if ($action === 'save_snapshot') {
    $month = isset($_POST['month']) ? sanitize($_POST['month']) : date('Y-m-01');
    
    $data = calculateBreakEven($month);
    
    // Check if snapshot exists
    $existing = $conn->query("SELECT id FROM breakeven_snapshots WHERE period_month = '{$data['period_month']}'")->fetch_assoc();
    
    if ($existing) {
        // Update existing
        $stmt = $conn->prepare("UPDATE breakeven_snapshots SET 
            total_fixed_costs=?, total_variable_costs=?, total_revenue=?, 
            total_units_sold=?, avg_selling_price=?, avg_variable_cost=?, 
            breakeven_units=?, breakeven_revenue=?, actual_profit=?, breakeven_achieved=?
            WHERE period_month=?");
        $stmt->bind_param("dddiididdis", 
            $data['total_fixed_costs'], $data['total_variable_costs'], $data['total_revenue'],
            $data['total_units_sold'], $data['avg_selling_price'], $data['avg_variable_cost'],
            $data['breakeven_units'], $data['breakeven_revenue'], $data['actual_profit'],
            $data['breakeven_achieved'], $data['period_month']);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO breakeven_snapshots 
            (period_month, total_fixed_costs, total_variable_costs, total_revenue, 
             total_units_sold, avg_selling_price, avg_variable_cost, 
             breakeven_units, breakeven_revenue, actual_profit, breakeven_achieved)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddididddi", 
            $data['period_month'], $data['total_fixed_costs'], $data['total_variable_costs'],
            $data['total_revenue'], $data['total_units_sold'], $data['avg_selling_price'],
            $data['avg_variable_cost'], $data['breakeven_units'], $data['breakeven_revenue'],
            $data['actual_profit'], $data['breakeven_achieved']);
    }
    
    if ($stmt->execute()) {
        logAudit('BREAKEVEN_SNAPSHOT', 'finance', "Saved break-even snapshot for " . date('M Y', strtotime($month)), 
                'breakeven_snapshots', null, null, [
                    'period_month' => $data['period_month'],
                    'total_revenue' => $data['total_revenue'],
                    'breakeven_revenue' => $data['breakeven_revenue'],
                    'actual_profit' => $data['actual_profit'],
                    'breakeven_achieved' => $data['breakeven_achieved']
                ]);
        respond(true, 'Snapshot saved successfully', $data);
    } else {
        respond(false, 'Failed to save snapshot', null, 500);
    }
    $stmt->close();
}

respond(false, 'Invalid action', null, 400);

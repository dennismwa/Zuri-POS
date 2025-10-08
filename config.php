<?php
// Enhanced config.php with security improvements and new features

// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone Configuration
date_default_timezone_set('Africa/Nairobi');

// Error Reporting (disable display in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Security Headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Database Configuration - Move to environment variables in production
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'vxjtgclw_Spirits');
define('DB_PASS', getenv('DB_PASS') ?: 'SGL~3^5O?]Xie%!6');
define('DB_NAME', getenv('DB_NAME') ?: 'vxjtgclw_Spirits');

// Application Constants
define('APP_NAME', 'Zuri Wines & Spirits POS');
define('APP_VERSION', '2.0.0');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Connect to Database with improved error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        if (php_sapi_name() === 'cli') {
            die("Database connection failed. Please check your configuration.\n");
        }
        die("System temporarily unavailable. Please try again later.");
    }
    
    $conn->set_charset("utf8mb4");
    
    // Set timezone for MySQL connection
    $conn->query("SET time_zone = '+03:00'");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("System temporarily unavailable. Please contact administrator.");
}

// ==================== ENHANCED HELPER FUNCTIONS ====================

/**
 * Sanitize input using prepared statements context
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Enhanced JSON response with security headers
 */
function respond($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Enhanced authentication with session timeout
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            respond(false, 'Session expired. Please login again.', null, 401);
        } else {
            header('Location: /index.php');
            exit;
        }
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: /index.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Rate limiting for login attempts
 */
function checkLoginAttempts($identifier) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                           WHERE identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $lockoutTime = LOGIN_LOCKOUT_TIME;
    $stmt->bind_param("si", $identifier, $lockoutTime);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['attempts'] < MAX_LOGIN_ATTEMPTS;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($identifier, $success) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, success, ip_address) VALUES (?, ?, ?)");
    $ipAddress = getClientIP();
    $stmt->bind_param("sis", $identifier, $success, $ipAddress);
    $stmt->execute();
    $stmt->close();
    
    // Clean old attempts
    $conn->query("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
}

function requireOwner() {
    requireAuth();
    if ($_SESSION['role'] !== 'owner') {
        header('Location: /403.php');
        exit;
    }
}

/**
 * Enhanced activity logging with IP and user agent
 */
function logActivity($action, $description = '', $metadata = null) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return;
    
    $user_id = (int)$_SESSION['user_id'];
    $action = sanitize($action);
    $description = sanitize($description);
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $metadata_json = $metadata ? json_encode($metadata) : null;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs 
                           (user_id, action, description, ip_address, user_agent, metadata) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $action, $description, $ip_address, $user_agent, $metadata_json);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Enhanced settings with caching
 */
function getSettings() {
    global $conn;
    
    // Check cache first (valid for 5 minutes)
    if (isset($_SESSION['settings_cache']) && 
        isset($_SESSION['settings_cache_time']) &&
        (time() - $_SESSION['settings_cache_time']) < 300) {
        return $_SESSION['settings_cache'];
    }
    
    $stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $_SESSION['settings_cache'] = $settings;
        $_SESSION['settings_cache_time'] = time();
        $stmt->close();
        return $settings;
    }
    
    $stmt->close();
    
    // Default settings
    $defaults = [
        'company_name' => 'Zuri Wines & Spirits',
        'logo_path' => '/logo.jpg',
        'primary_color' => '#ea580c',
        'secondary_color' => '#ffffff',
        'currency' => 'KSh',
        'currency_symbol' => 'KSh',
        'tax_rate' => 0,
        'receipt_footer' => '',
        'barcode_scanner_enabled' => 1,
        'low_stock_alert_enabled' => 1
    ];
    
    $_SESSION['settings_cache'] = $defaults;
    $_SESSION['settings_cache_time'] = time();
    return $defaults;
}

function clearSettingsCache() {
    unset($_SESSION['settings_cache']);
    unset($_SESSION['settings_cache_time']);
}

/**
 * SECURE: Get product by ID using prepared statement
 */
function getProduct($productId) {
    global $conn;
    $productId = (int)$productId;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}

/**
 * SECURE: Update product stock
 */
function updateProductStock($productId, $quantity, $operation = 'subtract') {
    global $conn;
    $productId = (int)$productId;
    $quantity = (int)$quantity;
    
    if ($operation === 'subtract') {
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? 
                               WHERE id = ? AND stock_quantity >= ?");
        $stmt->bind_param("iii", $quantity, $productId, $quantity);
    } elseif ($operation === 'add') {
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $productId);
    } else {
        return false;
    }
    
    $result = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $result && $affected > 0;
}

function recordStockMovement($productId, $userId, $movementType, $quantity, $referenceType = null, $referenceId = null, $notes = '') {
    global $conn;
    
    $productId = (int)$productId;
    $userId = (int)$userId;
    $movementType = sanitize($movementType);
    $quantity = (int)$quantity;
    $referenceType = $referenceType ? sanitize($referenceType) : null;
    $referenceId = $referenceId ? (int)$referenceId : null;
    $notes = sanitize($notes);
    
    $stmt = $conn->prepare("INSERT INTO stock_movements 
                           (product_id, user_id, movement_type, quantity, reference_type, reference_id, notes) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisisss", $productId, $userId, $movementType, $quantity, $referenceType, $referenceId, $notes);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function getStockStatus($currentStock, $reorderLevel) {
    if ($currentStock <= 0) {
        return [
            'status' => 'out',
            'label' => 'Out of Stock',
            'color' => 'red'
        ];
    } elseif ($currentStock <= $reorderLevel) {
        return [
            'status' => 'low',
            'label' => 'Low Stock',
            'color' => 'orange'
        ];
    } else {
        return [
            'status' => 'good',
            'label' => 'In Stock',
            'color' => 'green'
        ];
    }
}

/**
 * SECURE: Get user info
 */
function getUserInfo() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return null;
    
    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

function isValidPIN($pin) {
    return preg_match('/^\d{4}$/', $pin);
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatCurrency($amount) {
    $settings = getSettings();
    return $settings['currency'] . ' ' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function generateSaleNumber() {
    return 'ZWS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Enhanced API response
 */
function apiRespond($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Validate sale data
 */
function validateSaleData($items, $totalAmount, $amountPaid) {
    if (empty($items)) {
        return ['valid' => false, 'message' => 'No items in cart'];
    }
    
    if ($totalAmount <= 0) {
        return ['valid' => false, 'message' => 'Invalid total amount'];
    }
    
    if ($amountPaid < $totalAmount) {
        return ['valid' => false, 'message' => 'Insufficient payment amount'];
    }
    
    return ['valid' => true];
}

/**
 * SECURE: Get statistics with prepared statements
 */
function getLowStockCount() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products 
                           WHERE stock_quantity <= reorder_level 
                           AND stock_quantity > 0 AND status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

function getTodaySales() {
    global $conn;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
                           FROM sales WHERE DATE(sale_date) = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ?: ['count' => 0, 'total' => 0];
}

/**
 * CHECK USER PERMISSION - SINGLE DECLARATION
 */
function hasPermission($permissionCode) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) return false;
    
    // Owners have all permissions
    if ($_SESSION['role'] === 'owner') return true;
    
    $userId = (int)$_SESSION['user_id'];
    $permissionCode = sanitize($permissionCode);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_permissions up 
                           JOIN permissions p ON up.permission_id = p.id 
                           WHERE up.user_id = ? AND p.code = ?");
    $stmt->bind_param("is", $userId, $permissionCode);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] > 0;
}

/**
 * Database backup function
 */
function backupDatabase() {
    $backupFile = __DIR__ . '/backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s > %s',
        DB_USER,
        DB_PASS,
        DB_HOST,
        DB_NAME,
        $backupFile
    );
    
    exec($command, $output, $return);
    
    return $return === 0;
}

/**
 * Send email notification (requires PHPMailer or similar)
 */
function sendEmailNotification($to, $subject, $message) {
    // Implement with PHPMailer or mail() function
    // For now, just log it
    error_log("Email to $to: $subject");
    return true;
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($headers)) {
        fputcsv($output, $headers);
    } elseif (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Initialize error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

// Clean up old sessions periodically
if (rand(1, 100) === 1) {
    $conn->query("DELETE FROM sessions WHERE logout_time IS NOT NULL 
                 AND logout_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
}

// ==================== WHATSAPP HELPER FUNCTIONS ====================

/**
 * Send WhatsApp receipt after sale
 */
function sendWhatsAppReceiptForSale($saleId, $customerPhone = null) {
    global $conn;
    
    // Check if WhatsApp is enabled
    $config = $conn->query("SELECT send_receipts, api_status FROM whatsapp_config WHERE id = 1")->fetch_assoc();
    
    if (!$config || !$config['send_receipts'] || $config['api_status'] !== 'active') {
        return ['success' => false, 'message' => 'WhatsApp receipts not enabled'];
    }
    
    // If no phone provided, check customer
    if (!$customerPhone) {
        $stmt = $conn->prepare("SELECT c.whatsapp_number 
                               FROM sales s 
                               LEFT JOIN customers c ON s.customer_id = c.id 
                               WHERE s.id = ? AND c.whatsapp_opt_in = 1");
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $customerPhone = $result['whatsapp_number'] ?? null;
    }
    
    if (!$customerPhone) {
        return ['success' => false, 'message' => 'No customer phone number'];
    }
    
    // Validate phone format
    if (!preg_match('/^\+\d{10,15}$/', $customerPhone)) {
        return ['success' => false, 'message' => 'Invalid phone format'];
    }
    
    // Make async call to send receipt (non-blocking)
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . "/api/whatsapp.php";
    
    $postData = http_build_query([
        'action' => 'send_receipt',
        'sale_id' => $saleId,
        'phone_number' => $customerPhone
    ]);
    
    // Use non-blocking curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_exec($ch);
    curl_close($ch);
    
    return ['success' => true, 'message' => 'Receipt queued for sending'];
}

/**
 * Automatic low stock check (call via cron job)
 */
function autoCheckLowStock() {
    global $conn;
    
    $config = $conn->query("SELECT send_low_stock_alerts, api_status, low_stock_threshold, admin_number 
                           FROM whatsapp_config WHERE id = 1")->fetch_assoc();
    
    if (!$config || !$config['send_low_stock_alerts'] || $config['api_status'] !== 'active') {
        return;
    }
    
    // Get low stock products
    $products = $conn->query("SELECT p.*, 
        (SELECT created_at FROM whatsapp_messages 
         WHERE reference_type = 'product' 
         AND reference_id = p.id 
         AND message_type = 'low_stock_alert'
         ORDER BY created_at DESC LIMIT 1) as last_alert
        FROM products p
        WHERE p.stock_quantity <= p.reorder_level 
        AND p.stock_quantity > 0 
        AND p.status = 'active'")->fetch_all(MYSQLI_ASSOC);
    
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . "/api/whatsapp.php";
    
    foreach ($products as $product) {
        // Only send alert if no alert sent in last 24 hours
        if ($product['last_alert']) {
            $hoursSinceAlert = (time() - strtotime($product['last_alert'])) / 3600;
            if ($hoursSinceAlert < 24) {
                continue;
            }
        }
        
        // Queue alert
        $postData = http_build_query([
            'action' => 'send_low_stock',
            'product_id' => $product['id']
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_exec($ch);
        curl_close($ch);
    }
}

/**
 * Automatic daily summary (call via cron job)
 */
function autoSendDailySummary() {
    global $conn;
    
    $config = $conn->query("SELECT send_daily_summary, api_status 
                           FROM whatsapp_config WHERE id = 1")->fetch_assoc();
    
    if (!$config || !$config['send_daily_summary'] || $config['api_status'] !== 'active') {
        return;
    }
    
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://" . $_SERVER['HTTP_HOST'] . "/api/whatsapp.php";
    
    $postData = http_build_query([
        'action' => 'send_daily_summary',
        'date' => date('Y-m-d')
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * LOG AUDIT TRAIL - Enhanced version
 */
function logAudit($actionType, $actionCategory, $description = '', $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $conn;
    
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $ipAddress = getClientIP();
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
    $sessionId = session_id();
    
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;
    
    $stmt = $conn->prepare("INSERT INTO audit_logs 
        (user_id, action_type, action_category, table_name, record_id, description, old_values, new_values, ip_address, user_agent, session_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("isssissssss", $userId, $actionType, $actionCategory, $tableName, $recordId, 
                         $description, $oldValuesJson, $newValuesJson, $ipAddress, $userAgent, $sessionId);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * GET USER PERMISSIONS
 */
function getUserPermissions($userId = null) {
    global $conn;
    
    $userId = $userId ?? $_SESSION['user_id'] ?? null;
    if (!$userId) return [];
    
    $stmt = $conn->prepare("SELECT p.code, p.name, p.category, p.description 
                           FROM user_permissions up 
                           JOIN permissions p ON up.permission_id = p.id 
                           WHERE up.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    $stmt->close();
    
    return $permissions;
}

/**
 * CALCULATE BREAK-EVEN POINT
 */
function calculateBreakEven($month = null) {
    global $conn;
    
    $month = $month ?? date('Y-m-01');
    $monthStart = date('Y-m-01', strtotime($month));
    $monthEnd = date('Y-m-t', strtotime($month));
    
    // Get fixed costs for the month
    $fixedCostsQuery = $conn->query("SELECT COALESCE(SUM(monthly_amount), 0) as total 
                                     FROM fixed_costs 
                                     WHERE status = 'active' 
                                     AND start_date <= '$monthEnd' 
                                     AND (end_date IS NULL OR end_date >= '$monthStart')");
    $totalFixedCosts = $fixedCostsQuery->fetch_assoc()['total'];
    
    // Get sales data for the month
    $salesQuery = $conn->query("SELECT 
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(subtotal), 0) as total_subtotal
        FROM sales 
        WHERE DATE(sale_date) BETWEEN '$monthStart' AND '$monthEnd' 
        AND is_voided = 0");
    $salesData = $salesQuery->fetch_assoc();
    
    // Get variable costs (cost of goods sold)
    $cogsQuery = $conn->query("SELECT COALESCE(SUM(si.quantity * p.cost_price), 0) as total_cogs
                              FROM sale_items si
                              JOIN sales s ON si.sale_id = s.id
                              JOIN products p ON si.product_id = p.id
                              WHERE DATE(s.sale_date) BETWEEN '$monthStart' AND '$monthEnd'
                              AND s.is_voided = 0");
    $totalVariableCosts = $cogsQuery->fetch_assoc()['total_cogs'];
    
    // Get total items sold
    $itemsQuery = $conn->query("SELECT COALESCE(SUM(si.quantity), 0) as total_units
                               FROM sale_items si
                               JOIN sales s ON si.sale_id = s.id
                               WHERE DATE(s.sale_date) BETWEEN '$monthStart' AND '$monthEnd'
                               AND s.is_voided = 0");
    $totalUnitsSold = $itemsQuery->fetch_assoc()['total_units'];
    
    // Calculate averages
    $avgSellingPrice = $totalUnitsSold > 0 ? $salesData['total_revenue'] / $totalUnitsSold : 0;
    $avgVariableCost = $totalUnitsSold > 0 ? $totalVariableCosts / $totalUnitsSold : 0;
    
    // Calculate break-even point
    $contributionMargin = $avgSellingPrice - $avgVariableCost;
    $breakEvenUnits = $contributionMargin > 0 ? ceil($totalFixedCosts / $contributionMargin) : 0;
    $breakEvenRevenue = $breakEvenUnits * $avgSellingPrice;
    
    // Calculate actual profit
    $actualProfit = $salesData['total_revenue'] - $totalVariableCosts - $totalFixedCosts;
    $breakEvenAchieved = $salesData['total_revenue'] >= $breakEvenRevenue ? 1 : 0;
    
    return [
        'period_month' => $monthStart,
        'total_fixed_costs' => $totalFixedCosts,
        'total_variable_costs' => $totalVariableCosts,
        'total_revenue' => $salesData['total_revenue'],
        'total_units_sold' => $totalUnitsSold,
        'avg_selling_price' => $avgSellingPrice,
        'avg_variable_cost' => $avgVariableCost,
        'contribution_margin' => $contributionMargin,
        'breakeven_units' => $breakEvenUnits,
        'breakeven_revenue' => $breakEvenRevenue,
        'actual_profit' => $actualProfit,
        'breakeven_achieved' => $breakEvenAchieved,
        'progress_percentage' => $breakEvenRevenue > 0 ? min(100, ($salesData['total_revenue'] / $breakEvenRevenue) * 100) : 0
    ];
}

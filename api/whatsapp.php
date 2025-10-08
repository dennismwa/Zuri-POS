<?php
/**
 * WhatsApp Integration API
 * api/whatsapp.php
 */

ob_start();
require_once dirname(__DIR__) . '/config.php';
requireOwner(); // Only owners can manage WhatsApp

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== GET CONFIGURATION ====================
if ($action === 'get_config') {
    $stmt = $conn->prepare("SELECT * FROM whatsapp_config WHERE id = 1");
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Mask sensitive data
    if ($config && $config['auth_token']) {
        $config['auth_token'] = substr($config['auth_token'], 0, 10) . '********';
    }
    
    respond(true, 'Configuration retrieved', ['config' => $config]);
}

// ==================== UPDATE CONFIGURATION ====================
if ($action === 'update_config') {
    $provider = sanitize($_POST['provider']);
    $accountSid = sanitize($_POST['account_sid'] ?? '');
    $authToken = sanitize($_POST['auth_token'] ?? '');
    $fromNumber = sanitize($_POST['from_number']);
    $adminNumber = sanitize($_POST['admin_number']);
    $dailySummaryTime = sanitize($_POST['daily_summary_time'] ?? '18:00:00');
    $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 10);
    $sendReceipts = isset($_POST['send_receipts']) ? 1 : 0;
    $sendLowStockAlerts = isset($_POST['send_low_stock_alerts']) ? 1 : 0;
    $sendDailySummary = isset($_POST['send_daily_summary']) ? 1 : 0;
    
    // Validate phone numbers
    if (!preg_match('/^\+\d{10,15}$/', $fromNumber) || !preg_match('/^\+\d{10,15}$/', $adminNumber)) {
        respond(false, 'Invalid phone number format. Use +254XXXXXXXXX', null, 400);
    }
    
    // Check if auth_token should be updated (not masked)
    $updateToken = !empty($authToken) && !str_contains($authToken, '****');
    
    if ($updateToken) {
        $stmt = $conn->prepare("UPDATE whatsapp_config SET 
            provider=?, account_sid=?, auth_token=?, from_number=?, admin_number=?,
            daily_summary_time=?, low_stock_threshold=?, send_receipts=?, 
            send_low_stock_alerts=?, send_daily_summary=?
            WHERE id=1");
        $stmt->bind_param("ssssssiiiii", $provider, $accountSid, $authToken, $fromNumber, 
                         $adminNumber, $dailySummaryTime, $lowStockThreshold, 
                         $sendReceipts, $sendLowStockAlerts, $sendDailySummary);
    } else {
        $stmt = $conn->prepare("UPDATE whatsapp_config SET 
            provider=?, account_sid=?, from_number=?, admin_number=?,
            daily_summary_time=?, low_stock_threshold=?, send_receipts=?, 
            send_low_stock_alerts=?, send_daily_summary=?
            WHERE id=1");
        $stmt->bind_param("sssssiiiii", $provider, $accountSid, $fromNumber, 
                         $adminNumber, $dailySummaryTime, $lowStockThreshold, 
                         $sendReceipts, $sendLowStockAlerts, $sendDailySummary);
    }
    
    if ($stmt->execute()) {
        clearSettingsCache();
        logActivity('WHATSAPP_CONFIG_UPDATED', 'WhatsApp configuration updated');
        respond(true, 'Configuration updated successfully');
    } else {
        respond(false, 'Failed to update configuration', null, 500);
    }
    $stmt->close();
}

// ==================== TEST CONNECTION ====================
if ($action === 'test_connection') {
    $config = getWhatsAppConfig();
    
    if (!$config || $config['api_status'] === 'inactive') {
        respond(false, 'WhatsApp is not configured. Please update settings first.', null, 400);
    }
    
    // Send test message
    $testMessage = "✅ Test message from " . $config['company_name'] . " POS System\n\nWhatsApp integration is working correctly!";
    
    $result = sendWhatsAppMessage(
        $config['admin_number'],
        $testMessage,
        'test',
        null,
        null
    );
    
    if ($result['success']) {
        // Update last tested time and status
        $conn->query("UPDATE whatsapp_config SET last_tested_at = NOW(), api_status = 'active' WHERE id = 1");
        respond(true, 'Test message sent successfully! Check your WhatsApp.', $result);
    } else {
        respond(false, $result['message'], $result, 400);
    }
}

// ==================== SEND RECEIPT ====================
if ($action === 'send_receipt') {
    $saleId = intval($_POST['sale_id']);
    $phoneNumber = sanitize($_POST['phone_number']);
    
    // Validate phone number
    if (!preg_match('/^\+\d{10,15}$/', $phoneNumber)) {
        respond(false, 'Invalid phone number format. Use +254XXXXXXXXX', null, 400);
    }
    
    // Get sale details
    $stmt = $conn->prepare("SELECT s.*, u.name as seller_name 
                           FROM sales s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.id = ?");
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $sale = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$sale) {
        respond(false, 'Sale not found', null, 404);
    }
    
    // Get sale items
    $stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id");
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Generate receipt message
    $message = generateReceiptMessage($sale, $items);
    
    // Send WhatsApp message
    $result = sendWhatsAppMessage(
        $phoneNumber,
        $message,
        'receipt',
        'sale',
        $saleId
    );
    
    if ($result['success']) {
        respond(true, 'Receipt sent via WhatsApp successfully!', $result);
    } else {
        respond(false, $result['message'], $result, 400);
    }
}

// ==================== GET MESSAGE LOG ====================
if ($action === 'get_messages') {
    $page = intval($_GET['page'] ?? 1);
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    
    $messageType = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $where = [];
    if ($messageType) $where[] = "message_type = '$messageType'";
    if ($status) $where[] = "status = '$status'";
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $conn->prepare("SELECT * FROM whatsapp_messages 
                           $whereClause 
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM whatsapp_messages $whereClause";
    $total = $conn->query($countQuery)->fetch_assoc()['total'];
    
    respond(true, 'Messages retrieved', [
        'messages' => $messages,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $perPage)
    ]);
}

// ==================== SEND DAILY SUMMARY ====================
if ($action === 'send_daily_summary') {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $result = sendDailySummary($date);
    
    if ($result['success']) {
        respond(true, 'Daily summary sent successfully!', $result);
    } else {
        respond(false, $result['message'], $result, 400);
    }
}

// ==================== CHECK LOW STOCK ====================
if ($action === 'check_low_stock') {
    $result = checkAndSendLowStockAlerts();
    
    respond(true, 'Low stock check completed', $result);
}

respond(false, 'Invalid action: ' . $action, null, 400);

respond(false, 'Invalid action: ' . $action, null, 400);

// ==================== HELPER FUNCTIONS ====================

function getWhatsAppConfig() {
    global $conn;
    $stmt = $conn->prepare("SELECT wc.*, s.company_name, s.receipt_footer 
                           FROM whatsapp_config wc 
                           CROSS JOIN settings s 
                           WHERE wc.id = 1 AND s.id = 1");
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $config;
}

function sendWhatsAppMessage($toNumber, $message, $messageType, $referenceType = null, $referenceId = null) {
    global $conn;
    
    $config = getWhatsAppConfig();
    
    if (!$config || $config['api_status'] === 'inactive') {
        return ['success' => false, 'message' => 'WhatsApp not configured'];
    }
    
    // Log message as pending
    $stmt = $conn->prepare("INSERT INTO whatsapp_messages 
        (message_type, recipient_number, message_content, status, reference_type, reference_id) 
        VALUES (?, ?, ?, 'pending', ?, ?)");
    $stmt->bind_param("ssssi", $messageType, $toNumber, $message, $referenceType, $referenceId);
    $stmt->execute();
    $messageId = $conn->insert_id;
    $stmt->close();
    
    // Send via Twilio
    if ($config['provider'] === 'twilio') {
        $result = sendViaTwilio($config, $toNumber, $message);
    } else {
        $result = ['success' => false, 'message' => 'Provider not supported yet'];
    }
    
    // Update message status
    if ($result['success']) {
        $stmt = $conn->prepare("UPDATE whatsapp_messages 
                               SET status = 'sent', 
                                   provider_message_id = ?, 
                                   sent_at = NOW() 
                               WHERE id = ?");
        $stmt->bind_param("si", $result['message_sid'], $messageId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("UPDATE whatsapp_messages 
                               SET status = 'failed', 
                                   error_message = ? 
                               WHERE id = ?");
        $stmt->bind_param("si", $result['message'], $messageId);
        $stmt->execute();
        $stmt->close();
    }
    
    return $result;
}

function sendViaTwilio($config, $toNumber, $message) {
    if (empty($config['account_sid']) || empty($config['auth_token']) || empty($config['from_number'])) {
        return ['success' => false, 'message' => 'Twilio credentials not configured'];
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json";
    
    $data = [
        'From' => 'whatsapp:' . $config['from_number'],
        'To' => 'whatsapp:' . $toNumber,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, $config['account_sid'] . ':' . $config['auth_token']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        return [
            'success' => true,
            'message_sid' => $result['sid'] ?? null,
            'status' => $result['status'] ?? 'sent'
        ];
    } else {
        $error = json_decode($response, true);
        return [
            'success' => false,
            'message' => $error['message'] ?? 'Failed to send message'
        ];
    }
}

function generateReceiptMessage($sale, $items) {
    $config = getWhatsAppConfig();
    $settings = getSettings();
    
    // Get template
    $template = getTemplate('receipt_default');
    
    // Format items
    $itemsText = '';
    foreach ($items as $item) {
        $itemsText .= "• {$item['product_name']} x{$item['quantity']} @ {$settings['currency']} " . 
                     number_format($item['unit_price'], 2) . " = {$settings['currency']} " . 
                     number_format($item['subtotal'], 2) . "\n";
    }
    
    // Replace variables
    $message = str_replace(
        [
            '{company_name}', '{sale_number}', '{date}', '{time}', '{seller}',
            '{items}', '{subtotal}', '{tax}', '{total}', '{paid}', '{change}',
            '{payment_method}', '{footer}'
        ],
        [
            $config['company_name'],
            $sale['sale_number'],
            date('M d, Y', strtotime($sale['sale_date'])),
            date('h:i A', strtotime($sale['sale_date'])),
            $sale['seller_name'],
            $itemsText,
            $settings['currency'] . ' ' . number_format($sale['subtotal'], 2),
            $settings['currency'] . ' ' . number_format($sale['tax_amount'], 2),
            $settings['currency'] . ' ' . number_format($sale['total_amount'], 2),
            $settings['currency'] . ' ' . number_format($sale['amount_paid'], 2),
            $settings['currency'] . ' ' . number_format($sale['change_amount'], 2),
            strtoupper(str_replace('_', ' ', $sale['payment_method'])),
            $config['receipt_footer'] ?? ''
        ],
        $template
    );
    
    return $message;
}

function getTemplate($templateName) {
    global $conn;
    $stmt = $conn->prepare("SELECT template_content FROM whatsapp_templates 
                           WHERE template_name = ? AND is_active = 1");
    $stmt->bind_param("s", $templateName);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['template_content'] ?? '';
}

function sendDailySummary($date) {
    global $conn;
    $config = getWhatsAppConfig();
    
    if (!$config || !$config['send_daily_summary']) {
        return ['success' => false, 'message' => 'Daily summary not enabled'];
    }
    
    // Get stats
    $stats = $conn->query("SELECT 
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_sale,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) as cash_sales,
        COALESCE(SUM(CASE WHEN payment_method IN ('mpesa', 'mpesa_till') THEN total_amount ELSE 0 END), 0) as mpesa_sales,
        COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END), 0) as card_sales
        FROM sales WHERE DATE(sale_date) = '$date'")->fetch_assoc();
    
    // Get top products
    $topProducts = $conn->query("SELECT p.name, SUM(si.quantity) as qty, SUM(si.subtotal) as revenue
        FROM sale_items si 
        JOIN products p ON si.product_id = p.id
        JOIN sales s ON si.sale_id = s.id
        WHERE DATE(s.sale_date) = '$date'
        GROUP BY si.product_id
        ORDER BY revenue DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    
    // Get top sellers
    $topSellers = $conn->query("SELECT u.name, COUNT(s.id) as sales, SUM(s.total_amount) as revenue
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE DATE(s.sale_date) = '$date'
        GROUP BY s.user_id
        ORDER BY revenue DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    
    // Format data
    $settings = getSettings();
    $topProductsText = '';
    foreach ($topProducts as $idx => $p) {
        $topProductsText .= ($idx + 1) . ". {$p['name']} ({$p['qty']} sold) - {$settings['currency']} " . number_format($p['revenue'], 2) . "\n";
    }
    
    $topSellersText = '';
    foreach ($topSellers as $idx => $s) {
        $topSellersText .= ($idx + 1) . ". {$s['name']} ({$s['sales']} sales) - {$settings['currency']} " . number_format($s['revenue'], 2) . "\n";
    }
    
    // Get template and replace variables
    $template = getTemplate('daily_summary');
    $message = str_replace(
        ['{date}', '{total_sales}', '{total_revenue}', '{avg_sale}', 
         '{cash_sales}', '{mpesa_sales}', '{card_sales}', 
         '{top_products}', '{top_sellers}'],
        [
            date('M d, Y', strtotime($date)),
            $stats['total_sales'],
            $settings['currency'] . ' ' . number_format($stats['total_revenue'], 2),
            $settings['currency'] . ' ' . number_format($stats['avg_sale'], 2),
            $settings['currency'] . ' ' . number_format($stats['cash_sales'], 2),
            $settings['currency'] . ' ' . number_format($stats['mpesa_sales'], 2),
            $settings['currency'] . ' ' . number_format($stats['card_sales'], 2),
            $topProductsText ?: 'No sales',
            $topSellersText ?: 'No sellers'
        ],
        $template
    );
    
    return sendWhatsAppMessage($config['admin_number'], $message, 'daily_summary', null, null);
}

function checkAndSendLowStockAlerts() {
    global $conn;
    $config = getWhatsAppConfig();
    
    if (!$config || !$config['send_low_stock_alerts']) {
        return ['success' => false, 'message' => 'Low stock alerts not enabled'];
    }
    
    // Get low stock products
    $threshold = $config['low_stock_threshold'];
    $products = $conn->query("SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock_quantity <= p.reorder_level 
        AND p.stock_quantity > 0 
        AND p.status = 'active'
        ORDER BY p.stock_quantity ASC")->fetch_all(MYSQLI_ASSOC);
    
    $sentCount = 0;
    foreach ($products as $product) {
        $template = getTemplate('low_stock_alert');
        $message = str_replace(
            ['{product_name}', '{current_stock}', '{reorder_level}', '{branch}'],
            [$product['name'], $product['stock_quantity'], $product['reorder_level'], 'Main'],
            $template
        );
        
        $result = sendWhatsAppMessage($config['admin_number'], $message, 'low_stock_alert', 'product', $product['id']);
        if ($result['success']) $sentCount++;
    }
    
    return [
        'success' => true,
        'products_found' => count($products),
        'alerts_sent' => $sentCount
    ];
}
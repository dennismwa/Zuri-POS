<?php
/**
 * Online Orders API
 * api/online-orders.php
 */

ob_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Public actions (no auth required)
$publicActions = ['get_config', 'get_products', 'get_zones', 'place_order', 'track_order'];

if (!in_array($action, $publicActions)) {
    requireAuth();
}

// ==================== GET CONFIG (Public) ====================
if ($action === 'get_config') {
    $config = $conn->query("SELECT * FROM online_ordering_config WHERE id = 1")->fetch_assoc();
    $settings = getSettings();
    
    respond(true, 'Config retrieved', [
        'config' => $config,
        'company' => [
            'name' => $settings['company_name'],
            'logo' => $settings['logo_path'],
            'currency' => $settings['currency']
        ]
    ]);
}

// ==================== GET PRODUCTS (Public) ====================
if ($action === 'get_products') {
    $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    
    $where = ["p.status = 'active'", "p.stock_quantity > 0"];
    
    if ($category > 0) {
        $where[] = "p.category_id = $category";
    }
    
    if ($search) {
        $where[] = "(p.name LIKE '%$search%' OR p.barcode LIKE '%$search%')";
    }
    
    $whereClause = implode(' AND ', $where);
    
    $products = $conn->query("SELECT p.*, c.name as category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE $whereClause 
                             ORDER BY p.name ASC")->fetch_all(MYSQLI_ASSOC);
    
    $categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    
    respond(true, 'Products retrieved', [
        'products' => $products,
        'categories' => $categories
    ]);
}

// ==================== GET DELIVERY ZONES (Public) ====================
if ($action === 'get_zones') {
    $zones = $conn->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY delivery_fee ASC")->fetch_all(MYSQLI_ASSOC);
    
    foreach ($zones as &$zone) {
        $zone['areas'] = json_decode($zone['areas'], true);
    }
    
    respond(true, 'Zones retrieved', ['zones' => $zones]);
}

// ==================== PLACE ORDER (Public) ====================
if ($action === 'place_order') {
    $config = $conn->query("SELECT * FROM online_ordering_config WHERE id = 1")->fetch_assoc();
    
    if (!$config['is_enabled']) {
        respond(false, 'Online ordering is currently disabled', null, 503);
    }
    
    // Get form data
    $customerName = sanitize($_POST['customer_name']);
    $customerPhone = sanitize($_POST['customer_phone']);
    $customerEmail = isset($_POST['customer_email']) ? sanitize($_POST['customer_email']) : null;
    $customerWhatsApp = isset($_POST['customer_whatsapp']) ? sanitize($_POST['customer_whatsapp']) : null;
    
    $deliveryType = sanitize($_POST['delivery_type']);
    $deliveryAddress = isset($_POST['delivery_address']) ? sanitize($_POST['delivery_address']) : null;
    $deliveryCity = isset($_POST['delivery_city']) ? sanitize($_POST['delivery_city']) : null;
    $deliveryArea = isset($_POST['delivery_area']) ? sanitize($_POST['delivery_area']) : null;
    $deliveryInstructions = isset($_POST['delivery_instructions']) ? sanitize($_POST['delivery_instructions']) : null;
    $deliveryFee = floatval($_POST['delivery_fee'] ?? 0);
    
    $items = json_decode($_POST['items'], true);
    $orderNotes = isset($_POST['order_notes']) ? sanitize($_POST['order_notes']) : null;
    
    // Validation
    if (empty($customerName) || empty($customerPhone)) {
        respond(false, 'Customer name and phone are required', null, 400);
    }
    
    if (empty($items) || !is_array($items)) {
        respond(false, 'No items in order', null, 400);
    }
    
    if ($deliveryType === 'delivery' && empty($deliveryAddress)) {
        respond(false, 'Delivery address is required', null, 400);
    }
    
    // Validate phone format
    if (!preg_match('/^\+?\d{10,15}$/', $customerPhone)) {
        respond(false, 'Invalid phone number format', null, 400);
    }
    
    $conn->begin_transaction();
    
    try {
        // Calculate totals
        $subtotal = 0;
        $validatedItems = [];
        
        foreach ($items as $item) {
            $productId = intval($item['id']);
            $quantity = intval($item['quantity']);
            
            // Get product details
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                throw new Exception("Product not found: ID $productId");
            }
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("{$product['name']} - Only {$product['stock_quantity']} available");
            }
            
            $itemSubtotal = $product['selling_price'] * $quantity;
            $subtotal += $itemSubtotal;
            
            $validatedItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_sku' => $product['sku'],
                'quantity' => $quantity,
                'unit_price' => $product['selling_price'],
                'subtotal' => $itemSubtotal
            ];
        }
        
        // Check min order amount
        if ($subtotal < $config['min_order_amount']) {
            throw new Exception("Minimum order amount is " . formatCurrency($config['min_order_amount']));
        }
        
        // Check max order amount
        if ($subtotal > $config['max_order_amount']) {
            throw new Exception("Maximum order amount is " . formatCurrency($config['max_order_amount']));
        }
        
        // Calculate tax
        $settings = getSettings();
        $taxRate = floatval($settings['tax_rate']);
        $taxAmount = $subtotal * ($taxRate / 100);
        
        // Apply free delivery
        if ($config['free_delivery_above'] > 0 && $subtotal >= $config['free_delivery_above']) {
            $deliveryFee = 0;
        }
        
        $totalAmount = $subtotal + $taxAmount + $deliveryFee;
        
        // Generate order number
        $orderNumber = 'WEB-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        // Insert order
        $ipAddress = getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $status = $config['auto_confirm_orders'] ? 'confirmed' : 'pending';
        
        $stmt = $conn->prepare("INSERT INTO online_orders 
            (order_number, customer_name, customer_email, customer_phone, customer_whatsapp,
             delivery_type, delivery_address, delivery_city, delivery_area, delivery_instructions, delivery_fee,
             subtotal, tax_amount, total_amount, status, order_notes, ip_address, user_agent, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("ssssssssssdddsssss",
            $orderNumber, $customerName, $customerEmail, $customerPhone, $customerWhatsApp,
            $deliveryType, $deliveryAddress, $deliveryCity, $deliveryArea, $deliveryInstructions, $deliveryFee,
            $subtotal, $taxAmount, $totalAmount, $status, $orderNotes, $ipAddress, $userAgent
        );
        
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();
        
        // Insert order items
        foreach ($validatedItems as $item) {
            $stmt = $conn->prepare("INSERT INTO online_order_items 
                (order_id, product_id, product_name, product_sku, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("iissidd",
                $orderId, $item['product_id'], $item['product_name'], $item['product_sku'],
                $item['quantity'], $item['unit_price'], $item['subtotal']
            );
            
            $stmt->execute();
            $stmt->close();
        }
        
        // Log status history
        $conn->query("INSERT INTO order_status_history (order_id, new_status, notes) 
                     VALUES ($orderId, '$status', 'Order placed online')");
        
        $conn->commit();
        
        // Send notifications if WhatsApp enabled
        if (function_exists('sendWhatsAppReceiptForSale') && $customerWhatsApp) {
            // Queue WhatsApp notification (non-blocking)
        }
        
        respond(true, 'Order placed successfully!', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $totalAmount,
            'status' => $status
        ], 201);
        
    } catch (Exception $e) {
        $conn->rollback();
        respond(false, $e->getMessage(), null, 400);
    }
}

// ==================== TRACK ORDER (Public) ====================
if ($action === 'track_order') {
    $orderNumber = sanitize($_GET['order_number']);
    $phone = sanitize($_GET['phone']);
    
    $stmt = $conn->prepare("SELECT * FROM online_orders 
                           WHERE order_number = ? AND customer_phone = ?");
    $stmt->bind_param("ss", $orderNumber, $phone);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        respond(false, 'Order not found', null, 404);
    }
    
    // Get items
    $stmt = $conn->prepare("SELECT * FROM online_order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get status history
    $stmt = $conn->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    respond(true, 'Order found', [
        'order' => $order,
        'items' => $items,
        'history' => $history
    ]);
}

// ==================== ADMIN: GET ORDERS ====================
if ($action === 'get_orders') {
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    $dateFrom = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-d');
    $dateTo = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d');
    
    $where = ["DATE(order_date) BETWEEN '$dateFrom' AND '$dateTo'"];
    
    if ($status) {
        $where[] = "status = '$status'";
    }
    
    $whereClause = implode(' AND ', $where);
    
    $orders = $conn->query("SELECT o.*, 
                           u.name as assigned_user,
                           (SELECT COUNT(*) FROM online_order_items WHERE order_id = o.id) as item_count
                           FROM online_orders o
                           LEFT JOIN users u ON o.assigned_to = u.id
                           WHERE $whereClause
                           ORDER BY order_date DESC
                           LIMIT 100")->fetch_all(MYSQLI_ASSOC);
    
    respond(true, 'Orders retrieved', ['orders' => $orders]);
}

// ==================== ADMIN: UPDATE ORDER STATUS ====================
if ($action === 'update_status') {
    requireAuth();
    
    $orderId = intval($_POST['order_id']);
    $newStatus = sanitize($_POST['status']);
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : null;
    
    $validStatuses = ['pending', 'confirmed', 'processing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
    
    if (!in_array($newStatus, $validStatuses)) {
        respond(false, 'Invalid status', null, 400);
    }
    
    // Get current order
    $stmt = $conn->prepare("SELECT * FROM online_orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        respond(false, 'Order not found', null, 404);
    }
    
    $oldStatus = $order['status'];
    
    // Update order
    $timestamp = null;
    if ($newStatus === 'confirmed') $timestamp = 'confirmed_at = NOW()';
    if ($newStatus === 'completed') $timestamp = 'completed_at = NOW()';
    if ($newStatus === 'cancelled') $timestamp = 'cancelled_at = NOW()';
    
    $sql = "UPDATE online_orders SET status = ?";
    if ($timestamp) $sql .= ", $timestamp";
    $sql .= " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $orderId);
    $stmt->execute();
    $stmt->close();
    
    // Log status change
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO order_status_history 
                           (order_id, old_status, new_status, changed_by, notes)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $orderId, $oldStatus, $newStatus, $userId, $notes);
    $stmt->execute();
    $stmt->close();
    
    logActivity('ORDER_STATUS_UPDATED', "Order {$order['order_number']}: $oldStatus → $newStatus");
    
    respond(true, 'Order status updated successfully');
}

// ==================== ADMIN: ASSIGN ORDER ====================
if ($action === 'assign_order') {
    requireOwner();
    
    $orderId = intval($_POST['order_id']);
    $userId = intval($_POST['user_id']);
    
    $stmt = $conn->prepare("UPDATE online_orders SET assigned_to = ?, assigned_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $userId, $orderId);
    $stmt->execute();
    $stmt->close();
    
    respond(true, 'Order assigned successfully');
}

respond(false, 'Invalid action: ' . $action, null, 400);
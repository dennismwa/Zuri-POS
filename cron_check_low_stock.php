<?php
/**
 * Check Low Stock and Send Alerts
 * Add to crontab: 0 9,15 * * * php /path/to/cron_check_low_stock.php
 * Runs twice daily at 9 AM and 3 PM
 */

require_once __DIR__ . '/config.php';

// Only run if WhatsApp is configured
$config = $conn->query("SELECT send_low_stock_alerts, api_status 
                       FROM whatsapp_config WHERE id = 1")->fetch_assoc();

if (!$config || !$config['send_low_stock_alerts'] || $config['api_status'] !== 'active') {
    echo "WhatsApp not configured or low stock alerts disabled\n";
    exit;
}

// Call the helper function
if (function_exists('autoCheckLowStock')) {
    autoCheckLowStock();
    echo "Low stock check completed: " . date('Y-m-d H:i:s') . "\n";
} else {
    echo "Function autoCheckLowStock not found\n";
}
?>

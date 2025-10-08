<?php
/**
 * Send Daily Sales Summary
 * Add to crontab: 0 18 * * * php /path/to/cron_daily_summary.php
 * Runs daily at 6 PM (adjust time in whatsapp_config table)
 */

require_once __DIR__ . '/config.php';

// Get configured time
$config = $conn->query("SELECT send_daily_summary, api_status, daily_summary_time 
                       FROM whatsapp_config WHERE id = 1")->fetch_assoc();

if (!$config || !$config['send_daily_summary'] || $config['api_status'] !== 'active') {
    echo "WhatsApp not configured or daily summary disabled\n";
    exit;
}

// Check if it's the right time (within 5 minutes of configured time)
$configuredTime = strtotime($config['daily_summary_time']);
$currentTime = strtotime(date('H:i:s'));
$timeDiff = abs($currentTime - $configuredTime);

if ($timeDiff > 300) { // More than 5 minutes difference
    echo "Not the configured time yet. Configured: {$config['daily_summary_time']}, Current: " . date('H:i:s') . "\n";
    exit;
}

// Call the helper function
if (function_exists('autoSendDailySummary')) {
    autoSendDailySummary();
    echo "Daily summary sent: " . date('Y-m-d H:i:s') . "\n";
} else {
    echo "Function autoSendDailySummary not found\n";
}
?>
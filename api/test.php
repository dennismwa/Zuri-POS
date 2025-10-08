<?php
/**
 * Enhanced API Test with Path Diagnostics
 * api/test.php
 */

header('Content-Type: application/json');

// Get the current script path
$scriptPath = __FILE__;
$scriptDir = dirname(__FILE__);
$parentDir = dirname($scriptDir);

$response = [
    'success' => true,
    'message' => 'API folder is accessible!',
    'timestamp' => date('Y-m-d H:i:s'),
    'paths' => [
        'script_path' => $scriptPath,
        'script_dir' => $scriptDir,
        'parent_dir' => $parentDir,
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown'
    ],
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => PHP_VERSION,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'post_params' => array_keys($_POST),
    'files_exist' => [
        'config.php' => file_exists($parentDir . '/config.php'),
        'branches.php' => file_exists($scriptDir . '/branches.php'),
        'users.php' => file_exists($scriptDir . '/users.php'),
        'complete-sale.php' => file_exists($scriptDir . '/complete-sale.php')
    ],
    'recommended_urls' => [
        'branches' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . dirname($_SERVER['SCRIPT_NAME']) . '/branches.php',
        'users' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . dirname($_SERVER['SCRIPT_NAME']) . '/users.php'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
<?php
/**
 * API Diagnostic Tool
 * Upload as: api/diagnostic.php
 * Access at: https://stockhub.zuri.co.ke/api/diagnostic.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3b82f6; }
        .success { border-left-color: #10b981; background: #d1fae5; }
        .error { border-left-color: #ef4444; background: #fee2e2; }
        .warning { border-left-color: #f59e0b; background: #fef3c7; }
        pre { background: #1f2937; color: #10b981; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #1f2937; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🔍 API Diagnostic Tool</h1>
    
    <?php
    // Test 1: Current Script Info
    echo "<h2>1. Current Script Location</h2>";
    echo "<div class='box'>";
    echo "<strong>Script Path:</strong> " . __FILE__ . "<br>";
    echo "<strong>Script Dir:</strong> " . __DIR__ . "<br>";
    echo "<strong>Parent Dir:</strong> " . dirname(__DIR__) . "<br>";
    echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "<strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "<strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "<br>";
    echo "</div>";
    
    // Test 2: Check if files exist
    echo "<h2>2. File Existence Check</h2>";
    $files = [
        'config.php' => dirname(__DIR__) . '/config.php',
        'branches.php (API)' => __DIR__ . '/branches.php',
        'users.php (API)' => __DIR__ . '/users.php',
        'branches.php (Page)' => dirname(__DIR__) . '/branches.php'
    ];
    
    foreach ($files as $name => $path) {
        $exists = file_exists($path);
        $class = $exists ? 'success' : 'error';
        echo "<div class='box $class'>";
        echo "<strong>$name:</strong> " . ($exists ? '✅ EXISTS' : '❌ NOT FOUND') . "<br>";
        echo "<strong>Path:</strong> $path<br>";
        if ($exists) {
            echo "<strong>Readable:</strong> " . (is_readable($path) ? 'Yes' : 'No') . "<br>";
            echo "<strong>Size:</strong> " . filesize($path) . " bytes<br>";
        }
        echo "</div>";
    }
    
    // Test 3: Try to include config
    echo "<h2>3. Config.php Test</h2>";
    $configPath = dirname(__DIR__) . '/config.php';
    
    if (file_exists($configPath)) {
        echo "<div class='box'>";
        try {
            // Start output buffering to catch any output
            ob_start();
            require_once $configPath;
            $configOutput = ob_get_clean();
            
            echo "<strong>✅ Config loaded successfully</strong><br>";
            
            if (!empty($configOutput)) {
                echo "<div class='box warning'>";
                echo "<strong>⚠️ Config produced output (this may cause issues):</strong><br>";
                echo "<pre>" . htmlspecialchars($configOutput) . "</pre>";
                echo "</div>";
            }
            
            // Check if connection works
            if (isset($conn) && $conn instanceof mysqli) {
                echo "<strong>Database Connection:</strong> ✅ Connected<br>";
                echo "<strong>Host:</strong> " . DB_HOST . "<br>";
                echo "<strong>Database:</strong> " . DB_NAME . "<br>";
                
                // Test a simple query
                $result = $conn->query("SELECT COUNT(*) as count FROM branches");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<strong>Branches Count:</strong> " . $row['count'] . "<br>";
                } else {
                    echo "<strong>Query Error:</strong> " . $conn->error . "<br>";
                }
            } else {
                echo "<strong>Database Connection:</strong> ❌ Not available<br>";
            }
            
            // Check session
            echo "<strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "<br>";
            if (isset($_SESSION['user_id'])) {
                echo "<strong>User ID:</strong> " . $_SESSION['user_id'] . "<br>";
                echo "<strong>Role:</strong> " . ($_SESSION['role'] ?? 'not set') . "<br>";
            } else {
                echo "<strong>User:</strong> Not logged in<br>";
            }
            
        } catch (Exception $e) {
            echo "<strong>❌ Error loading config:</strong><br>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "<strong>❌ Config.php not found at:</strong> $configPath";
        echo "</div>";
    }
    
    // Test 4: Test branches API directly
    echo "<h2>4. Test Branches API (Internal)</h2>";
    if (file_exists(__DIR__ . '/branches.php')) {
        echo "<div class='box'>";
        
        // Set up environment for API test
        $_GET['action'] = 'get_branches';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        echo "<strong>Testing internal API call...</strong><br>";
        
        ob_start();
        try {
            include __DIR__ . '/branches.php';
            $apiOutput = ob_get_clean();
            
            echo "<strong>✅ API file executed</strong><br>";
            echo "<strong>Output Type:</strong> " . (json_decode($apiOutput) ? 'Valid JSON' : 'Not JSON') . "<br>";
            
            if ($jsonData = json_decode($apiOutput, true)) {
                echo "<strong>Success:</strong> " . ($jsonData['success'] ? 'Yes' : 'No') . "<br>";
                echo "<strong>Message:</strong> " . ($jsonData['message'] ?? 'none') . "<br>";
                if (isset($jsonData['data']['branches'])) {
                    echo "<strong>Branches Count:</strong> " . count($jsonData['data']['branches']) . "<br>";
                }
            }
            
            echo "<strong>Raw Output:</strong><br>";
            echo "<pre>" . htmlspecialchars(substr($apiOutput, 0, 500)) . "</pre>";
            
        } catch (Exception $e) {
            $error = ob_get_clean();
            echo "<strong>❌ Error:</strong><br>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            if ($error) {
                echo "<strong>Output before error:</strong><br>";
                echo "<pre>" . htmlspecialchars($error) . "</pre>";
            }
        }
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "❌ branches.php not found in API directory";
        echo "</div>";
    }
    
    // Test 5: Server Configuration
    echo "<h2>5. Server Configuration</h2>";
    echo "<div class='box'>";
    echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
    echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
    echo "<strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "<br>";
    echo "<strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
    echo "<strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "<strong>Protocol:</strong> " . ($_SERVER['SERVER_PROTOCOL'] ?? 'Unknown') . "<br>";
    echo "</div>";
    
    // Test 6: .htaccess Check
    echo "<h2>6. .htaccess Configuration</h2>";
    $htaccessPath = dirname(__DIR__) . '/.htaccess';
    if (file_exists($htaccessPath)) {
        echo "<div class='box success'>";
        echo "<strong>✅ .htaccess exists</strong><br>";
        echo "<strong>Content:</strong><br>";
        echo "<pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='box warning'>";
        echo "⚠️ No .htaccess file found (may be OK depending on server config)";
        echo "</div>";
    }
    
    // Test 7: Permissions
    echo "<h2>7. File Permissions</h2>";
    echo "<div class='box'>";
    $apiDir = __DIR__;
    echo "<strong>API Directory:</strong> $apiDir<br>";
    echo "<strong>Readable:</strong> " . (is_readable($apiDir) ? '✅ Yes' : '❌ No') . "<br>";
    echo "<strong>Writable:</strong> " . (is_writable($apiDir) ? '✅ Yes' : '❌ No') . "<br>";
    echo "<strong>Executable:</strong> " . (is_executable($apiDir) ? '✅ Yes' : '❌ No') . "<br>";
    
    if (file_exists(__DIR__ . '/branches.php')) {
        echo "<br><strong>branches.php:</strong><br>";
        $perms = fileperms(__DIR__ . '/branches.php');
        echo "<strong>Permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "<br>";
        echo "<strong>Owner:</strong> " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(__DIR__ . '/branches.php'))['name'] : 'Unknown') . "<br>";
    }
    echo "</div>";
    
    // Test 8: Provide URLs for testing
    echo "<h2>8. Test URLs</h2>";
    echo "<div class='box'>";
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    
    $testUrls = [
        'This Diagnostic' => $baseUrl . $_SERVER['SCRIPT_NAME'],
        'API Branches (GET)' => $baseUrl . $basePath . '/branches.php?action=get_branches',
        'API Users (GET)' => $baseUrl . $basePath . '/users.php',
        'Branches Page' => str_replace('/api', '', $baseUrl . $basePath) . '/branches.php'
    ];
    
    foreach ($testUrls as $name => $url) {
        echo "<strong>$name:</strong><br>";
        echo "<a href='$url' target='_blank' style='color: #3b82f6; text-decoration: underline;'>$url</a><br><br>";
    }
    echo "</div>";
    
    // Test 9: Error Log
    echo "<h2>9. Recent PHP Errors</h2>";
    $errorLog = dirname(__DIR__) . '/error.log';
    if (file_exists($errorLog)) {
        echo "<div class='box warning'>";
        echo "<strong>⚠️ Error log exists - showing last 20 lines:</strong><br>";
        $lines = file($errorLog);
        $lastLines = array_slice($lines, -20);
        echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='box success'>";
        echo "✅ No error log found (or errors not being logged)";
        echo "</div>";
    }
    ?>
    
    <h2>📋 Summary</h2>
    <div class="box">
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Review all the checks above</li>
            <li>Click the test URLs to see actual responses</li>
            <li>Check if branches.php returns valid JSON</li>
            <li>Verify authentication is working</li>
        </ol>
    </div>
    
    <div class="box" style="border-left-color: #8b5cf6; background: #f3e8ff;">
        <strong>🔧 Quick Fix Options:</strong><br><br>
        1. If config.php is not loading: Check file path<br>
        2. If not authenticated: Login first at <a href="<?php echo str_replace('/api', '', $baseUrl . $basePath); ?>/index.php">Login Page</a><br>
        3. If 404 errors: Check .htaccess or server routing<br>
        4. If permission errors: Run: chmod 644 api/*.php<br>
    </div>
</body>
</html>
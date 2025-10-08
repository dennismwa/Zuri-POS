<?php
/**
 * Root Directory Diagnostic
 * Upload to ROOT: diagnostic.php
 * Access: https://stockhub.zuri.co.ke/diagnostic.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; max-width: 1200px; margin: 0 auto; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left-color: #10b981; background: #d1fae5; }
        .error { border-left-color: #ef4444; background: #fee2e2; }
        .warning { border-left-color: #f59e0b; background: #fef3c7; }
        pre { background: #1f2937; color: #10b981; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        h2 { color: #1f2937; margin-top: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        td:first-child { font-weight: bold; width: 200px; }
        .btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔍 System Diagnostic Report</h1>
    <p style="background: #dbeafe; padding: 15px; border-radius: 5px; border-left: 4px solid #3b82f6;">
        <strong>Purpose:</strong> This diagnostic will help identify why the branches API is returning 404 errors.
    </p>
    
    <?php
    $issues = [];
    $fixes = [];
    
    // Test 1: Current Location
    echo "<h2>1. Current File Location</h2>";
    echo "<div class='box'>";
    echo "<table>";
    echo "<tr><td>This File:</td><td>" . __FILE__ . "</td></tr>";
    echo "<tr><td>Directory:</td><td>" . __DIR__ . "</td></tr>";
    echo "<tr><td>Document Root:</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
    echo "<tr><td>Script Name:</td><td>" . $_SERVER['SCRIPT_NAME'] . "</td></tr>";
    echo "<tr><td>Request URI:</td><td>" . $_SERVER['REQUEST_URI'] . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Test 2: Directory Structure
    echo "<h2>2. Directory Structure</h2>";
    $rootDir = __DIR__;
    
    echo "<div class='box'>";
    echo "<strong>Scanning root directory...</strong><br><br>";
    
    $items = scandir($rootDir);
    $directories = [];
    $phpFiles = [];
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $rootDir . '/' . $item;
        if (is_dir($path)) {
            $directories[] = $item;
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $phpFiles[] = $item;
        }
    }
    
    echo "<strong>Directories found:</strong><br>";
    if (empty($directories)) {
        echo "<span style='color: #ef4444;'>❌ No directories found!</span><br>";
    } else {
        foreach ($directories as $dir) {
            $isApi = $dir === 'api';
            $icon = $isApi ? '✅' : '📁';
            $color = $isApi ? '#10b981' : '#6b7280';
            echo "<span style='color: $color;'>$icon $dir</span><br>";
        }
    }
    
    echo "<br><strong>PHP Files in root:</strong><br>";
    foreach ($phpFiles as $file) {
        $important = in_array($file, ['config.php', 'branches.php', 'index.php']);
        $icon = $important ? '⭐' : '📄';
        echo "$icon $file<br>";
    }
    echo "</div>";
    
    // Test 3: Check API Directory
    echo "<h2>3. API Directory Check</h2>";
    $apiDir = $rootDir . '/api';
    
    if (is_dir($apiDir)) {
        echo "<div class='box success'>";
        echo "✅ <strong>API directory EXISTS</strong><br><br>";
        
        echo "<strong>Files in API directory:</strong><br>";
        $apiFiles = scandir($apiDir);
        foreach ($apiFiles as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $apiDir . '/' . $file;
            $size = filesize($filePath);
            $readable = is_readable($filePath) ? '✅' : '❌';
            
            echo "$readable $file (" . number_format($size) . " bytes)<br>";
        }
        echo "</div>";
        
        // Check specific files
        $requiredFiles = ['branches.php', 'users.php', 'test.php'];
        foreach ($requiredFiles as $file) {
            $filePath = $apiDir . '/' . $file;
            if (!file_exists($filePath)) {
                $issues[] = "Missing file: api/$file";
                $fixes[] = "Upload api/$file to the api directory";
            }
        }
        
    } else {
        echo "<div class='box error'>";
        echo "❌ <strong>API directory NOT FOUND!</strong><br>";
        echo "<strong>Expected location:</strong> $apiDir<br><br>";
        echo "<strong>This is the main problem!</strong> You need to create the api directory.";
        echo "</div>";
        
        $issues[] = "API directory does not exist";
        $fixes[] = "Create the 'api' folder in your root directory";
        $fixes[] = "Upload all API files (branches.php, users.php, etc.) to the api folder";
    }
    
    // Test 4: Check config.php
    echo "<h2>4. Configuration File</h2>";
    $configPath = $rootDir . '/config.php';
    
    if (file_exists($configPath)) {
        echo "<div class='box success'>";
        echo "✅ <strong>config.php exists</strong><br><br>";
        
        try {
            ob_start();
            require_once $configPath;
            $configOutput = ob_get_clean();
            
            echo "✅ Config loaded successfully<br>";
            
            if (!empty($configOutput)) {
                echo "<div class='box warning' style='margin: 10px 0;'>";
                echo "⚠️ <strong>Warning:</strong> Config file produces output before headers:<br>";
                echo "<pre>" . htmlspecialchars($configOutput) . "</pre>";
                echo "</div>";
                $issues[] = "Config.php produces output";
                $fixes[] = "Remove any echo, print, or whitespace before <?php in config.php";
            }
            
            // Check database
            if (isset($conn) && $conn instanceof mysqli) {
                echo "✅ Database connected<br>";
                
                // Test branches table
                $result = $conn->query("SELECT COUNT(*) as count FROM branches");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "✅ Branches table accessible (found {$row['count']} branches)<br>";
                } else {
                    echo "❌ Cannot query branches table: " . $conn->error . "<br>";
                    $issues[] = "Cannot access branches table";
                }
            } else {
                echo "❌ Database not connected<br>";
                $issues[] = "Database connection failed";
            }
            
            // Check session
            if (session_status() === PHP_SESSION_ACTIVE) {
                echo "✅ Session is active<br>";
                
                if (isset($_SESSION['user_id'])) {
                    echo "✅ User is logged in (ID: {$_SESSION['user_id']}, Role: " . ($_SESSION['role'] ?? 'unknown') . ")<br>";
                } else {
                    echo "⚠️ Not logged in<br>";
                    $fixes[] = "Login at: <a href='/index.php'>Login Page</a>";
                }
            } else {
                echo "⚠️ Session not active<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "<br>";
            $issues[] = "Config.php has errors";
        }
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "❌ <strong>config.php NOT FOUND!</strong><br>";
        echo "Expected at: $configPath";
        echo "</div>";
        $issues[] = "config.php missing";
        $fixes[] = "Upload config.php to root directory";
    }
    
    // Test 5: Test API URLs
    echo "<h2>5. Test API Access</h2>";
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    
    $testUrls = [
        'Test API' => "$protocol://$host$basePath/api/test.php",
        'Branches API' => "$protocol://$host$basePath/api/branches.php?action=get_branches",
        'Users API' => "$protocol://$host$basePath/api/users.php",
    ];
    
    echo "<div class='box'>";
    echo "<strong>Click these URLs to test:</strong><br><br>";
    foreach ($testUrls as $name => $url) {
        echo "<a href='$url' target='_blank' class='btn'>🔗 Test $name</a><br>";
        echo "<small style='color: #6b7280;'>$url</small><br><br>";
    }
    echo "</div>";
    
    // Test 6: Server Info
    echo "<h2>6. Server Configuration</h2>";
    echo "<div class='box'>";
    echo "<table>";
    echo "<tr><td>PHP Version:</td><td>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td>Server Software:</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Server Name:</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</td></tr>";
    echo "<tr><td>Document Root:</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Test 7: .htaccess
    echo "<h2>7. URL Rewriting (.htaccess)</h2>";
    $htaccessPath = $rootDir . '/.htaccess';
    
    if (file_exists($htaccessPath)) {
        echo "<div class='box'>";
        echo "✅ .htaccess file exists<br><br>";
        echo "<strong>Content:</strong><br>";
        echo "<pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='box warning'>";
        echo "⚠️ No .htaccess file found<br>";
        echo "This might be OK if your server doesn't use Apache or mod_rewrite";
        echo "</div>";
    }
    
    // Summary
    echo "<h2>📊 Summary</h2>";
    
    if (empty($issues)) {
        echo "<div class='box success'>";
        echo "<h3>✅ No Critical Issues Found!</h3>";
        echo "<p>Your system appears to be configured correctly. If you're still getting 404 errors, try:</p>";
        echo "<ol>";
        echo "<li>Clear your browser cache</li>";
        echo "<li>Test the API URLs above</li>";
        echo "<li>Check if you're logged in</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "<h3>❌ Issues Found:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='box warning'>";
        echo "<h3>🔧 Recommended Fixes:</h3>";
        echo "<ol>";
        foreach ($fixes as $fix) {
            echo "<li>$fix</li>";
        }
        echo "</ol>";
        echo "</div>";
    }
    
    // Quick Actions
    echo "<h2>🚀 Quick Actions</h2>";
    echo "<div class='box'>";
    
    if (!is_dir($apiDir)) {
        echo "<div style='background: #fef3c7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>⚠️ CRITICAL: API Directory Missing!</strong><br><br>";
        echo "<strong>To fix this:</strong><br>";
        echo "1. Using FTP/File Manager, create a folder named 'api' in your root directory<br>";
        echo "2. Upload all API files (branches.php, users.php, test.php, etc.) into this api folder<br>";
        echo "3. Refresh this page<br>";
        echo "</div>";
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo "<div style='background: #dbeafe; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "ℹ️ You are not logged in. <a href='/index.php' class='btn'>Login Now</a>";
        echo "</div>";
    }
    
    echo "<a href='javascript:location.reload()' class='btn'>🔄 Refresh Diagnostic</a>";
    echo "<a href='/branches.php' class='btn'>📍 Go to Branches Page</a>";
    echo "</div>";
    ?>
    
    <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin-top: 30px; text-align: center;">
        <small style="color: #6b7280;">
            Diagnostic generated on <?php echo date('Y-m-d H:i:s'); ?><br>
            After fixing issues, delete this diagnostic.php file for security
        </small>
    </div>
</body>
</html>
<?php
/**
 * Direct API Test - Bypasses HTTP and tests directly
 * test-api.php
 */

require_once 'config.php';
requireOwner();

header('Content-Type: text/html; charset=utf-8');

$settings = getSettings();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct API Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .test-section { margin: 30px 0; padding: 20px; background: #f9fafb; border-radius: 8px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 12px 24px; background: <?php echo $settings['primary_color']; ?>; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: 600; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .json-viewer { background: #1f2937; color: #10b981; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Direct API Test</h1>
        <p>This page tests the branch API by calling it directly (no HTTP request)</p>

        <?php
        // ==================== TEST 1: Direct Database Query ====================
        echo "<div class='test-section'>";
        echo "<h2>📊 Test 1: Direct Database Query</h2>";
        
        $query = "SELECT b.*, 
                  u.name as manager_name,
                  (SELECT COUNT(*) FROM users WHERE branch_id = b.id AND status = 'active') as staff_count,
                  (SELECT COUNT(DISTINCT product_id) FROM branch_inventory WHERE branch_id = b.id) as products_count,
                  (SELECT COALESCE(SUM(stock_quantity), 0) FROM branch_inventory WHERE branch_id = b.id) as total_stock
                  FROM branches b
                  LEFT JOIN users u ON b.manager_id = u.id
                  ORDER BY b.created_at DESC";
        
        $result = $conn->query($query);
        
        if ($result) {
            $branches = [];
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
            
            echo "<div class='status success'>";
            echo "✓ Successfully queried database! Found " . count($branches) . " branches";
            echo "</div>";
            
            if (count($branches) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Staff</th><th>Products</th><th>Stock</th><th>Status</th></tr>";
                foreach ($branches as $b) {
                    echo "<tr>";
                    echo "<td>{$b['id']}</td>";
                    echo "<td>{$b['name']}</td>";
                    echo "<td><strong>{$b['code']}</strong></td>";
                    echo "<td>{$b['staff_count']}</td>";
                    echo "<td>{$b['products_count']}</td>";
                    echo "<td>{$b['total_stock']}</td>";
                    echo "<td>{$b['status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<div class='json-viewer'>";
                echo json_encode(['success' => true, 'data' => ['branches' => $branches]], JSON_PRETTY_PRINT);
                echo "</div>";
            } else {
                echo "<div class='status error'>✗ No branches found in database!</div>";
                echo "<div class='status info'>";
                echo "Run this SQL to create MAIN branch:<br><br>";
                echo "<code>INSERT INTO branches (name, code, address, city, status) VALUES ('Main Branch', 'MAIN', 'Head Office', 'Nairobi', 'active');</code>";
                echo "</div>";
            }
        } else {
            echo "<div class='status error'>✗ Query failed: " . $conn->error . "</div>";
        }
        
        echo "</div>";
        
        // ==================== TEST 2: JavaScript Fetch Test ====================
        echo "<div class='test-section'>";
        echo "<h2>🌐 Test 2: JavaScript Fetch (AJAX)</h2>";
        echo "<div id='fetchTest' class='status info'>Click button to test...</div>";
        echo "<button onclick='testFetch()' class='btn'>Test Fetch API</button>";
        echo "<div id='fetchResult' style='margin-top: 20px;'></div>";
        echo "</div>";
        
        // ==================== TEST 3: Check API File ====================
        echo "<div class='test-section'>";
        echo "<h2>📁 Test 3: API File Status</h2>";
        
        $apiFile = __DIR__ . '/api/branches.php';
        if (file_exists($apiFile)) {
            echo "<div class='status success'>✓ API file exists at: $apiFile</div>";
            echo "<div class='status info'>File size: " . filesize($apiFile) . " bytes</div>";
            echo "<div class='status info'>Last modified: " . date('Y-m-d H:i:s', filemtime($apiFile)) . "</div>";
            
            // Check if file is readable
            if (is_readable($apiFile)) {
                echo "<div class='status success'>✓ File is readable</div>";
            } else {
                echo "<div class='status error'>✗ File is not readable! Check permissions.</div>";
            }
        } else {
            echo "<div class='status error'>✗ API file NOT found at: $apiFile</div>";
            echo "<div class='status info'>Make sure api/branches.php exists in your root directory</div>";
        }
        
        echo "</div>";
        
        // ==================== TEST 4: Session & Auth ====================
        echo "<div class='test-section'>";
        echo "<h2>🔐 Test 4: Authentication Status</h2>";
        
        if (isset($_SESSION['user_id'])) {
            echo "<div class='status success'>✓ User is authenticated</div>";
            echo "<div class='status info'>";
            echo "User ID: {$_SESSION['user_id']}<br>";
            echo "Role: {$_SESSION['role']}<br>";
            echo "Name: {$_SESSION['name']}";
            echo "</div>";
        } else {
            echo "<div class='status error'>✗ User not authenticated!</div>";
        }
        
        echo "</div>";
        
        // ==================== TEST 5: Server Info ====================
        echo "<div class='test-section'>";
        echo "<h2>⚙️ Test 5: Server Information</h2>";
        
        echo "<div class='status info'>";
        echo "PHP Version: " . PHP_VERSION . "<br>";
        echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
        echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
        echo "Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
        echo "Base URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . "<br>";
        echo "</div>";
        
        echo "</div>";
        ?>

        <hr style="margin: 40px 0;">
        
        <div style="text-align: center;">
            <a href="branches.php" class="btn">Go to Branches Page</a>
            <a href="check-branches.php" class="btn">System Check</a>
            <a href="javascript:location.reload()" class="btn">Refresh Tests</a>
        </div>
    </div>

    <script>
    async function testFetch() {
        const resultDiv = document.getElementById('fetchResult');
        const statusDiv = document.getElementById('fetchTest');
        
        statusDiv.className = 'status info';
        statusDiv.innerHTML = '⏳ Testing fetch...';
        resultDiv.innerHTML = '';
        
        try {
            // Test with query parameter
            const response = await fetch('/api/branches.php?action=get_branches');
            
            statusDiv.innerHTML = `✓ Fetch successful! Status: ${response.status} ${response.statusText}`;
            
            const text = await response.text();
            
            resultDiv.innerHTML = '<h3>Response Headers:</h3>';
            resultDiv.innerHTML += '<pre>';
            response.headers.forEach((value, key) => {
                resultDiv.innerHTML += `${key}: ${value}\n`;
            });
            resultDiv.innerHTML += '</pre>';
            
            resultDiv.innerHTML += '<h3>Response Body:</h3>';
            resultDiv.innerHTML += '<div class="json-viewer">' + text + '</div>';
            
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    statusDiv.className = 'status success';
                    statusDiv.innerHTML = `✓ API Working! Found ${data.data.branches.length} branches`;
                } else {
                    statusDiv.className = 'status error';
                    statusDiv.innerHTML = `✗ API Error: ${data.message}`;
                }
            } catch (e) {
                statusDiv.className = 'status error';
                statusDiv.innerHTML = `✗ Invalid JSON response`;
                console.error('JSON parse error:', e);
            }
            
        } catch (error) {
            statusDiv.className = 'status error';
            statusDiv.innerHTML = `✗ Fetch failed: ${error.message}`;
            resultDiv.innerHTML = '<div class="status error">Error details: ' + error.toString() + '</div>';
            console.error('Fetch error:', error);
        }
    }
    
    // Auto-test on page load
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(testFetch, 500);
    });
    </script>
</body>
</html>
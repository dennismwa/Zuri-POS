<?php
require_once 'config.php';
requireOwner();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Branch System Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
        pre { background: #f9fafb; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Branch System Status Check</h1>
        
        <?php
        $errors = [];
        $warnings = [];
        $success = [];
        
        // Check tables exist
        echo "<h2>📋 Database Tables</h2>";
        $tables = ['branches', 'branch_inventory', 'stock_transfers'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<div class='status success'>✓ Table '$table' exists</div>";
            } else {
                echo "<div class='status error'>✗ Table '$table' is MISSING!</div>";
                $errors[] = "Table $table missing";
            }
        }
        
        // Check columns
        echo "<h2>📊 Table Columns</h2>";
        $columns = [
            'users' => ['branch_id'],
            'sales' => ['branch_id'],
            'expenses' => ['branch_id']
        ];
        
        foreach ($columns as $table => $cols) {
            foreach ($cols as $col) {
                $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
                if ($result && $result->num_rows > 0) {
                    echo "<div class='status success'>✓ Column '$table.$col' exists</div>";
                } else {
                    echo "<div class='status warning'>⚠ Column '$table.$col' is missing (will use defaults)</div>";
                }
            }
        }
        
        // Check MAIN branch
        echo "<h2>🏪 Main Branch</h2>";
        $mainBranch = $conn->query("SELECT * FROM branches WHERE code = 'MAIN' LIMIT 1");
        if ($mainBranch && $mainBranch->num_rows > 0) {
            $main = $mainBranch->fetch_assoc();
            echo "<div class='status success'>✓ MAIN branch exists (ID: {$main['id']})</div>";
            echo "<pre>";
            print_r($main);
            echo "</pre>";
        } else {
            echo "<div class='status error'>✗ MAIN branch does NOT exist!</div>";
            echo "<div class='status info'>Run this SQL to create it:</div>";
            echo "<pre>INSERT INTO branches (name, code, address, city, status) 
VALUES ('Main Branch', 'MAIN', 'Head Office', 'Nairobi', 'active');</pre>";
        }
        
        // All branches
        echo "<h2>🏢 All Branches</h2>";
        $allBranches = $conn->query("SELECT * FROM branches ORDER BY id");
        if ($allBranches && $allBranches->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>City</th><th>Status</th></tr>";
            while ($b = $allBranches->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$b['id']}</td>";
                echo "<td>{$b['name']}</td>";
                echo "<td><strong>{$b['code']}</strong></td>";
                echo "<td>{$b['city']}</td>";
                echo "<td>{$b['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='status warning'>⚠ No branches found!</div>";
        }
        
        // API Test
        echo "<h2>🔌 API Test</h2>";
        echo "<div class='status info'>Testing API endpoint...</div>";
        
        $apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                  . "://" . $_SERVER['HTTP_HOST'] 
                  . dirname($_SERVER['PHP_SELF']) . "/api/branches.php?action=get_branches";
        
        echo "<p>API URL: <code>$apiUrl</code></p>";
        
        // Try to call the API
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
            ]
        ]);
        
        $apiResponse = @file_get_contents($apiUrl, false, $context);
        if ($apiResponse) {
            $apiData = json_decode($apiResponse, true);
            if ($apiData && isset($apiData['success'])) {
                if ($apiData['success']) {
                    echo "<div class='status success'>✓ API is working! Found " . count($apiData['data']['branches']) . " branches</div>";
                    echo "<pre>" . json_encode($apiData, JSON_PRETTY_PRINT) . "</pre>";
                } else {
                    echo "<div class='status error'>✗ API returned error: " . $apiData['message'] . "</div>";
                }
            } else {
                echo "<div class='status error'>✗ API returned invalid JSON</div>";
                echo "<pre>$apiResponse</pre>";
            }
        } else {
            echo "<div class='status error'>✗ Could not connect to API</div>";
        }
        
        // Users without branch
        echo "<h2>👥 User Assignment</h2>";
        $unbranched = $conn->query("SELECT COUNT(*) as count FROM users WHERE branch_id IS NULL AND status = 'active'");
        $count = $unbranched->fetch_assoc()['count'];
        if ($count > 0) {
            echo "<div class='status warning'>⚠ $count users are not assigned to any branch</div>";
            if ($main) {
                echo "<div class='status info'>Run this SQL to assign them to MAIN:</div>";
                echo "<pre>UPDATE users SET branch_id = {$main['id']} WHERE branch_id IS NULL AND status = 'active';</pre>";
            }
        } else {
            echo "<div class='status success'>✓ All active users are assigned to branches</div>";
        }
        
        // Summary
        echo "<h2>📈 Summary</h2>";
        if (count($errors) === 0) {
            echo "<div class='status success'><strong>✓ System is ready!</strong> No critical errors found.</div>";
        } else {
            echo "<div class='status error'><strong>✗ System has " . count($errors) . " error(s)</strong></div>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
        
        echo "<hr>";
        echo "<a href='branches.php' class='btn'>Go to Branches Page</a>";
        echo "<a href='javascript:location.reload()' class='btn'>Refresh Check</a>";
        ?>
    </div>
</body>
</html>
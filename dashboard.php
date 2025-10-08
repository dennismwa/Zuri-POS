<?php
require_once 'config.php';
requireOwner();

$page_title = 'Dashboard';
$settings = getSettings();

// Get today's stats
$today = date('Y-m-d');
$today_sales_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$today'");
$today_sales = $today_sales_query ? $today_sales_query->fetch_assoc() : ['count' => 0, 'total' => 0];

// Get yesterday's stats for comparison
$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterday_sales_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$yesterday'");
$yesterday_sales = $yesterday_sales_query ? $yesterday_sales_query->fetch_assoc() : ['count' => 0, 'total' => 0];

// Calculate percentage changes
$revenue_change = $yesterday_sales['total'] > 0 ? (($today_sales['total'] - $yesterday_sales['total']) / $yesterday_sales['total']) * 100 : 0;
$sales_change = $yesterday_sales['count'] > 0 ? (($today_sales['count'] - $yesterday_sales['count']) / $yesterday_sales['count']) * 100 : 0;

// Get this month's stats
$this_month = date('Y-m');
$month_sales_query = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = '$this_month'");
$month_sales = $month_sales_query ? $month_sales_query->fetch_assoc() : ['count' => 0, 'total' => 0];

// Get low stock count
$low_stock_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
$low_stock = $low_stock_query ? $low_stock_query->fetch_assoc() : ['count' => 0];

// Get out of stock count
$out_stock_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0 AND status = 'active'");
$out_stock = $out_stock_query ? $out_stock_query->fetch_assoc() : ['count' => 0];

// Get total products
$total_products_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$total_products = $total_products_query ? $total_products_query->fetch_assoc() : ['count' => 0];

// Get payment method breakdown for today
$payment_methods_query = $conn->query("SELECT 
    payment_method,
    COUNT(*) as count,
    SUM(total_amount) as total
    FROM sales 
    WHERE DATE(sale_date) = '$today'
    GROUP BY payment_method");

$payment_methods = ['cash' => 0, 'mpesa' => 0, 'card' => 0];
if ($payment_methods_query) {
    while ($row = $payment_methods_query->fetch_assoc()) {
        $method = $row['payment_method'] === 'mpesa_till' ? 'mpesa' : $row['payment_method'];
        $payment_methods[$method] = $row['total'];
    }
}

// Get recent sales
$recent_sales = $conn->query("SELECT s.*, u.name as seller_name FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.sale_date DESC LIMIT 10");

// Get top products
$top_products = $conn->query("SELECT p.name, SUM(si.quantity) as total_sold, SUM(si.subtotal) as total_revenue FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = '$this_month' GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");

// Get daily sales for chart (last 7 days)
$daily_sales = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$date'");
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    $daily_sales[] = [
        'date' => date('D', strtotime($date)),
        'full_date' => date('M d', strtotime($date)),
        'total' => $row['total']
    ];
}

// Get hourly sales for today
$hourly_sales = [];
for ($hour = 0; $hour < 24; $hour++) {
    $hour_str = str_pad($hour, 2, '0', STR_PAD_LEFT);
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = '$today' AND HOUR(sale_date) = $hour");
    $row = $result ? $result->fetch_assoc() : ['total' => 0];
    $hourly_sales[] = [
        'hour' => $hour . ':00',
        'total' => $row['total']
    ];
}

// Get top sellers this month
$top_sellers = $conn->query("SELECT u.name, COUNT(s.id) as sales_count, SUM(s.total_amount) as total_sales FROM users u LEFT JOIN sales s ON u.id = s.user_id AND DATE_FORMAT(s.sale_date, '%Y-%m') = '$this_month' WHERE u.role = 'seller' GROUP BY u.id ORDER BY total_sales DESC LIMIT 5");

include 'header.php';
?>

<style>
/* Enhanced Card Styles */
.stat-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--card-color) 0%, var(--card-color-light) 100%);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    position: relative;
    background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color-light) 100%);
}

.stat-icon::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color-light) 100%);
    filter: blur(8px);
    opacity: 0.5;
    z-index: -1;
}

.trend-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.quick-action-card {
    background: white;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    cursor: pointer;
    border: 2px solid transparent;
}

.quick-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px rgba(0,0,0,0.12);
    border-color: <?php echo $settings['primary_color']; ?>;
}

.chart-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.activity-item {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    border-left: 4px solid;
    transition: all 0.2s;
}

.activity-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<!-- Welcome Section -->
<div class="stat-card mb-6" style="--card-color: <?php echo $settings['primary_color']; ?>; --card-color-light: <?php echo $settings['primary_color']; ?>dd;">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>
            </h1>
            <p class="text-sm md:text-base text-gray-600">
                <?php echo date('l, F j, Y'); ?> • Here's what's happening today
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/pos.php" class="px-4 md:px-6 py-2 md:py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg text-sm md:text-base"
               style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-cash-register mr-2"></i>New Sale
            </a>
        </div>
    </div>
</div>

<!-- Key Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <!-- Today's Revenue -->
    <div class="stat-card" style="--card-color: #10b981; --card-color-light: #34d399;" onclick="location.href='/sales.php'">
        <div class="flex items-start justify-between mb-3">
            <div class="stat-icon text-white">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <?php if ($revenue_change != 0): ?>
            <span class="trend-badge <?php echo $revenue_change > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <i class="fas fa-arrow-<?php echo $revenue_change > 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($revenue_change, 1)); ?>%
            </span>
            <?php endif; ?>
        </div>
        <p class="text-xs text-gray-600 mb-1 font-medium">Today's Revenue</p>
        <h3 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900 mb-1"><?php echo formatCurrency($today_sales['total']); ?></h3>
        <p class="text-xs text-gray-500"><?php echo $today_sales['count']; ?> transactions</p>
    </div>

    <!-- This Month -->
    <div class="stat-card" style="--card-color: #3b82f6; --card-color-light: #60a5fa;" onclick="location.href='/reports.php'">
        <div class="flex items-start justify-between mb-3">
            <div class="stat-icon text-white">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <p class="text-xs text-gray-600 mb-1 font-medium">This Month</p>
        <h3 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900 mb-1"><?php echo formatCurrency($month_sales['total']); ?></h3>
        <p class="text-xs text-gray-500"><?php echo $month_sales['count']; ?> transactions</p>
    </div>

    <!-- Total Products -->
    <div class="stat-card" style="--card-color: #8b5cf6; --card-color-light: #a78bfa;" onclick="location.href='/products.php'">
        <div class="flex items-start justify-between mb-3">
            <div class="stat-icon text-white">
                <i class="fas fa-wine-bottle"></i>
            </div>
        </div>
        <p class="text-xs text-gray-600 mb-1 font-medium">Active Products</p>
        <h3 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900 mb-1"><?php echo $total_products['count']; ?></h3>
        <p class="text-xs text-gray-500">In inventory</p>
    </div>

    <!-- Low Stock Alert -->
    <div class="stat-card" style="--card-color: #ef4444; --card-color-light: #f87171;" onclick="location.href='/products.php?filter=low_stock'">
        <div class="flex items-start justify-between mb-3">
            <div class="stat-icon text-white">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <?php if ($out_stock['count'] > 0): ?>
            <span class="trend-badge bg-red-100 text-red-800">
                <?php echo $out_stock['count']; ?> out
            </span>
            <?php endif; ?>
        </div>
        <p class="text-xs text-gray-600 mb-1 font-medium">Low Stock Items</p>
        <h3 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900 mb-1"><?php echo $low_stock['count']; ?></h3>
        <p class="text-xs text-gray-500">Need attention</p>
    </div>
</div>

<!-- Payment Methods Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="stat-card" style="--card-color: #10b981; --card-color-light: #34d399;">
        <div class="flex items-center gap-4">
            <div class="stat-icon text-white text-xl">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-600 font-medium mb-1">Cash Payments</p>
                <h4 class="text-xl md:text-2xl font-bold text-gray-900"><?php echo formatCurrency($payment_methods['cash']); ?></h4>
            </div>
        </div>
    </div>

    <div class="stat-card" style="--card-color: #f59e0b; --card-color-light: #fbbf24;">
        <div class="flex items-center gap-4">
            <div class="stat-icon text-white text-xl">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-600 font-medium mb-1">M-Pesa Payments</p>
                <h4 class="text-xl md:text-2xl font-bold text-gray-900"><?php echo formatCurrency($payment_methods['mpesa']); ?></h4>
            </div>
        </div>
    </div>

    <div class="stat-card" style="--card-color: #6366f1; --card-color-light: #818cf8;">
        <div class="flex items-center gap-4">
            <div class="stat-icon text-white text-xl">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-600 font-medium mb-1">Card Payments</p>
                <h4 class="text-xl md:text-2xl font-bold text-gray-900"><?php echo formatCurrency($payment_methods['card']); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="chart-card mb-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <a href="/pos.php" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-green-100 flex items-center justify-center">
                <i class="fas fa-cash-register text-green-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">New Sale</p>
        </a>

        <a href="/products.php?action=add" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">Add Product</p>
        </a>

        <a href="/expenses.php" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-red-100 flex items-center justify-center">
                <i class="fas fa-receipt text-red-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">Expenses</p>
        </a>

        <a href="/reports.php" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-purple-100 flex items-center justify-center">
                <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">Reports</p>
        </a>

        <a href="/branches.php" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-indigo-100 flex items-center justify-center">
                <i class="fas fa-store text-indigo-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">Branches</p>
        </a>

        <a href="/settings.php" class="quick-action-card text-center">
            <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-gray-100 flex items-center justify-center">
                <i class="fas fa-cog text-gray-600 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">Settings</p>
        </a>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Sales Trend Chart -->
    <div class="chart-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="fas fa-chart-line text-blue-500 mr-2"></i>7-Day Sales Trend
            </h3>
            <select id="chartPeriod" class="text-sm border-2 border-gray-200 rounded-lg px-3 py-1 focus:outline-none">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
            </select>
        </div>
        <div style="height: 280px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="chart-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Products This Month
        </h3>
        <div class="space-y-3">
            <?php 
            $rank = 1;
            $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
            if ($top_products && $top_products->num_rows > 0) {
                while ($product = $top_products->fetch_assoc()): 
            ?>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-white text-lg"
                     style="background: <?php echo $colors[$rank - 1]; ?>">
                    <?php echo $rank++; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($product['name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo $product['total_sold']; ?> units sold</p>
                </div>
                <p class="text-sm font-bold" style="color: <?php echo $settings['primary_color']; ?>">
                    <?php echo formatCurrency($product['total_revenue']); ?>
                </p>
            </div>
            <?php 
                endwhile;
            } else {
            ?>
            <div class="text-center py-12">
                <i class="fas fa-chart-bar text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-400">No sales data yet</p>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Sales -->
    <div class="lg:col-span-2 chart-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="fas fa-history text-green-500 mr-2"></i>Recent Sales
            </h3>
            <a href="/sales.php" class="text-sm font-medium hover:underline" style="color: <?php echo $settings['primary_color']; ?>">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <!-- Desktop Table -->
        <div class="overflow-x-auto hidden md:block">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Sale #</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Date & Time</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Seller</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Payment</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    if ($recent_sales && $recent_sales->num_rows > 0) {
                        while ($sale = $recent_sales->fetch_assoc()): 
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['sale_number']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-900"><?php echo date('M d', strtotime($sale['sale_date'])); ?></span>
                            <span class="text-xs text-gray-500 block"><?php echo date('h:i A', strtotime($sale['sale_date'])); ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <?php echo htmlspecialchars($sale['seller_name']); ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-bold rounded-full <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : ($sale['payment_method'] === 'card' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'); ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $sale['payment_method'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                            <?php echo formatCurrency($sale['total_amount']); ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                    ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">No sales yet</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="space-y-3 md:hidden">
            <?php 
            if ($recent_sales) {
                $recent_sales->data_seek(0);
                while ($sale = $recent_sales->fetch_assoc()): 
            ?>
            <div class="activity-item border-blue-500">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-sm text-gray-900"><?php echo htmlspecialchars($sale['sale_number']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('M d, h:i A', strtotime($sale['sale_date'])); ?></p>
                    </div>
                    <span class="text-sm font-bold" style="color: <?php echo $settings['primary_color']; ?>">
                        <?php echo formatCurrency($sale['total_amount']); ?>
                    </span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700"><?php echo htmlspecialchars($sale['seller_name']); ?></span>
                    <span class="px-2 py-1 rounded-full <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo strtoupper($sale['payment_method']); ?>
                    </span>
                </div>
            </div>
            <?php 
                endwhile;
            }
            ?>
        </div>
    </div>

    <!-- Top Sellers -->
    <div class="chart-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-users text-purple-500 mr-2"></i>Top Sellers
        </h3>
        <div class="space-y-3">
            <?php 
            if ($top_sellers && $top_sellers->num_rows > 0) {
                $rank = 1;
                while ($seller = $top_sellers->fetch_assoc()): 
            ?>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                     style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <?php echo strtoupper(substr($seller['name'], 0, 2)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($seller['name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo $seller['sales_count']; ?> sales</p>
                </div>
                <p class="text-sm font-bold text-green-600">
                    <?php echo formatCurrency($seller['total_sales']); ?>
                </p>
            </div>
            <?php 
                endwhile;
            } else {
            ?>
            <div class="text-center py-8">
                <i class="fas fa-user-tie text-4xl text-gray-300 mb-2"></i>
                <p class="text-gray-400 text-sm">No seller data yet</p>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const dailySalesData = <?php echo json_encode($daily_sales); ?>;
const primaryColor = '<?php echo $settings['primary_color']; ?>';
const currency = '<?php echo $settings['currency']; ?>';

// Sales Chart
const ctx = document.getElementById('salesChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailySalesData.map(d => d.date),
            datasets: [{
                label: 'Sales',
                data: dailySalesData.map(d => d.total),
                borderColor: primaryColor,
                backgroundColor: primaryColor + '20',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: primaryColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(context) {
                            return dailySalesData[context[0].dataIndex].full_date;
                        },
                        label: function(context) {
                            return currency + ' ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: v => currency + ' ' + v.toLocaleString()
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Auto-refresh every 5 minutes
setInterval(() => {
    location.reload();
}, 300000);
</script>

<?php include 'footer.php'; ?>

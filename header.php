<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}
$settings = getSettings();
$page_title = $page_title ?? 'Dashboard';
$currentPage = basename($_SERVER['PHP_SELF']);
$isOwner = $_SESSION['role'] === 'owner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . $settings['company_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $settings['primary_color']; ?>;
            --primary-dark: <?php echo $settings['primary_color']; ?>dd;
            --primary-light: <?php echo $settings['primary_color']; ?>22;
        }
        
        .bg-primary { background-color: var(--primary-color); }
        .text-primary { color: var(--primary-color); }
        .border-primary { border-color: var(--primary-color); }
        .hover-primary:hover { background-color: var(--primary-color); }
        
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        
        /* Sidebar Animations */
        .sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 40;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        
        .sidebar-link {
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar-link:hover::before,
        .sidebar-link.active::before {
            transform: scaleY(1);
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 1.25rem;
        }
        
        .sidebar-link.active {
            background: rgba(234, 88, 12, 0.2);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Top Bar Gradient */
        .top-bar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* User Avatar */
        .user-avatar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 6px rgba(234, 88, 12, 0.3);
        }
        
        /* Stats Badge */
        .stats-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        /* Notification Dot */
        .notification-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Mobile Sidebar */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            }
            .mobile-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 50;
                background: linear-gradient(180deg, transparent 0%, rgba(255,255,255,0.98) 10%, white 100%);
                backdrop-filter: blur(10px);
            }
        }
        
        /* Smooth Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden transition-opacity"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 h-screen w-64">
        <div class="flex flex-col h-full">
            <!-- Logo Section - SMALLER & MORE COMPACT -->
            <div class="p-3 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-wine-bottle text-white text-base"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-bold text-sm text-white leading-tight truncate"><?php echo htmlspecialchars($settings['company_name']); ?></h2>
                            <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                        </div>
                    </div>
                    <button id="closeSidebar" class="md:hidden text-gray-400 hover:text-white flex-shrink-0 ml-2">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-3">
                <ul class="space-y-0.5">
                    <?php if ($isOwner): ?>
                    <li>
                        <a href="/dashboard.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line w-4"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="/pos.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'pos.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cash-register w-4"></i>
                            <span>Point of Sale</span>
                        </a>
                    </li>
                    
                    <!-- INVENTORY SECTION -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Inventory</p>
                    </li>
                    
                    <li>
                        <a href="/products.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-wine-bottle w-4"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/categories.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tags w-4"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/inventory.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
                            <i class="fas fa-boxes w-4"></i>
                            <span>Stock Management</span>
                        </a>
                    </li>
                    
                    <?php if ($isOwner): ?>
                    <li>
                        <a href="/stock-movements.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'stock-movements.php' ? 'active' : ''; ?>">
                            <i class="fas fa-history w-4"></i>
                            <span>Stock Audit Trail</span>
                        </a>
                    </li>
                    
                    <!-- MULTI-STORE SECTION -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Multi-Store</p>
                    </li>
                    
                    <li>
                        <a href="/branches.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'branches.php' ? 'active' : ''; ?>">
                            <i class="fas fa-store w-4"></i>
                            <span>Branch Management</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/branch-comparison.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'branch-comparison.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line w-4"></i>
                            <span>Branch Comparison</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/stock-transfers.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'stock-transfers.php' ? 'active' : ''; ?>">
                            <i class="fas fa-exchange-alt w-4"></i>
                            <span>Stock Transfers</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- TRANSACTIONS SECTION -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Transactions</p>
                    </li>
                    
                    <li>
                        <a href="/sales.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'sales.php' ? 'active' : ''; ?>">
                            <i class="fas fa-receipt w-4"></i>
                            <span>Sales History</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/cash-register.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'cash-register.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cash-register w-4"></i>
                            <span>Cash Register</span>
                        </a>
                    </li>
                    
                    <?php if ($isOwner): ?>
                    <li>
                        <a href="/register-history.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'register-history.php' ? 'active' : ''; ?>">
                            <i class="fas fa-history w-4"></i>
                            <span>Register History</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="/expenses.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'expenses.php' ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill-wave w-4"></i>
                            <span>Expenses</span>
                        </a>
                    </li>
                    
                    <?php if ($isOwner): ?>
                    <!-- ANALYTICS SECTION -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Analytics</p>
                    </li>
                    
                    <li>
                        <a href="/reports.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar w-4"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/breakeven-analysis.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'breakeven-analysis.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line w-4"></i>
                            <span>Break-Even Analysis</span>
                        </a>
                    </li>
                    
                    <!-- SETTINGS SECTION -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Settings</p>
                    </li>
                    
                    <li>
                        <a href="/users.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users w-4"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/permissions-management.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'permissions-management.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield w-4"></i>
                            <span>Permissions</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/audit-logs.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'audit-logs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list w-4"></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    
                    <!-- WHATSAPP MENU ITEM - NOW VISIBLE -->
                    <li>
                        <a href="/whatsapp-integration.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'whatsapp-integration.php' ? 'active' : ''; ?>">
                            <i class="fab fa-whatsapp w-4"></i>
                            <span>WhatsApp Integration</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/settings.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog w-4"></i>
                            <span>System Settings</span>
                        </a>
                    </li>
                    <?php elseif (hasPermission('can_view_audit_logs')): ?>
                    <!-- For non-owners with audit permission -->
                    <li class="pt-3">
                        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Admin</p>
                    </li>
                    <li>
                        <a href="/audit-logs.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-gray-300 transition text-sm <?php echo $currentPage === 'audit-logs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list w-4"></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- LOGOUT ONLY - User profile removed -->
            <div class="p-3 border-t border-gray-700">
                <a href="/logout.php" class="sidebar-link flex items-center gap-2 px-3 py-2 rounded-lg text-red-400 hover:bg-red-500 hover:bg-opacity-10 transition text-sm">
                    <i class="fas fa-sign-out-alt w-4"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="md:ml-64 min-h-screen flex flex-col pb-20 md:pb-0">
        <!-- Top Bar - SMALLER & MORE RESPONSIVE -->
        <header class="top-bar sticky top-0 z-20 no-print">
            <div class="px-3 py-2 flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <button id="menuBtn" class="md:hidden text-gray-600 hover:text-primary transition flex-shrink-0">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-base md:text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($page_title); ?></h1>
                        <p class="text-xs text-gray-500 hidden sm:block truncate"><?php echo date('M d, Y'); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2 flex-shrink-0">
                    <!-- Quick Stats -->
                    <?php if ($isOwner): ?>
                    <div class="hidden lg:flex items-center gap-1 stats-badge px-2 py-1 rounded-lg text-white text-xs font-semibold">
                        <i class="fas fa-chart-line text-xs"></i>
                        <span><?php 
                            $today_quick = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
                            echo $today_quick ? formatCurrency($today_quick->fetch_assoc()['total']) : 'KSh 0.00';
                        ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Time -->
                    <div class="hidden sm:flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-lg">
                        <i class="far fa-clock text-gray-600 text-xs"></i>
                        <span id="currentTime" class="text-xs font-medium text-gray-700"></span>
                    </div>
                    
                    <!-- User Avatar Only - No Name -->
                    <div class="user-avatar w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold cursor-pointer" title="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-4 md:p-6">

<script>
// Sidebar Toggle
const menuBtn = document.getElementById('menuBtn');
const closeSidebar = document.getElementById('closeSidebar');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebarOverlay');

menuBtn?.addEventListener('click', () => {
    sidebar.classList.add('open');
    overlay.classList.remove('hidden');
});

closeSidebar?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.add('hidden');
});

overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.add('hidden');
});

// Update Time
function updateTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
    const timeEl = document.getElementById('currentTime');
    if (timeEl) timeEl.textContent = timeStr;
}
updateTime();
setInterval(updateTime, 1000);

// Close sidebar on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        overlay.classList.add('hidden');
    }
});
</script>

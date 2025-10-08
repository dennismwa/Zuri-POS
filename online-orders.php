<?php
require_once 'config.php';
requireAuth();
$page_title = 'Online Orders';
$settings = getSettings();
include 'header.php';
?>

<style>
.order-card { background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s; }
.order-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
</style>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="order-card mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-shopping-bag text-blue-600 mr-3"></i>Online Orders
                </h1>
                <p class="text-gray-600">Manage customer orders from website</p>
            </div>
            <a href="/order.php" target="_blank" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700">
                <i class="fas fa-external-link-alt mr-2"></i>View Order Page
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="order-card mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select id="statusFilter" onchange="loadOrders()" class="px-4 py-2 border-2 rounded-lg">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="processing">Processing</option>
                <option value="ready">Ready</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input type="date" id="dateFrom" value="<?php echo date('Y-m-d'); ?>" onchange="loadOrders()" class="px-4 py-2 border-2 rounded-lg">
            <input type="date" id="dateTo" value="<?php echo date('Y-m-d'); ?>" onchange="loadOrders()" class="px-4 py-2 border-2 rounded-lg">
            <button onclick="loadOrders()" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Orders List -->
    <div id="ordersList" class="space-y-4">
        <div class="flex justify-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 bg-blue-600 text-white rounded-t-2xl sticky top-0">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold" id="modalOrderNumber">Order Details</h3>
                <button onclick="closeOrderModal()" class="text-white/80 hover:text-white"><i class="fas fa-times text-2xl"></i></button>
            </div>
        </div>
        <div id="orderDetails" class="p-6"></div>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
const currency = '<?php echo $settings['currency']; ?>';
let orders = [];

document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
    setInterval(loadOrders, 30000); // Auto-refresh every 30s
});

async function loadOrders() {
    const status = document.getElementById('statusFilter').value;
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;
    
    const params = new URLSearchParams({ action: 'get_orders', status, from, to });
    
    try {
        const res = await fetch(`/api/online-orders.php?${params}`);
        const data = await res.json();
        if (data.success) {
            orders = data.data.orders;
            renderOrders();
        }
    } catch (error) {
        console.error(error);
    }
}

function renderOrders() {
    const container = document.getElementById('ordersList');
    
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="order-card text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 font-semibold">No orders found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = orders.map(order => {
        const statusConfig = {
            pending: { class: 'bg-yellow-100 text-yellow-800', icon: 'clock' },
            confirmed: { class: 'bg-blue-100 text-blue-800', icon: 'check' },
            processing: { class: 'bg-purple-100 text-purple-800', icon: 'cog' },
            ready: { class: 'bg-green-100 text-green-800', icon: 'check-circle' },
            completed: { class: 'bg-gray-100 text-gray-800', icon: 'check-double' },
            cancelled: { class: 'bg-red-100 text-red-800', icon: 'times' }
        }[order.status] || { class: 'bg-gray-100 text-gray-800', icon: 'question' };
        
        return `
            <div class="order-card">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="font-bold text-lg">${order.order_number}</h3>
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${statusConfig.class}">
                                <i class="fas fa-${statusConfig.icon} mr-1"></i>${order.status.toUpperCase()}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div><i class="fas fa-user mr-2 text-gray-400"></i>${order.customer_name}</div>
                            <div><i class="fas fa-phone mr-2 text-gray-400"></i>${order.customer_phone}</div>
                            <div><i class="fas fa-clock mr-2 text-gray-400"></i>${new Date(order.order_date).toLocaleString()}</div>
                        </div>
                        ${order.delivery_type === 'delivery' ? `
                        <div class="mt-2 text-sm text-gray-600">
                            <i class="fas fa-map-marker-alt mr-2"></i>${order.delivery_address}
                        </div>
                        ` : '<div class="mt-2 text-sm text-blue-600"><i class="fas fa-store mr-2"></i>Customer Pickup</div>'}
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-600">${currency} ${parseFloat(order.total_amount).toFixed(0)}</p>
                        <p class="text-xs text-gray-500">${order.item_count} items</p>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="viewOrder(${order.id})" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                    ${order.status === 'pending' ? `
                    <button onclick="updateStatus(${order.id}, 'confirmed')" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">
                        <i class="fas fa-check mr-1"></i>Confirm
                    </button>
                    ` : ''}
                    ${order.status === 'confirmed' ? `
                    <button onclick="updateStatus(${order.id}, 'processing')" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold">
                        <i class="fas fa-cog mr-1"></i>Process
                    </button>
                    ` : ''}
                    ${order.status === 'processing' ? `
                    <button onclick="updateStatus(${order.id}, 'ready')" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">
                        <i class="fas fa-check-circle mr-1"></i>Ready
                    </button>
                    ` : ''}
                    ${['pending', 'confirmed'].includes(order.status) ? `
                    <button onclick="updateStatus(${order.id}, 'cancelled')" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-semibold">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

async function viewOrder(orderId) {
    // Simplified - show basic order info
    const order = orders.find(o => o.id == orderId);
    document.getElementById('modalOrderNumber').textContent = order.order_number;
    document.getElementById('orderDetails').innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                <div><strong>Customer:</strong> ${order.customer_name}</div>
                <div><strong>Phone:</strong> ${order.customer_phone}</div>
                <div><strong>Total:</strong> ${currency} ${order.total_amount}</div>
                <div><strong>Items:</strong> ${order.item_count}</div>
            </div>
            <div class="grid grid-cols-5 gap-2">
                ${['pending', 'confirmed', 'processing', 'ready', 'completed'].map(s => `
                <button onclick="updateStatus(${order.id}, '${s}')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-semibold">
                    ${s}
                </button>
                `).join('')}
            </div>
        </div>
    `;
    document.getElementById('orderModal').classList.remove('hidden');
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
}

async function updateStatus(orderId, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', status);
    
    try {
        const res = await fetch('/api/online-orders.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast('Status updated!', 'success');
            loadOrders();
            closeOrderModal();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error updating status', 'error');
    }
}

function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-xl text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} z-[200]`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<?php include 'footer.php'; ?>
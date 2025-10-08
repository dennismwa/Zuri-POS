<?php
require_once 'config.php';
requireOwner();

$page_title = 'Register History';
$settings = getSettings();

include 'header.php';
?>

<style>
.history-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.history-card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    .history-card {
        padding: 1rem;
    }
}
</style>

<!-- Header -->
<div class="history-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-history mr-2" style="color: <?php echo $settings['primary_color']; ?>"></i>
                Cash Register History
            </h1>
            <p class="text-sm text-gray-600">View all register sessions and shift reports</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="history-card mb-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">From</label>
            <input type="date" id="dateFrom" value="<?php echo date('Y-m-01'); ?>"
                   class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
        </div>
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">To</label>
            <input type="date" id="dateTo" value="<?php echo date('Y-m-d'); ?>"
                   class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
        </div>
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">User</label>
            <select id="userFilter" class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
                <option value="">All Users</option>
                <!-- Populated via JavaScript -->
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Status</label>
            <select id="statusFilter" class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
                <option value="">All</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button onclick="loadSessions()" 
                    class="w-full px-6 py-2 rounded-lg font-bold text-white transition hover:opacity-90"
                    style="background-color: <?php echo $settings['primary_color']; ?>">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </div>
</div>

<!-- Sessions List -->
<div id="sessionsList" class="space-y-4">
    <div class="flex items-center justify-center py-20">
        <i class="fas fa-spinner fa-spin text-5xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
    </div>
</div>

<!-- Session Details Modal -->
<div id="sessionModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 bg-white p-6 border-b flex items-center justify-between z-10 rounded-t-2xl">
            <h3 class="text-xl font-bold text-gray-900">Shift Report</h3>
            <button onclick="closeSessionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="sessionDetails" class="p-6">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<script>
const currency = '<?php echo $settings['currency']; ?>';
const primaryColor = '<?php echo $settings['primary_color']; ?>';
let sessions = [];
let users = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    loadSessions();
});

// Load users
async function loadUsers() {
    try {
        const response = await fetch('/api/users.php');
        const data = await response.json();
        
        if (data.success) {
            users = data.data.users;
            const select = document.getElementById('userFilter');
            select.innerHTML = '<option value="">All Users</option>' +
                users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Load sessions
async function loadSessions() {
    const params = new URLSearchParams({
        action: 'get_sessions',
        from: document.getElementById('dateFrom').value,
        to: document.getElementById('dateTo').value,
        user: document.getElementById('userFilter').value,
        status: document.getElementById('statusFilter').value
    });
    
    try {
        const response = await fetch(`/api/cash-register.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            sessions = data.data.sessions;
            renderSessions();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        showToast('Failed to load sessions', 'error');
    }
}

// Render sessions
function renderSessions() {
    const container = document.getElementById('sessionsList');
    
    if (sessions.length === 0) {
        container.innerHTML = `
            <div class="history-card text-center py-20">
                <i class="fas fa-cash-register text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 font-semibold mb-2">No register sessions found</p>
                <p class="text-gray-400">Try adjusting your filters</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = sessions.map(session => {
        const isOpen = session.status === 'open';
        const statusClass = isOpen ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
        const hasVariance = session.variance && Math.abs(parseFloat(session.variance)) > 0.01;
        const varianceClass = session.variance > 0 ? 'text-green-600' : 'text-red-600';
        
        return `
            <div class="history-card cursor-pointer" onclick="viewSessionDetails(${session.id})">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <!-- User Info -->
                    <div class="md:col-span-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg"
                                 style="background: linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}dd 100%)">
                                ${session.user_name.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">${session.user_name}</p>
                                <p class="text-xs text-gray-500 capitalize">${session.role}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Session Info -->
                    <div class="md:col-span-3">
                        <p class="text-xs text-gray-500 mb-1">Session #${session.id}</p>
                        <p class="font-semibold text-sm text-gray-900">
                            ${new Date(session.opened_at).toLocaleDateString()}
                        </p>
                        <p class="text-xs text-gray-500">
                            ${new Date(session.opened_at).toLocaleTimeString()} - 
                            ${isOpen ? 'Open' : new Date(session.closed_at).toLocaleTimeString()}
                        </p>
                    </div>
                    
                    <!-- Stats -->
                    <div class="md:col-span-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-600">Sales</p>
                                <p class="font-bold text-lg">${session.sales_count || 0}</p>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <p class="text-xs text-gray-600">Revenue</p>
                                <p class="font-bold text-sm text-green-600">${currency} ${parseFloat(session.total_sales || 0).toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status & Variance -->
                    <div class="md:col-span-2 text-right">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${statusClass} mb-2">
                            ${isOpen ? 'OPEN' : 'CLOSED'}
                        </span>
                        ${!isOpen && hasVariance ? `
                            <p class="text-xs text-gray-600">Variance</p>
                            <p class="font-bold ${varianceClass}">
                                ${parseFloat(session.variance) > 0 ? '+' : ''}${currency} ${parseFloat(session.variance).toFixed(2)}
                            </p>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// View session details
async function viewSessionDetails(sessionId) {
    document.getElementById('sessionModal').classList.remove('hidden');
    document.getElementById('sessionModal').classList.add('flex');
    document.getElementById('sessionDetails').innerHTML = `
        <div class="flex items-center justify-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl" style="color: ${primaryColor}"></i>
        </div>
    `;
    
    try {
        const params = new URLSearchParams({ action: 'get_shift_report', session_id: sessionId });
        const response = await fetch(`/api/cash-register.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            renderSessionDetails(data.data);
        } else {
            showToast(data.message, 'error');
            closeSessionModal();
        }
    } catch (error) {
        console.error('Error loading session details:', error);
        showToast('Failed to load session details', 'error');
        closeSessionModal();
    }
}

function renderSessionDetails(data) {
    const session = data.session;
    const sales = data.sales;
    const transactions = data.transactions;
    
    const isOpen = session.status === 'open';
    const hasVariance = session.variance && Math.abs(parseFloat(session.variance)) > 0.01;
    
    let html = `
        <div class="space-y-6">
            <!-- Session Header -->
            <div class="bg-gradient-to-r from-${session.status === 'open' ? 'green' : 'blue'}-50 to-${session.status === 'open' ? 'green' : 'blue'}-100 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-2xl font-bold text-gray-900">Session #${session.id}</h4>
                        <p class="text-sm text-gray-600">${session.user_name} - ${session.role}</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-bold ${session.status === 'open' ? 'bg-green-500' : 'bg-gray-500'} text-white">
                        ${session.status.toUpperCase()}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600 mb-1">Opened</p>
                        <p class="font-semibold text-gray-900">${new Date(session.opened_at).toLocaleString()}</p>
                    </div>
                    ${!isOpen ? `
                    <div>
                        <p class="text-gray-600 mb-1">Closed</p>
                        <p class="font-semibold text-gray-900">${new Date(session.closed_at).toLocaleString()}</p>
                    </div>
                    ` : ''}
                    ${session.branch_name ? `
                    <div>
                        <p class="text-gray-600 mb-1">Branch</p>
                        <p class="font-semibold text-gray-900">${session.branch_name}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Financial Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-sm text-blue-600 mb-1">Opening Float</p>
                    <p class="text-2xl font-bold text-blue-700">${currency} ${parseFloat(session.opening_float).toFixed(2)}</p>
                </div>
                
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <p class="text-sm text-green-600 mb-1">Total Sales</p>
                    <p class="text-2xl font-bold text-green-700">${currency} ${parseFloat(session.total_sales || 0).toFixed(2)}</p>
                    <p class="text-xs text-green-600 mt-1">${session.sales_count || 0} transactions</p>
                </div>
                
                ${!isOpen ? `
                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <p class="text-sm text-purple-600 mb-1">Expected Cash</p>
                    <p class="text-2xl font-bold text-purple-700">${currency} ${parseFloat(session.expected_cash).toFixed(2)}</p>
                </div>
                
                <div class="bg-${hasVariance && session.variance < 0 ? 'red' : 'gray'}-50 rounded-xl p-4 text-center">
                    <p class="text-sm text-${hasVariance && session.variance < 0 ? 'red' : 'gray'}-600 mb-1">Actual Cash</p>
                    <p class="text-2xl font-bold text-${hasVariance && session.variance < 0 ? 'red' : 'gray'}-700">${currency} ${parseFloat(session.actual_cash).toFixed(2)}</p>
                    ${hasVariance ? `
                        <p class="text-xs font-bold ${session.variance > 0 ? 'text-green-600' : 'text-red-600'} mt-1">
                            ${session.variance > 0 ? '+' : ''}${currency} ${parseFloat(session.variance).toFixed(2)}
                        </p>
                    ` : ''}
                </div>
                ` : ''}
            </div>
            
            <!-- Payment Breakdown -->
            ${!isOpen ? `
            <div class="bg-gray-50 rounded-xl p-4">
                <h5 class="font-bold text-gray-900 mb-3">Payment Methods Breakdown</h5>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Cash</p>
                        <p class="font-bold text-green-600">${currency} ${parseFloat(session.total_cash_sales || 0).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">M-Pesa</p>
                        <p class="font-bold text-blue-600">${currency} ${parseFloat(session.total_mpesa_sales || 0).toFixed(2)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Card</p>
                        <p class="font-bold text-purple-600">${currency} ${parseFloat(session.total_card_sales || 0).toFixed(2)}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            <!-- Cash In/Out Transactions -->
            ${transactions.length > 0 ? `
            <div>
                <h5 class="font-bold text-gray-900 mb-3">Cash In/Out Transactions</h5>
                <div class="space-y-2">
                    ${transactions.map(trans => {
                        const isIn = trans.transaction_type === 'in';
                        return `
                            <div class="flex items-center justify-between p-3 ${isIn ? 'bg-green-50' : 'bg-red-50'} rounded-lg">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-arrow-${isIn ? 'up' : 'down'} ${isIn ? 'text-green-600' : 'text-red-600'}"></i>
                                    <div>
                                        <p class="font-semibold text-sm text-gray-900">${trans.reason}</p>
                                        <p class="text-xs text-gray-500">${new Date(trans.created_at).toLocaleString()}</p>
                                        ${trans.notes ? `<p class="text-xs text-gray-600 mt-1">${trans.notes}</p>` : ''}
                                    </div>
                                </div>
                                <p class="font-bold text-lg ${isIn ? 'text-green-600' : 'text-red-600'}">
                                    ${isIn ? '+' : '-'}${currency} ${parseFloat(trans.amount).toFixed(2)}
                                </p>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Sales List -->
            <div>
                <h5 class="font-bold text-gray-900 mb-3">Sales (${sales.length})</h5>
                ${sales.length > 0 ? `
                    <div class="max-h-64 overflow-y-auto space-y-2">
                        ${sales.map(sale => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div>
                                    <p class="font-semibold text-sm text-gray-900">${sale.sale_number}</p>
                                    <p class="text-xs text-gray-500">${new Date(sale.sale_date).toLocaleString()}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">${currency} ${parseFloat(sale.total_amount).toFixed(2)}</p>
                                    <p class="text-xs text-gray-500 uppercase">${sale.payment_method.replace('_', ' ')}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : '<p class="text-gray-400 text-center py-8">No sales in this session</p>'}
            </div>
            
            ${session.notes ? `
            <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4">
                <p class="font-semibold text-sm text-yellow-800 mb-2">Notes</p>
                <p class="text-sm text-yellow-900 whitespace-pre-line">${session.notes}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('sessionDetails').innerHTML = html;
}

function closeSessionModal() {
    document.getElementById('sessionModal').classList.add('hidden');
    document.getElementById('sessionModal').classList.remove('flex');
}

function showToast(message, type) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-[200]`;
    toast.style.animation = 'slideIn 0.3s ease-out';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSessionModal();
    }
});
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<?php include 'footer.php'; ?>
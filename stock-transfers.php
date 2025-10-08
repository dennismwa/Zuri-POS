<?php
require_once 'config.php';
requireOwner();

$page_title = 'Stock Transfers';
$settings = getSettings();

include 'header.php';
?>

<style>
.transfer-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.transfer-card:hover {
    shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .transfer-card {
        padding: 1rem;
    }
}
</style>

<!-- Header -->
<div class="transfer-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-exchange-alt gradient-text mr-3"></i>
                Stock Transfer History
            </h1>
            <p class="text-sm md:text-base text-gray-600">Track inventory movements between branches</p>
        </div>
        
        <div class="flex gap-2">
            <button onclick="openTransferModal()" 
                    class="px-4 md:px-6 py-2 md:py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg text-sm md:text-base"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-plus mr-2"></i>New Transfer
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="transfer-card mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <select id="statusFilter" onchange="loadTransfers()" 
                class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
        </select>
        
        <select id="branchFilter" onchange="loadTransfers()" 
                class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none">
            <option value="">All Branches</option>
            <!-- Populated via JavaScript -->
        </select>
        
        <button onclick="loadTransfers()" 
                class="px-6 py-2 rounded-lg font-bold text-white transition hover:opacity-90"
                style="background-color: <?php echo $settings['primary_color']; ?>">
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>
</div>

<!-- Transfers List -->
<div id="transfersList" class="space-y-4">
    <div class="flex items-center justify-center py-20">
        <i class="fas fa-spinner fa-spin text-5xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-4 md:p-6 rounded-t-2xl text-white sticky top-0 z-10" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl md:text-2xl font-bold">Initiate Stock Transfer</h3>
                    <p class="text-white/80 text-xs md:text-sm">Move inventory between branches</p>
                </div>
                <button onclick="closeTransferModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl md:text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="transferForm" class="p-4 md:p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">From Branch *</label>
                    <select id="fromBranch" name="from_branch_id" required onchange="updateFromBranchProducts()"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-base">
                        <option value="">Select source branch...</option>
                        <!-- Populated via JavaScript -->
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">To Branch *</label>
                    <select id="toBranch" name="to_branch_id" required 
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-base">
                        <option value="">Select destination branch...</option>
                        <!-- Populated via JavaScript -->
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Product *</label>
                    <select id="transferProduct" name="product_id" required onchange="updateAvailableStock()"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-base">
                        <option value="">Select product...</option>
                        <!-- Populated via JavaScript -->
                    </select>
                    <p id="availableStock" class="text-sm text-gray-600 mt-2 hidden">
                        <i class="fas fa-box mr-1"></i>Available: <span class="font-bold" id="stockAmount">0</span> units
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Quantity *</label>
                    <input type="number" id="transferQuantity" name="quantity" required min="1"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-lg font-bold"
                           placeholder="0">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Notes</label>
                    <textarea id="transferNotes" name="notes" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-base"
                              placeholder="Optional transfer notes..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeTransferModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="transferSubmitBtn"
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-exchange-alt mr-2"></i>Initiate Transfer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
const currency = '<?php echo $settings['currency']; ?>';
let transfers = [];
let branches = [];
let branchProducts = [];

// Get API URL
function getApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}/api/${endpoint}`;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadBranches();
    loadTransfers();
});

// Load branches
async function loadBranches() {
    try {
        const apiUrl = getApiUrl('branches.php');
        const params = new URLSearchParams({ action: 'get_branches' });
        
        const response = await fetch(`${apiUrl}?${params}`);
        const data = await response.json();
        
        if (data.success) {
            branches = data.data.branches.filter(b => b.status === 'active');
            populateBranchDropdowns();
        }
    } catch (error) {
        console.error('Error loading branches:', error);
    }
}

function populateBranchDropdowns() {
    const fromSelect = document.getElementById('fromBranch');
    const toSelect = document.getElementById('toBranch');
    const filterSelect = document.getElementById('branchFilter');
    
    const options = branches.map(b => 
        `<option value="${b.id}">${escapeHtml(b.name)} (${escapeHtml(b.code)})</option>`
    ).join('');
    
    fromSelect.innerHTML = '<option value="">Select source branch...</option>' + options;
    toSelect.innerHTML = '<option value="">Select destination branch...</option>' + options;
    filterSelect.innerHTML = '<option value="">All Branches</option>' + options;
}

// Load products for selected branch
async function updateFromBranchProducts() {
    const branchId = document.getElementById('fromBranch').value;
    const productSelect = document.getElementById('transferProduct');
    
    if (!branchId) {
        productSelect.innerHTML = '<option value="">Select product...</option>';
        document.getElementById('availableStock').classList.add('hidden');
        return;
    }
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const params = new URLSearchParams({
            action: 'get_branch_inventory',
            branch_id: branchId
        });
        
        const response = await fetch(`${apiUrl}?${params}`);
        const data = await response.json();
        
        if (data.success) {
            branchProducts = data.data.inventory.filter(item => item.stock_quantity > 0);
            
            productSelect.innerHTML = '<option value="">Select product...</option>' +
                branchProducts.map(item => 
                    `<option value="${item.product_id}" data-stock="${item.stock_quantity}">
                        ${escapeHtml(item.name)} (Available: ${item.stock_quantity})
                    </option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function updateAvailableStock() {
    const select = document.getElementById('transferProduct');
    const stockDiv = document.getElementById('availableStock');
    const stockAmount = document.getElementById('stockAmount');
    const quantityInput = document.getElementById('transferQuantity');
    
    const selectedOption = select.options[select.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock');
    
    if (stock) {
        stockDiv.classList.remove('hidden');
        stockAmount.textContent = stock;
        quantityInput.max = stock;
    } else {
        stockDiv.classList.add('hidden');
        quantityInput.max = '';
    }
}

// Load transfers
async function loadTransfers() {
    const status = document.getElementById('statusFilter').value;
    const branchId = document.getElementById('branchFilter').value;
    
    const params = new URLSearchParams({
        action: 'get_transfers',
        status: status,
        branch_id: branchId
    });
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const response = await fetch(`${apiUrl}?${params}`);
        const data = await response.json();
        
        if (data.success) {
            transfers = data.data.transfers;
            renderTransfers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading transfers:', error);
        showToast('Failed to load transfers', 'error');
    }
}

// Render transfers
function renderTransfers() {
    const container = document.getElementById('transfersList');
    
    if (transfers.length === 0) {
        container.innerHTML = `
            <div class="transfer-card text-center py-20">
                <i class="fas fa-exchange-alt text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 font-semibold mb-2">No transfers found</p>
                <p class="text-gray-400">Stock transfers between branches will appear here</p>
                <button onclick="openTransferModal()" 
                        class="mt-6 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg"
                        style="background: linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}dd 100%)">
                    <i class="fas fa-plus mr-2"></i>Create First Transfer
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = transfers.map(transfer => {
        const statusConfig = getStatusConfig(transfer.status);
        
        return `
            <div class="transfer-card">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Transfer Icon & Number -->
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-2xl"
                             style="background: linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}dd 100%)">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div>
                            <p class="font-bold text-lg text-gray-900">${transfer.transfer_number}</p>
                            <p class="text-sm text-gray-600">${new Date(transfer.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    
                    <!-- Transfer Details -->
                    <div class="flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-600 mb-1">From</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(transfer.from_branch_name)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">To</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(transfer.to_branch_name)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Product</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(transfer.product_name)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Quantity</p>
                                <p class="font-bold text-2xl" style="color: ${primaryColor}">${transfer.quantity}</p>
                            </div>
                        </div>
                        
                        ${transfer.notes ? `
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <p class="text-xs text-gray-600 mb-1">Notes</p>
                            <p class="text-sm text-gray-700">${escapeHtml(transfer.notes)}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Status & Actions -->
                    <div class="flex flex-col items-end justify-between gap-4">
                        <span class="status-badge ${statusConfig.class}">
                            <i class="fas fa-${statusConfig.icon}"></i>
                            ${statusConfig.label}
                        </span>
                        
                        ${transfer.status === 'pending' ? `
                        <div class="flex gap-2">
                            <button onclick="completeTransfer(${transfer.id})" 
                                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition text-sm">
                                <i class="fas fa-check mr-1"></i>Complete
                            </button>
                        </div>
                        ` : ''}
                        
                        <div class="text-xs text-gray-500 text-right">
                            <p>Initiated: ${escapeHtml(transfer.initiated_by_name)}</p>
                            ${transfer.received_by_name ? `<p>Received: ${escapeHtml(transfer.received_by_name)}</p>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getStatusConfig(status) {
    const configs = {
        pending: { label: 'Pending', class: 'bg-yellow-100 text-yellow-800', icon: 'clock' },
        completed: { label: 'Completed', class: 'bg-green-100 text-green-800', icon: 'check-circle' }
    };
    return configs[status] || configs.pending;
}

// Modal functions
function openTransferModal() {
    document.getElementById('transferModal').classList.remove('hidden');
    document.getElementById('transferModal').classList.add('flex');
    document.getElementById('transferForm').reset();
    document.getElementById('availableStock').classList.add('hidden');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.add('hidden');
    document.getElementById('transferModal').classList.remove('flex');
}

// Submit Transfer
document.getElementById('transferForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('transferSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    const formData = new FormData(this);
    formData.append('action', 'initiate_transfer');
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Transfer initiated successfully', 'success');
            closeTransferModal();
            loadTransfers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Initiate Transfer';
    }
});

// Complete Transfer
async function completeTransfer(id) {
    if (!confirm('Complete this transfer?\n\nStock will be added to the destination branch.')) return;
    
    const formData = new FormData();
    formData.append('action', 'complete_transfer');
    formData.append('transfer_id', id);
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadTransfers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    }
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

// ESC to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTransferModal();
    }
});
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.gradient-text {
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<?php include 'footer.php'; ?>
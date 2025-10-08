<?php
require_once 'config.php';
requireAuth();

$page_title = 'Cash Register';
$settings = getSettings();
$isOwner = $_SESSION['role'] === 'owner';

include 'header.php';
?>

<style>
.register-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.register-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.stat-badge {
    padding: 0.75rem 1.25rem;
    border-radius: 1rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .register-card {
        padding: 1rem;
    }
}
</style>

<!-- No Active Session View -->
<div id="noSessionView" class="hidden">
    <div class="register-card max-w-2xl mx-auto text-center py-12">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center"
             style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?>20 0%, <?php echo $settings['primary_color']; ?>10 100%)">
            <i class="fas fa-cash-register text-5xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-900 mb-3">Open Cash Register</h2>
        <p class="text-gray-600 mb-8">Start your shift by declaring your opening float</p>
        
        <form id="openRegisterForm" class="max-w-md mx-auto">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-left">Opening Float (<?php echo $settings['currency']; ?>) *</label>
                    <input type="number" id="openingFloat" step="0.01" min="0" required
                           class="w-full px-6 py-4 border-2 border-gray-200 rounded-xl focus:outline-none text-2xl font-bold text-center"
                           style="color: <?php echo $settings['primary_color']; ?>"
                           placeholder="0.00" autofocus>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-left">Notes (Optional)</label>
                    <textarea id="openingNotes" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-base"
                              placeholder="Any notes about this shift..."></textarea>
                </div>
            </div>
            
            <button type="submit" id="openRegisterBtn"
                    class="w-full mt-6 px-8 py-4 rounded-xl font-bold text-white text-lg transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-unlock mr-2"></i>Open Register
            </button>
        </form>
    </div>
</div>

<!-- Active Session View -->
<div id="activeSessionView" class="hidden">
    <!-- Header -->
    <div class="register-card mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-cash-register mr-2" style="color: <?php echo $settings['primary_color']; ?>"></i>
                    Cash Register - Session #<span id="sessionId"></span>
                </h1>
                <p class="text-sm text-gray-600">
                    Opened: <span id="sessionOpenedTime" class="font-semibold"></span>
                </p>
            </div>
            
            <div class="flex gap-2">
                <button onclick="openCashTransactionModal('in')"
                        class="px-4 md:px-6 py-2 md:py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Cash In
                </button>
                <button onclick="openCashTransactionModal('out')"
                        class="px-4 md:px-6 py-2 md:py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-minus mr-2"></i>Cash Out
                </button>
                <button onclick="openCloseRegisterModal()"
                        class="px-4 md:px-6 py-2 md:py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-lock mr-2"></i>Close Register
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="register-card">
            <p class="text-sm text-gray-600 mb-1">Opening Float</p>
            <h3 class="text-2xl md:text-3xl font-bold text-blue-600" id="openingFloat">-</h3>
        </div>
        
        <div class="register-card">
            <p class="text-sm text-gray-600 mb-1">Cash Sales</p>
            <h3 class="text-2xl md:text-3xl font-bold text-green-600" id="cashSales">-</h3>
        </div>
        
        <div class="register-card">
            <p class="text-sm text-gray-600 mb-1">Expected Cash</p>
            <h3 class="text-2xl md:text-3xl font-bold" style="color: <?php echo $settings['primary_color']; ?>" id="expectedCash">-</h3>
        </div>
        
        <div class="register-card">
            <p class="text-sm text-gray-600 mb-1">Total Sales</p>
            <h3 class="text-2xl md:text-3xl font-bold text-purple-600" id="totalSales">-</h3>
            <p class="text-xs text-gray-500 mt-1"><span id="salesCount">0</span> transactions</p>
        </div>
    </div>
    
    <!-- Transactions -->
    <div class="register-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Cash In/Out Transactions</h3>
        <div id="transactionsList" class="space-y-2"></div>
    </div>
</div>

<!-- Cash Transaction Modal -->
<div id="cashTransactionModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" id="transactionModalHeader">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold" id="transactionModalTitle"></h3>
                    <p class="text-white/80 text-sm">Record cash movement</p>
                </div>
                <button onclick="closeCashTransactionModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form id="cashTransactionForm" class="p-6">
            <input type="hidden" id="transactionType">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Amount (<?php echo $settings['currency']; ?>) *</label>
                    <input type="number" id="transactionAmount" step="0.01" min="0.01" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-2xl font-bold">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Reason *</label>
                    <select id="transactionReason" required onchange="toggleCustomReason()"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none">
                        <option value="">Select reason...</option>
                        <option value="Bank Deposit">Bank Deposit</option>
                        <option value="Change Request">Change Request</option>
                        <option value="Petty Cash">Petty Cash</option>
                        <option value="Till Shortage">Till Shortage</option>
                        <option value="Till Surplus">Till Surplus</option>
                        <option value="Other">Other (Specify)</option>
                    </select>
                </div>
                
                <div id="customReasonField" class="hidden">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Specify Reason *</label>
                    <input type="text" id="customReason"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Additional Notes</label>
                    <textarea id="transactionNotes" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeCashTransactionModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="transactionSubmitBtn"
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Close Register Modal -->
<div id="closeRegisterModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
        <div class="p-6 rounded-t-2xl bg-red-500 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Close Cash Register</h3>
                    <p class="text-white/80 text-sm">End your shift and reconcile cash</p>
                </div>
                <button onclick="closeCloseRegisterModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form id="closeRegisterForm" class="p-6">
            <div class="space-y-4">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Opening Float</p>
                            <p class="font-bold text-lg" id="closeOpeningFloat">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Cash Sales</p>
                            <p class="font-bold text-lg text-green-600" id="closeCashSales">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Cash In</p>
                            <p class="font-bold text-lg text-green-600" id="closeCashIn">-</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Cash Out</p>
                            <p class="font-bold text-lg text-red-600" id="closeCashOut">-</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 border-2 border-purple-200 rounded-xl p-4">
                    <p class="text-sm text-gray-600 mb-1">Expected Cash in Drawer</p>
                    <p class="text-3xl font-bold text-purple-600" id="closeExpectedCash">-</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Actual Cash Count (<?php echo $settings['currency']; ?>) *</label>
                    <input type="number" id="actualCash" step="0.01" min="0" required
                           class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none text-2xl font-bold text-center"
                           placeholder="0.00" onchange="calculateVariance()">
                </div>
                
                <div id="varianceDisplay" class="hidden rounded-xl p-4 border-2">
                    <p class="text-sm font-semibold mb-1">Variance</p>
                    <p class="text-2xl font-bold" id="varianceAmount">-</p>
                    <p class="text-xs mt-1" id="varianceMessage">-</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Closing Notes</label>
                    <textarea id="closingNotes" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"
                              placeholder="Any issues or notes..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeCloseRegisterModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="closeRegisterBtn"
                        class="flex-1 px-6 py-3 bg-red-500 hover:bg-red-600 rounded-xl font-bold text-white transition">
                    <i class="fas fa-lock mr-2"></i>Close Register
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currency = '<?php echo $settings['currency']; ?>';
const primaryColor = '<?php echo $settings['primary_color']; ?>';
let currentSession = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadActiveSession();
});

// Load active session
async function loadActiveSession() {
    try {
        const params = new URLSearchParams({ action: 'get_active_session' });
        const response = await fetch(`/api/cash-register.php?${params}`);
        const data = await response.json();
        
        if (data.success && data.data.session) {
            currentSession = data.data.session;
            showActiveSession();
            updateSessionDisplay();
        } else {
            showNoSession();
        }
    } catch (error) {
        console.error('Error loading session:', error);
        showToast('Failed to load register session', 'error');
    }
}

function showNoSession() {
    document.getElementById('noSessionView').classList.remove('hidden');
    document.getElementById('activeSessionView').classList.add('hidden');
}

function showActiveSession() {
    document.getElementById('noSessionView').classList.add('hidden');
    document.getElementById('activeSessionView').classList.remove('hidden');
}

function updateSessionDisplay() {
    if (!currentSession) return;
    
    document.getElementById('sessionId').textContent = currentSession.id;
    document.getElementById('sessionOpenedTime').textContent = new Date(currentSession.opened_at).toLocaleString();
    document.getElementById('openingFloat').textContent = currency + ' ' + parseFloat(currentSession.opening_float).toFixed(2);
    
    // Calculate current stats
    const cashIn = currentSession.transactions
        .filter(t => t.transaction_type === 'in')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
    
    const cashOut = currentSession.transactions
        .filter(t => t.transaction_type === 'out')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
    
    // For now, we'll need to fetch sales from API
    fetchSessionSales();
    
    // Display transactions
    const transactionsList = document.getElementById('transactionsList');
    if (currentSession.transactions.length === 0) {
        transactionsList.innerHTML = '<p class="text-gray-400 text-center py-4">No cash in/out transactions yet</p>';
    } else {
        transactionsList.innerHTML = currentSession.transactions.map(trans => {
            const isIn = trans.transaction_type === 'in';
            const color = isIn ? 'text-green-600' : 'text-red-600';
            const bg = isIn ? 'bg-green-50' : 'bg-red-50';
            const icon = isIn ? 'arrow-up' : 'arrow-down';
            
            return `
                <div class="${bg} rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg ${bg} flex items-center justify-center">
                                <i class="fas fa-${icon} ${color}"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">${trans.reason}</p>
                                <p class="text-xs text-gray-500">${new Date(trans.created_at).toLocaleString()}</p>
                            </div>
                        </div>
                        <p class="font-bold text-lg ${color}">
                            ${isIn ? '+' : '-'}${currency} ${parseFloat(trans.amount).toFixed(2)}
                        </p>
                    </div>
                    ${trans.notes ? `<p class="text-xs text-gray-600 mt-2 ml-13">${trans.notes}</p>` : ''}
                </div>
            `;
        }).join('');
    }
}

async function fetchSessionSales() {
    // This would need to be implemented in the API
    // For now, just show placeholder
    document.getElementById('cashSales').textContent = currency + ' 0.00';
    document.getElementById('expectedCash').textContent = currency + ' ' + parseFloat(currentSession.opening_float).toFixed(2);
    document.getElementById('totalSales').textContent = currency + ' 0.00';
    document.getElementById('salesCount').textContent = '0';
}

// Open Register
document.getElementById('openRegisterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('openRegisterBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Opening...';
    
    const formData = new FormData();
    formData.append('action', 'open_register');
    formData.append('opening_float', document.getElementById('openingFloat').value);
    formData.append('notes', document.getElementById('openingNotes').value);
    
    try {
        const response = await fetch('/api/cash-register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Register opened successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-unlock mr-2"></i>Open Register';
    }
});

// Cash Transaction Modal
function openCashTransactionModal(type) {
    const modal = document.getElementById('cashTransactionModal');
    const header = document.getElementById('transactionModalHeader');
    const title = document.getElementById('transactionModalTitle');
    const submitBtn = document.getElementById('transactionSubmitBtn');
    
    document.getElementById('transactionType').value = type;
    
    if (type === 'in') {
        header.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        title.textContent = 'Cash In';
        submitBtn.style.background = '#10b981';
    } else {
        header.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
        title.textContent = 'Cash Out';
        submitBtn.style.background = '#f59e0b';
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('cashTransactionForm').reset();
}

function closeCashTransactionModal() {
    document.getElementById('cashTransactionModal').classList.add('hidden');
    document.getElementById('cashTransactionModal').classList.remove('flex');
}

function toggleCustomReason() {
    const reason = document.getElementById('transactionReason').value;
    const customField = document.getElementById('customReasonField');
    
    if (reason === 'Other') {
        customField.classList.remove('hidden');
        document.getElementById('customReason').required = true;
    } else {
        customField.classList.add('hidden');
        document.getElementById('customReason').required = false;
    }
}

document.getElementById('cashTransactionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('transactionSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    let reason = document.getElementById('transactionReason').value;
    if (reason === 'Other') {
        reason = document.getElementById('customReason').value;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_transaction');
    formData.append('session_id', currentSession.id);
    formData.append('type', document.getElementById('transactionType').value);
    formData.append('amount', document.getElementById('transactionAmount').value);
    formData.append('reason', reason);
    formData.append('notes', document.getElementById('transactionNotes').value);
    
    try {
        const response = await fetch('/api/cash-register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Transaction recorded successfully', 'success');
            closeCashTransactionModal();
            loadActiveSession();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        const type = document.getElementById('transactionType').value;
        btn.innerHTML = type === 'in' ? 'Cash In' : 'Cash Out';
    }
});

// Close Register
function openCloseRegisterModal() {
    document.getElementById('closeOpeningFloat').textContent = currency + ' ' + parseFloat(currentSession.opening_float).toFixed(2);
    
    // Calculate cash in/out
    const cashIn = currentSession.transactions
        .filter(t => t.transaction_type === 'in')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
    
    const cashOut = currentSession.transactions
        .filter(t => t.transaction_type === 'out')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0);
    
    document.getElementById('closeCashIn').textContent = currency + ' ' + cashIn.toFixed(2);
    document.getElementById('closeCashOut').textContent = currency + ' ' + cashOut.toFixed(2);
    
    // For cash sales, we'd need to fetch from API
    // For now, placeholder
    document.getElementById('closeCashSales').textContent = currency + ' 0.00';
    
    const expected = parseFloat(currentSession.opening_float) + 0 + cashIn - cashOut;
    document.getElementById('closeExpectedCash').textContent = currency + ' ' + expected.toFixed(2);
    
    document.getElementById('closeRegisterModal').classList.remove('hidden');
    document.getElementById('closeRegisterModal').classList.add('flex');
}

function closeCloseRegisterModal() {
    document.getElementById('closeRegisterModal').classList.add('hidden');
    document.getElementById('closeRegisterModal').classList.remove('flex');
}

function calculateVariance() {
    const actual = parseFloat(document.getElementById('actualCash').value) || 0;
    const expectedText = document.getElementById('closeExpectedCash').textContent;
    const expected = parseFloat(expectedText.replace(currency, '').trim());
    
    const variance = actual - expected;
    
    const display = document.getElementById('varianceDisplay');
    const amount = document.getElementById('varianceAmount');
    const message = document.getElementById('varianceMessage');
    
    display.classList.remove('hidden', 'bg-green-50', 'border-green-200', 'bg-red-50', 'border-red-200', 'bg-gray-50', 'border-gray-200');
    
    if (variance > 0) {
        display.classList.add('bg-green-50', 'border-green-200');
        amount.className = 'text-2xl font-bold text-green-600';
        amount.textContent = '+' + currency + ' ' + variance.toFixed(2);
        message.textContent = 'Over by ' + currency + ' ' + variance.toFixed(2);
        message.className = 'text-xs mt-1 text-green-600';
    } else if (variance < 0) {
        display.classList.add('bg-red-50', 'border-red-200');
        amount.className = 'text-2xl font-bold text-red-600';
        amount.textContent = currency + ' ' + variance.toFixed(2);
        message.textContent = 'Short by ' + currency + ' ' + Math.abs(variance).toFixed(2);
        message.className = 'text-xs mt-1 text-red-600';
    } else {
        display.classList.add('bg-gray-50', 'border-gray-200');
        amount.className = 'text-2xl font-bold text-gray-600';
        amount.textContent = currency + ' 0.00';
        message.textContent = 'Perfect match!';
        message.className = 'text-xs mt-1 text-gray-600';
    }
}

document.getElementById('closeRegisterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!confirm('Close register and end shift?\n\nThis action cannot be undone.')) return;
    
    const btn = document.getElementById('closeRegisterBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Closing...';
    
    const formData = new FormData();
    formData.append('action', 'close_register');
    formData.append('session_id', currentSession.id);
    formData.append('actual_cash', document.getElementById('actualCash').value);
    formData.append('notes', document.getElementById('closingNotes').value);
    
    try {
        const response = await fetch('/api/cash-register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Register closed successfully!', 'success');
            
            // Show variance alert if needed
            const variance = data.data.variance;
            if (Math.abs(variance) > 0.01) {
                const msg = variance > 0 
                    ? `Over by ${currency} ${variance.toFixed(2)}` 
                    : `Short by ${currency} ${Math.abs(variance).toFixed(2)}`;
                alert('Register Closed\n\n' + msg);
            }
            
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock mr-2"></i>Close Register';
    }
});

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

// ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCashTransactionModal();
        closeCloseRegisterModal();
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
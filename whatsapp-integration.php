<?php
require_once 'config.php';
requireOwner();

$page_title = 'WhatsApp Integration';
$settings = getSettings();

include 'header.php';
?>

<style>
.whatsapp-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.whatsapp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@media (max-width: 768px) {
    .whatsapp-card {
        padding: 1rem;
    }
}
</style>

<!-- Page Header -->
<div class="whatsapp-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fab fa-whatsapp text-green-600 mr-3"></i>
                WhatsApp Integration
            </h1>
            <p class="text-sm md:text-base text-gray-600">Send receipts, alerts, and summaries via WhatsApp</p>
        </div>
        
        <div id="connectionStatus" class="hidden">
            <div class="flex items-center gap-2 px-4 py-2 rounded-lg">
                <span class="status-indicator"></span>
                <span class="font-semibold text-sm"></span>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="whatsapp-card mb-6">
    <div class="flex flex-wrap gap-2">
        <button onclick="showTab('settings')" id="tab-settings" class="tab-btn px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition text-sm md:text-base">
            <i class="fas fa-cog mr-2"></i>Settings
        </button>
        <button onclick="showTab('messages')" id="tab-messages" class="tab-btn px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition text-sm md:text-base">
            <i class="fas fa-comments mr-2"></i>Messages Log
        </button>
        <button onclick="showTab('actions')" id="tab-actions" class="tab-btn px-4 md:px-6 py-2 md:py-3 rounded-lg font-semibold transition text-sm md:text-base">
            <i class="fas fa-bolt mr-2"></i>Actions
        </button>
    </div>
</div>

<!-- Settings Tab -->
<div id="content-settings" class="tab-content">
    <form id="configForm" class="whatsapp-card">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Configuration</h3>
        
        <div class="space-y-4">
            <!-- Provider Selection -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Provider *</label>
                <select name="provider" id="provider" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none">
                    <option value="twilio">Twilio (Recommended)</option>
                    <option value="whatsapp_business" disabled>WhatsApp Business API (Coming Soon)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Get Twilio credentials at <a href="https://www.twilio.com/console" target="_blank" class="text-blue-600 hover:underline">twilio.com/console</a></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Account SID *</label>
                    <input type="text" name="account_sid" id="account_sid" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none font-mono text-sm"
                           placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Auth Token *</label>
                    <input type="password" name="auth_token" id="auth_token" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none font-mono text-sm"
                           placeholder="Enter your auth token">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">From Number (WhatsApp) *</label>
                    <input type="tel" name="from_number" id="from_number" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"
                           placeholder="+254700000000">
                    <p class="text-xs text-gray-500 mt-1">Format: +[country code][number]</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Admin Number *</label>
                    <input type="tel" name="admin_number" id="admin_number" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"
                           placeholder="+254700000000">
                    <p class="text-xs text-gray-500 mt-1">Receive alerts and summaries</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Daily Summary Time</label>
                    <input type="time" name="daily_summary_time" id="daily_summary_time"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" id="low_stock_threshold" min="1"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none">
                </div>
            </div>
            
            <!-- Feature Toggles -->
            <div class="space-y-3 pt-4 border-t">
                <h4 class="font-bold text-gray-900">Features</h4>
                
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <div>
                        <p class="font-semibold text-gray-800">Send Receipts</p>
                        <p class="text-sm text-gray-600">Send sale receipts via WhatsApp</p>
                    </div>
                    <input type="checkbox" name="send_receipts" id="send_receipts" class="w-6 h-6 rounded">
                </label>
                
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <div>
                        <p class="font-semibold text-gray-800">Low Stock Alerts</p>
                        <p class="text-sm text-gray-600">Get notified when stock is low</p>
                    </div>
                    <input type="checkbox" name="send_low_stock_alerts" id="send_low_stock_alerts" class="w-6 h-6 rounded">
                </label>
                
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                    <div>
                        <p class="font-semibold text-gray-800">Daily Summary</p>
                        <p class="text-sm text-gray-600">Receive daily sales summary</p>
                    </div>
                    <input type="checkbox" name="send_daily_summary" id="send_daily_summary" class="w-6 h-6 rounded">
                </label>
            </div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button type="button" onclick="testConnection()" id="testBtn"
                    class="flex-1 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-semibold transition">
                <i class="fas fa-vial mr-2"></i>Test Connection
            </button>
            <button type="submit" id="saveBtn"
                    class="flex-1 px-6 py-3 rounded-xl font-semibold text-white transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-save mr-2"></i>Save Configuration
            </button>
        </div>
    </form>
</div>

<!-- Messages Log Tab -->
<div id="content-messages" class="tab-content hidden">
    <div class="whatsapp-card mb-4">
        <div class="flex flex-wrap gap-3">
            <select id="messageTypeFilter" onchange="loadMessages()" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none text-sm">
                <option value="">All Types</option>
                <option value="receipt">Receipts</option>
                <option value="low_stock_alert">Low Stock Alerts</option>
                <option value="daily_summary">Daily Summary</option>
                <option value="test">Test Messages</option>
            </select>
            
            <select id="messageStatusFilter" onchange="loadMessages()" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:outline-none text-sm">
                <option value="">All Statuses</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
                <option value="pending">Pending</option>
            </select>
            
            <button onclick="loadMessages()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold transition text-sm">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>
    
    <div id="messagesList" class="space-y-3">
        <div class="flex items-center justify-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
        </div>
    </div>
</div>

<!-- Actions Tab -->
<div id="content-actions" class="tab-content hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="whatsapp-card text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fas fa-paper-plane text-blue-600 text-2xl"></i>
            </div>
            <h3 class="font-bold text-lg text-gray-900 mb-2">Send Daily Summary</h3>
            <p class="text-sm text-gray-600 mb-4">Send today's sales summary now</p>
            <button onclick="sendDailySummary()" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition text-sm">
                Send Now
            </button>
        </div>
        
        <div class="whatsapp-card text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-orange-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-orange-600 text-2xl"></i>
            </div>
            <h3 class="font-bold text-lg text-gray-900 mb-2">Check Low Stock</h3>
            <p class="text-sm text-gray-600 mb-4">Send alerts for low stock items</p>
            <button onclick="checkLowStock()" class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition text-sm">
                Check Now
            </button>
        </div>
        
        <div class="whatsapp-card text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-xl bg-green-100 flex items-center justify-center">
                <i class="fas fa-file-invoice text-green-600 text-2xl"></i>
            </div>
            <h3 class="font-bold text-lg text-gray-900 mb-2">Resend Receipt</h3>
            <p class="text-sm text-gray-600 mb-4">Resend a receipt to customer</p>
            <button onclick="openResendModal()" class="px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition text-sm">
                Resend
            </button>
        </div>
    </div>
</div>

<!-- Resend Receipt Modal -->
<div id="resendModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="p-6 bg-green-600 rounded-t-2xl text-white">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Resend Receipt</h3>
                <button onclick="closeResendModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form id="resendForm" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sale Number *</label>
                    <input type="text" id="resend_sale_number" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"
                           placeholder="ZWS-20240101-ABC123">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">WhatsApp Number *</label>
                    <input type="tel" id="resend_phone" required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none"
                           placeholder="+254700000000">
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeResendModal()"
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700">
                    Cancel
                </button>
                <button type="submit" id="resendBtn"
                        class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold">
                    <i class="fab fa-whatsapp mr-2"></i>Send
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
let config = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadConfig();
    showTab('settings');
});

// Tab Management
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.style.backgroundColor = '';
        el.style.color = '';
    });
    
    document.getElementById('content-' + tab).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tab);
    btn.style.backgroundColor = primaryColor;
    btn.style.color = 'white';
    
    if (tab === 'messages') loadMessages();
}

// Load Configuration
async function loadConfig() {
    try {
        const params = new URLSearchParams({ action: 'get_config' });
        const response = await fetch(`/api/whatsapp.php?${params}`);
        const data = await response.json();
        
        if (data.success && data.data.config) {
            config = data.data.config;
            populateForm(config);
            updateConnectionStatus(config.api_status);
        }
    } catch (error) {
        console.error('Error loading config:', error);
    }
}

function populateForm(config) {
    document.getElementById('provider').value = config.provider || 'twilio';
    document.getElementById('account_sid').value = config.account_sid || '';
    document.getElementById('auth_token').value = config.auth_token || '';
    document.getElementById('from_number').value = config.from_number || '';
    document.getElementById('admin_number').value = config.admin_number || '';
    document.getElementById('daily_summary_time').value = config.daily_summary_time || '18:00:00';
    document.getElementById('low_stock_threshold').value = config.low_stock_threshold || 10;
    document.getElementById('send_receipts').checked = config.send_receipts == 1;
    document.getElementById('send_low_stock_alerts').checked = config.send_low_stock_alerts == 1;
    document.getElementById('send_daily_summary').checked = config.send_daily_summary == 1;
}

function updateConnectionStatus(status) {
    const statusDiv = document.getElementById('connectionStatus');
    const indicator = statusDiv.querySelector('.status-indicator');
    const text = statusDiv.querySelector('span:last-child');
    
    statusDiv.classList.remove('hidden');
    
    if (status === 'active') {
        statusDiv.className = statusDiv.className.replace(/bg-\w+-100/g, '') + ' bg-green-100';
        indicator.className = 'status-indicator bg-green-500';
        text.textContent = 'Connected';
        text.className = 'font-semibold text-sm text-green-800';
    } else {
        statusDiv.className = statusDiv.className.replace(/bg-\w+-100/g, '') + ' bg-gray-100';
        indicator.className = 'status-indicator bg-gray-400';
        text.textContent = 'Not Configured';
        text.className = 'font-semibold text-sm text-gray-600';
    }
}

// Save Configuration
document.getElementById('configForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    const formData = new FormData(this);
    formData.append('action', 'update_config');
    
    try {
        const response = await fetch('/api/whatsapp.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Configuration saved successfully!', 'success');
            loadConfig();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Configuration';
    }
});

// Test Connection
async function testConnection() {
    const btn = document.getElementById('testBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
    
    const formData = new FormData();
    formData.append('action', 'test_connection');
    
    try {
        const response = await fetch('/api/whatsapp.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Test message sent! Check WhatsApp.', 'success');
            updateConnectionStatus('active');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-vial mr-2"></i>Test Connection';
    }
}

// Load Messages
async function loadMessages() {
    const type = document.getElementById('messageTypeFilter').value;
    const status = document.getElementById('messageStatusFilter').value;
    
    const params = new URLSearchParams({
        action: 'get_messages',
        type: type,
        status: status
    });
    
    try {
        const response = await fetch(`/api/whatsapp.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            renderMessages(data.data.messages);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messagesList');
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="whatsapp-card text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 font-semibold">No messages yet</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = messages.map(msg => {
        const statusConfig = {
            sent: { class: 'bg-green-100 text-green-800', icon: 'check' },
            failed: { class: 'bg-red-100 text-red-800', icon: 'times' },
            pending: { class: 'bg-yellow-100 text-yellow-800', icon: 'clock' }
        }[msg.status] || { class: 'bg-gray-100 text-gray-800', icon: 'question' };
        
        const typeConfig = {
            receipt: { icon: 'receipt', color: 'blue' },
            low_stock_alert: { icon: 'exclamation-triangle', color: 'orange' },
            daily_summary: { icon: 'chart-bar', color: 'purple' },
            test: { icon: 'vial', color: 'gray' }
        }[msg.message_type] || { icon: 'comment', color: 'gray' };
        
        return `
            <div class="whatsapp-card">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-lg bg-${typeConfig.color}-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-${typeConfig.icon} text-${typeConfig.color}-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-bold text-sm text-gray-900">${msg.recipient_number}</p>
                                <p class="text-xs text-gray-500">${new Date(msg.created_at).toLocaleString()}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${statusConfig.class}">
                                <i class="fas fa-${statusConfig.icon} mr-1"></i>${msg.status.toUpperCase()}
                            </span>
                        </div>
                        <p class="text-sm text-gray-700 line-clamp-2">${escapeHtml(msg.message_content.substring(0, 100))}...</p>
                        ${msg.error_message ? `<p class="text-xs text-red-600 mt-2">Error: ${escapeHtml(msg.error_message)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Actions
async function sendDailySummary() {
    if (!confirm('Send daily summary now?')) return;
    
    const formData = new FormData();
    formData.append('action', 'send_daily_summary');
    
    try {
        const response = await fetch('/api/whatsapp.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Daily summary sent!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    }
}

async function checkLowStock() {
    const formData = new FormData();
    formData.append('action', 'check_low_stock');
    
    try {
        const response = await fetch('/api/whatsapp.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const result = data.data;
            showToast(`Found ${result.products_found} low stock items. Sent ${result.alerts_sent} alerts.`, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    }
}

function openResendModal() {
    document.getElementById('resendModal').classList.remove('hidden');
    document.getElementById('resendModal').classList.add('flex');
}

function closeResendModal() {
    document.getElementById('resendModal').classList.add('hidden');
    document.getElementById('resendModal').classList.remove('flex');
}

// Utility Functions
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
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'footer.php'; ?>
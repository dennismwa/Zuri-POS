<?php
require_once 'config.php';
requireAuth();

if (!hasPermission('can_view_audit_logs')) {
    header('Location: /403.php');
    exit;
}

$page_title = 'Audit Logs';
$settings = getSettings();

include 'header.php';
?>

<style>
.audit-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.log-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.log-item {
    border-left: 4px solid;
    transition: all 0.2s;
}

.log-item:hover {
    background: #f9fafb;
}

.value-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    background: white;
    border: 1px solid #e5e7eb;
}

.value-row:nth-child(even) {
    background: #f9fafb;
}

.value-label {
    font-weight: 600;
    color: #374151;
    text-transform: capitalize;
}

.value-content {
    color: #6b7280;
    word-break: break-word;
}

.old-value {
    color: #dc2626;
    text-decoration: line-through;
}

.new-value {
    color: #16a34a;
    font-weight: 600;
}

@media (max-width: 768px) {
    .audit-card {
        padding: 1rem;
    }
    
    .value-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
}
</style>

<!-- Page Header -->
<div class="audit-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-clipboard-list mr-2" style="color: <?php echo $settings['primary_color']; ?>"></i>
                System Audit Logs
            </h1>
            <p class="text-sm text-gray-600">Comprehensive trail of all system activities</p>
        </div>
        <button onclick="exportLogs()" 
                class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-semibold transition shadow-lg">
            <i class="fas fa-file-csv mr-2"></i>Export CSV
        </button>
    </div>
</div>

<!-- Filters -->
<div class="audit-card mb-6">
    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 mb-2">From Date</label>
            <input type="date" id="dateFrom" value="<?php echo date('Y-m-01'); ?>" 
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-sm">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 mb-2">To Date</label>
            <input type="date" id="dateTo" value="<?php echo date('Y-m-d'); ?>" 
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-sm">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 mb-2">Action Type</label>
            <select id="actionType" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-sm">
                <option value="">All Types</option>
            </select>
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 mb-2">Category</label>
            <select id="actionCategory" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-sm">
                <option value="">All Categories</option>
            </select>
        </div>
        
        <div class="md:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-2">Search</label>
            <input type="text" id="search" placeholder="Search logs..." 
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-sm">
        </div>
        
        <div class="md:col-span-1 flex items-end">
            <button type="submit" 
                    class="w-full px-4 py-2 rounded-lg font-semibold text-white transition hover:opacity-90"
                    style="background-color: <?php echo $settings['primary_color']; ?>">
                <i class="fas fa-filter"></i>
            </button>
        </div>
    </form>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="audit-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-600 mb-1">Total Logs</p>
                <h3 class="text-2xl font-bold text-gray-900" id="totalLogs">-</h3>
            </div>
            <i class="fas fa-clipboard-list text-3xl text-blue-500"></i>
        </div>
    </div>
    
    <div class="audit-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-600 mb-1">Today</p>
                <h3 class="text-2xl font-bold text-green-600" id="todayLogs">-</h3>
            </div>
            <i class="fas fa-calendar-day text-3xl text-green-500"></i>
        </div>
    </div>
    
    <div class="audit-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-600 mb-1">Active Users</p>
                <h3 class="text-2xl font-bold text-purple-600" id="activeUsers">-</h3>
            </div>
            <i class="fas fa-users text-3xl text-purple-500"></i>
        </div>
    </div>
    
    <div class="audit-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-600 mb-1">Categories</p>
                <h3 class="text-2xl font-bold text-orange-600" id="categoryCount">-</h3>
            </div>
            <i class="fas fa-layer-group text-3xl text-orange-500"></i>
        </div>
    </div>
</div>

<!-- Logs List -->
<div class="audit-card">
    <div id="logsList"></div>
    
    <div id="pagination" class="flex items-center justify-between mt-6 pt-6 border-t hidden">
        <div class="text-sm text-gray-600" id="paginationInfo"></div>
        <div class="flex gap-2" id="paginationButtons"></div>
    </div>
</div>

<!-- Log Details Modal -->
<div id="logDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 p-6 rounded-t-2xl text-white z-10" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Log Details</h3>
                <button onclick="closeLogDetails()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div id="logDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
            </div>
        </div>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
let currentPage = 1;
let totalPages = 1;
let allLogs = {};

// Fields to exclude from display (meta fields)
const excludedFields = ['action', 'id', 'created_at', 'updated_at', 'created_by', 'updated_by'];

// Friendly field names
const fieldLabels = {
    cost_name: 'Cost Name',
    category: 'Category',
    monthly_amount: 'Monthly Amount',
    start_date: 'Start Date',
    end_date: 'End Date',
    description: 'Description',
    status: 'Status',
    name: 'Name',
    email: 'Email',
    phone: 'Phone',
    role: 'Role',
    selling_price: 'Selling Price',
    cost_price: 'Cost Price',
    stock_quantity: 'Stock Quantity',
    reorder_level: 'Reorder Level',
    barcode: 'Barcode',
    sku: 'SKU'
};

document.addEventListener('DOMContentLoaded', function() {
    loadActionTypes();
    loadActionCategories();
    loadLogs();
    
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadLogs();
    });
});

async function loadActionTypes() {
    try {
        const response = await fetch('/api/audit-logs.php?action=get_action_types');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('actionType');
            data.data.types.forEach(type => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = type;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading action types:', error);
    }
}

async function loadActionCategories() {
    try {
        const response = await fetch('/api/audit-logs.php?action=get_action_categories');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('actionCategory');
            data.data.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat.toUpperCase();
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

async function loadLogs() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const actionType = document.getElementById('actionType').value;
    const actionCategory = document.getElementById('actionCategory').value;
    const search = document.getElementById('search').value;
    
    const params = new URLSearchParams({
        action: 'get_logs',
        date_from: dateFrom,
        date_to: dateTo,
        action_type: actionType,
        action_category: actionCategory,
        search: search,
        page: currentPage
    });
    
    document.getElementById('logsList').innerHTML = `
        <div class="flex items-center justify-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl" style="color: ${primaryColor}"></i>
        </div>
    `;
    
    try {
        const response = await fetch(`/api/audit-logs.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            allLogs = {};
            data.data.logs.forEach(log => {
                allLogs[log.id] = log;
            });
            
            renderLogs(data.data.logs);
            updateStats(data.data);
            updatePagination(data.data);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showToast('Failed to load logs', 'error');
    }
}

function renderLogs(logs) {
    const list = document.getElementById('logsList');
    
    if (logs.length === 0) {
        list.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500">No logs found</p>
            </div>
        `;
        return;
    }
    
    const categoryColors = {
        sales: '#10b981',
        inventory: '#3b82f6',
        reports: '#f59e0b',
        admin: '#ef4444',
        finance: '#8b5cf6',
        auth: '#6366f1'
    };
    
    list.innerHTML = logs.map(log => {
        const color = categoryColors[log.action_category] || primaryColor;
        const hasChanges = log.old_values || log.new_values;
        
        return `
            <div class="log-item p-4 mb-3 rounded-lg bg-white border-2 border-gray-100 ${hasChanges ? 'cursor-pointer' : ''}"
                 style="border-left-color: ${color}"
                 ${hasChanges ? `onclick="viewLogDetails(${log.id})"` : ''}>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="log-badge" style="background-color: ${color}20; color: ${color}">
                                ${log.action_type}
                            </span>
                            <span class="log-badge bg-gray-100 text-gray-700">
                                ${log.action_category}
                            </span>
                            ${log.user_name ? `
                            <span class="text-xs text-gray-600">
                                <i class="fas fa-user mr-1"></i>${escapeHtml(log.user_name)}
                            </span>
                            ` : ''}
                        </div>
                        
                        <p class="text-sm font-medium text-gray-900 mb-1">${escapeHtml(log.description)}</p>
                        
                        <div class="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
                            <span>
                                <i class="fas fa-clock mr-1"></i>${new Date(log.created_at).toLocaleString()}
                            </span>
                            ${log.ip_address ? `
                            <span>
                                <i class="fas fa-map-marker-alt mr-1"></i>${log.ip_address}
                            </span>
                            ` : ''}
                            ${log.table_name ? `
                            <span>
                                <i class="fas fa-database mr-1"></i>${log.table_name}
                            </span>
                            ` : ''}
                        </div>
                    </div>
                    
                    ${hasChanges ? `
                    <button class="px-3 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition flex-shrink-0">
                        <i class="fas fa-eye mr-1"></i>Details
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function updateStats(data) {
    document.getElementById('totalLogs').textContent = data.total.toLocaleString();
    
    const today = new Date().toDateString();
    const todayCount = data.logs.filter(log => 
        new Date(log.created_at).toDateString() === today
    ).length;
    document.getElementById('todayLogs').textContent = todayCount;
    
    const uniqueUsers = new Set(data.logs.map(log => log.user_id).filter(id => id));
    document.getElementById('activeUsers').textContent = uniqueUsers.size;
    
    const categories = new Set(data.logs.map(log => log.action_category));
    document.getElementById('categoryCount').textContent = categories.size;
}

function updatePagination(data) {
    const paginationDiv = document.getElementById('pagination');
    const infoDiv = document.getElementById('paginationInfo');
    const buttonsDiv = document.getElementById('paginationButtons');
    
    totalPages = data.total_pages;
    
    if (totalPages <= 1) {
        paginationDiv.classList.add('hidden');
        return;
    }
    
    paginationDiv.classList.remove('hidden');
    
    const start = ((data.page - 1) * data.per_page) + 1;
    const end = Math.min(data.page * data.per_page, data.total);
    
    infoDiv.textContent = `Showing ${start}-${end} of ${data.total} logs`;
    
    let buttons = '';
    
    if (data.page > 1) {
        buttons += `<button onclick="goToPage(${data.page - 1})" 
                           class="px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm">
                        <i class="fas fa-chevron-left"></i>
                    </button>`;
    }
    
    const startPage = Math.max(1, data.page - 2);
    const endPage = Math.min(totalPages, data.page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === data.page) {
            buttons += `<button class="px-4 py-2 rounded-lg font-semibold text-white text-sm"
                               style="background-color: ${primaryColor}">${i}</button>`;
        } else {
            buttons += `<button onclick="goToPage(${i})" 
                               class="px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm">
                            ${i}
                        </button>`;
        }
    }
    
    if (data.page < totalPages) {
        buttons += `<button onclick="goToPage(${data.page + 1})" 
                           class="px-4 py-2 rounded-lg font-semibold text-white transition text-sm"
                           style="background-color: ${primaryColor}">
                        <i class="fas fa-chevron-right"></i>
                    </button>`;
    }
    
    buttonsDiv.innerHTML = buttons;
}

function goToPage(page) {
    currentPage = page;
    loadLogs();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function viewLogDetails(logId) {
    const log = allLogs[logId];
    
    if (!log) {
        showToast('Log not found', 'error');
        return;
    }
    
    document.getElementById('logDetailsModal').classList.remove('hidden');
    document.getElementById('logDetailsModal').classList.add('flex');
    
    renderLogDetails(log);
}

function cleanAndParseValues(values) {
    if (!values) return null;
    
    // Handle string JSON
    let parsed;
    try {
        parsed = typeof values === 'string' ? JSON.parse(values) : values;
    } catch (e) {
        console.error('Failed to parse values:', e);
        return null;
    }
    
    // Remove excluded fields and empty values
    const cleaned = {};
    for (let key in parsed) {
        const value = parsed[key];
        
        // Skip excluded fields
        if (excludedFields.includes(key)) continue;
        
        // Skip empty strings and null, but keep 0 and false
        if (value === '' || value === null || value === undefined) continue;
        
        cleaned[key] = value;
    }
    
    return Object.keys(cleaned).length > 0 ? cleaned : null;
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') {
        return '<em class="text-gray-400">Not set</em>';
    }
    
    // Format dates
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
        return new Date(value).toLocaleDateString();
    }
    
    // Format numbers with currency if it's a price/amount
    if (typeof value === 'number' || !isNaN(value)) {
        return value.toLocaleString();
    }
    
    return escapeHtml(value);
}

function renderLogDetails(log) {
    const content = document.getElementById('logDetailsContent');
    
    const categoryColors = {
        sales: '#10b981',
        inventory: '#3b82f6',
        reports: '#f59e0b',
        admin: '#ef4444',
        finance: '#8b5cf6',
        auth: '#6366f1'
    };
    
    const color = categoryColors[log.action_category] || primaryColor;
    
    let html = `
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Action Type</p>
                    <p class="font-bold text-gray-900">${log.action_type}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Category</p>
                    <p class="font-bold text-gray-900">${log.action_category.toUpperCase()}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">User</p>
                    <p class="font-bold text-gray-900">${log.user_name || 'System'}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Date & Time</p>
                    <p class="font-bold text-gray-900">${new Date(log.created_at).toLocaleString()}</p>
                </div>
            </div>
            
            <div class="p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
                <p class="text-sm font-bold text-blue-900 mb-1">Description</p>
                <p class="text-sm text-blue-800">${escapeHtml(log.description)}</p>
            </div>
    `;
    
    if (log.ip_address || log.user_agent) {
        html += `
            <div class="space-y-2">
                ${log.ip_address ? `
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-map-marker-alt text-gray-500"></i>
                    <span class="text-gray-600">IP Address:</span>
                    <span class="font-mono font-semibold">${log.ip_address}</span>
                </div>
                ` : ''}
                ${log.user_agent ? `
                <div class="flex items-start gap-2 text-xs">
                    <i class="fas fa-desktop text-gray-500 mt-1"></i>
                    <div>
                        <span class="text-gray-600">User Agent:</span>
                        <p class="font-mono text-gray-500 mt-1">${escapeHtml(log.user_agent)}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    // Clean and parse values
    const oldValues = cleanAndParseValues(log.old_values);
    const newValues = cleanAndParseValues(log.new_values);
    
    if (oldValues || newValues) {
        html += `<div class="space-y-4">`;
        html += `<h4 class="font-bold text-gray-900 text-lg">Changes Made</h4>`;
        
        // Get all unique keys from both old and new values
        const allKeys = new Set([
            ...Object.keys(oldValues || {}),
            ...Object.keys(newValues || {})
        ]);
        
        allKeys.forEach(key => {
            const oldVal = oldValues?.[key];
            const newVal = newValues?.[key];
            const label = fieldLabels[key] || key.replace(/_/g, ' ');
            
            // Only show if there's actually a change
            if (oldVal !== newVal) {
                html += `
                    <div class="value-row">
                        <div class="value-label">
                            <i class="fas fa-tag mr-2 text-gray-400"></i>${label}
                        </div>
                        <div class="value-content">
                            ${oldVal !== undefined ? `<div class="old-value mb-2">${formatValue(oldVal)}</div>` : ''}
                            ${newVal !== undefined ? `<div class="new-value">→ ${formatValue(newVal)}</div>` : ''}
                        </div>
                    </div>
                `;
            }
        });
        
        html += `</div>`;
    }
    
    html += `</div>`;
    
    content.innerHTML = html;
}

function closeLogDetails() {
    document.getElementById('logDetailsModal').classList.add('hidden');
    document.getElementById('logDetailsModal').classList.remove('flex');
}

function exportLogs() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const actionType = document.getElementById('actionType').value;
    const actionCategory = document.getElementById('actionCategory').value;
    
    const params = new URLSearchParams({
        action: 'export_csv',
        date_from: dateFrom,
        date_to: dateTo,
        action_type: actionType,
        action_category: actionCategory
    });
    
    window.location.href = `/api/audit-logs.php?${params}`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-[200]`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLogDetails();
    }
});
</script>

<?php include 'footer.php'; ?>

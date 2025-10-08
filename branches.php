<?php
require_once 'config.php';
requireOwner();

$page_title = 'Branch Management';
$settings = getSettings();

include 'header.php';
?>

<style>
.branch-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.branch-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
}

.branch-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.branch-icon {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.gradient-text {
    background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

@media (max-width: 768px) {
    .branch-card {
        padding: 1rem;
    }
    
    .branch-icon {
        width: 3.5rem;
        height: 3.5rem;
        font-size: 1.5rem;
    }
}
</style>

<!-- Page Header -->
<div class="branch-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-store gradient-text mr-3"></i>
                Multi-Store Management
            </h1>
            <p class="text-sm md:text-base text-gray-600">Manage multiple locations and track performance</p>
        </div>
        
        <div class="flex gap-2">
            <button onclick="openBranchModal()" 
                    class="px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg text-sm md:text-base"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-plus mr-2"></i>Add New Branch
            </button>
        </div>
    </div>
</div>

<!-- Debug Info (Remove in production) -->
<div id="debugInfo" class="branch-card mb-6 bg-blue-50 border-2 border-blue-200" style="display: none;">
    <h3 class="font-bold text-blue-900 mb-2">🔍 Debug Information</h3>
    <div id="debugContent" class="text-xs text-blue-800 font-mono"></div>
</div>

<!-- Loading State -->
<div id="loadingState" class="hidden">
    <div class="flex items-center justify-center py-20">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-5xl md:text-6xl mb-4" style="color: <?php echo $settings['primary_color']; ?>"></i>
            <p class="text-lg md:text-xl text-gray-600 font-semibold">Loading branches...</p>
        </div>
    </div>
</div>

<!-- Branches Grid -->
<div id="branchesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
    <!-- Branches will be loaded here via JavaScript -->
</div>

<!-- Empty State -->
<div id="emptyState" class="hidden branch-card text-center py-12 md:py-20">
    <i class="fas fa-store text-6xl md:text-7xl text-gray-300 mb-4"></i>
    <h3 class="text-xl md:text-2xl font-bold text-gray-600 mb-3">No Branches Yet</h3>
    <p class="text-sm md:text-base text-gray-500 mb-6">Start by adding your first branch location</p>
    <button onclick="openBranchModal()" 
            class="px-8 py-4 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg"
            style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
        <i class="fas fa-plus mr-2"></i>Create First Branch
    </button>
</div>

<!-- Branch Modal -->
<div id="branchModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 p-4 md:p-6 rounded-t-2xl text-white z-10" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl md:text-2xl font-bold" id="branchModalTitle">Add Branch</h3>
                    <p class="text-white/80 text-xs md:text-sm">Create a new store location</p>
                </div>
                <button onclick="closeBranchModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl md:text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="branchForm" class="p-4 md:p-6">
            <input type="hidden" id="branchId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">Branch Name *</label>
                    <input type="text" id="branchName" name="name" required 
                           class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base"
                           placeholder="Downtown Branch">
                </div>
                
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">Branch Code *</label>
                    <input type="text" id="branchCode" name="code" required maxlength="20"
                           class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base font-mono uppercase"
                           placeholder="DWN">
                </div>
                
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">Manager</label>
                    <select id="branchManager" name="manager_id" 
                            class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base">
                        <option value="">No Manager</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">Address</label>
                    <textarea id="branchAddress" name="address" rows="2"
                              class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base"
                              placeholder="Street address"></textarea>
                </div>
                
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">City</label>
                    <input type="text" id="branchCity" name="city"
                           class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base"
                           placeholder="Nairobi">
                </div>
                
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2">Phone</label>
                    <input type="tel" id="branchPhone" name="phone"
                           class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl focus:outline-none text-sm md:text-base"
                           placeholder="+254 700 000000">
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeBranchModal()" 
                        class="flex-1 px-4 md:px-6 py-2 md:py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition text-sm md:text-base">
                    Cancel
                </button>
                <button type="submit" id="branchSubmitBtn"
                        class="flex-1 px-4 md:px-6 py-2 md:py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg text-sm md:text-base"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Save Branch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// COMPLETE FIXED JAVASCRIPT FOR branches.php
// Replace the entire <script> section with this

const primaryColor = '<?php echo $settings['primary_color']; ?>';
const currency = '<?php echo $settings['currency']; ?>';
let branches = [];
let users = [];

function getApiUrl(endpoint) {
    // Simple and reliable URL construction
    const protocol = window.location.protocol;
    const host = window.location.host;
    
    // Build clean API URL - no double slashes
    const apiUrl = `${protocol}//${host}/api/${endpoint}`;
    
    console.log('🔗 API URL:', apiUrl);
    return apiUrl;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 Page loaded, initializing...');
    loadBranches();
    loadUsers();
});

// Load branches
async function loadBranches() {
    showLoading();
    console.log('🔄 Starting to load branches...');
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const fullUrl = `${apiUrl}?action=get_branches`;
        
        console.log('📡 Fetching from:', fullUrl);
        
        const response = await fetch(fullUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        console.log('📨 Response status:', response.status, 'OK:', response.ok);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Response error:', errorText);
            throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Non-JSON response:', text.substring(0, 200));
            throw new Error('Server returned non-JSON response');
        }
        
        const data = await response.json();
        console.log('✅ Data received:', data.success, 'Branches:', data.data?.branches?.length);
        
        if (data.success) {
            branches = data.data.branches || [];
            renderBranches();
            showToast(`✅ Loaded ${branches.length} branches`, 'success');
        } else {
            throw new Error(data.message || 'Failed to load branches');
        }
    } catch (error) {
        console.error('❌ Error loading branches:', error);
        showToast('Failed to load: ' + error.message, 'error');
        
        document.getElementById('branchesGrid').innerHTML = `
            <div class="col-span-full branch-card text-center py-12 bg-red-50 border-2 border-red-200">
                <i class="fas fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                <h3 class="text-xl font-bold text-red-800 mb-2">Failed to Load Branches</h3>
                <p class="text-red-600 mb-4">${error.message}</p>
                <button onclick="location.reload()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-redo mr-2"></i>Retry
                </button>
            </div>
        `;
    } finally {
        hideLoading();
    }
}

// Load users
async function loadUsers() {
    try {
        const apiUrl = getApiUrl('users.php');
        console.log('📡 Loading users from:', apiUrl);
        
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            users = data.data.users || [];
            populateManagerDropdown();
            console.log('✅ Loaded', users.length, 'users');
        }
    } catch (error) {
        console.error('❌ Error loading users:', error);
    }
}

// Render branches
function renderBranches() {
    const grid = document.getElementById('branchesGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (!branches || branches.length === 0) {
        grid.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    
    grid.innerHTML = branches.map(branch => `
        <div class="branch-card cursor-pointer" onclick="viewBranchDetails(${branch.id})">
            <div class="flex items-start gap-4 mb-4">
                <div class="branch-icon text-white">
                    <i class="fas fa-store"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-lg md:text-xl text-gray-900 mb-1 truncate">${escapeHtml(branch.name)}</h3>
                    <p class="text-xs md:text-sm text-gray-600 mb-2">${escapeHtml(branch.city || 'N/A')}</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-3 py-1 rounded-full text-xs font-bold ${branch.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${branch.status.toUpperCase()}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                            <i class="fas fa-code mr-1"></i>${escapeHtml(branch.code)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Staff</p>
                    <p class="text-xl font-bold text-gray-900">${branch.staff_count || 0}</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600 mb-1">Stock</p>
                    <p class="text-xl font-bold text-gray-900">${parseInt(branch.total_stock || 0).toLocaleString()}</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button onclick="event.stopPropagation(); editBranch(${branch.id})" 
                        class="flex-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition text-xs md:text-sm">
                    <i class="fas fa-edit mr-1"></i>Edit
                </button>
                ${branch.code !== 'MAIN' ? `
                <button onclick="event.stopPropagation(); deleteBranch(${branch.id}, '${escapeHtml(branch.name).replace(/'/g, "\\'")}' )" 
                        class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-trash"></i>
                </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Populate manager dropdown
function populateManagerDropdown() {
    const select = document.getElementById('branchManager');
    if (select) {
        select.innerHTML = '<option value="">No Manager</option>' + 
            users.map(user => `<option value="${user.id}">${escapeHtml(user.name)}</option>`).join('');
    }
}

// Modal functions
function openBranchModal() {
    document.getElementById('branchModal').classList.remove('hidden');
    document.getElementById('branchModal').classList.add('flex');
    document.getElementById('branchModalTitle').textContent = 'Add Branch';
    document.getElementById('branchForm').reset();
    document.getElementById('branchId').value = '';
}

function closeBranchModal() {
    document.getElementById('branchModal').classList.add('hidden');
    document.getElementById('branchModal').classList.remove('flex');
}

function editBranch(id) {
    const branch = branches.find(b => b.id == id);
    if (!branch) return;
    
    document.getElementById('branchModal').classList.remove('hidden');
    document.getElementById('branchModal').classList.add('flex');
    document.getElementById('branchModalTitle').textContent = 'Edit Branch';
    document.getElementById('branchId').value = branch.id;
    document.getElementById('branchName').value = branch.name;
    document.getElementById('branchCode').value = branch.code;
    document.getElementById('branchManager').value = branch.manager_id || '';
    document.getElementById('branchAddress').value = branch.address || '';
    document.getElementById('branchCity').value = branch.city || '';
    document.getElementById('branchPhone').value = branch.phone || '';
}

// Save Branch
document.getElementById('branchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('branchSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    const formData = new FormData(this);
    formData.append('action', 'save_branch');
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeBranchModal();
            loadBranches();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Branch';
    }
});

// Delete Branch
async function deleteBranch(id, name) {
    if (!confirm(`Delete branch "${name}"?\n\nThis action cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_branch');
    formData.append('id', id);
    
    try {
        const apiUrl = getApiUrl('branches.php');
        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadBranches();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error: ' + error.message, 'error');
    }
}

// Utility Functions
function showLoading() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('branchesGrid').classList.add('hidden');
}

function hideLoading() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('branchesGrid').classList.remove('hidden');
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
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        info: 'info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-4 md:px-6 py-3 rounded-xl shadow-lg z-[200] text-sm md:text-base max-w-md`;
    toast.style.animation = 'slideIn 0.3s ease-out';
    toast.innerHTML = `<i class="fas fa-${icons[type]} mr-2"></i>${message}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function viewBranchDetails(id) {
    window.location.href = `branch-inventory.php?branch_id=${id}`;
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBranchModal();
    }
});
</script>

<style>
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>

<?php include 'footer.php'; ?>
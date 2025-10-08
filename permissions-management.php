<?php
require_once 'config.php';
requireOwner();

$page_title = 'Permissions Management';
$settings = getSettings();

include 'header.php';
?>

<style>
.perm-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.perm-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.user-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.permission-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.375rem;
    border: 2px solid #d1d5db;
    cursor: pointer;
}

.permission-checkbox:checked {
    background-color: <?php echo $settings['primary_color']; ?>;
    border-color: <?php echo $settings['primary_color']; ?>;
}

@media (max-width: 768px) {
    .perm-card {
        padding: 1rem;
    }
    
    .user-avatar {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 1rem;
    }
}
</style>

<!-- Page Header -->
<div class="perm-card mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-user-shield mr-2" style="color: <?php echo $settings['primary_color']; ?>"></i>
                Permissions Management
            </h1>
            <p class="text-sm text-gray-600">Control what users can do in the system</p>
        </div>
    </div>
</div>

<!-- Info Banner -->
<div class="perm-card mb-6 bg-blue-50 border-2 border-blue-200">
    <div class="flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
        <div>
            <h3 class="font-bold text-blue-900 mb-1">About Permissions</h3>
            <p class="text-sm text-blue-800">Owners have all permissions by default. Set granular permissions for sellers and other users to control their access.</p>
        </div>
    </div>
</div>

<!-- Users Grid -->
<div id="usersGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
    <div class="flex items-center justify-center py-12">
        <i class="fas fa-spinner fa-spin text-4xl" style="color: <?php echo $settings['primary_color']; ?>"></i>
    </div>
</div>

<!-- Permissions Modal -->
<div id="permissionsModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 p-4 md:p-6 rounded-t-2xl text-white z-10" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-xl md:text-2xl font-bold" id="modalUserName">User Permissions</h3>
                    <p class="text-white/80 text-xs md:text-sm">Select permissions to grant</p>
                </div>
                <button onclick="closePermissionsModal()" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-xl md:text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="permissionsForm" class="p-4 md:p-6">
            <input type="hidden" id="selectedUserId" name="user_id">
            
            <div id="permissionsList" class="space-y-6"></div>
            
            <div class="flex gap-3 mt-6 pt-6 border-t">
                <button type="button" onclick="closePermissionsModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="savePermissionsBtn"
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg"
                        style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                    <i class="fas fa-save mr-2"></i>Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
let users = [];
let permissions = [];

document.addEventListener('DOMContentLoaded', function() {
    loadPermissions();
    loadUsers();
});

async function loadPermissions() {
    try {
        const response = await fetch('/api/permissions.php?action=get_permissions');
        const data = await response.json();
        
        if (data.success) {
            permissions = data.data.permissions;
        }
    } catch (error) {
        console.error('Error loading permissions:', error);
    }
}

async function loadUsers() {
    try {
        const response = await fetch('/api/permissions.php?action=get_users_permissions');
        const data = await response.json();
        
        if (data.success) {
            users = data.data.users;
            renderUsers();
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showToast('Failed to load users', 'error');
    }
}

function renderUsers() {
    const grid = document.getElementById('usersGrid');
    
    if (users.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-users-slash text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-xl">No users found</p>
            </div>
        `;
        return;
    }
    
    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
    
    grid.innerHTML = users.map((user, index) => {
        const bgColor = colors[index % colors.length];
        const isOwner = user.role === 'owner';
        
        return `
            <div class="perm-card">
                <div class="flex items-start gap-4 mb-4">
                    <div class="user-avatar" style="background: linear-gradient(135deg, ${bgColor} 0%, ${bgColor}dd 100%)">
                        ${user.name.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-lg text-gray-900 truncate">${escapeHtml(user.name)}</h3>
                        <p class="text-sm text-gray-600 truncate">${escapeHtml(user.email)}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="px-3 py-1 text-xs font-bold rounded-full ${isOwner ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'}">
                                ${user.role.toUpperCase()}
                            </span>
                            <span class="px-3 py-1 text-xs font-bold rounded-full ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${user.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Permissions Granted</p>
                        <p class="text-2xl font-bold" style="color: ${bgColor}">${isOwner ? 'ALL' : user.permission_count}</p>
                    </div>
                    ${!isOwner ? `
                    <button onclick="openPermissionsModal(${user.id}, '${escapeHtml(user.name)}')" 
                            class="px-4 py-2 rounded-lg font-semibold text-white transition hover:opacity-90"
                            style="background-color: ${bgColor}">
                        <i class="fas fa-edit mr-2"></i>Manage
                    </button>
                    ` : `
                    <div class="text-xs text-gray-500 text-center">
                        <i class="fas fa-crown text-yellow-500 text-xl mb-1"></i><br>
                        Full Access
                    </div>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

async function openPermissionsModal(userId, userName) {
    document.getElementById('selectedUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName + ' - Permissions';
    
    // Load user's current permissions
    try {
        const response = await fetch(`/api/permissions.php?action=get_user_permissions&user_id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const userPermissions = data.data.permissions.map(p => p.code);
            renderPermissionsList(userPermissions);
        }
    } catch (error) {
        console.error('Error loading user permissions:', error);
        renderPermissionsList([]);
    }
    
    document.getElementById('permissionsModal').classList.remove('hidden');
    document.getElementById('permissionsModal').classList.add('flex');
}

function renderPermissionsList(userPermissions) {
    const permissionsList = document.getElementById('permissionsList');
    
    // Group permissions by category
    const grouped = {};
    permissions.forEach(perm => {
        if (!grouped[perm.category]) {
            grouped[perm.category] = [];
        }
        grouped[perm.category].push(perm);
    });
    
    const categoryIcons = {
        sales: 'fa-shopping-cart',
        inventory: 'fa-boxes',
        reports: 'fa-chart-line',
        admin: 'fa-user-shield',
        finance: 'fa-dollar-sign'
    };
    
    const categoryColors = {
        sales: '#10b981',
        inventory: '#3b82f6',
        reports: '#f59e0b',
        admin: '#ef4444',
        finance: '#8b5cf6'
    };
    
    permissionsList.innerHTML = Object.keys(grouped).map(category => {
        const icon = categoryIcons[category] || 'fa-cog';
        const color = categoryColors[category] || primaryColor;
        
        return `
            <div>
                <h4 class="font-bold text-gray-900 mb-3 flex items-center gap-2 text-sm md:text-base">
                    <i class="fas ${icon}" style="color: ${color}"></i>
                    ${category.toUpperCase()} PERMISSIONS
                </h4>
                <div class="space-y-2">
                    ${grouped[category].map(perm => `
                        <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition">
                            <input type="checkbox" 
                                   class="permission-checkbox mt-1" 
                                   name="permissions[]" 
                                   value="${perm.id}"
                                   ${userPermissions.includes(perm.code) ? 'checked' : ''}>
                            <div class="flex-1">
                                <p class="font-semibold text-sm text-gray-900">${escapeHtml(perm.name)}</p>
                                <p class="text-xs text-gray-600">${escapeHtml(perm.description)}</p>
                            </div>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
}

function closePermissionsModal() {
    document.getElementById('permissionsModal').classList.add('hidden');
    document.getElementById('permissionsModal').classList.remove('flex');
}

document.getElementById('permissionsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('savePermissionsBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    const formData = new FormData(this);
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]:checked');
    const permissionIds = Array.from(checkboxes).map(cb => cb.value);
    
    formData.append('action', 'update_user_permissions');
    formData.append('permission_ids', JSON.stringify(permissionIds));
    
    try {
        const response = await fetch('/api/permissions.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Permissions updated successfully', 'success');
            closePermissionsModal();
            loadUsers();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Permissions';
    }
});

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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePermissionsModal();
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
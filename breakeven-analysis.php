<?php
require_once 'config.php';
requireOwner();

$page_title = 'Break-Even Analysis';
$settings = getSettings();

include 'header.php';
?>

<style>
.be-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.be-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.progress-circle {
    width: 200px;
    height: 200px;
}

@media (max-width: 768px) {
    .be-card {
        padding: 1rem;
    }
    
    .progress-circle {
        width: 150px;
        height: 150px;
    }
}
</style>

<!-- Page Header -->
<div class="be-card mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-chart-line mr-2" style="color: <?php echo $settings['primary_color']; ?>"></i>
                Break-Even Analysis
            </h1>
            <p class="text-sm text-gray-600">Track your path to profitability</p>
        </div>
        
        <div class="flex gap-2">
            <select id="monthSelector" onchange="loadBreakEven()" 
                    class="px-4 py-2 border-2 border-gray-200 rounded-lg font-semibold text-sm">
                <!-- Populated by JavaScript -->
            </select>
            <button onclick="openFixedCostsModal()" 
                    class="px-6 py-3 rounded-xl font-semibold text-white transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-cog mr-2"></i>Fixed Costs
            </button>
        </div>
    </div>
</div>

<!-- Current Month Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Progress Circle -->
    <div class="be-card lg:col-span-1">
        <h3 class="font-bold text-gray-900 mb-4 text-center">Break-Even Progress</h3>
        <div class="flex items-center justify-center">
            <div class="progress-circle relative">
                <svg viewBox="0 0 200 200">
                    <circle cx="100" cy="100" r="90" fill="none" stroke="#e5e7eb" stroke-width="20"/>
                    <circle id="progressCircle" cx="100" cy="100" r="90" fill="none" 
                            stroke="<?php echo $settings['primary_color']; ?>" 
                            stroke-width="20" 
                            stroke-dasharray="565.48" 
                            stroke-dashoffset="565.48"
                            transform="rotate(-90 100 100)"
                            style="transition: stroke-dashoffset 1s ease-in-out"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <p class="text-4xl font-bold" style="color: <?php echo $settings['primary_color']; ?>" id="progressPercent">0%</p>
                        <p class="text-xs text-gray-600 mt-1" id="progressStatus">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="be-card bg-gradient-to-br from-blue-50 to-white">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-dollar-sign text-3xl text-blue-600"></i>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">REVENUE</span>
            </div>
            <p class="text-xs text-gray-600 mb-1">Total Revenue</p>
            <h3 class="text-2xl font-bold text-blue-600" id="totalRevenue">-</h3>
        </div>
        
        <div class="be-card bg-gradient-to-br from-red-50 to-white">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-coins text-3xl text-red-600"></i>
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">COSTS</span>
            </div>
            <p class="text-xs text-gray-600 mb-1">Total Costs</p>
            <h3 class="text-2xl font-bold text-red-600" id="totalCosts">-</h3>
        </div>
        
        <div class="be-card bg-gradient-to-br from-green-50 to-white">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-chart-line text-3xl text-green-600"></i>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-bold">PROFIT</span>
            </div>
            <p class="text-xs text-gray-600 mb-1">Net Profit/Loss</p>
            <h3 class="text-2xl font-bold" id="netProfit">-</h3>
        </div>
        
        <div class="be-card bg-gradient-to-br from-purple-50 to-white">
            <div class="flex items-center justify-between mb-3">
                <i class="fas fa-bullseye text-3xl text-purple-600"></i>
                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-bold">TARGET</span>
            </div>
            <p class="text-xs text-gray-600 mb-1">Break-Even Point</p>
            <h3 class="text-2xl font-bold text-purple-600" id="breakEvenRevenue">-</h3>
        </div>
    </div>
</div>

<!-- Detailed Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Cost Breakdown -->
    <div class="be-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-pie-chart text-orange-500 mr-2"></i>Cost Breakdown
        </h3>
        <div class="space-y-3">
            <div class="p-4 bg-orange-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Fixed Costs</span>
                    <span class="text-lg font-bold text-orange-600" id="fixedCosts">-</span>
                </div>
                <p class="text-xs text-gray-600">Rent, salaries, utilities, etc.</p>
            </div>
            
            <div class="p-4 bg-red-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Variable Costs</span>
                    <span class="text-lg font-bold text-red-600" id="variableCosts">-</span>
                </div>
                <p class="text-xs text-gray-600">Cost of goods sold (COGS)</p>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-900">Contribution Margin</span>
                <span class="text-lg font-bold" style="color: <?php echo $settings['primary_color']; ?>" id="contributionMargin">-</span>
            </div>
            <p class="text-xs text-gray-600 mt-1">Average profit per unit sold</p>
        </div>
    </div>
    
    <!-- Units Breakdown -->
    <div class="be-card">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            <i class="fas fa-boxes text-blue-500 mr-2"></i>Units Analysis
        </h3>
        <div class="space-y-3">
            <div class="p-4 bg-blue-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Units Sold</span>
                    <span class="text-2xl font-bold text-blue-600" id="unitsSold">-</span>
                </div>
                <p class="text-xs text-gray-600">Total items sold this month</p>
            </div>
            
            <div class="p-4 bg-purple-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Break-Even Units</span>
                    <span class="text-2xl font-bold text-purple-600" id="breakEvenUnits">-</span>
                </div>
                <p class="text-xs text-gray-600">Units needed to break even</p>
            </div>
            
            <div class="p-4 bg-green-50 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold text-gray-700">Units Remaining</span>
                    <span class="text-2xl font-bold" id="unitsRemaining">-</span>
                </div>
                <p class="text-xs text-gray-600">To reach break-even point</p>
            </div>
        </div>
    </div>
</div>

<!-- Historical Trend Chart -->
<div class="be-card mb-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">
        <i class="fas fa-chart-area text-green-500 mr-2"></i>Historical Trend
    </h3>
    <div style="height: 350px;">
        <canvas id="historicalChart"></canvas>
    </div>
</div>

<!-- Fixed Costs Management Modal -->
<div id="fixedCostsModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="sticky top-0 p-6 rounded-t-2xl text-white z-10" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-2xl font-bold">Fixed Costs Management</h3>
                    <p class="text-white/80 text-sm">Manage recurring business expenses</p>
                </div>
                <button onclick="closeFixedCostsModal()" class="text-white/80 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <button onclick="openAddCostModal()" 
                    class="w-full mb-6 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90 shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
                <i class="fas fa-plus mr-2"></i>Add Fixed Cost
            </button>
            
            <div id="fixedCostsList"></div>
        </div>
    </div>
</div>

<!-- Add/Edit Cost Modal -->
<div id="costFormModal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl">
        <div class="p-6 rounded-t-2xl text-white" style="background: linear-gradient(135deg, <?php echo $settings['primary_color']; ?> 0%, <?php echo $settings['primary_color']; ?>dd 100%)">
            <h3 class="text-xl font-bold" id="costFormTitle">Add Fixed Cost</h3>
        </div>
        
        <form id="costForm" class="p-6">
            <input type="hidden" id="costId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Cost Name *</label>
                    <input type="text" id="costName" name="cost_name" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl"
                           placeholder="e.g., Office Rent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Category *</label>
                    <select id="costCategory" name="category" required 
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl">
                        <option value="rent">Rent</option>
                        <option value="salaries">Salaries</option>
                        <option value="utilities">Utilities</option>
                        <option value="insurance">Insurance</option>
                        <option value="subscriptions">Subscriptions</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Monthly Amount (<?php echo $settings['currency']; ?>) *</label>
                    <input type="number" id="monthlyAmount" name="monthly_amount" step="0.01" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-bold">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Start Date *</label>
                    <input type="date" id="startDate" name="start_date" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">End Date (Optional)</label>
                    <input type="date" id="endDate" name="end_date" 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                    <textarea id="costDescription" name="description" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeCostFormModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="saveCostBtn"
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-white transition hover:opacity-90"
                        style="background-color: <?php echo $settings['primary_color']; ?>">
                    <i class="fas fa-save mr-2"></i>Save Cost
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const primaryColor = '<?php echo $settings['primary_color']; ?>';
const currency = '<?php echo $settings['currency']; ?>';
let currentMonth = '<?php echo date('Y-m-01'); ?>';
let historicalChart = null;

document.addEventListener('DOMContentLoaded', function() {
    populateMonthSelector();
    loadBreakEven();
    loadHistoricalData();
});

function populateMonthSelector() {
    const select = document.getElementById('monthSelector');
    const today = new Date();
    
    for (let i = 0; i < 12; i++) {
        const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
        const value = date.toISOString().split('T')[0];
        const label = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        if (i === 0) option.selected = true;
        select.appendChild(option);
    }
}

async function loadBreakEven() {
    currentMonth = document.getElementById('monthSelector').value;
    
    try {
        const response = await fetch(`/api/breakeven.php?action=get_breakeven&month=${currentMonth}`);
        const data = await response.json();
        
        if (data.success) {
            updateDashboard(data.data);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error loading break-even data:', error);
        showToast('Failed to load data', 'error');
    }
}

function updateDashboard(data) {
    // Progress circle
    const progress = Math.min(100, data.progress_percentage);
    const circumference = 565.48;
    const offset = circumference - (progress / 100) * circumference;
    document.getElementById('progressCircle').style.strokeDashoffset = offset;
    document.getElementById('progressPercent').textContent = Math.round(progress) + '%';
    
    const achieved = data.breakeven_achieved;
    document.getElementById('progressStatus').textContent = achieved ? 'Break-Even Achieved!' : 'To Break-Even';
    document.getElementById('progressStatus').style.color = achieved ? '#10b981' : '#6b7280';
    
    // Key metrics
    document.getElementById('totalRevenue').textContent = currency + ' ' + parseFloat(data.total_revenue).toLocaleString();
    
    const totalCosts = parseFloat(data.total_fixed_costs) + parseFloat(data.total_variable_costs);
    document.getElementById('totalCosts').textContent = currency + ' ' + totalCosts.toLocaleString();
    
    const profit = parseFloat(data.actual_profit);
    const profitEl = document.getElementById('netProfit');
    profitEl.textContent = currency + ' ' + profit.toLocaleString();
    profitEl.style.color = profit >= 0 ? '#10b981' : '#ef4444';
    
    document.getElementById('breakEvenRevenue').textContent = currency + ' ' + parseFloat(data.breakeven_revenue).toLocaleString();
    
    // Cost breakdown
    document.getElementById('fixedCosts').textContent = currency + ' ' + parseFloat(data.total_fixed_costs).toLocaleString();
    document.getElementById('variableCosts').textContent = currency + ' ' + parseFloat(data.total_variable_costs).toLocaleString();
    document.getElementById('contributionMargin').textContent = currency + ' ' + parseFloat(data.contribution_margin).toFixed(2);
    
    // Units
    document.getElementById('unitsSold').textContent = parseInt(data.total_units_sold).toLocaleString();
    document.getElementById('breakEvenUnits').textContent = parseInt(data.breakeven_units).toLocaleString();
    
    const remaining = Math.max(0, data.breakeven_units - data.total_units_sold);
    const remainingEl = document.getElementById('unitsRemaining');
    remainingEl.textContent = remaining.toLocaleString();
    remainingEl.style.color = remaining === 0 ? '#10b981' : '#f59e0b';
}

async function loadHistoricalData() {
    try {
        const response = await fetch('/api/breakeven.php?action=get_historical&months=6');
        const data = await response.json();
        
        if (data.success && data.data.history.length > 0) {
            renderHistoricalChart(data.data.history);
        }
    } catch (error) {
        console.error('Error loading historical data:', error);
    }
}

function renderHistoricalChart(history) {
    const ctx = document.getElementById('historicalChart');
    
    if (historicalChart) {
        historicalChart.destroy();
    }
    
    historicalChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: history.map(h => new Date(h.period_month).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })),
            datasets: [{
                label: 'Revenue',
                data: history.map(h => parseFloat(h.total_revenue)),
                borderColor: '#10b981',
                backgroundColor: '#10b98120',
                tension: 0.4,
                fill: true
            }, {
                label: 'Break-Even Point',
                data: history.map(h => parseFloat(h.breakeven_revenue)),
                borderColor: primaryColor,
                backgroundColor: primaryColor + '20',
                tension: 0.4,
                fill: true,
                borderDash: [5, 5]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: context => context.dataset.label + ': ' + currency + ' ' + context.parsed.y.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => currency + ' ' + v.toLocaleString() }
                }
            }
        }
    });
}

async function openFixedCostsModal() {
    document.getElementById('fixedCostsModal').classList.remove('hidden');
    document.getElementById('fixedCostsModal').classList.add('flex');
    loadFixedCosts();
}

function closeFixedCostsModal() {
    document.getElementById('fixedCostsModal').classList.add('hidden');
    document.getElementById('fixedCostsModal').classList.remove('flex');
}

async function loadFixedCosts() {
    try {
        const response = await fetch('/api/breakeven.php?action=get_fixed_costs');
        const data = await response.json();
        
        if (data.success) {
            renderFixedCosts(data.data.costs);
        }
    } catch (error) {
        console.error('Error loading fixed costs:', error);
    }
}

function renderFixedCosts(costs) {
    const list = document.getElementById('fixedCostsList');
    
    if (costs.length === 0) {
        list.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No fixed costs yet</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = costs.map(cost => `
        <div class="p-4 mb-3 bg-gray-50 rounded-lg border-2 border-gray-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="font-bold text-gray-900">${escapeHtml(cost.cost_name)}</h4>
                    <p class="text-sm text-gray-600 mt-1">${escapeHtml(cost.description || '')}</p>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded">${cost.category.toUpperCase()}</span>
                        <span class="text-xs text-gray-600">
                            <i class="fas fa-calendar mr-1"></i>${new Date(cost.start_date).toLocaleDateString()}
                            ${cost.end_date ? ' - ' + new Date(cost.end_date).toLocaleDateString() : ' (ongoing)'}
                        </span>
                    </div>
                </div>
                <div class="text-right ml-4">
                    <p class="text-2xl font-bold" style="color: ${primaryColor}">${currency} ${parseFloat(cost.monthly_amount).toLocaleString()}</p>
                    <p class="text-xs text-gray-600">per month</p>
                    <div class="flex gap-2 mt-2">
                        <button onclick='editCost(${JSON.stringify(cost)})' 
                                class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCost(${cost.id}, '${escapeHtml(cost.cost_name)}')" 
                                class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function openAddCostModal() {
    document.getElementById('costFormTitle').textContent = 'Add Fixed Cost';
    document.getElementById('costForm').reset();
    document.getElementById('costId').value = '';
    document.getElementById('costFormModal').classList.remove('hidden');
    document.getElementById('costFormModal').classList.add('flex');
}

function editCost(cost) {
    document.getElementById('costFormTitle').textContent = 'Edit Fixed Cost';
    document.getElementById('costId').value = cost.id;
    document.getElementById('costName').value = cost.cost_name;
    document.getElementById('costCategory').value = cost.category;
    document.getElementById('monthlyAmount').value = cost.monthly_amount;
    document.getElementById('startDate').value = cost.start_date;
    document.getElementById('endDate').value = cost.end_date || '';
    document.getElementById('costDescription').value = cost.description || '';
    
    document.getElementById('costFormModal').classList.remove('hidden');
    document.getElementById('costFormModal').classList.add('flex');
}

function closeCostFormModal() {
    document.getElementById('costFormModal').classList.add('hidden');
    document.getElementById('costFormModal').classList.remove('flex');
}

document.getElementById('costForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('saveCostBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    const formData = new FormData(this);
    formData.append('action', 'save_fixed_cost');
    
    try {
        const response = await fetch('/api/breakeven.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Fixed cost saved successfully', 'success');
            closeCostFormModal();
            loadFixedCosts();
            loadBreakEven();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Cost';
    }
});

async function deleteCost(id, name) {
    if (!confirm(`Delete fixed cost "${name}"?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_fixed_cost');
    formData.append('id', id);
    
    try {
        const response = await fetch('/api/breakeven.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Fixed cost deleted', 'success');
            loadFixedCosts();
            loadBreakEven();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
    }
}

function escapeHtml(text) {
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
</script>

<?php include 'footer.php'; ?>
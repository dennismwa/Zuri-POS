<?php
require_once 'config.php';
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Online - <?php echo $settings['company_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card { transition: all 0.3s; }
        .product-card:hover { transform: translateY(-4px); }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        .slide-in { animation: slideIn 0.3s; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?php echo $settings['logo_path']; ?>" alt="Logo" class="h-12 w-12 object-contain">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900"><?php echo $settings['company_name']; ?></h1>
                        <p class="text-xs text-gray-600">Order Online</p>
                    </div>
                </div>
                <button onclick="toggleCart()" class="relative px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Cart (<span id="cartCount">0</span>)
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Products Section -->
        <div class="lg:col-span-2">
            <!-- Search & Filter -->
            <div class="bg-white rounded-xl shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" id="searchInput" placeholder="Search products..." 
                           class="px-4 py-2 border-2 rounded-lg focus:outline-none focus:border-blue-500">
                    <select id="categoryFilter" onchange="filterProducts()" 
                            class="px-4 py-2 border-2 rounded-lg focus:outline-none">
                        <option value="">All Categories</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="col-span-full flex justify-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Cart Sidebar -->
        <div class="lg:sticky lg:top-24 h-fit">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">Your Cart</h2>
                
                <div id="cartItems" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                    <p class="text-gray-500 text-center py-8">Cart is empty</p>
                </div>

                <div class="border-t pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal:</span>
                        <span id="cartSubtotal" class="font-semibold">KSh 0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Delivery:</span>
                        <span id="cartDelivery" class="font-semibold">KSh 0</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total:</span>
                        <span id="cartTotal" class="text-blue-600">KSh 0</span>
                    </div>
                </div>

                <button onclick="openCheckout()" id="checkoutBtn" disabled 
                        class="w-full mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg font-bold disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-700">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 bg-blue-600 text-white rounded-t-2xl sticky top-0">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-bold">Checkout</h3>
                    <button onclick="closeCheckout()" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <form id="checkoutForm" class="p-6 space-y-4">
                <div>
                    <label class="block font-bold mb-2">Full Name *</label>
                    <input type="text" name="customer_name" required 
                           class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Phone Number *</label>
                        <input type="tel" name="customer_phone" required 
                               placeholder="+254700000000"
                               class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">WhatsApp (Optional)</label>
                        <input type="tel" name="customer_whatsapp" 
                               placeholder="+254700000000"
                               class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-2">Email (Optional)</label>
                    <input type="email" name="customer_email" 
                           class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block font-bold mb-2">Delivery Type *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="delivery_type" value="delivery" checked onchange="toggleDeliveryFields()">
                            <span class="ml-3 font-semibold">🚚 Delivery</span>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="delivery_type" value="pickup" onchange="toggleDeliveryFields()">
                            <span class="ml-3 font-semibold">🏪 Pickup</span>
                        </label>
                    </div>
                </div>

                <div id="deliveryFields">
                    <div>
                        <label class="block font-bold mb-2">Delivery Area *</label>
                        <select name="delivery_area" id="deliveryArea" required onchange="updateDeliveryFee()"
                                class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500">
                            <option value="">Select area...</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label class="block font-bold mb-2">Delivery Address *</label>
                        <textarea name="delivery_address" rows="2" required
                                  class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500"
                                  placeholder="Building name, street, apartment..."></textarea>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Delivery Instructions (Optional)</label>
                        <textarea name="delivery_instructions" rows="2"
                                  class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500"
                                  placeholder="Gate code, landmarks, etc..."></textarea>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-2">Order Notes (Optional)</label>
                    <textarea name="order_notes" rows="2"
                              class="w-full px-4 py-3 border-2 rounded-xl focus:outline-none focus:border-blue-500"
                              placeholder="Special requests..."></textarea>
                </div>

                <button type="submit" id="placeOrderBtn"
                        class="w-full px-6 py-4 bg-blue-600 text-white rounded-xl font-bold text-lg hover:bg-blue-700">
                    <i class="fas fa-check-circle mr-2"></i>Place Order
                </button>
            </form>
        </div>
    </div>

    <script>
    let cart = [];
    let products = [];
    let zones = [];
    let config = {};
    const currency = '<?php echo $settings['currency']; ?>';

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadConfig();
        loadProducts();
        loadZones();
    });

    async function loadConfig() {
        const res = await fetch('/api/online-orders.php?action=get_config');
        const data = await res.json();
        if (data.success) config = data.data.config;
    }

    async function loadProducts() {
        const res = await fetch('/api/online-orders.php?action=get_products');
        const data = await res.json();
        if (data.success) {
            products = data.data.products;
            renderProducts(products);
            populateCategories(data.data.categories);
        }
    }

    async function loadZones() {
        const res = await fetch('/api/online-orders.php?action=get_zones');
        const data = await res.json();
        if (data.success) {
            zones = data.data.zones;
            populateZones();
        }
    }

    function renderProducts(prods) {
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = prods.map(p => `
            <div class="product-card bg-white rounded-xl shadow p-4">
                <div class="aspect-square bg-gray-100 rounded-lg mb-3 flex items-center justify-center">
                    <i class="fas fa-wine-bottle text-4xl text-gray-400"></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">${p.name}</h3>
                <p class="text-sm text-gray-600 mb-2">${p.category_name || 'Uncategorized'}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xl font-bold text-blue-600">${currency} ${parseFloat(p.selling_price).toFixed(0)}</span>
                    <button onclick="addToCart(${p.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">${p.stock_quantity} in stock</p>
            </div>
        `).join('');
    }

    function populateCategories(cats) {
        const select = document.getElementById('categoryFilter');
        select.innerHTML = '<option value="">All Categories</option>' +
            cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }

    function populateZones() {
        const select = document.getElementById('deliveryArea');
        zones.forEach(z => {
            z.areas.forEach(area => {
                const opt = document.createElement('option');
                opt.value = z.id;
                opt.textContent = `${area} (${currency} ${z.delivery_fee})`;
                opt.dataset.fee = z.delivery_fee;
                select.appendChild(opt);
            });
        });
    }

    function addToCart(productId) {
        const product = products.find(p => p.id == productId);
        const existing = cart.find(item => item.id == productId);

        if (existing) {
            if (existing.quantity < product.stock_quantity) {
                existing.quantity++;
            } else {
                alert('Maximum stock reached');
                return;
            }
        } else {
            cart.push({ ...product, quantity: 1 });
        }

        updateCart();
        showToast('Added to cart!', 'success');
    }

    function updateCart() {
        const container = document.getElementById('cartItems');
        const countEl = document.getElementById('cartCount');
        const checkoutBtn = document.getElementById('checkoutBtn');

        countEl.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
        checkoutBtn.disabled = cart.length === 0;

        if (cart.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Cart is empty</p>';
        } else {
            container.innerHTML = cart.map(item => `
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm truncate">${item.name}</p>
                        <p class="text-xs text-gray-600">${currency} ${item.selling_price} × ${item.quantity}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="updateQuantity(${item.id}, -1)" class="w-8 h-8 bg-red-500 text-white rounded">-</button>
                        <span class="font-bold">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, 1)" class="w-8 h-8 bg-green-500 text-white rounded">+</button>
                    </div>
                </div>
            `).join('');
        }

        updateTotals();
    }

    function updateQuantity(productId, change) {
        const item = cart.find(i => i.id == productId);
        if (!item) return;

        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id != productId);
        }

        updateCart();
    }

    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.selling_price * item.quantity), 0);
        const deliveryFee = parseFloat(document.getElementById('deliveryArea')?.selectedOptions[0]?.dataset.fee || 0);
        const total = subtotal + deliveryFee;

        document.getElementById('cartSubtotal').textContent = `${currency} ${subtotal.toFixed(0)}`;
        document.getElementById('cartDelivery').textContent = `${currency} ${deliveryFee.toFixed(0)}`;
        document.getElementById('cartTotal').textContent = `${currency} ${total.toFixed(0)}`;
    }

    function openCheckout() {
        if (cart.length === 0) return;
        document.getElementById('checkoutModal').classList.remove('hidden');
    }

    function closeCheckout() {
        document.getElementById('checkoutModal').classList.add('hidden');
    }

    function toggleDeliveryFields() {
        const type = document.querySelector('input[name="delivery_type"]:checked').value;
        document.getElementById('deliveryFields').style.display = type === 'delivery' ? 'block' : 'none';
        updateTotals();
    }

    function updateDeliveryFee() {
        updateTotals();
    }

    document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('placeOrderBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Placing Order...';

        const formData = new FormData(this);
        formData.append('action', 'place_order');
        formData.append('items', JSON.stringify(cart.map(item => ({
            id: item.id,
            quantity: item.quantity
        }))));
        formData.append('delivery_fee', document.getElementById('deliveryArea')?.selectedOptions[0]?.dataset.fee || 0);

        try {
            const res = await fetch('/api/online-orders.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                alert(`Order placed successfully!\n\nOrder Number: ${data.data.order_number}\n\nWe'll contact you soon!`);
                cart = [];
                updateCart();
                closeCheckout();
                this.reset();
            } else {
                alert(data.message);
            }
        } catch (error) {
            alert('Error placing order');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Place Order';
        }
    });

    function showToast(msg, type) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} slide-in z-50`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>
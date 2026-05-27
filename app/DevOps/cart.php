<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart — Brew & Bean</title>

    <link rel="stylesheet" href="style.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-inner">

            <div class="nav-left">
                <a href="dashboard.php" class="nav-logo">
                    ☕ Brew & Bean
                </a>
            </div>

            <div class="nav-center">
                <h2 class="cart-page-title">Your Cart</h2>
            </div>

            <div class="nav-right">
                <span class="user-name">
                    <?= htmlspecialchars($user['name']) ?>
                </span>

                <a href="dashboard.php" class="btn-back">
                    ← Back
                </a>

                <a href="logout.php" class="btn-logout">
                    Logout
                </a>
            </div>

        </div>
    </nav>

    <main class="cart-page page-enter">

        <div class="cart-container">

            <div id="cartContent"></div>

        </div>

    </main>

    <script>
        const products = <?= json_encode($pdo->query("SELECT id, name, price, image_url")->fetchAll()) ?>;

        // Convert to map for fast lookup
        const productMap = Object.fromEntries(products.map(p => [p.id, p]));

        // ===== CART STATE (SINGLE SOURCE OF TRUTH) =====
        let cart = JSON.parse(sessionStorage.getItem('cbs_cart') || '{}');

        // ===== SAVE CART =====
        function saveCart() {
            sessionStorage.setItem('cbs_cart', JSON.stringify(cart));
        }

        // ===== UPDATE CART =====
        function setQuantity(productId, quantity) {
            if (quantity <= 0) {
                delete cart[productId];
            } else {
                cart[productId] = quantity;
            }

            saveCart();
            renderCart();
        }

        // ===== CALCULATIONS =====
        function getTotals() {
            let subtotal = 0;

            Object.entries(cart).forEach(([id, qty]) => {
                const product = productMap[id];
                if (!product) return;

                subtotal += product.price * qty;
            });

            const tax = subtotal * 0.08;
            const total = subtotal + tax;

            return {
                subtotal,
                tax,
                total
            };
        }

        // ===== RENDER CART =====
        function renderCart() {
            const container = document.getElementById('cartContent');
            const ids = Object.keys(cart);

            // EMPTY CART
            if (ids.length === 0) {
                container.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>Add some items to continue</p>
                <a href="dashboard.php" class="btn btn-primary">Shop Now</a>
            </div>
        `;
                return;
            }

            let itemsHTML = '';

            ids.forEach(id => {
                const product = productMap[id];
                const qty = cart[id];

                if (!product) return;

                const subtotal = product.price * qty;

                itemsHTML += `
            <div class="cart-item">
                <img src="${product.image_url}" class="cart-item-image">

                <div class="cart-item-details">
                    <h3>${product.name}</h3>
                    <p>$${Number(product.price).toFixed(2)}</p>
                </div>

                <div class="cart-item-quantity">
                    <button class="qty-btn" data-id="${id}" data-action="minus">−</button>

                    <input class="qty-input"
                        value="${qty}"
                        data-id="${id}"
                        readonly
                    >

                    <button class="qty-btn" data-id="${id}" data-action="plus">+</button>
                </div>

                <div class="cart-item-subtotal">
                    $${subtotal.toFixed(2)}
                </div>

                <button class="btn-remove" data-id="${id}">×</button>
            </div>
        `;
            });

            const {
                subtotal,
                tax,
                total
            } = getTotals();

            container.innerHTML = `
        <div class="cart-content">

            <div class="cart-items">
                ${itemsHTML}
            </div>

            <div class="cart-summary">
                <h2>Order Summary</h2>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>

                <div class="summary-row">
                    <span>Tax</span>
                    <span>$${tax.toFixed(2)}</span>
                </div>

                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span>$${total.toFixed(2)}</span>
                </div>

                <form id="checkoutForm" action="checkout.php" method="POST">
                    <input type="hidden" name="cart" id="cartData">
                    <button class="btn btn-primary btn-large">Checkout</button>
                </form>

                <a href="dashboard.php" class="btn btn-secondary btn-large">
                    Continue Shopping
                </a>
            </div>

        </div>
    `;

            attachEvents();
        }

        // ===== EVENTS (CLEAN EVENT DELEGATION) =====
        function attachEvents() {

            // PLUS / MINUS
            document.querySelectorAll('.qty-btn').forEach(btn => {
                btn.onclick = () => {
                    const id = btn.dataset.id;
                    const action = btn.dataset.action;

                    let qty = cart[id] || 1;

                    qty = action === 'plus' ? qty + 1 : qty - 1;

                    setQuantity(id, qty);
                };
            });

            // REMOVE
            document.querySelectorAll('.btn-remove').forEach(btn => {
                btn.onclick = () => {
                    setQuantity(btn.dataset.id, 0);
                };
            });

            // CHECKOUT
            document.getElementById('checkoutForm').onsubmit = function() {
                document.getElementById('cartData').value = JSON.stringify(cart);
            };
        }

        // ===== INIT =====
        renderCart();
    </script>
</body>

</html>
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
        // ===== PRODUCTS =====
        const pdo_products = <?php
                                $stmt = $pdo->prepare("
                SELECT id, name, price, image_url
                FROM products
            ");
                                $stmt->execute();

                                echo json_encode($stmt->fetchAll());
                                ?>;

        // ===== PRODUCT MAP =====
        const productMap = {};

        pdo_products.forEach(product => {
            productMap[product.id] = product;
        });

        // ===== CART =====
        let cart = JSON.parse(
            sessionStorage.getItem('cbs_cart') || '[]'
        );

        const cartData = {};

        cart.forEach(item => {
            cartData[item.productId] = item.quantity;
        });

        // ===== RENDER CART =====
        function renderCart() {

            const container =
                document.getElementById('cartContent');

            const productIds =
                Object.keys(cartData);

            // ===== EMPTY CART =====
            if (productIds.length === 0) {

                container.innerHTML = `
                    <div class="empty-cart">

                        <div class="empty-cart-icon">
                            🛒
                        </div>

                        <h2>
                            Your cart is empty
                        </h2>

                        <p>
                            Looks like you haven't added any coffee yet.
                        </p>

                        <a href="dashboard.php"
                           class="btn btn-primary">
                            Continue Shopping
                        </a>

                    </div>
                `;

                return;
            }

            let total = 0;
            let itemsHTML = '';

            // ===== CART ITEMS =====
            productIds.forEach(id => {

                const product = productMap[id];

                if (!product) return;

                const qty = cartData[id];

                const subtotal =
                    product.price * qty;

                total += subtotal;

                itemsHTML += `
                    <div class="cart-item">

                        <img
                            src="${product.image_url}"
                            alt="${product.name}"
                            class="cart-item-image"
                        >

                        <div class="cart-item-details">

                            <h3>
                                ${product.name}
                            </h3>

                            <p class="cart-item-price">
                                $${Number(product.price).toFixed(2)} each
                            </p>

                        </div>

                        <div class="cart-item-quantity">

                            <button
                                class="qty-btn"
                                data-product-id="${id}"
                                data-action="decrease"
                            >
                                −
                            </button>

                            <input
                                type="number"
                                class="qty-input"
                                value="${qty}"
                                min="1"
                                data-product-id="${id}"
                            >

                            <button
                                class="qty-btn"
                                data-product-id="${id}"
                                data-action="increase"
                            >
                                +
                            </button>

                        </div>

                        <div class="cart-item-subtotal">
                            $${subtotal.toFixed(2)}
                        </div>

                        <button
                            class="btn-remove"
                            data-product-id="${id}"
                            title="Remove item"
                        >
                            ×
                        </button>

                    </div>
                `;
            });

            // ===== TOTAL =====
            const tax = total * 0.08;
            const finalTotal = total + tax;

            // ===== HTML =====
            container.innerHTML = `
                <div class="cart-content">

                    <div class="cart-items">
                        ${itemsHTML}
                    </div>

                    <div class="cart-summary">

                        <h2>
                            Order Summary
                        </h2>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$${total.toFixed(2)}</span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>$0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>Tax</span>
                            <span>$${tax.toFixed(2)}</span>
                        </div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>$${finalTotal.toFixed(2)}</span>
                        </div>

                        <form
                            id="checkoutForm"
                            action="checkout.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="cart"
                                id="cartData"
                                value=""
                            >

                            <button
                                type="submit"
                                class="btn btn-primary btn-large"
                            >
                                Proceed to Checkout
                            </button>

                        </form>

                        <a
                            href="dashboard.php"
                            class="btn btn-secondary btn-large"
                        >
                            Continue Shopping
                        </a>

                    </div>

                </div>
            `;

            attachEventListeners();
        }

        // ===== UPDATE CART =====
        function updateCart(productId, quantity) {

            if (quantity > 0) {

                cartData[productId] = quantity;

            } else {

                delete cartData[productId];
            }

            const updatedCart =
                Object.entries(cartData).map(([pid, qty]) => ({
                    productId: pid,
                    quantity: qty
                }));

            sessionStorage.setItem(
                'cbs_cart',
                JSON.stringify(updatedCart)
            );

            renderCart();
        }

        // ===== EVENTS =====
        function attachEventListeners() {

            // Increase / Decrease
            document.querySelectorAll('.qty-btn')
                .forEach(btn => {

                    btn.addEventListener('click', function() {

                        const productId =
                            this.dataset.productId;

                        const input =
                            document.querySelector(
                                `.qty-input[data-product-id="${productId}"]`
                            );

                        let qty =
                            parseInt(input.value);

                        if (this.dataset.action === 'increase') {

                            qty++;

                        } else if (qty > 1) {

                            qty--;
                        }

                        updateCart(productId, qty);
                    });
                });

            // Remove
            document.querySelectorAll('.btn-remove')
                .forEach(btn => {

                    btn.addEventListener('click', function() {

                        const productId =
                            this.dataset.productId;

                        updateCart(productId, 0);
                    });
                });

            // Checkout
            document.getElementById('checkoutForm')
                ?.addEventListener('submit', function() {

                    document.getElementById('cartData').value =
                        JSON.stringify(cartData);
                });
        }

        // ===== START =====
        renderCart();
    </script>

</body>

</html>
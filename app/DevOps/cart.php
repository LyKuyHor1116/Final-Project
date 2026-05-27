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
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-inner">

            <div class="nav-left">
                <a href="dashboard.php" class="nav-logo">☕ Brew & Bean</a>
            </div>

            <div class="nav-center">
                <h2>Your Cart</h2>
            </div>

            <div class="nav-right">
                <span><?= htmlspecialchars($user['name']) ?></span>
                <a href="dashboard.php">← Back</a>
                <a href="logout.php">Logout</a>
            </div>

        </div>
    </nav>

    <main class="cart-page">
        <div id="cartContent"></div>
    </main>

    <script>
        // ===== PRODUCTS (SAFE PHP → JS) =====
        const products = <?= json_encode(
                                $pdo->query("SELECT id, name, price, image_url FROM products")
                                    ->fetchAll(PDO::FETCH_ASSOC),
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                            ) ?>;

        // Map for fast lookup
        const productMap = Object.fromEntries(products.map(p => [p.id, p]));

        // ===== CART STATE =====
        let cart = JSON.parse(sessionStorage.getItem('cbs_cart') || '{}');

        // Save cart
        function saveCart() {
            sessionStorage.setItem('cbs_cart', JSON.stringify(cart));
        }

        // Update cart
        function setQuantity(id, qty) {
            if (qty <= 0) {
                delete cart[id];
            } else {
                cart[id] = qty;
            }

            saveCart();
            renderCart();
        }

        // Calculate totals
        function getTotals() {
            let subtotal = 0;

            for (let id in cart) {
                const product = productMap[id];
                if (!product) continue;

                subtotal += product.price * cart[id];
            }

            const tax = subtotal * 0.08;
            const total = subtotal + tax;

            return {
                subtotal,
                tax,
                total
            };
        }

        // Render cart
        function renderCart() {
            const container = document.getElementById('cartContent');
            const ids = Object.keys(cart);

            if (ids.length === 0) {
                container.innerHTML = `
            <div style="text-align:center;padding:50px;">
                <h2>Your cart is empty 🛒</h2>
                <a href="dashboard.php">Continue Shopping</a>
            </div>
        `;
                return;
            }

            let html = '';

            ids.forEach(id => {
                const p = productMap[id];
                const qty = cart[id];

                if (!p) return;

                html += `
        <div style="display:flex;gap:10px;margin:10px 0;">
            <img src="${p.image_url}" width="80">

            <div style="flex:1;">
                <h3>${p.name}</h3>
                <p>$${p.price}</p>

                <button onclick="setQuantity(${id}, ${qty - 1})">-</button>
                <span>${qty}</span>
                <button onclick="setQuantity(${id}, ${qty + 1})">+</button>
                <button onclick="setQuantity(${id}, 0)">Remove</button>
            </div>

            <div>
                $${(p.price * qty).toFixed(2)}
            </div>
        </div>
        `;
            });

            const {
                subtotal,
                tax,
                total
            } = getTotals();

            container.innerHTML = `
        <h2>Cart</h2>

        ${html}

        <hr>

        <p>Subtotal: $${subtotal.toFixed(2)}</p>
        <p>Tax: $${tax.toFixed(2)}</p>
        <h3>Total: $${total.toFixed(2)}</h3>

        <form method="POST" action="checkout.php">
            <input type="hidden" name="cart" value='${JSON.stringify(cart)}'>
            <button type="submit">Checkout</button>
        </form>
    `;
        }

        // INIT
        renderCart();
    </script>

</body>

</html>
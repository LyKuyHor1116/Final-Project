<?php

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

$error = '';
$success = false;

// ===== GET CART =====
$cart = isset($_POST['cart'])
    ? json_decode($_POST['cart'], true)
    : [];

if (empty($cart)) {
    header('Location: dashboard.php');
    exit;
}

// ===== PRODUCTS =====
$productIds = array_keys($cart);

$placeholders =
    implode(',', array_fill(0, count($productIds), '?'));

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id IN ($placeholders)
");

$stmt->execute($productIds);

$products = $stmt->fetchAll();

// ===== MAP =====
$productMap = [];

$total = 0;

foreach ($products as $product) {

    $productMap[$product['id']] = $product;

    $total +=
        $product['price']
        * $cart[$product['id']];
}

// ===== TOTAL =====
$totalWithTax = $total * 1.08;

// ===== PAYMENT =====
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['process_payment'])
) {

    $address =
        trim($_POST['address'] ?? '');

    $city =
        trim($_POST['city'] ?? '');

    $zip =
        trim($_POST['zip'] ?? '');

    $cardNumber =
        trim($_POST['card_number'] ?? '');

    $cardName =
        trim($_POST['card_name'] ?? '');

    // ===== VALIDATION =====
    if (
        empty($address)
        || empty($city)
        || empty($zip)
        || empty($cardNumber)
        || empty($cardName)
    ) {

        $error =
            'Please fill in all required fields.';
    } elseif (
        strlen(str_replace(' ', '', $cardNumber)) < 13
    ) {

        $error =
            'Please enter a valid card number.';
    } else {

        try {

            $pdo->beginTransaction();

            // ===== INSERT ORDER =====
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    total_amount,
                    status,
                    payment_method,
                    shipping_address,
                    shipping_city,
                    shipping_zip
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user['id'],
                $totalWithTax,
                'completed',
                'credit_card',
                $address,
                $city,
                $zip
            ]);

            $orderId =
                $pdo->lastInsertId();

            // ===== INSERT ITEMS =====
            foreach ($cart as $productId => $quantity) {

                if (isset($productMap[$productId])) {

                    $product =
                        $productMap[$productId];

                    $stmt = $pdo->prepare("
                        INSERT INTO order_items
                        (
                            order_id,
                            product_id,
                            quantity,
                            price_at_purchase
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $orderId,
                        $productId,
                        $quantity,
                        $product['price']
                    ]);
                }
            }

            $pdo->commit();

            $success = true;
        } catch (Exception $e) {

            $pdo->rollBack();

            $error =
                'Payment processing failed. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Checkout — Brew & Bean
    </title>

    <link rel="stylesheet" href="style.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">

        <div class="nav-inner">

            <div class="nav-left">

                <a
                    href="dashboard.php"
                    class="nav-logo">
                    ☕ Brew & Bean
                </a>

            </div>

            <div class="nav-center">

                <h2 class="cart-page-title">
                    Checkout
                </h2>

            </div>

            <div class="nav-right">

                <span class="user-name">
                    <?= htmlspecialchars($user['name']) ?>
                </span>

                <a
                    href="cart.php"
                    class="btn-back">
                    ← Back
                </a>

                <a
                    href="logout.php"
                    class="btn-logout">
                    Logout
                </a>

            </div>

        </div>

    </nav>

    <main class="checkout-page page-enter">

        <?php if ($success): ?>

            <!-- SUCCESS -->
            <div class="checkout-container">

                <div class="success-message">

                    <div class="success-icon">
                        ✓
                    </div>

                    <h1>
                        Order Confirmed!
                    </h1>

                    <p>
                        Thank you for your purchase.
                        Your coffee is on its way! 🎉
                    </p>

                    <div class="order-details">

                        <h2>
                            Order Details
                        </h2>

                        <div class="detail-row">
                            <span>Order Total</span>

                            <span>
                                $<?= number_format($totalWithTax, 2) ?>
                            </span>
                        </div>

                        <div class="detail-row">

                            <span>
                                Delivery Address
                            </span>

                            <span>
                                <?= htmlspecialchars($_POST['address']) ?>,
                                <?= htmlspecialchars($_POST['city']) ?>
                                <?= htmlspecialchars($_POST['zip']) ?>
                            </span>

                        </div>

                    </div>

                    <a
                        href="dashboard.php"
                        class="btn btn-primary btn-large">
                        Continue Shopping
                    </a>

                    <!-- CLEAR CART -->
                    <script>
                        sessionStorage.removeItem('cbs_cart');
                    </script>

                </div>

            </div>

        <?php else: ?>

            <!-- CHECKOUT -->
            <div class="checkout-container">

                <!-- FORM -->
                <div class="checkout-form-section">

                    <h1 class="checkout-title">
                        Checkout
                    </h1>

                    <?php if ($error): ?>

                        <div class="message message-error">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <form
                        method="POST"
                        class="checkout-form"
                        novalidate>

                        <input
                            type="hidden"
                            name="cart"
                            value="<?= htmlspecialchars(json_encode($cart)) ?>">

                        <!-- SHIPPING -->
                        <fieldset class="form-section">

                            <legend class="form-legend">
                                Shipping Address
                            </legend>

                            <div class="form-group">

                                <label for="address">
                                    Street Address
                                </label>

                                <input
                                    type="text"
                                    id="address"
                                    name="address"
                                    placeholder="123 Coffee St."
                                    required>

                            </div>

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="city">
                                        City
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        placeholder="San Francisco"
                                        required>

                                </div>

                                <div class="form-group">

                                    <label for="zip">
                                        ZIP Code
                                    </label>

                                    <input
                                        type="text"
                                        id="zip"
                                        name="zip"
                                        placeholder="94102"
                                        required>

                                </div>

                            </div>

                        </fieldset>

                        <!-- PAYMENT -->
                        <fieldset class="form-section">

                            <legend class="form-legend">
                                Payment Method
                            </legend>

                            <div class="form-group">

                                <label for="card_name">
                                    Cardholder Name
                                </label>

                                <input
                                    type="text"
                                    id="card_name"
                                    name="card_name"
                                    placeholder="John Doe"
                                    required>

                            </div>

                            <div class="form-group">

                                <label for="card_number">
                                    Card Number
                                </label>

                                <input
                                    type="text"
                                    id="card_number"
                                    name="card_number"
                                    placeholder="1234 5678 9012 3456"
                                    maxlength="19"
                                    required>

                            </div>

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="expiry">
                                        Expiry Date
                                    </label>

                                    <input
                                        type="text"
                                        id="expiry"
                                        name="expiry"
                                        placeholder="MM/YY"
                                        maxlength="5">

                                </div>

                                <div class="form-group">

                                    <label for="cvc">
                                        CVC
                                    </label>

                                    <input
                                        type="text"
                                        id="cvc"
                                        name="cvc"
                                        placeholder="123"
                                        maxlength="3">

                                </div>

                            </div>

                        </fieldset>

                        <button
                            type="submit"
                            name="process_payment"
                            class="btn btn-primary btn-large">
                            Pay $<?= number_format($totalWithTax, 2) ?>
                        </button>

                    </form>

                </div>

                <!-- SUMMARY -->
                <div class="checkout-summary">

                    <h2>
                        Order Summary
                    </h2>

                    <div class="summary-items">

                        <?php foreach ($cart as $productId => $quantity): ?>

                            <?php if (isset($productMap[$productId])): ?>

                                <div class="summary-item">

                                    <div class="summary-item-name">

                                        <?= htmlspecialchars($productMap[$productId]['name']) ?>

                                        <span class="summary-item-qty">
                                            × <?= htmlspecialchars($quantity) ?>
                                        </span>

                                    </div>

                                    <div class="summary-item-price">

                                        $<?= number_format(
                                                $productMap[$productId]['price'] * $quantity,
                                                2
                                            ) ?>

                                    </div>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span>Subtotal</span>

                        <span>
                            $<?= number_format($total, 2) ?>
                        </span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>

                    <div class="summary-row">
                        <span>Tax</span>

                        <span>
                            $<?= number_format($total * 0.08, 2) ?>
                        </span>
                    </div>

                    <div class="summary-row summary-total">

                        <span>Total</span>

                        <span>
                            $<?= number_format($totalWithTax, 2) ?>
                        </span>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </main>

    <script>
        // ===== CARD FORMAT =====
        document
            .getElementById('card_number')
            ?.addEventListener('input', function() {

                let value =
                    this.value.replace(/\s/g, '');

                let formatted =
                    value.match(/.{1,4}/g)?.join(' ') ||
                    value;

                this.value = formatted;
            });

        // ===== EXPIRY FORMAT =====
        document
            .getElementById('expiry')
            ?.addEventListener('input', function() {

                let value =
                    this.value.replace(/\D/g, '');

                if (value.length >= 2) {

                    value =
                        value.slice(0, 2) +
                        '/' +
                        value.slice(2, 4);
                }

                this.value = value;
            });
    </script>

</body>

</html>
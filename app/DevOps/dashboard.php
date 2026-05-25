<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDB();

$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category === 'hot' || $category === 'iced') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew & Bean — Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-inner">
            <div class="nav-left">
                <span class="nav-logo">☕ Brew & Bean</span>
            </div>
            <div class="nav-center">
                <div class="search-bar">
                    <span class="search-icon">🔍</span>
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search coffee..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
            </div>
            <div class="nav-right">
                <div class="cart-icon" id="cartIcon">
                    🛒
                    <span class="cart-badge" id="cartBadge">0</span>
                </div>
                <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class="dashboard page-enter">
        <!-- Hero -->
        <section class="hero">
            <h1 class="hero-title">Good <?= date('a') === 'pm' ? 'evening' : 'afternoon' ?>, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> ☕</h1>
            <p class="hero-subtitle">What are you craving today?</p>
        </section>

        <!-- Filters -->
        <div class="filters">
            <a href="dashboard.php<?= $search ? '?search=' . urlencode($search) : '' ?>" class="filter-pill <?= $category === '' ? 'active' : '' ?>">All</a>
            <a href="dashboard.php?category=hot<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-pill <?= $category === 'hot' ? 'active' : '' ?>">🔥 Hot Coffee</a>
            <a href="dashboard.php?category=iced<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-pill <?= $category === 'iced' ? 'active' : '' ?>">🧊 Iced Coffee</a>
        </div>

        <!-- Product Grid -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>No products found. Try a different search or category.</p>
            </div>
        <?php else: ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-id="<?= $product['id'] ?>">
                        <div class="product-image-wrapper">
                            <img
                                src="<?= htmlspecialchars($product['image_url']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="product-image"
                                loading="lazy"
                            >
                            <span class="category-tag tag-<?= $product['category'] ?>">
                                <?= $product['category'] === 'hot' ? '🔥 Hot' : '🧊 Iced' ?>
                            </span>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="product-footer">
                                <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                                <button class="btn-add-cart" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>">+</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- About Section -->
    <section class="about">
        <div class="about-inner">
            <h2 class="about-title">☕ About Brew & Bean</h2>
            <div class="about-divider"></div>
            <p class="about-text">
                We source single-origin beans from small farms around the world and roast them in small batches right here in our shop. Every cup is crafted with care — because great coffee starts with great ingredients and a little bit of love.
            </p>
        </div>
    </section>

    <script>
        // ========== Search (live filter via AJAX) ==========
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                const cat = params.get('category');
                let url = 'dashboard.php';
                const parts = [];
                if (this.value) parts.push('search=' + encodeURIComponent(this.value));
                if (cat) parts.push('category=' + encodeURIComponent(cat));
                if (parts.length) url += '?' + parts.join('&');
                window.location.href = url;
            }, 400);
        });

        // ========== Cart ==========
        let cart = JSON.parse(sessionStorage.getItem('cbs_cart') || '[]');

        function updateCartBadge() {
            const badge = document.getElementById('cartBadge');
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            badge.textContent = count;
        }

        document.querySelectorAll('.btn-add-cart').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const existing = cart.find(item => item.productId === id);
                if (existing) {
                    existing.quantity++;
                } else {
                    cart.push({ productId: id, quantity: 1 });
                }
                sessionStorage.setItem('cbs_cart', JSON.stringify(cart));
                updateCartBadge();

                // Brief animation on cart icon
                const cartIcon = document.getElementById('cartIcon');
                cartIcon.style.transform = 'scale(1.2)';
                setTimeout(() => { cartIcon.style.transform = ''; }, 200);
            });
        });

        updateCartBadge();
    </script>
</body>
</html>

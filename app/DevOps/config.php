<?php
define('DB_NAME', __DIR__ . '/../coffee_shop.db');

function getDB() {
    try {
        $pdo = new PDO(
            "sqlite:" . DB_NAME,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pdo->exec("PRAGMA journal_mode=WAL");
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function initDB() {
    $pdo = getDB();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL CHECK(category IN ('hot', 'iced')),
            price REAL NOT NULL,
            description TEXT NOT NULL,
            image_url TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO products (name, category, price, description, image_url) VALUES
            ('Espresso', 'hot', 3.50, 'Bold, intense single shot pulled to perfection.', 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?w=400'),
            ('Cappuccino', 'hot', 4.50, 'Espresso topped with velvety steamed milk foam.', 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=400'),
            ('Caramel Latte', 'hot', 5.00, 'Smooth latte kissed with house-made caramel drizzle.', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400'),
            ('Flat White', 'hot', 4.75, 'Micro-foam milk over a double ristretto. Rich and silky.', 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=400'),
            ('Iced Americano', 'iced', 4.00, 'Double espresso over ice with cold water. Crisp and clean.', 'https://images.unsplash.com/photo-1517959105821-eaf2591984ca?w=400'),
            ('Cold Brew', 'iced', 5.50, '12-hour slow steep. Naturally sweet and incredibly smooth.', 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=400'),
            ('Iced Latte', 'iced', 5.00, 'Espresso and cold milk over ice. Light and refreshing.', 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=400'),
            ('Frappuccino', 'iced', 6.00, 'Blended coffee, ice, and cream. Sweet, cold perfection.', 'https://images.unsplash.com/photo-1570197788417-0e82375c9371?w=400')
        ");
    }
}

session_start();

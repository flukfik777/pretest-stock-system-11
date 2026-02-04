<?php
require 'db.php';

try {
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'products' created successfully (or already exists).<br>";

    // Create users table
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlUsers);
    echo "Table 'users' created successfully (or already exists).<br>";

    // Insert dummy users if empty
    $checkUsersSql = "SELECT COUNT(*) FROM users";
    $stmtUsers = $pdo->query($checkUsersSql);
    $countUsers = $stmtUsers->fetchColumn();

    if ($countUsers == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $userPass = password_hash('user123', PASSWORD_DEFAULT);
        
        $insertUsersSql = "INSERT INTO users (username, password, role) VALUES 
            ('admin', '$adminPass', 'admin'),
            ('user', '$userPass', 'user');";
        $pdo->exec($insertUsersSql);
        echo "Inserted initial dummy users (admin/admin123, user/user123).<br>";
    }

    // Insert dummy data if empty
    $checkSql = "SELECT COUNT(*) FROM products";
    $stmt = $pdo->query($checkSql);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $insertSql = "INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES 
            ('Intel Core i9-14900K', 'CPU', 24900.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=CPU'),
            ('RTX 4090 ROG Strix', 'GPU', 75000.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'),
            ('Corsair Dominator 32GB', 'RAM', 6500.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'),
            ('Samsung 990 Pro 1TB', 'Storage', 4500.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD');";
        $pdo->exec($insertSql);
        echo "Inserted initial dummy data.<br>";
    }

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>

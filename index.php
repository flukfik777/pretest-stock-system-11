<?php
require 'db.php';
require 'auth.php';

// Auto-init DB (Moved up to ensure it runs before login check)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert dummy products if empty
    $checkProd = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($checkProd == 0) {
        $insertProdSql = "INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES 
            ('Intel Core i9-14900K', 'CPU', 24900.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=CPU'),
            ('AMD Ryzen 9 7950X', 'CPU', 22500.00, 8, 'https://placehold.co/300x300/1a1a1a/00eeff?text=CPU'),
            ('RTX 4090 ROG Strix', 'GPU', 75000.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'),
            ('RTX 4080 Super TUF', 'GPU', 42000.00, 12, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'),
            ('RX 7900 XTX Nitro+', 'GPU', 38500.00, 7, 'https://placehold.co/300x300/1a1a1a/ff3300?text=GPU'),
            ('Corsair Dominator 32GB', 'RAM', 6500.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'),
            ('Kingston FURY Beast 16GB', 'RAM', 2400.00, 35, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'),
            ('Samsung 990 Pro 1TB', 'Storage', 4500.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'),
            ('WD Black SN850X 2TB', 'Storage', 6200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'),
            ('NZXT H7 Flow Black', 'Case', 4200.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'),
            ('Lian Li O11 Dynamic', 'Case', 5500.00, 8, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'),
            ('Corsair RM850e 850W', 'Power Supply', 4800.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'),
            ('ASUS ROG Thor 1000W', 'Power Supply', 12500.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'),
            ('ROG Ryujin III 360', 'Cooling', 13900.00, 6, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler'),
            ('Noctua NH-D15 chromax', 'Cooling', 4200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler');";
        $pdo->exec($insertProdSql);
    }

    // Auto-create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert initial users if empty
    $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($check == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $userPass = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES 
            ('admin', '$adminPass', 'admin'),
            ('user', '$userPass', 'user')");
    }

    // Auto-create orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'paid', 'shipped') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Auto-create order_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

} catch (PDOException $e) { /* Ignore if exists */ }

// Initialize Cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Enforce login
requireLogin();
$currentUser = getCurrentUser();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            requireAdmin();
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['category'],
                $_POST['price'],
                $_POST['stock_quantity'],
                $_POST['image_url'] ?: 'https://placehold.co/300x300?text=No+Image'
            ]);
        } elseif ($_POST['action'] === 'delete') {
            requireAdmin();
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        } elseif ($_POST['action'] === 'add_to_cart') {
            $product_id = $_POST['id'];
            if (!isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = 1;
            } else {
                $_SESSION['cart'][$product_id]++;
            }
        }
        header("Location: index.php");
        exit;
    }
}

// Cart Count
$cartCount = array_sum($_SESSION['cart']);

// Fetch Products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$totalItems = count($products);
$lowStock = 0;
$totalValue = 0;

foreach ($products as $p) {
    if ($p['stock_quantity'] < 5) $lowStock++;
    $totalValue += $p['price'] * $p['stock_quantity'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบสต็อกร้านประกอบคอม - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>ร้านประกอบคอมเทพ</h1>
            <span style="color: var(--text-secondary);">ระบบจัดการสินค้าในคลัง</span>
        </div>
        <div style="text-align: right;">
            <div style="color: var(--accent-color); font-weight: 600;">
                <?php echo htmlspecialchars($currentUser['username']); ?> 
                <span style="color: var(--text-secondary); font-size: 0.8em; font-weight: normal;">
                    (<?php echo ucfirst($currentUser['role']); ?>)
                </span>
            </div>
            <div style="margin-top: 5px;">
                <a href="orders.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9em; margin-right: 15px;">ออเดอร์ของฉัน</a>
                <a href="cart.php" style="color: var(--accent-color); text-decoration: none; font-size: 0.9em; font-weight: 600; margin-right: 15px;">
                    ตะกร้า (<?php echo $cartCount; ?>)
                </a>
                <a href="profile.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9em; margin-right: 15px;">โปรไฟล์ของฉัน</a>
                <?php if (isAdmin()): ?>
                    <a href="admin_orders.php" style="color: var(--accent-color); text-decoration: none; font-size: 0.9em; margin-right: 15px;">Manage Orders</a>
                    <a href="admin.php" style="color: var(--accent-color); text-decoration: none; font-size: 0.9em; margin-right: 15px;">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" style="color: var(--danger-color); text-decoration: none; font-size: 0.9em;">ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <!-- Stats -->
    <div class="stats-panel">
        <div class="stat-card">
            <div>สินค้าทั้งหมด</div>
            <div class="stat-number"><?php echo $totalItems; ?></div>
        </div>
        <div class="stat-card">
            <div>มูลค่ารวม (บาท)</div>
            <div class="stat-number"><?php echo number_format($totalValue); ?></div>
        </div>
        <div class="stat-card">
            <div>สินค้าใกล้หมด</div>
            <div class="stat-number" style="color: <?php echo $lowStock > 0 ? 'var(--danger-color)' : 'var(--accent-color)'; ?>">
                <?php echo $lowStock; ?>
            </div>
        </div>
    </div>

    <!-- Actions (Admin Only) -->
    <?php if (isAdmin()): ?>
    <div class="action-bar">
        <button class="btn" onclick="openModal()">+ เพิ่มสินค้าใหม่</button>
    </div>
    <?php endif; ?>

    <!-- Product Grid -->
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
            <div class="product-info">
                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                <div class="product-price">฿<?php echo number_format($product['price']); ?></div>
                
                <div class="product-stock">
                    <span class="<?php echo $product['stock_quantity'] < 5 ? 'low-stock' : ''; ?>">
                        คงเหลือ: <?php echo $product['stock_quantity']; ?> ชิ้น
                    </span>
                    <div style="display: flex; gap: 5px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.8em;">ใส่ตะกร้า</button>
                        </form>
                        <?php if (isAdmin()): ?>
                        <form method="POST" onsubmit="return confirm('ยืนยันการลบสินค้า?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8em;">ลบ</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
            <h2>ยังไม่มีสินค้าในระบบ</h2>
            <p>ลองกดปุ่ม "เพิ่มสินค้าใหม่" ด้านบนเพื่อเริ่มใช้งาน</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for Add Product -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="color: var(--accent-color);">เพิ่มสินค้าใหม่</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>ชื่อสินค้า</label>
                <input type="text" name="name" required placeholder="เช่น RTX 4090">
            </div>

            <div class="form-group">
                <label>หมวดหมู่</label>
                <select name="category" required>
                    <option value="CPU">CPU</option>
                    <option value="GPU">GPU (การ์ดจอ)</option>
                    <option value="RAM">RAM</option>
                    <option value="Mainboard">Mainboard</option>
                    <option value="Storage">SSD/HDD</option>
                    <option value="Power Supply">Power Supply</option>
                    <option value="Case">Case</option>
                    <option value="Cooling">Cooling</option>
                    <option value="Accessory">อุปกรณ์เสริม</option>
                </select>
            </div>

            <div class="form-group">
                <label>ราคา (บาท)</label>
                <input type="number" name="price" step="0.01" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label>จำนวนในสต็อก</label>
                <input type="number" name="stock_quantity" required placeholder="0">
            </div>

            <div class="form-group">
                <label>ลิงก์รูปภาพ (URL)</label>
                <input type="url" name="image_url" placeholder="https:// example.com/image.jpg">
            </div>

            <div class="text-right">
                <button type="submit" class="btn">บันทึกสินค้า</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('addModal').style.display = 'none';
    }

    // Close modal if clicked outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('addModal')) {
            closeModal();
        }
    }
</script>

</body>
</html>
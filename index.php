<?php
require 'db.php';

// Auto-init DB for convenience
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
} catch (PDOException $e) { /* Ignore if exists */ }

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['category'],
                $_POST['price'],
                $_POST['stock_quantity'],
                $_POST['image_url'] ?: 'https://placehold.co/300x300?text=No+Image'
            ]);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
        header("Location: index.php");
        exit;
    }
}

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
        <div>
            <span style="color: var(--accent-color);">Admin: User</span>
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

    <!-- Actions -->
    <div class="action-bar">
        <button class="btn" onclick="openModal()">+ เพิ่มสินค้าใหม่</button>
    </div>

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
                    <form method="POST" onsubmit="return confirm('ยืนยันการลบสินค้า?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8em;">ลบ</button>
                    </form>
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
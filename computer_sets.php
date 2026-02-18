<?php
require 'db.php';
require 'auth.php';

// Initialize Cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Enforce login
requireLogin();
$currentUser = getCurrentUser();

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = $_POST['id'];
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 1;
    } else {
        $_SESSION['cart'][$product_id]++;
    }
    header("Location: computer_sets.php?added=1");
    exit;
}

// Fetch Computer Sets
$sets = $pdo->query("SELECT * FROM products WHERE category = 'Computer Set' ORDER BY price ASC")->fetchAll();
$cartCount = array_sum($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชุดคอมพร้อมเล่น - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .set-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        .set-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .set-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 255, 0, 0.1);
            border-color: var(--accent-color);
        }
        .set-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--accent-color);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8em;
            z-index: 10;
        }
        .set-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .set-content {
            padding: 25px;
        }
        .set-name {
            font-size: 1.4em;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
        }
        .set-price {
            font-size: 1.8em;
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        .set-features {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        .set-features li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .set-features li::before {
            content: '✓';
            color: var(--accent-color);
            font-weight: bold;
        }
        .btn-large {
            width: 100%;
            padding: 15px;
            font-size: 1.1em;
            font-weight: 600;
        }
        .success-toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--accent-color);
            color: #000;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.5s ease-out;
            z-index: 1000;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="container">
    <?php include 'navbar.php'; ?>

    <?php if (isset($_GET['added'])): ?>
        <div class="success-toast" id="toast">เพิ่มลงตะกร้าเรียบร้อยแล้ว!</div>
        <script>setTimeout(() => document.getElementById('toast').style.display='none', 3000);</script>
    <?php endif; ?>

    <div class="set-grid">
        <?php foreach ($sets as $set): ?>
        <div class="set-card">
            <div class="set-badge">HOT SET</div>
            <img src="<?php echo htmlspecialchars($set['image_url']); ?>" alt="<?php echo htmlspecialchars($set['name']); ?>" class="set-image">
            <div class="set-content">
                <div class="set-name"><?php echo htmlspecialchars($set['name']); ?></div>
                <div class="set-price">฿<?php echo number_format($set['price']); ?></div>
                
                <ul class="set-features">
                    <?php 
                    // Mock features based on set name for demo
                    if (strpos($set['name'], 'Starter') !== false) {
                        echo "<li>Intel Core i5 + GTX 1650</li><li>RAM 16GB RGB</li><li>SSD 500GB NVMe</li><li>Monitor 24\" 75Hz</li>";
                    } elseif (strpos($set['name'], 'Pro') !== false) {
                        echo "<li>Intel Core i7 + RTX 4060</li><li>RAM 32GB RGB</li><li>SSD 1TB NVMe</li><li>Monitor 27\" 144Hz</li>";
                    } else {
                        echo "<li>Intel Core i9 + RTX 4080</li><li>RAM 64GB DDR5</li><li>SSD 2TB Gen4</li><li>Dual Monitor 27\" 2K</li>";
                    }
                    ?>
                    <li>ประกันศูนย์ 3 ปีเต็ม</li>
                    <li>บริการติดตั้งและจัดส่งฟรี</li>
                </ul>

                <form method="POST">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="id" value="<?php echo $set['id']; ?>">
                    <button type="submit" class="btn btn-large">สั่งซื้อเซ็ตนี้</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($sets)): ?>
        <div style="text-align: center; padding: 100px; color: var(--text-secondary);">
            <h2>ขออภัย ขณะนี้ยังไม่มีชุดคอมพิวเตอร์จัดเซ็ต</h2>
            <p>กรุณากลับมาตรวจสอบใหม่อีกครั้งในภายหลัง</p>
            <br>
            <a href="index.php" class="btn">ไปที่ร้านค้าทั่วไป</a>
        </div>
    <?php endif; ?>

    <div style="margin-top: 60px; text-align: center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 15px;">
        <h3 style="color: var(--accent-color); margin-bottom: 15px;">ทำไมต้องเลือกชุดคอมจากเรา?</h3>
        <p style="color: var(--text-secondary); max-width: 700px; margin: 0 auto; line-height: 1.6;">
            เราคัดสรรอุปกรณ์ทุกชิ้นที่มีคุณภาพสูงและเข้ากันได้อย่างสมบูรณ์แบบ ผ่านการทดสอบ Stress Test นานกว่า 24 ชั่วโมงก่อนส่งมอบ 
            พร้อมบริการดูแลหลังการขายโดยทีมงานมืออาชีพ
        </p>
    </div>
</div>

</body>
</html>

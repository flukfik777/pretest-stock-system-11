<?php
require 'db.php';
require 'auth.php';

requireLogin();
$currentUser = getCurrentUser();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_qty') {
            $product_id = $_POST['id'];
            $qty = (int)$_POST['quantity'];
            if ($qty > 0) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        } elseif ($_POST['action'] === 'remove') {
            unset($_SESSION['cart'][$_POST['id']]);
        } elseif ($_POST['action'] === 'checkout') {
            if (empty($_SESSION['cart'])) {
                $error = "ตะกร้าสินค้าว่างเปล่า";
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $total = 0;
                    $cart_items = [];
                    
                    // Validate stock and calculate total
                    foreach ($_SESSION['cart'] as $id => $qty) {
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
                        $stmt->execute([$id]);
                        $product = $stmt->fetch();
                        
                        if (!$product || $product['stock_quantity'] < $qty) {
                            throw new Exception("สินค้า {$product['name']} มีไม่เพียงพอ (เหลือ {$product['stock_quantity']} ช้น)");
                        }
                        
                        $item_total = $product['price'] * $qty;
                        $total += $item_total;
                        $cart_items[] = [
                            'id' => $id,
                            'qty' => $qty,
                            'price' => $product['price']
                        ];
                    }
                    
                    // Create Order
                    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'paid')");
                    $stmt->execute([$_SESSION['user_id'], $total]);
                    $order_id = $pdo->lastInsertId();
                    
                    // Create Order Items and Update Stock
                    foreach ($cart_items as $item) {
                        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
                        
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                        $stmt->execute([$item['qty'], $item['id']]);
                    }
                    
                    $pdo->commit();
                    $_SESSION['cart'] = [];
                    $message = "สั่งซื้อและชำระเงินเรียบร้อยแล้ว! รหัสคำสั่งซื้อ: #$order_id";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "ไม่สามารถดำเนินการสั่งซื้อได้: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Cart Data
$cart_products = [];
$grand_total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $total = $p['price'] * $qty;
        $grand_total += $total;
        $cart_products[] = array_merge($p, ['qty' => $qty, 'total' => $total]);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .cart-table th { background: rgba(255, 255, 255, 0.1); color: var(--accent-color); }
        .qty-input {
            width: 60px;
            padding: 5px;
            background: #333;
            border: 1px solid #555;
            color: #fff;
            border-radius: 4px;
            text-align: center;
        }
        .cart-summary {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            text-align: right;
        }
        .grand-total {
            font-size: 1.5em;
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; }
        .alert-danger { background: rgba(255, 0, 0, 0.1); border: 1px solid var(--danger-color); color: var(--danger-color); }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>ตะกร้าสินค้า</h1>
            <span style="color: var(--text-secondary);">ตรวจสอบสินค้าและชำระเงิน</span>
        </div>
        <div>
            <a href="profile.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9em; margin-right: 15px;">โปรไฟล์ของฉัน</a>
            <a href="index.php" class="btn" style="background: transparent; border: 1px solid var(--accent-color); margin-right: 10px;">ซื้อสินค้าเพิ่ม</a>
            <a href="logout.php" style="color: var(--danger-color); text-decoration: none;">ออกจากระบบ</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($cart_products)): ?>
        <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
            <h2>ตะกร้าสินค้าของคุณว่างเปล่า</h2>
            <p>ไปเลือกซื้อสินค้ากันเถอะ!</p>
            <br>
            <a href="index.php" class="btn">ไปที่หน้าร้านค้า</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>ราคา/ชิ้น</th>
                    <th>จำนวน</th>
                    <th>รวม</th>
                    <th>การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_products as $item): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" width="50" style="border-radius: 5px;">
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="font-size: 0.8em; color: var(--text-secondary);"><?php echo htmlspecialchars($item['category']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>฿<?php echo number_format($item['price']); ?></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 5px;">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="qty-input">
                            <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.8em;">แก้</button>
                        </form>
                    </td>
                    <td>฿<?php echo number_format($item['total']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8em;">ลบ</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="grand-total">ยอดรวมสุทธิ: ฿<?php echo number_format($grand_total); ?></div>
            <form method="POST">
                <input type="hidden" name="action" value="checkout">
                <button type="submit" class="btn" style="padding: 15px 40px; font-size: 1.1em; font-weight: 600;" onclick="return confirm('ยืนยันการสั่งซื้อและชำระเงิน?');">
                    ยืนยันการสั่งซื้อและชำระเงิน
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

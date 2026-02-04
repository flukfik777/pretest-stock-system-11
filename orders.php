<?php
require 'db.php';
require 'auth.php';

requireLogin();
$currentUser = getCurrentUser();

// Fetch Orders for the user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Handle Detail view if requested
$selectedOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $selectedOrder = $stmt->fetch();
    
    if ($selectedOrder) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$selectedOrder['id']]);
        $orderItems = $stmt->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออเดอร์ของฉัน - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .status-paid { background: rgba(0, 255, 0, 0.1); color: #00ff00; border: 1px solid #00ff00; }
        
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            margin-top: 20px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>ออเดอร์ของฉัน</h1>
            <span style="color: var(--text-secondary);">ประวัติการสั่งซื้อทั้งหมด</span>
        </div>
        <div>
            <?php if (isAdmin()): ?>
                <a href="admin_orders.php" class="btn" style="background: var(--text-secondary); color: #000; margin-right: 10px;">📦 จัดการออเดอร์ทั้งหมด</a>
            <?php endif; ?>
            <a href="index.php" class="btn" style="background: transparent; border: 1px solid var(--accent-color); margin-right: 10px;">กลับหน้าหลัก</a>
            <a href="logout.php" style="color: var(--danger-color); text-decoration: none;">ออกจากระบบ</a>
        </div>
    </header>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
            <h2>คุณยังไม่มีประวัติการสั่งซื้อ</h2>
            <br>
            <a href="index.php" class="btn">เริ่มช้อปปิ้งเลย</a>
        </div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div>
                    <div style="font-weight: 600; font-size: 1.1em; color: var(--accent-color);">Order #<?php echo $order['id']; ?></div>
                    <div style="font-size: 0.9em; color: var(--text-secondary);"><?php echo $order['created_at']; ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600; margin-bottom: 5px;">฿<?php echo number_format($order['total_amount']); ?></div>
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo $order['status'] === 'paid' ? 'ชำระเงินแล้ว' : ucfirst($order['status']); ?>
                    </span>
                    <a href="?id=<?php echo $order['id']; ?>" class="btn" style="padding: 5px 15px; font-size: 0.8em; margin-left: 10px; background: transparent; border: 1px solid var(--accent-color);">ดูรายละเอียด</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Detail Modal (Fake Modal using display) -->
    <?php if ($selectedOrder): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <a href="orders.php" class="close" style="text-decoration: none;">&times;</a>
            <h2 style="color: var(--accent-color);">รายละเอียดออเดอร์ #<?php echo $selectedOrder['id']; ?></h2>
            <div style="color: var(--text-secondary); margin-bottom: 20px;">วันที่: <?php echo $selectedOrder['created_at']; ?></div>
            
            <div class="modal-body">
                <?php foreach ($orderItems as $item): ?>
                <div class="item-row">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" width="40" style="border-radius: 5px;">
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="font-size: 0.8em; color: var(--text-secondary);">฿<?php echo number_format($item['price']); ?> x <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 600;">฿<?php echo number_format($item['price'] * $item['quantity']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px; text-align: right; border-top: 2px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <div style="color: var(--text-secondary);">ยอดรวมทั้งหมด</div>
                <div style="font-size: 1.5em; color: var(--accent-color); font-weight: 600;">฿<?php echo number_format($selectedOrder['total_amount']); ?></div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="orders.php" class="btn">ปิดหน้าต่างนี้</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>

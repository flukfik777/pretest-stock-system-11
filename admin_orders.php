<?php
require 'db.php';
require 'auth.php';

// Only admins can access this page
requireAdmin();

$currentUser = getCurrentUser();

// Fetch all orders with usernames
$sql = "
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
";
$orders = $pdo->query($sql)->fetchAll();

// Handle Detail view if requested
$selectedOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$_GET['id']]);
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
    <title>จัดการออเดอร์ - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .order-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }
        .order-table th, .order-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .order-table th { background: rgba(255, 255, 255, 0.1); color: var(--accent-color); }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .status-paid { background: rgba(0, 255, 0, 0.1); color: #00ff00; border: 1px solid #00ff00; }
        
        .modal-body {
            max-height: 60vh;
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

<div class="container" style="max-width: 1100px;">
    <header>
        <div>
            <h1>จัดการการสั่งซื้อ</h1>
            <span style="color: var(--text-secondary);">ตรวจสอบรายการสั่งซื้อทั้งหมดจาก User</span>
        </div>
        <div>
            <a href="profile.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9em; margin-right: 15px;">โปรไฟล์ของฉัน</a>
            <a href="admin.php" class="btn" style="background: transparent; border: 1px solid var(--accent-color); margin-right: 10px;">กลับหน้า Admin</a>
            <a href="logout.php" style="color: var(--danger-color); text-decoration: none;">ออกจากระบบ</a>
        </div>
    </header>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; color: var(--text-secondary);">
            <h2>ยังไม่มีรายการสั่งซื้อในระบบ</h2>
        </div>
    <?php else: ?>
        <table class="order-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>การกระทำ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td style="font-weight: 600; color: var(--accent-color);"><?php echo htmlspecialchars($order['username']); ?></td>
                    <td>฿<?php echo number_format($order['total_amount']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo $order['status'] === 'paid' ? 'ชำระเงินแล้ว' : ucfirst($order['status']); ?>
                        </span>
                    </td>
                    <td style="font-size: 0.9em; color: var(--text-secondary);"><?php echo $order['created_at']; ?></td>
                    <td>
                        <a href="?id=<?php echo $order['id']; ?>" class="btn" style="padding: 5px 15px; font-size: 0.8em; background: transparent; border: 1px solid var(--accent-color);">ดูรายละเอียด</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Detail Modal -->
    <?php if ($selectedOrder): ?>
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <a href="admin_orders.php" class="close" style="text-decoration: none;">&times;</a>
            <h2 style="color: var(--accent-color);">รายละเอียดออเดอร์ #<?php echo $selectedOrder['id']; ?></h2>
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <div style="color: var(--text-secondary);">
                    สั่งซื้อโดย: <strong style="color: #fff;"><?php echo htmlspecialchars($selectedOrder['username']); ?></strong><br>
                    วันที่: <?php echo $selectedOrder['created_at']; ?>
                </div>
                <div style="text-align: right;">
                    สถานะ: <span class="status-badge status-<?php echo $selectedOrder['status']; ?>">
                        <?php echo $selectedOrder['status'] === 'paid' ? 'ชำระเงินแล้ว' : ucfirst($selectedOrder['status']); ?>
                    </span>
                </div>
            </div>
            
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
                <a href="admin_orders.php" class="btn">ปิดหน้าต่างนี้</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>

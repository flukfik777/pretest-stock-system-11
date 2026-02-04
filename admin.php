<?php
require 'db.php';
require 'auth.php';

// Only admins can access this page
requireAdmin();

$currentUser = getCurrentUser();
$message = '';
$error = '';

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_user') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $password, $role]);
                $message = "เพิ่มผู้ใช้ '$username' เรียบร้อยแล้ว";
            } catch (PDOException $e) {
                $error = "ไม่สามารถเพิ่มผู้ใช้ได้: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'delete_user') {
            $user_id = $_POST['user_id'];
            
            // Prevent self-deletion
            if ($user_id == $_SESSION['user_id']) {
                $error = "คุณไม่สามารถลบตัวเองได้";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "ลบผู้ใช้เรียบร้อยแล้ว";
            }
        } elseif ($_POST['action'] === 'update_role') {
            $user_id = $_POST['user_id'];
            $role = $_POST['role'];
            
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            $message = "อัปเดตสิทธิ์ผู้ใช้เรียบร้อยแล้ว";
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC")->fetchAll();

// System Stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers = count($users);
$totalValue = $pdo->query("SELECT SUM(price * stock_quantity) FROM products")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-box h3 {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        .stat-box .val {
            font-size: 1.8em;
            color: var(--accent-color);
            margin-top: 10px;
            font-weight: 600;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .user-table th, .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .user-table th {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-color);
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; }
        .alert-danger { background: rgba(255, 0, 0, 0.1); border: 1px solid var(--danger-color); color: var(--danger-color); }
        
        .role-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .role-admin { background: var(--accent-color); color: #000; }
        .role-user { background: rgba(255,255,255,0.2); color: #fff; }
    </style>
</head>
<body>

<div class="container admin-container">
    <header>
        <div>
            <h1>Admin Panel</h1>
            <span style="color: var(--text-secondary);">ระบบจัดการหลังบ้าน</span>
        </div>
        <div>
            <a href="index.php" class="btn" style="background: transparent; border: 1px solid var(--accent-color); margin-right: 10px;">กลับหน้าหลัก</a>
            <a href="logout.php" style="color: var(--danger-color); text-decoration: none;">ออกจากระบบ</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-box">
            <h3>ผู้ใช้งานทั้งหมด</h3>
            <div class="val"><?php echo $totalUsers; ?></div>
        </div>
        <div class="stat-box">
            <h3>สินค้าทั้งหมด</h3>
            <div class="val"><?php echo $totalProducts; ?></div>
        </div>
        <div class="stat-box">
            <h3>มูลค่าคลังสินค้า</h3>
            <div class="val">฿<?php echo number_format($totalValue); ?></div>
        </div>
    </div>

    <section style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="color: var(--accent-color);">จัดการผู้ใช้งาน</h2>
            <button class="btn" onclick="document.getElementById('userModal').style.display='block'">+ เพิ่มผู้ใช้ใหม่</button>
        </div>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td>
                        <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                            <?php echo strtoupper($user['role']); ?>
                        </span>
                    </td>
                    <td><?php echo $user['created_at']; ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('ยืนยันการลบผู้ใช้?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8em;">ลบ</button>
                        </form>
                        
                        <form method="POST" style="display: inline; margin-left: 5px;">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" onchange="this.form.submit()" style="padding: 4px; background: #333; color: #fff; border: 1px solid #555; border-radius: 4px;">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>ตั้งเป็น User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>ตั้งเป็น Admin</option>
                            </select>
                        </form>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-size: 0.8em;">คุณ (Current Admin)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<!-- Modal for Add User -->
<div id="userModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="document.getElementById('userModal').style.display='none'">&times;</span>
        <h2 style="color: var(--accent-color); margin-bottom: 20px;">เพิ่มผู้ใช้งานใหม่</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username" required placeholder="Username">
            </div>

            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" required placeholder="Password">
            </div>

            <div class="form-group">
                <label>สิทธิ์การใช้งาน</label>
                <select name="role" required>
                    <option value="user">User (ดูได้อย่างเดียว)</option>
                    <option value="admin">Admin (จัดการระบบได้)</option>
                </select>
            </div>

            <div class="text-right" style="margin-top: 20px;">
                <button type="submit" class="btn">บันทึกผู้ใช้</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Close modal if clicked outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('userModal')) {
            document.getElementById('userModal').style.display = 'none';
        }
    }
</script>

</body>
</html>

<?php
require 'db.php';
require 'auth.php';

requireLogin();
$currentUser = getCurrentUser();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_username) {
        try {
            // Check if username unique (if changed)
            if ($new_username !== $currentUser['username']) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    throw new Exception("ชื่อผู้ใช้นี้ถูกใช้งานแล้ว");
                }
            }

            // Update logic
            if ($new_password) {
                if ($new_password !== $confirm_password) {
                    throw new Exception("รหัสผ่านใหม่ไม่ตรงกัน");
                }
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_username, $hashed, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $_SESSION['user_id']]);
            }

            // Update Session
            $_SESSION['username'] = $new_username;
            $currentUser = getCurrentUser(); // Refresh data
            $message = "บันทึกโปรไฟล์เรียบร้อยแล้ว";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = "กรุณากรอกชื่อผู้ใช้";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            margin: 40px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; }
        .alert-danger { background: rgba(255, 0, 0, 0.1); border: 1px solid var(--danger-color); color: var(--danger-color); }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>โปรไฟล์ของฉัน</h1>
            <span style="color: var(--text-secondary);">จัดการข้อมูลส่วนตัวของคุณ</span>
        </div>
        <div>
            <a href="index.php" class="btn" style="background: transparent; border: 1px solid var(--accent-color); margin-right: 10px;">กลับหน้าหลัก</a>
            <a href="logout.php" style="color: var(--danger-color); text-decoration: none;">ออกจากระบบ</a>
        </div>
    </header>

    <div class="profile-card">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <h3 style="color: var(--accent-color); margin-bottom: 15px;">เปลี่ยนรหัสผ่าน</h3>
                <p style="font-size: 0.8em; color: var(--text-secondary); margin-bottom: 20px;">* ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="password" placeholder="รหัสผ่านใหม่">
                </div>
                <div class="form-group" style="margin-top: 20px;">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่">
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%; margin-top: 30px; font-weight: 600;">บันทึกการแก้ไข</button>
        </form>
    </div>
</div>

</body>
</html>

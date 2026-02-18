<?php
require 'db.php';
require 'auth.php';

requireLogin();
$currentUser = getCurrentUser();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_profile') {
            $new_username = trim($_POST['username'] ?? '');
            $new_phone = trim($_POST['phone'] ?? '');
            $new_address = trim($_POST['address'] ?? '');

            if (!$new_username) throw new Exception("กรุณากรอกชื่อผู้ใช้");

            // Check if username unique (if changed)
            if ($new_username !== $currentUser['username']) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $_SESSION['user_id']]);
                if ($stmt->fetch()) throw new Exception("ชื่อผู้ใช้นี้ถูกใช้งานแล้ว");
            }

            $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$new_username, $new_phone, $new_address, $_SESSION['user_id']]);

            $_SESSION['username'] = $new_username;
            $message = "บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว";
        } elseif ($action === 'change_password') {
            $new_password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (!$new_password) throw new Exception("กรุณากรอกรหัสผ่านใหม่");
            if ($new_password !== $confirm_password) throw new Exception("รหัสผ่านใหม่ไม่ตรงกัน");

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
        }
        
        $currentUser = getCurrentUser(); // Refresh data
    } catch (Exception $e) {
        $error = $e->getMessage();
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
        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            box-sizing: border-box;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus {
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
        .section-title {
            color: var(--accent-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="profile-container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Personal Info Form -->
            <div class="profile-card">
                <div class="section-title">👤 ข้อมูลส่วนตัว</div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>ชื่อผู้ใช้</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" placeholder="08x-xxx-xxxx">
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label>ที่อยู่สำหรับจัดส่ง</label>
                        <textarea name="address" style="height: 100px;" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; margin-top: 20px;">บันทึกข้อมูลส่วนตัว</button>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="profile-card">
                <div class="section-title">🔒 เปลี่ยนรหัสผ่าน</div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>รหัสผ่านใหม่</label>
                        <input type="password" name="password" required placeholder="กรอกรหัสผ่านใหม่">
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label>ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" required placeholder="ยืนยันรหัสผ่านใหม่">
                    </div>
                    <button type="submit" class="btn" style="width: 100%; margin-top: 20px; background: transparent; border: 1px solid var(--accent-color); color: var(--accent-color);">เปลี่ยนรหัสผ่าน</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>


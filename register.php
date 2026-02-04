<?php
require 'db.php';
require 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = 'รหัสผ่านคิอไม่ตรงกัน';
        } else {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                if ($stmt->execute([$username, $hashed_password])) {
                    $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
                } else {
                    $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
                }
            }
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - PC Shop Stock</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        }
        .register-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: var(--accent-color);
            margin: 0;
            font-size: 1.8em;
        }
        .register-header p {
            color: var(--text-secondary);
            margin-top: 5px;
        }
        .error-msg {
            background: rgba(255, 68, 68, 0.1);
            color: var(--danger-color);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9em;
            text-align: center;
            border: 1px solid var(--danger-color);
        }
        .success-msg {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9em;
            text-align: center;
            border: 1px solid #00ff00;
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
        .register-btn {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            font-size: 1em;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <h1>สมัครสมาชิก</h1>
        <p>สร้างบัญชีเพื่อเริ่มต้นใช้งาน</p>
    </div>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>ชื่อผู้ใช้</label>
            <input type="text" name="username" required placeholder="ชื่อผู้ใช้" autofocus>
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" required placeholder="รหัสผ่าน">
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" required placeholder="ยืนยันรหัสผ่าน">
        </div>
        <button type="submit" class="btn register-btn">สมัครสมาชิก</button>
    </form>
    
    <div style="margin-top: 20px; text-align: center; font-size: 0.9em;">
        <p style="color: var(--text-secondary);">มีบัญชีอยู่แล้ว? <a href="login.php" style="color: var(--accent-color); text-decoration: none;">เข้าสู่ระบบ</a></p>
    </div>
</div>

</body>
</html>

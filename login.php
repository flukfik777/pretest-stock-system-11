<?php
require 'db.php';
require 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
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
    <title>เข้าสู่ระบบ - PC Shop Stock</title>
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
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--accent-color);
            margin: 0;
            font-size: 1.8em;
        }
        .login-header p {
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
        .login-btn {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            font-size: 1em;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h1>ร้านประกอบคอมเทพ</h1>
        <p>กรุณาเข้าสู่ระบบเพื่อใช้งาน</p>
    </div>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
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
        <button type="submit" class="btn login-btn">เข้าสู่ระบบ</button>
    </form>
    
    <div style="margin-top: 30px; text-align: center; font-size: 0.8em; color: var(--text-secondary);">
        <p>Admin: admin / admin123</p>
        <p>User: user / user123</p>
    </div>
</div>

</body>
</html>

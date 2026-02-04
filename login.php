<?php
require 'db.php';
require 'auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Auto-init Users table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert dummy products if empty (Added for catalog variety)
    $checkProd = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($checkProd == 0) {
        $insertProdSql = "INSERT INTO products (name, category, price, stock_quantity, image_url) VALUES 
            ('Intel Core i9-14900K', 'CPU', 24900.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=CPU'),
            ('AMD Ryzen 9 7950X', 'CPU', 22500.00, 8, 'https://placehold.co/300x300/1a1a1a/00eeff?text=CPU'),
            ('RTX 4090 ROG Strix', 'GPU', 75000.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'),
            ('RTX 4080 Super TUF', 'GPU', 42000.00, 12, 'https://placehold.co/300x300/1a1a1a/00ff00?text=GPU'),
            ('RX 7900 XTX Nitro+', 'GPU', 38500.00, 7, 'https://placehold.co/300x300/1a1a1a/ff3300?text=GPU'),
            ('Corsair Dominator 32GB', 'RAM', 6500.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'),
            ('Kingston FURY Beast 16GB', 'RAM', 2400.00, 35, 'https://placehold.co/300x300/1a1a1a/00ff00?text=RAM'),
            ('Samsung 990 Pro 1TB', 'Storage', 4500.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'),
            ('WD Black SN850X 2TB', 'Storage', 6200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=SSD'),
            ('NZXT H7 Flow Black', 'Case', 4200.00, 15, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'),
            ('Lian Li O11 Dynamic', 'Case', 5500.00, 8, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Case'),
            ('Corsair RM850e 850W', 'Power Supply', 4800.00, 20, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'),
            ('ASUS ROG Thor 1000W', 'Power Supply', 12500.00, 5, 'https://placehold.co/300x300/1a1a1a/00ff00?text=PSU'),
            ('ROG Ryujin III 360', 'Cooling', 13900.00, 6, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler'),
            ('Noctua NH-D15 chromax', 'Cooling', 4200.00, 10, 'https://placehold.co/300x300/1a1a1a/00ff00?text=Cooler');";
        $pdo->exec($insertProdSql);
    }

    // Insert initial users if empty
    $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($check == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $userPass = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES 
            ('admin', '$adminPass', 'admin'),
            ('user', '$userPass', 'user')");
    }
} catch (PDOException $e) { /* Ignore if exists */ }

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
    
    <div style="margin-top: 20px; text-align: center; font-size: 0.9em;">
        <p style="color: var(--text-secondary);">ยังไม่มีบัญชี? <a href="register.php" style="color: var(--accent-color); text-decoration: none;">สมัครสมาชิก</a></p>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 0.8em; color: var(--text-secondary);">
        <p>Admin: admin / admin123</p>
        <p>User: user / user123</p>
    </div>
</div>

</body>
</html>

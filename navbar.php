<?php
// navbar.php
if (!isset($currentUser)) {
    $currentUser = getCurrentUser();
}
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>
<header style="padding: 15px 0;">
    <div style="cursor: pointer;" onclick="window.location.href='index.php'">
        <h1>ร้านประกอบคอมเทพ</h1>
        <div style="color: var(--accent-color); font-size: 0.8em; letter-spacing: 1px; font-weight: 600;">PREMIUM COMPUTER HARDWARE</div>
    </div>
    
    <div class="nav-menu">
        <nav style="display: flex; gap: 8px; align-items: center;">
            <a href="index.php" class="nav-link <?php echo isActive('index.php', $currentPage); ?>">🏠 หน้าหลัก</a>
            <a href="computer_sets.php" class="nav-link nav-btn <?php echo isActive('computer_sets.php', $currentPage); ?>">🚀 ชุดคอมพร้อมเล่น</a>
            <a href="orders.php" class="nav-link <?php echo isActive('orders.php', $currentPage); ?>">📋 ออเดอร์</a>
            <a href="cart.php" class="nav-link cart-link <?php echo isActive('cart.php', $currentPage); ?>">
                🛒 ตะกร้า (<?php echo $cartCount; ?>)
            </a>
            
            <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.1); margin: 0 10px;"></div>

            <div class="user-info">
                <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                <span class="user-role"><?php echo $currentUser['role']; ?></span>
            </div>

            <a href="profile.php" class="nav-link <?php echo isActive('profile.php', $currentPage); ?>" title="โปรไฟล์">⚙️</a>
            
            <?php if (isAdmin()): ?>
                <a href="admin.php" class="nav-link <?php echo isActive('admin.php', $currentPage); ?> <?php echo isActive('admin_orders.php', $currentPage); ?>" title="ระบบจัดการ">🔑</a>
            <?php endif; ?>
            
            <a href="logout.php" class="nav-link danger" onclick="return confirm('ยืนยันออกจากระบบ?');" title="ออกจากระบบ">🚪</a>
        </nav>
    </div>
</header>


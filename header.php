<?php
session_start();
?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Luxury Cars - Siêu xe sang trọng</title>

    <!-- Luôn dùng đường dẫn tuyệt đối khi include từ file con -->
    <script src="script.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Vùng cảm ứng để hiện header -->
    <div class="header-hover-zone"></div>

    <!-- Header -->
    <header id="header">
        <nav class="navbar">
            <a href="index.php" class="logo"><b>Luxury Cars</b></a>
            <ul class="nav-links">
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'ADMIN'): ?>
                    <li><a href="/showroom_oto/admin/index.php">Trang quản trị</a></li>
                <?php endif; ?>
                <li><a href="/showroom_oto/index.php">Trang chủ</a></li>
                <li><a href="/showroom_oto/featured.php">Xe nổi bật</a></li>
                <li><a href="/showroom_oto/locxe.php">Các loại xe</a></li>
                <li><a href="/showroom_oto/services.php">Dịch vụ</a></li>
                <li class="dropdown">
                <?php if (isset($_SESSION['username'])): ?>
                    <a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></a>
                    <ul class="dropdown-menu">
                        <li><a href="/showroom_oto/Taikhoan/dangxuat.php">Đăng xuất</a></li>
                    </ul>
                <?php else: ?>
                    <a href="#"><i class="fas fa-user"></i> Tài khoản</a>
                    <ul class="dropdown-menu">
                        <li><a href="/showroom_oto/Taikhoan/dangnhap.php" class="login-btn">Đăng nhập</a></li>
                        <li><a href="/showroom_oto/Taikhoan/dangky.php" class="register-btn">Đăng ký</a></li>
                    </ul>
                <?php endif; ?>
                </li>
            </ul>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

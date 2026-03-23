<?php
session_start();

// Nếu đã đăng nhập thì về trang chính
if (isset($_SESSION['user_code']) && !empty($_SESSION['user_code'])) {
    header("Location: admin/index.php");
    exit();
}

require_once("xuly.php"); // xử lý đăng nhập
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="stylelogin.css">
    <style>
        .message-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s;
        }
        .message-box.show {
            opacity: 1;
        }
        .message-box.error {
            background-color: #f44336;
        }
        .message-box.success {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- THÔNG BÁO BÊN NGOÀI VÙNG MỜ -->
    <?php if (!empty($message)): ?>
        <div id="message-box" class="message-box <?= str_starts_with($message, 'Đăng nhập') ? 'success' : 'error' ?> show">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            setTimeout(function () {
                const box = document.getElementById('message-box');
                if (box) {
                    box.classList.remove("show");
                    box.classList.add("hidden");
                }
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="login-container">
        <form id="loginForm" method="POST" class="login-form">
            <input type="hidden" name="action" value="login">

            <!-- Nếu có redirect thì thêm -->
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect']) ?>">
            <?php endif; ?>

            <h2>Đăng nhập</h2>

            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Đăng nhập</button>

            <div class="register-link">
                Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
            </div>
        </form>
    </div>
</body>
</html>
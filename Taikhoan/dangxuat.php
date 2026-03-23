<?php
session_start();

// Xóa tất cả biến session
$_SESSION = [];

// Hủy toàn bộ session
session_destroy();

// Xóa cookie phiên nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Chuyển hướng đến trang đăng nhập
header("Location: dangnhap.php");
exit();
?>

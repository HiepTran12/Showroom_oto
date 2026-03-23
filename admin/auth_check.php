<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa login thì chuyển hướng về trang login
if (!isset($_SESSION['user_code'])) {
    header("Location: ../Taikhoan/dangnhap.php");
    exit();
}
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa login thì chuyển hướng về trang login
if (!isset($_SESSION['user_code'])) {
    header("Location: ../Taikhoan/dangnhap.php");
    exit();
}

// Nếu không phải admin thì chuyển hướng về trang chủ thường
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../index.php"); // hoặc trang báo lỗi
    exit();
}
?>
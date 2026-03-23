<?php
require_once("../connect.php");
// Chỉ admin mới được phép thực hiện các thao tác này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../Taikhoan/dangnhap.php");
    exit();
}

$user_code = $_POST['user_code'] ?? '';
$action = $_POST['action'] ?? '';

if (!empty($user_code)) {
    if ($action === 'approve') {
        // Duyệt tài khoản: kích hoạt và đánh dấu đã duyệt
        $stmt = $conn->prepare("UPDATE users SET is_approved = 1, is_active = 1 WHERE user_code = ?");
        $stmt->bind_param("s", $user_code);
        $stmt->execute();
        $stmt->close();
       
    } elseif ($action === 'delete') {
        // Lấy customer_code trước khi xóa
        $stmt = $conn->prepare("SELECT customer_code FROM users WHERE user_code = ?");
        $stmt->bind_param("s", $user_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer_code = $result->fetch_assoc()['customer_code'];
        $stmt->close();
        
        // Xóa user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_code = ?");
        $stmt->bind_param("s", $user_code);
        $stmt->execute();
        $stmt->close();
        
        // Xóa customer tương ứng
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_code = ?");
        $stmt->bind_param("s", $customer_code);
        $stmt->execute();
        $stmt->close();
    }
}

// Quay lại trang quản lý người dùng
header("Location: manage_users.php");
exit();
?>
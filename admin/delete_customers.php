<?php
include 'auth_check.php';
include '../connect.php';

if (!isset($_GET['code']) || empty($_GET['code'])) {
    header("Location: customers.php?error=1&message=" . urlencode("Không tìm thấy mã khách hàng"));
    exit;
}

$customer_code = $_GET['code'];

try {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    // Xóa feedback trước
    $delete_feedback = $conn->prepare("DELETE FROM customer_feedback WHERE customer_code = ?");
    $delete_feedback->bind_param("s", $customer_code);
    $delete_feedback->execute();
    
    // Xóa khách hàng
    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_code = ?");
    $stmt->bind_param("s", $customer_code);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $conn->commit();
        header("Location: customers.php?success=1&message=" . urlencode("Xóa khách hàng thành công"));
    } else {
        throw new Exception("Không tìm thấy khách hàng để xóa");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: customers.php?error=1&message=" . urlencode("Lỗi: " . $e->getMessage()));
}

exit;
?>
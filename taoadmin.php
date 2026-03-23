<?php
include 'connect.php';

$user_code = "U001";
$username = "admin1";
$email = "admin1@example.com";
$password_hash = password_hash("admin123", PASSWORD_DEFAULT);
$full_name = "Quản trị viên";
$role = "ADMIN";
$customer_code = null; // Admin không cần customer_code
$is_active = 1;

// Chuẩn bị câu lệnh insert
$stmt = $conn->prepare("INSERT INTO users 
    (user_code, username, email, password_hash, full_name, role, customer_code, is_active) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "sssssssi",
    $user_code, $username, $email, $password_hash, $full_name, $role, $customer_code, $is_active
);

if ($stmt->execute()) {
    echo "Tạo tài khoản admin thành công!";
} else {
    echo "Lỗi: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
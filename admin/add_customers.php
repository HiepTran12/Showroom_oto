<?php
include 'auth_check.php';
// KẾT NỐI DB trước khi in HTML (đổi đường dẫn nếu khác thư mục)
require_once '../connect.php';

// Sinh mã CUS-NNNN an toàn (khóa trong transaction để tránh đụng độ)
function next_customer_code(mysqli $conn): string {
    $code = 'CUS-0001';
    try {
        $conn->begin_transaction();
        $sql = "SELECT MAX(CAST(SUBSTRING(customer_code,5) AS UNSIGNED)) AS maxnum
                FROM customers FOR UPDATE";
        $rs  = $conn->query($sql);
        $row = $rs ? $rs->fetch_assoc() : null;
        $next = (int)($row['maxnum'] ?? 0) + 1;
        $code = 'CUS-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        // Fallback nếu không khóa được
        $rs  = $conn->query("SELECT MAX(CAST(SUBSTRING(customer_code,5) AS UNSIGNED)) AS maxnum FROM customers");
        $row = $rs ? $rs->fetch_assoc() : null;
        $next = (int)($row['maxnum'] ?? 0) + 1;
        $code = 'CUS-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
    return $code;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    if ($full_name === '') {
        $error = 'Vui lòng nhập Họ tên.';
    }

    // Kiểm tra trùng phone
    if ($error === '' && $phone !== '') {
        $chk = $conn->prepare("SELECT 1 FROM customers WHERE phone = ? LIMIT 1");
        $chk->bind_param("s", $phone);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $error = 'Số điện thoại đã tồn tại.';
    }

    // Kiểm tra trùng email
    if ($error === '' && $email !== '') {
        $chk = $conn->prepare("SELECT 1 FROM customers WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) $error = 'Email đã tồn tại.';
    }

    if ($error === '') {
        $customer_code = next_customer_code($conn);

        // Chèn (đúng tên cột snake_case)
        $stmt = $conn->prepare("
            INSERT INTO customers (customer_code, full_name, phone, email, address, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            $error = 'Lỗi prepare: ' . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $customer_code, $full_name, $phone, $email, $address, $notes);
            if ($stmt->execute()) {
                header("Location: customers.php?success=1");
                exit;
            } else {
                // Bắt lỗi trùng do UNIQUE
                if ($conn->errno == 1062) {
                    if (strpos($conn->error, 'phone') !== false) $error = 'Số điện thoại đã tồn tại.';
                    elseif (strpos($conn->error, 'email') !== false) $error = 'Email đã tồn tại.';
                    else $error = 'Mã khách hàng đã tồn tại, vui lòng thử lại.';
                } else {
                    $error = 'Lỗi thêm khách hàng: ' . $conn->error;
                }
            }
        }
    }
}

// Sau xử lý mới in HTML
include 'header.php';
?>
<head>
    <meta charset="UTF-8">
    <title>Thêm khách hàng</title>
    <link rel="stylesheet" href="customers.css">
</head>

<div class="main-content">
    <h2>Thêm khách hàng</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 rounded shadow-sm" style="max-width: 600px; margin:auto;">
        <div class="mb-3">
            <label>Họ tên <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Điện thoại</label>
            <input type="text" name="phone" class="form-control">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Địa chỉ</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="mb-3">
            <label>Ghi chú</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Lưu</button>
        <a href="customers.php" class="btn btn-secondary">Huỷ</a>
    </form>
</div>

<?php include 'footer.php'; ?>

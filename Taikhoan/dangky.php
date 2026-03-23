<?php
require_once("../connect.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username   = trim($_POST["username"] ?? '');
    $email      = trim($_POST["email"] ?? '');
    $fullname   = trim($_POST["fullname"] ?? '');
    $password   = $_POST["password"] ?? '';
    $confirmPwd = $_POST["confirm_password"] ?? '';

    $phone      = isset($_POST["phone"])   ? trim($_POST["phone"])   : null;
    $address    = isset($_POST["address"]) ? trim($_POST["address"]) : null;
    $notes      = isset($_POST["notes"])   ? trim($_POST["notes"])   : null;

    // ===== Validate =====
    if ($username === '' || $email === '' || $fullname === '' || $password === '' || $confirmPwd === '') {
        $message = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ.";
    } elseif ($password !== $confirmPwd) {
        $message = "Mật khẩu xác nhận không khớp.";
    } else {
        if ($phone !== null && $phone !== '') {
            $onlyDigits = preg_replace('/\D+/', '', $phone);
            if (!preg_match('/^\d{9,12}$/', $onlyDigits)) {
                $message = "Số điện thoại không hợp lệ (chỉ số, 9-12 ký tự).";
            } else { $phone = $onlyDigits; }
        } else { $phone = null; }
    }

    // Check trùng
    if ($message === "") {
        $st = $conn->prepare("
            SELECT 1 FROM users WHERE username = ? OR email = ?
            UNION
            SELECT 1 FROM customers WHERE email = ?
        ");
        $st->bind_param("sss", $username, $email, $email);
        $st->execute(); $st->store_result();
        if ($st->num_rows > 0) $message = "⚠️ Tên đăng nhập hoặc email đã tồn tại.";
        $st->close();
    }

    if ($message === "") {
        try {
            $conn->begin_transaction();

            $address = ($address !== '') ? $address : null;
            $notes   = ($notes   !== '') ? $notes   : null;

            // ===== 1) Tạo CUSTOMER với code tạm để không vi phạm NOT NULL =====
            $tmpCusCode = 'TMP-' . bin2hex(random_bytes(6)); // tạm độc nhất

            $insCus = $conn->prepare("
                INSERT INTO customers (customer_code, full_name, phone, email, address, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $insCus->bind_param("ssssss", $tmpCusCode, $fullname, $phone, $email, $address, $notes);
            $insCus->execute();
            $customer_id = $conn->insert_id;
            $insCus->close();

            if (!$customer_id) throw new Exception("Không lấy được ID khách hàng vừa tạo.");

            $customer_code = sprintf('CUS-%04d', $customer_id);
            $upCus = $conn->prepare("UPDATE customers SET customer_code = ? WHERE id = ?");
            $upCus->bind_param("si", $customer_code, $customer_id);
            $upCus->execute(); $upCus->close();

            // ===== 2) Tạo USER với code tạm, rồi cập nhật lại =====
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $tmpUsrCode = 'TMP-' . bin2hex(random_bytes(6));

            $insUser = $conn->prepare("
                INSERT INTO users
                    (user_code, username, email, password_hash, full_name, role, customer_code, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 'CUSTOMER', ?, 0, NOW())
            ");
            $insUser->bind_param("ssssss", $tmpUsrCode, $username, $email, $hashedPassword, $fullname, $customer_code);
            $insUser->execute();
            $user_id = $conn->insert_id;
            $insUser->close();

            if (!$user_id) throw new Exception("Không lấy được ID người dùng vừa tạo.");

            $user_code = sprintf('USR-%04d', $user_id);
            $upUser = $conn->prepare("UPDATE users SET user_code = ? WHERE id = ?");
            $upUser->bind_param("si", $user_code, $user_id);
            $upUser->execute(); $upUser->close();

            $conn->commit();
            $message = "✅ Đăng ký thành công! Mã tài khoản: " . htmlspecialchars($user_code) . ". Vui lòng chờ admin duyệt.";
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "Lỗi khi tạo tài khoản: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="stylelogin.css">
    <style>
        .message { margin-top: 15px; padding: 10px; border-radius: 5px; text-align:center; background:#f8d7da; color:#721c24; }
        .message.success { background:#d4edda; color:#155724; }
        .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    </style>
</head>
<body>
    <div class="register-container">
        <form class="register-form" method="POST" action="">
            <h2>Đăng ký tài khoản</h2>

            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" placeholder="username" required>
            </div>

            <div class="row">
              <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" placeholder="email" required>
              </div>
              <div class="form-group">
                  <label for="phone">Số điện thoại (tuỳ chọn)</label>
                  <input type="text" id="phone" name="phone" placeholder="0901xxxxxx">
              </div>
            </div>

            <div class="row">
              <div class="form-group">
                  <label for="fullname">Họ và tên</label>
                  <input type="text" id="fullname" name="fullname" placeholder="fullname" required>
              </div>
              <div class="form-group">
                  <label for="address">Địa chỉ (tuỳ chọn)</label>
                  <input type="text" id="address" name="address" placeholder="Địa chỉ">
              </div>
            </div>

            <div class="form-group">
                <label for="notes">Ghi chú (tuỳ chọn)</label>
                <textarea id="notes" name="notes" rows="2" placeholder="Ghi chú thêm..."></textarea>
            </div>

            <div class="row">
              <div class="form-group">
                  <label for="password">Mật khẩu</label>
                  <input type="password" id="password" name="password" placeholder="password" required>
              </div>
              <div class="form-group">
                  <label for="confirm_password">Nhập lại mật khẩu</label>
                  <input type="password" id="confirm_password" name="confirm_password" placeholder="confirm_password" required>
              </div>
            </div>

            <button type="submit" class="register-btn">Đăng ký</button>

            <div class="login-link">
                Đã có tài khoản? <a href="dangnhap.php">Đăng nhập ngay</a>
            </div>

            <?php if (!empty($message)): ?>
              <div id="message-box" class="message <?= strpos($message, '✅') === 0 ? 'success' : '' ?>">
                  <?= htmlspecialchars($message) ?>
              </div>
              <script>
                setTimeout(() => { const box = document.getElementById('message-box'); if (box) box.style.display = 'none'; }, 3000);
              </script>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>

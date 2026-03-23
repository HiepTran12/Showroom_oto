<?php
// 1) KẾT NỐI DB TRƯỚC – KHÔNG IN HTML Ở TRÊN
include 'auth_check.php';
require_once '../connect.php'; // đổi đường dẫn nếu cần

$customer_code = $_GET['code'] ?? '';
if ($customer_code === '') {
  die("<div class='alert alert-danger'>Mã khách hàng không hợp lệ.</div>");
}

$error = '';
$customer = null;

// 2) XỬ LÝ POST CẬP NHẬT (TRƯỚC KHI IN HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $phone     = trim($_POST['phone'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $address   = trim($_POST['address'] ?? '');
  $notes     = trim($_POST['notes'] ?? '');

  if ($full_name === '') {
    $error = 'Vui lòng nhập Họ tên.';
  }

  // Kiểm tra trùng phone (loại trừ bản ghi hiện tại)
  if ($error === '' && $phone !== '') {
    $chk = $conn->prepare("SELECT 1 FROM customers WHERE phone = ? AND customer_code <> ? LIMIT 1");
    $chk->bind_param("ss", $phone, $customer_code);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) $error = 'Số điện thoại đã tồn tại.';
  }

  // Kiểm tra trùng email (loại trừ bản ghi hiện tại)
  if ($error === '' && $email !== '') {
    $chk = $conn->prepare("SELECT 1 FROM customers WHERE email = ? AND customer_code <> ? LIMIT 1");
    $chk->bind_param("ss", $email, $customer_code);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) $error = 'Email đã tồn tại.';
  }

  if ($error === '') {
    $stmt = $conn->prepare("
      UPDATE customers
      SET full_name = ?, phone = ?, email = ?, address = ?, notes = ?
      WHERE customer_code = ?
    ");
    if (!$stmt) {
      $error = 'Lỗi prepare: ' . $conn->error;
    } else {
      $stmt->bind_param("ssssss", $full_name, $phone, $email, $address, $notes, $customer_code);
      if ($stmt->execute()) {
        // Thành công → quay lại danh sách
        header("Location: customers.php?updated=1");
        exit;
      } else {
        // Nếu vướng UNIQUE mà lọt qua bước kiểm tra, vẫn báo rõ
        if ($conn->errno == 1062) {
          if (strpos($conn->error, 'phone') !== false)  $error = 'Số điện thoại đã tồn tại.';
          elseif (strpos($conn->error, 'email') !== false) $error = 'Email đã tồn tại.';
          else $error = 'Dữ liệu đã tồn tại.';
        } else {
          $error = 'Lỗi cập nhật: ' . $stmt->error;
        }
      }
    }
  }
}

// 3) LẤY LẠI DỮ LIỆU (sau khi POST thất bại hoặc truy cập lần đầu)
$st = $conn->prepare("SELECT * FROM customers WHERE customer_code = ? LIMIT 1");
$st->bind_param("s", $customer_code);
$st->execute();
$res = $st->get_result();
$customer = $res->fetch_assoc();

if (!$customer) {
  die("<div class='alert alert-danger'>Không tìm thấy khách hàng</div>");
}

// 4) BẮT ĐẦU IN HTML
include 'header.php';
?>
<head>
  <meta charset="UTF-8">
  <title>Sửa khách hàng</title>
  <link rel="stylesheet" href="customers.css">
</head>

<div class="main-content">
  <h2>Sửa khách hàng</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-4 rounded shadow-sm" style="max-width: 600px; margin:auto;">
    <div class="mb-3">
      <label>Mã KH</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($customer['customer_code']) ?>" readonly>
    </div>
    <div class="mb-3">
      <label>Họ tên <span class="text-danger">*</span></label>
      <input type="text" name="full_name" class="form-control"
             value="<?= htmlspecialchars($customer['full_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Điện thoại</label>
      <input type="text" name="phone" class="form-control"
             value="<?= htmlspecialchars($customer['phone']) ?>">
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars($customer['email']) ?>">
    </div>
    <div class="mb-3">
      <label>Địa chỉ</label>
      <input type="text" name="address" class="form-control"
             value="<?= htmlspecialchars($customer['address']) ?>">
    </div>
    <div class="mb-3">
      <label>Ghi chú</label>
      <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($customer['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Cập nhật</button>
    <a href="customers.php" class="btn btn-secondary">Huỷ</a>
  </form>
</div>

<?php include 'footer.php'; ?>

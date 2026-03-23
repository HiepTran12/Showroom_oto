<?php
include 'auth_check.php';
include 'header.php';

// helper tránh deprecated khi gặp NULL
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function fmt_dt($v) {
    if (empty($v)) return '';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y H:i', $ts) : '';
}

// tìm kiếm theo tên (dùng prepared statement để an toàn)
$keyword = $_GET['keyword'] ?? '';

if ($keyword !== '') {
    $sql  = "SELECT customer_code, full_name, phone, email, address, notes, created_at
             FROM customers
             WHERE full_name LIKE CONCAT('%', ?, '%')
             ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT customer_code, full_name, phone, email, address, notes, created_at
            FROM customers
            ORDER BY created_at DESC";
    $result = $conn->query($sql);
}

// thông báo success/error
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = $_GET['message'] ?? 'Thao tác thành công!';
    echo '<div class="alert alert-success text-center">' . h($message) . '</div>';
}
if (isset($_GET['error']) && $_GET['error'] == '1') {
    $message = $_GET['message'] ?? 'Có lỗi xảy ra!';
    echo '<div class="alert alert-danger text-center">' . h($message) . '</div>';
}
?>
<head>
    <meta charset="UTF-8">
    <title>Khách hàng</title>
    <link rel="stylesheet" href="customers.css">
</head>

<div class="main-content">
    <h2>Danh sách khách hàng</h2>
    <div class="mb-3 d-flex gap-2">
        <a href="export_customers_excel.php" class="btn btn-success">📄 Xuất Excel</a>
        <a href="export_customers_pdf.php" class="btn btn-danger" target="_blank">🖨️ In PDF</a>
    </div>

    <!-- Tìm kiếm + Thêm -->
    <form method="GET" class="d-flex mb-3 gap-2">
        <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên..." value="<?= h($keyword) ?>">
        <button type="submit" class="btn btn-primary">Tìm</button>
        <a href="add_customers.php" class="btn btn-success">Thêm khách hàng</a>
    </form>

    <!-- Bảng dữ liệu -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Họ tên</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Ghi chú</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($row['customer_code']) ?></td>
                            <td><?= h($row['full_name']) ?></td>
                            <td><?= h($row['phone']) ?></td>
                            <td><?= h($row['email']) ?></td>
                            <td><?= h($row['address']) ?></td>
                            <td><?= h($row['notes']) ?></td>
                            <td><?= h(fmt_dt($row['created_at'])) ?></td>
                            <td>
                                <a href="edit_customers.php?code=<?= urlencode((string)$row['customer_code']) ?>" class="btn btn-sm btn-warning">Sửa</a>
                                <a href="delete_customers.php?code=<?= urlencode((string)$row['customer_code']) ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng <?= h($row['full_name']) ?>?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">Không tìm thấy khách hàng nào</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Tự động ẩn thông báo sau 5 giây
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 1000);
        });
    }, 5000);
</script>

<style>
.fade-out { opacity: 0; transition: opacity 1s ease-out; }
</style>

<?php include 'footer.php'; ?>

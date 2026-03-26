<?php
ob_start(); 
include 'auth_check.php';
include 'header.php';

// 🔴 XỬ LÝ DELETE TRƯỚC KHI OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {

    $feedback_no = $_POST['feedback_no'] ?? '';

    if ($feedback_no !== '') {
        $stmt = $conn->prepare("DELETE FROM customer_feedback WHERE feedback_no = ?");
        $stmt->bind_param("s", $feedback_no);
        $stmt->execute();
    }

    header("Location: ".$_SERVER['REQUEST_URI']);
    exit();
}

// helper: escape an toàn cho HTML
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// helper: định dạng ngày (tránh NULL)
function fmtDate($s) {
    return $s ? date('d/m/Y H:i:s', strtotime($s)) : '';
}

// Phân trang
$limit = 9;
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng
$countRes = $conn->query("SELECT COUNT(*) AS total FROM customer_feedback");
$totalRows = $countRes ? ($countRes->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = (int)ceil($totalRows / $limit);

// Lấy dữ liệu
$sql = "
    SELECT
        feedback_no, customer_code, car_code, rating, comment, created_at
    FROM customer_feedback
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
?>
<head>
    <meta charset="UTF-8">
    <title>Danh sách phản hồi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="logs.css">
</head>

<h2>Danh sách phản hồi</h2>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Mã phản hồi</th>
            <th>Mã khách hàng</th>
            <th>Mã xe</th>
            <th>Đánh giá</th>
            <th>Bình luận</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): $i = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= e($row['feedback_no']) ?></td>
                    <td><?= e($row['customer_code']) ?></td>
                    <td><?= e($row['car_code']) ?></td>
                    <td><?= e((string)($row['rating'] ?? '')) ?></td>
                    <td style="max-width: 250px; overflow-wrap: break-word;">
                        <?= nl2br(e($row['comment'])) ?>
                    </td>
                    <td><?= fmtDate($row['created_at'] ?? null) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa phản hồi này?');">
                            <input type="hidden" name="delete_feedback" value="1">
                            <input type="hidden" name="feedback_no" value="<?= e($row['feedback_no']) ?>">
                            <button type="submit" class="btn-delete">
                                <i class="fa fa-trash"></i> Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center text-muted">Không có dữ liệu phản hồi.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>

<style>
    /* TABLE STYLE */
table.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
    font-size: 15px;
    margin-bottom: 140px;
}

.table thead {
    background-color: #f8f9fa;
    color: #333;
}

.table th, .table td {
    padding: 12px 14px;
    text-align: left;
    vertical-align: top;
}

.table tbody tr:hover {
    background-color: #f1f1f1;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
    color: white;
    font-weight: 500;
}

.badge.bg-info {
    background-color: #0dcaf0;
}
.badge.bg-success {
    background-color: #198754;
}
.badge.bg-warning {
    background-color: #ffc107;
}
.badge.bg-danger {
    background-color: #dc3545;
}

/* Responsive truncate for long content */
.table td {
    max-width: 250px;
    word-wrap: break-word;
    white-space: pre-line;
    
}

/* Section Title */
h2 {
    font-size: 24px;
    margin-top: 20px;
    color: #333;
    border-left: 4px solid #0d6efd;
    padding-left: 12px;
}
.btn-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.btn-delete:hover {
    background-color: #b02a37;
}


</style>
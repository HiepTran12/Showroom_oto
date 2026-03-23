<?php
include 'auth_check.php';
require_once '../connect.php';
include 'header.php';

// Tìm kiếm theo tên xe
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where  = $search ? "WHERE c.name LIKE ?" : "";

// Truy vấn danh sách xe + ảnh chính
$sql = "
SELECT
  c.*,
  b.name AS brand_name,
  LEFT(c.description, 90) AS description_short,
  (
    SELECT f.storage_path
    FROM car_images ci
    JOIN files f ON f.file_code = ci.file_code
    WHERE ci.car_code = c.car_code
    ORDER BY ci.is_primary DESC, ci.created_at ASC
    LIMIT 1
  ) AS storage_path
FROM cars c
LEFT JOIN brands b ON c.brand_code = b.brand_code
$where
ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die('Lỗi truy vấn: '.$conn->error); }
if ($search) {
    $param = "%$search%";
    $stmt->bind_param("s", $param);
}
$stmt->execute();
$result = $stmt->get_result();

// (tuỳ chọn) rút gọn mô tả
function short($s, $len=90) {
    $s = (string)$s;
    if (mb_strlen($s,'UTF-8') <= $len) return $s;
    return mb_substr($s, 0, $len, 'UTF-8') . '…';
}
?>
<head>
  <meta charset="UTF-8">
  <title>Quản lý xe</title>
  <link rel="stylesheet" href="kho.css">
  <style>.kho-table img{width:80px;height:60px;object-fit:cover;border-radius:6px}</style>
</head>

<div class="kho-container">
  <div class="kho-header">
    <h2>Danh sách xe</h2>
    <a href="them_xe.php" class="btn btn-success">+ Thêm xe</a>
  </div>

  <form method="GET" class="kho-search-form">
    <input type="text" name="search" placeholder="Tìm theo tên xe..." value="<?= htmlspecialchars($search) ?>" />
    <button type="submit">Tìm</button>
  </form>

  <table class="kho-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Hình</th>
        <th>Mã xe</th>
        <th>Hãng</th>
        <th>Tên xe</th>
        <th>Mô tả</th>
        <th>Giá</th>
        <th>Động cơ</th>
        <th>Công suất</th>
        <th>Vận tốc tối đa</th>
        <th>Nhiên liệu</th>
        <th>Hộp số</th>
        <th>Số ghế</th>
        <th>Ngày tạo</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; while($row=$result->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td>
          <?php if (!empty($row['storage_path'])):
              $imgUrl = (strpos($row['storage_path'], '/')===0) ? $row['storage_path'] : '/'.$row['storage_path'];
              $imgAbs = rtrim($_SERVER['DOCUMENT_ROOT'],'/\\') . $imgUrl;
              if (is_file($imgAbs)): ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Ảnh xe"
                     onerror="this.onerror=null;this.replaceWith(document.createTextNode('Ảnh lỗi'));"><?php
              else: ?>
                <span>Ảnh không tồn tại</span>
              <?php endif; ?>
          <?php else: ?>
            <span>Chưa có ảnh</span>
          <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($row['car_code']) ?></td>
        <td><?= htmlspecialchars($row['brand_name'] ?? 'Không rõ') ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td title="<?= htmlspecialchars($row['description']) ?>">
          <?= htmlspecialchars($row['description_short']) ?>…
        </td>

        <td><?= number_format((float)$row['price'], 0, ',', '.') ?> ₫</td>
        <td><?= htmlspecialchars($row['engine']) ?></td>
        <td><?= htmlspecialchars($row['power']) ?></td>
        <td><?= htmlspecialchars($row['top_speed']) ?></td>
        <td><?= htmlspecialchars($row['fuel_type']) ?></td>
        <td><?= htmlspecialchars($row['transmission']) ?></td>
        <td><?= (int)$row['seats'] ?></td>  
        <td><?= $row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '' ?></td>
        <td class="kho-actions">
          <a href="sua_xe.php?code=<?= urlencode($row['car_code']) ?>" class="btn btn-warning">Sửa</a>
          <a href="xoa_xe.php?code=<?= urlencode($row['car_code']) ?>" class="btn btn-danger" onclick="return confirm('Xoá xe này?')">Xoá</a>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if ($result->num_rows==0): ?>
        <tr><td colspan="16">Không tìm thấy xe nào.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include 'footer.php'; ?>

<?php

// locxe.php - hiển thị danh sách xe theo brand
require_once __DIR__ . '/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$brandParam = strtoupper(trim($_GET['brand'] ?? ''));
if ($brandParam === '') {
  header('Location: index.php'); exit;
}

// Lấy thông tin hãng
$st = $conn->prepare("SELECT brand_code, name, country
                      FROM brands
                      WHERE brand_code = ? OR UPPER(name) = ?");
$st->bind_param("ss", $brandParam, $brandParam);
$st->execute();
$brand = $st->get_result()->fetch_assoc();
$st->close();

if (!$brand) { // không có hãng -> quay về trang chính
  header('Location: index.php'); exit;
}

// Lấy xe thuộc hãng
$sql = "SELECT c.car_code, c.name, c.price,
               f.storage_path AS image
        FROM cars c
        LEFT JOIN car_images ci ON ci.car_code = c.car_code AND ci.is_primary = 1
        LEFT JOIN files f       ON f.file_code = ci.file_code
        WHERE c.brand_code = ?
        ORDER BY c.created_at DESC, c.name ASC";
$st = $conn->prepare($sql);
$st->bind_param("s", $brand['brand_code']);
$st->execute();
$cars = $st->get_result();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Xe <?= h($brand['name']) ?></title>
  <link rel="stylesheet" href="site.css"><!-- nếu có -->
  <style>
    .brand-header{max-width:1200px;margin:28px auto 8px;padding:0 16px}
    .brand-header h2{margin:0 0 6px}
    .car-grid{
      max-width:1200px;margin:12px auto 40px;padding:0 16px;
      display:grid;gap:18px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    }
    .car-card{border-radius:14px;background:#fff;box-shadow:0 8px 20px rgba(0,0,0,.06);overflow:hidden}
    .car-card img{width:100%;height:180px;object-fit:cover;display:block}
    .car-body{padding:12px 14px}
    .car-name{font-weight:700;margin:0 0 6px}
    .car-price{color:#d00;font-weight:700}
    .back-link{display:inline-block;margin:12px 16px}
  </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="brand-header">
  <a class="back-link" href="brands.php">&larr; Tất cả thương hiệu</a>
  <h2>Xe của hãng <?= h($brand['name']) ?> (<?= h($brand['brand_code']) ?>)</h2>
  <?php if (!empty($brand['country'])): ?>
    <div>Quốc gia: <?= h($brand['country']) ?></div>
  <?php endif; ?>
</div>

<div class="car-grid">
  <?php while ($c = $cars->fetch_assoc()):
    $img = $c['image'] ? ('/' . ltrim($c['image'], '/')) : 'LOGO/placeholder_car.jpg';
  ?>
    <div class="car-card">
      <a href="chitiet.php?car_code=<?= h($c['car_code']) ?>">
        <img src="<?= h($img) ?>" alt="<?= h($c['name']) ?>">
      </a>
      <div class="car-body">
        <h3 class="car-name"><?= h($c['name']) ?></h3>
        <div class="car-price">Giá: <?= number_format((float)$c['price'], 0, ',', '.') ?> ₫</div>
      </div>
    </div>
  <?php endwhile; $cars->free(); ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>

<?php
include 'header.php';
include 'connect.php';

// Bật báo lỗi MySQLi (giúp thấy lỗi sớm nếu có)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
?>

<section class="featured-section">
  <div class="featured-title">
    <h2>Xe Nổi Bật</h2>
    <p>Khám phá những mẫu xe sang trọng và đẳng cấp nhất trong bộ sưu tập của chúng tôi</p>
  </div>

  <div class="card-container">
    <?php
    // Lấy 10 xe giá cao nhất + tên hãng + ảnh chính (nếu có)
    $sql = "
      SELECT 
        c.car_code,
        c.name,
        b.name AS brand,
        f.storage_path
      FROM cars c
      JOIN brands b ON b.brand_code = c.brand_code
      LEFT JOIN car_images ci 
        ON ci.car_code = c.car_code AND ci.is_primary = 1
      LEFT JOIN files f 
        ON f.file_code = ci.file_code
      ORDER BY c.price DESC
      LIMIT 10
    ";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      while ($car = $result->fetch_assoc()) {
        $car_code   = htmlspecialchars($car['car_code']);
        $brand_name = htmlspecialchars($car['brand']);
        $name       = htmlspecialchars($car['name']);
        $image_path = $car['storage_path'] ?: 'images/default.jpg'; // đường dẫn ảnh mặc định

        echo '
        <div class="card">
          <a href="chitiet.php?car_code=' . $car_code . '">
            <div class="card-background" style="background-image: url(\'' . htmlspecialchars($image_path) . '\');"></div>
            <div class="content">
              <div class="card-category">' . $brand_name . '</div>
              <h3 class="card-heading">' . $name . '</h3>
            </div>
          </a>
        </div>';
      }
    } else {
      echo '<p style="text-align:center;color:#999;">Chưa có xe nào để hiển thị.</p>';
    }
    ?>
  </div>
</section>

<!-- ================== BRANDS ================== -->
<?php
// Dùng kết nối $conn có sẵn ở đầu file
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$sql = "
  SELECT b.brand_code, b.name,
         COALESCE(f.storage_path, '') AS logo
  FROM brands b
  LEFT JOIN files f ON f.file_code = b.logo_file_code
  ORDER BY b.name ASC
";
$brands = $conn->query($sql);
?>
<section class="section brands" id="brands">
  <div class="section-title">
    <h2>Thương Hiệu</h2>
    <p>Đối tác của chúng tôi - Những thương hiệu xe hơi hàng đầu thế giới</p>
  </div>

  <div class="brands-container">
    <?php while ($row = $brands->fetch_assoc()):
      $code = strtoupper($row['brand_code']);        // đảm bảo ổn định
      $name = $row['name'];
      // logo có thể rỗng -> dùng ảnh dự phòng
      $logo = trim($row['logo']) !== '' ? $row['logo'] : 'LOGO/placeholder.png';
      // chuẩn hóa đường dẫn (thêm / đầu nếu thiếu)
      if ($logo[0] !== '/' && !preg_match('~^https?://~i', $logo)) {
        $logo = '/'.ltrim($logo, '/');
      }
      // Link lọc theo brand_code (đúng và ổn định)
      $href = 'locxe.php?brand_code='.rawurlencode($code);
    ?>
      <a class="brand-item" href="<?= h($href) ?>" title="<?= h($name) ?>">
        <img src="<?= h($logo) ?>" alt="<?= h($name) ?>">
      </a>
    <?php endwhile; $brands->free(); ?>
  </div>
</section>

<style>
.section.brands{padding:48px 0;background:#f7f9fb}
.section.brands .section-title{text-align:center;margin-bottom:22px}
.section.brands .section-title h2{font-size:40px;margin:0 0 8px}
.brands-container{
  max-width:1200px;margin:0 auto;display:grid;
  grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
  gap:22px;padding:0 16px;
}
.brand-item{
  display:flex;align-items:center;justify-content:center;
  height:120px;border-radius:16px;background:#fff;
  box-shadow:0 8px 22px rgba(0,0,0,.06);
  transition:transform .15s ease, box-shadow .15s ease;
}
.brand-item:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(0,0,0,.1)}
.brand-item img{max-height:70px;max-width:80%;object-fit:contain;filter:grayscale(.1)}
</style>

<?php include 'footer.php'; ?>

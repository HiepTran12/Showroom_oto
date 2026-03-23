<?php
require_once 'connect.php';
include 'header.php';
$conn->set_charset('utf8mb4');

$q = trim($_GET['q'] ?? '');
?>
<section class="section">
  <div class="section-title">
    <h2>Kết quả tìm kiếm</h2>
    <p>Từ khóa: <b><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></b></p>
  </div>

  <div class="featured-carousel">
  <?php
  if ($q !== '') {
      $stmt = $conn->prepare("
        SELECT c.car_code, c.name, c.description, c.price, f.storage_path
        FROM cars c
        LEFT JOIN car_images ci ON c.car_code = ci.car_code AND ci.is_primary = 1
        LEFT JOIN files f ON ci.file_code = f.file_code
        WHERE c.name LIKE CONCAT('%', ?, '%')
           OR c.car_code LIKE CONCAT('%', ?, '%')
           OR c.description LIKE CONCAT('%', ?, '%')
        ORDER BY c.price DESC
      ");
      $stmt->bind_param("sss", $q, $q, $q);
      $stmt->execute();
      $rs = $stmt->get_result();

      if ($rs->num_rows > 0) {
          while ($car = $rs->fetch_assoc()) {
              echo '<div class="featured-card">
                      <a href="chitiet.php?car_code='.urlencode($car['car_code']).'">
                        <img src="'.htmlspecialchars($car['storage_path'] ?? '', ENT_QUOTES).'" 
                             alt="'.htmlspecialchars($car['name'], ENT_QUOTES).'" 
                             style="height:250px;width:100%;object-fit:cover;">
                        <div class="featured-info">
                          <h4>'.htmlspecialchars($car['name'], ENT_QUOTES).'</h4>
                          <p>'.number_format($car['price'],0,',','.').' VNĐ</p>
                        </div>
                      </a>
                    </div>';
          }
      } else {
          echo "<p style='text-align:center;'>Không tìm thấy xe nào phù hợp.</p>";
      }
      $stmt->close();
  }
  ?>
  </div>
</section>

<?php include 'footer.php'; ?>

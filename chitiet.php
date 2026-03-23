<?php
include 'header.php';
include 'connect.php';

$car_code = isset($_GET['car_code']) ? $conn->real_escape_string($_GET['car_code']) : '';

$sql = "
SELECT
    c.car_code,
    c.name,
    c.description,
    c.price,
    c.engine,
    c.power,
    c.acceleration,
    c.top_speed,
    c.fuel_type,
    c.transmission,
    c.seats,
    b.name AS brand_name,
    b.brand_code
FROM cars c
JOIN brands b ON c.brand_code = b.brand_code
WHERE c.car_code = '$car_code'
LIMIT 1
";
$result = $conn->query($sql) or die("Lỗi SQL: " . $conn->error);
$car = $result->fetch_assoc();

// Lấy tất cả ảnh xe
$images = [];
if ($car) {
    $img_sql = "
    SELECT COALESCE(f.storage_path, 'hinh/no-image.jpg') AS image
    FROM car_images ci
    LEFT JOIN files f ON ci.file_code = f.file_code
    WHERE ci.car_code = '$car_code'
    ORDER BY ci.is_primary DESC, ci.created_at ASC
    ";
    $img_res = $conn->query($img_sql);
    while ($row = $img_res->fetch_assoc()) {
        $images[] = $row['image'];
    }
}
?>

<?php if ($car): ?>

<div class="car-detail-page"><!-- Thêm bao ngoài để CSS áp dụng -->

<style>
/* ====== CSS CHI TIẾT XE ====== */
.car-detail-page {
  font-family: Arial, sans-serif;
  background: #fff;
  color: #1e293b;
}

.car-detail-page .car-detail {
  padding: 2rem 1rem;
  max-width: 1200px;
  margin: 0 auto;
}

.car-detail-page .car-detail-container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  align-items: stretch;
}

@media (min-width: 1024px) {
  .car-detail-page .car-detail-container {
    grid-template-columns: 60% 40%; /* Ảnh 60%, nội dung 40% */
    align-items: start;
  }
}

/* Ảnh chính */
.car-detail-page .car-image {
  position: relative;
}

.car-detail-page .main-image {
  border-radius: 1rem;
  overflow: hidden;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  aspect-ratio: 16/9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.car-detail-page .main-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.car-detail-page .main-image:hover img {
  transform: scale(1.05);
}

.car-detail-page .thumbnail-list {
  display: flex;
  gap: 0.75rem;
  overflow-x: auto;
  padding: 0.5rem 0;
}

.car-detail-page .thumbnail {
  flex: 0 0 auto;
  width: 80px;
  height: 60px;
  border-radius: 0.25rem;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.car-detail-page .thumbnail:hover,
.car-detail-page .thumbnail.active {
  border-color: #2563eb;
}

/* Thông tin */
.car-detail-page .car-info {
  display: flex;
  flex-direction: column;
  
}

.car-detail-page .car-info h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.car-detail-page .car-description {
  line-height: 1.6;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.car-detail-page .car-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ef4444;
  margin-bottom: 1.5rem;
}

.car-detail-page .car-specs {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.5rem;
}

.car-detail-page .car-specs td {
  padding: 0.75rem 0.5rem;
}

.car-detail-page .car-specs td:first-child {
  font-weight: 600;
  width: 40%;
}

/* Nút hành động */
.car-detail-page .action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.car-detail-page .btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
  flex: 1 1 auto;
  min-width: 150px;
}

.car-detail-page .btn-action.buy {
  background-color: #ef4444;
  color: white;
}

.car-detail-page .btn-action.buy:hover {
  background-color: #dc2626;
}

/* Xe liên quan */
.car-detail-page .related-cars {
    margin-top: 2rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 10px;
}

.car-detail-page .related-cars h3 {
    text-align: left; /* Căn trái tiêu đề */
    font-size: 1.4rem;
    font-weight: bold;
    margin-bottom: 1.5rem;
    color: #333;
}

.car-detail-page .related-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start; /* Căn trái danh sách */
    gap: 20px;
}

.car-detail-page .related-card {
    width: 250px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.car-detail-page .related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.car-detail-page .related-image {
    width: 100%;
    height: 150px;
    overflow: hidden;
}

.car-detail-page .related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.car-detail-page .related-card h4 {
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
    margin: 0.75rem 0;
    color: #222;
}

</style>

<section class="car-detail">
    <div class="car-detail-container">

        <!-- Ảnh xe -->
        <div class="car-image">
            <div class="main-image">
                <img id="mainCarImage" src="<?= htmlspecialchars($images[0] ?? 'hinh/no-image.jpg') ?>" alt="<?= htmlspecialchars($car['name']) ?>">
            </div>
            <div class="thumbnail-list">
                <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="thumbnail" onclick="changeMainImage(this)">
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Thông tin xe -->
        <div class="car-info">
            <h2><?= htmlspecialchars($car['name']) ?> - <?= htmlspecialchars($car['brand_name']) ?></h2>
            <p class="car-description"><?= nl2br(htmlspecialchars($car['description'])) ?></p>
            <p class="car-price">Giá: <strong><?= number_format($car['price'], 0, ',', '.') ?> VND</strong></p>

            <table class="car-specs">
                <tr><td>Động cơ:</td><td><?= htmlspecialchars($car['engine']) ?></td></tr>
                <tr><td>Công suất:</td><td><?= htmlspecialchars($car['power']) ?></td></tr>
                <tr><td>Tăng tốc:</td><td><?= htmlspecialchars($car['acceleration']) ?></td></tr>
                <tr><td>Tốc độ tối đa:</td><td><?= htmlspecialchars($car['top_speed']) ?> km/h</td></tr>
                <tr><td>Nhiên liệu:</td><td><?= htmlspecialchars($car['fuel_type']) ?></td></tr>
                <tr><td>Hộp số:</td><td><?= htmlspecialchars($car['transmission']) ?></td></tr>
                <tr><td>Số chỗ:</td><td><?= htmlspecialchars($car['seats']) ?></td></tr>
            </table>

            <div class="action-buttons">
                <a href="datmua.php?car_code=<?= urlencode($car['car_code']) ?>" class="btn-action buy">🛒 Đặt mua</a>
                <a href="#" onclick="toggleCall(this)" class="btn-action book">📞 Liên hệ</a>
            </div>
        </div>

    </div>
</section>

<!-- Xe liên quan -->
<section class="related-cars">
    <h3>Các xe khác cùng hãng <?= htmlspecialchars($car['brand_name']) ?>:</h3>
    <div class="related-list">
        <?php
        $brand_code = $conn->real_escape_string($car['brand_code']);
        $related_sql = "
  SELECT
    c.car_code,
    c.name,
    COALESCE((
      SELECT f.storage_path
      FROM car_images ci2
      JOIN files f ON f.file_code = ci2.file_code
      WHERE ci2.car_code = c.car_code
      ORDER BY ci2.is_primary DESC, ci2.created_at ASC, ci2.car_image_code ASC
      LIMIT 1
    ), 'hinh/no-image.jpg') AS image
  FROM cars c
  WHERE c.brand_code = '{$brand_code}' AND c.car_code <> '{$car['car_code']}'
  LIMIT 4
";

        $related = $conn->query($related_sql);
        while ($relatedCar = $related->fetch_assoc()):
        ?>
            <div class="related-card">
                <a href="chitiet.php?car_code=<?= urlencode($relatedCar['car_code']) ?>">
                    <div class="related-image">
                        <img src="<?= htmlspecialchars($relatedCar['image']) ?>" alt="<?= htmlspecialchars($relatedCar['name']) ?>">
                    </div>
                    <h4><?= htmlspecialchars($relatedCar['name']) ?></h4>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
</section>

</div><!-- đóng .car-detail-page -->

<?php else: ?>
    <p style="padding: 20px; color: red;">Không tìm thấy xe.</p>
<?php endif; ?>

<script>
function changeMainImage(el) {
    document.getElementById("mainCarImage").src = el.src;
    document.querySelectorAll(".thumbnail").forEach(img => img.classList.remove("active"));
    el.classList.add("active");
}

function toggleCall(button) {
    const number = "0901 234 567";
    if (!button.dataset.clicked) {
        button.textContent = "📞 " + number;
        button.dataset.clicked = "1";
    } else {
        window.location.href = "tel:" + number;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const thumbnails = document.querySelectorAll(".thumbnail-list img");
    const mainImage = document.getElementById("mainCarImage");
    if (!thumbnails.length) return;

    let currentIndex = 0;
    thumbnails[currentIndex].classList.add("active");

    setInterval(() => {
        // Bỏ active cũ
        thumbnails[currentIndex].classList.remove("active");

        // Tăng index, quay lại 0 nếu hết ảnh
        currentIndex = (currentIndex + 1) % thumbnails.length;

        // Đổi ảnh chính
        mainImage.src = thumbnails[currentIndex].src;

        // Thêm active mới
        thumbnails[currentIndex].classList.add("active");

        // Cuộn thumbnail-list nếu bị tràn
        thumbnails[currentIndex].scrollIntoView({
            behavior: "smooth",
            inline: "center",
            block: "nearest"
        });
    }, 2000);
});

</script>


<?php
$conn->close();
include 'footer.php';
?>


<?php
// ================== BOOTSTRAP ==================
if (session_status() === PHP_SESSION_NONE)
require_once 'connect.php';
include 'header.php';
// Bật lỗi khi dev (có thể tắt khi lên production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ================== XỬ LÝ SUBMIT FEEDBACK ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feedback'])) {
    $comment  = trim($_POST['comment'] ?? '');
    $rating   = ($_POST['rating'] ?? '') === '' ? null : (int)$_POST['rating'];
    $car_code = trim($_POST['car_code'] ?? '');
    if ($car_code === '') $car_code = null;

    if ($comment === '') {
        $_SESSION['fb_msg'] = 'Nội dung phản hồi không được để trống.';
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }

    if (!isset($_SESSION['username'])) {
        $_SESSION['fb_msg'] = 'Bạn cần đăng nhập để gửi phản hồi.';
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }

    // Lấy customer_code từ users
    $stmt = $conn->prepare("SELECT customer_code FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($u['customer_code'])) {
        $_SESSION['fb_msg'] = 'Tài khoản chưa liên kết khách hàng.';
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }
    $customer_code = $u['customer_code'];

    // Nếu có chọn xe thì xác thực tồn tại
    if (!is_null($car_code)) {
        $stmt = $conn->prepare("SELECT 1 FROM cars WHERE car_code = ? LIMIT 1");
        $stmt->bind_param("s", $car_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows !== 1) {
            $stmt->close();
            $_SESSION['fb_msg'] = 'Mã xe không hợp lệ.';
            header('Location: '.$_SERVER['REQUEST_URI']); exit;
        }
        $stmt->close();
    }

    // Sinh mã đơn giản (tránh phụ thuộc bảng sequence)
    $feedback_no = 'FB'.date('YmdHis');

    // Chèn phản hồi
    $stmt = $conn->prepare("
        INSERT INTO customer_feedback (feedback_no, customer_code, car_code, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssds", $feedback_no, $customer_code, $car_code, $rating, $comment);
    $stmt->execute();

    $_SESSION['fb_msg'] = 'Gửi phản hồi thành công!';
    header('Location: '.$_SERVER['REQUEST_URI']); // tránh submit lại khi F5
    exit;
}

// ================== DATA CHO DROPDOWN XE ==================
$cars = [];
$resCars = $conn->query("SELECT car_code, name FROM cars ORDER BY name ASC");
if ($resCars) {
    while ($c = $resCars->fetch_assoc()) $cars[] = $c;
    $resCars->free();
}
?>

<!-- ================== HERO ================== -->
<section class="hero">
  <video class="hero-video" autoplay muted loop>
    <source src="LOGO/video.mp4" type="video/mp4">
  </video>
  <div class="hero-content">
    <h1 align="left">Khám phá thế giới xe sang</h1>
    <p>Luxury Cars mang đến cho bạn những mẫu xe sang trọng nhất, đẳng cấp nhất với dịch vụ chuyên nghiệp và tận tâm.</p>
    <div class="btn-group">
      <a href="#xnb" class="btn">Khám phá ngay</a>
    </div>
  </div>
</section>

<form method="get" action="timkiem.php" class="search-form">
  <input type="text" name="q" placeholder="Tìm kiếm xe..." required>
  <button type="submit">Tìm kiếm</button>
</form>

<style>
.search-form{
  display:flex;justify-content:center;margin:20px auto;max-width:500px;
}
.search-form input{
  flex:1;padding:10px;border:1px solid #ccc;border-radius:8px 0 0 8px;outline:none;
}
.search-form button{
  padding:10px 15px;border:none;background:#111;color:#fff;
  border-radius:0 8px 8px 0;cursor:pointer;font-weight:bold;
}
.search-form button:hover{background:#333;}
</style>

<!-- ================== FEATURED CARS ================== -->
<section class="featured-section">
  <div class="featured-title" id="xnb">
    <h2>Xe Nổi Bật</h2>
    <p>Khám phá những mẫu xe sang trọng và đẳng cấp nhất trong bộ sưu tập của chúng tôi</p>
  </div>

  <div class="featured-carousel">
    <?php
    $sql = "
SELECT
  c.car_code,
  c.name,
  (
    SELECT f.storage_path
    FROM car_images ci2
    JOIN files f ON f.file_code = ci2.file_code
    WHERE ci2.car_code = c.car_code
    ORDER BY ci2.is_primary DESC, ci2.created_at ASC, ci2.car_image_code ASC
    LIMIT 1
  ) AS storage_path
FROM cars c
ORDER BY c.price DESC
LIMIT 10";

    if ($result = $conn->query($sql)):
      while ($car = $result->fetch_assoc()):
    ?>
      <div class="featured-card">
        <a href="chitiet.php?car_code=<?= urlencode($car['car_code']) ?>">
          <img src="<?= htmlspecialchars($car['storage_path'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($car['name'], ENT_QUOTES, 'UTF-8') ?>"
               style="height:300px;width:100%;object-fit:cover;">
          <div class="featured-info">
            <h4><?= htmlspecialchars($car['name'], ENT_QUOTES, 'UTF-8') ?></h4>
          </div>
        </a>
      </div>
    <?php endwhile; $result->free(); endif; ?>
  </div>
</section>

<!-- ================== BRANDS ================== -->
<?php
// brands_section.php
require_once __DIR__ . '/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$sql = "SELECT b.brand_code, b.name,
               COALESCE(f.storage_path,'') AS logo
        FROM brands b
        LEFT JOIN files f ON f.file_code = b.logo_file_code
        ORDER BY b.name";
$brands = $conn->query($sql);
?>
<section class="section brands" id="brands">
  <div class="section-title">
    <h2>Thương Hiệu</h2>
    <p>Đối tác của chúng tôi - Những thương hiệu xe hơi hàng đầu thế giới</p>
  </div>

  <div class="brands-container">
    <?php while ($row = $brands->fetch_assoc()): 
      $code = strtoupper($row['brand_code']);
      // logo có thể rỗng -> dùng ảnh dự phòng
      $logo = trim($row['logo']) !== '' ? ('/' . ltrim($row['logo'], '/')) : 'LOGO/placeholder.png';
    ?>
      <a class="brand-item" href="locxe.php?brand=<?= h($code) ?>" title="<?= h($row['name']) ?>">
        <img src="<?= h($logo) ?>" alt="<?= h($row['name']) ?>">
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


<!-- ================== TESTIMONIALS + FORM ================== -->
<section class="section testimonials">
  <div class="section-title">
    <h2 class="white">Khách Hàng Nói Gì Về Chúng Tôi</h2>
    <p>Những phản hồi từ khách hàng đã trải nghiệm dịch vụ của Luxury Motors</p>
  </div>

  <!-- Thông báo -->
  <?php if (!empty($_SESSION['fb_msg'])): ?>
    <div class="feedback-message alert success" style="margin:12px auto;">
      <?= htmlspecialchars($_SESSION['fb_msg'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['fb_msg']); ?>
  <?php endif; ?>

  <!-- Form -->
  <div class="feedback-panel">
    <form id="feedbackForm" method="post" class="testimonial-form" style="margin:0;" onsubmit="return kiemTraDangNhap();">
      <input type="hidden" name="add_feedback" value="1">

      <div class="form-group" style="margin-bottom:12px;">
        <label for="comment">Nội dung phản hồi</label>
        <textarea id="comment" name="comment" class="feedback-textarea" required></textarea>
      </div>

      <div class="feedback-row">
        <div class="feedback-col">
          <label for="rating">Đánh giá (1–5)</label>
          <select id="rating" name="rating" class="feedback-select">
            <option value="">-- Chọn --</option>
            <option value="5">5 - Tuyệt vời</option>
            <option value="4">4 - Hài lòng</option>
            <option value="3">3 - Bình thường</option>
            <option value="2">2 - Chưa tốt</option>
            <option value="1">1 - Tệ</option>
          </select>
        </div>

        <div class="feedback-col wide">
          <label for="car_code">Xe đã trải nghiệm (tuỳ chọn)</label>
          <select id="car_code" name="car_code" class="feedback-select">
            <option value="">-- Không chọn --</option>
            <?php foreach ($cars as $c): ?>
              <option value="<?= htmlspecialchars($c['car_code'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($c['car_code'], ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <button type="submit" class="feedback-submit">Gửi phản hồi</button>
    </form>
  </div>

  <!-- Danh sách 3 phản hồi mới nhất -->
  <div class="testimonials-container">
  <?php
  $sql = "SELECT cf.comment, cf.rating, cf.created_at, cus.full_name
          FROM customer_feedback AS cf
          INNER JOIN customers AS cus ON cf.customer_code = cus.customer_code
          ORDER BY cf.created_at DESC
          LIMIT 3";
  $rs = $conn->query($sql);

  if ($rs && $rs->num_rows > 0):
    while ($row = $rs->fetch_assoc()):
      $stars = isset($row['rating']) ? (int)$row['rating'] : 0;
  ?>
    <div class="testimonial-card">
      <div class="testimonial-header">
        <div class="author-meta">
          <h4 class="author-name"><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></h4>
          <p class="author-date"><?= date("d/m/Y", strtotime($row['created_at'])) ?></p>
        </div>

        <?php if ($stars > 0): ?>
          <div class="rating-stars" aria-label="Đánh giá <?= $stars ?>/5 sao">
            <?php
            // vẽ 5 sao: sao đầy trước, sao rỗng sau
            for ($i=1; $i<=5; $i++) {
              if ($i <= $stars) echo '<i class="fa-solid fa-star"></i>';
              else echo '<i class="fa-regular fa-star empty"></i>';
            }
            ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="testimonial-text">
        <?= nl2br(htmlspecialchars($row['comment'], ENT_QUOTES, 'UTF-8')) ?>
      </div>
    </div>
  <?php
    endwhile;
  else:
    echo '<p style="text-align:center;">Chưa có phản hồi nào từ khách hàng.</p>';
  endif;
  ?>
  </div>

</section>

<!-- ================== MODAL ĐĂNG NHẬP ================== -->
<div id="loginModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
  <div class="modal-content">
    <h2 id="loginModalTitle">⚠ Bạn chưa đăng nhập</h2>
    <p>Bạn cần đăng nhập để gửi ý kiến. Bạn có muốn chuyển đến trang đăng nhập không?</p>
    <div class="modal-buttons">
      <!-- Tránh dấu cách trong đường dẫn: đổi thư mục hoặc encode %20 -->
      <button onclick="window.location.href='Taikhoan%20(1)/dangnhap.php'">Đăng nhập</button>
      <button onclick="closeModal()">Hủy</button>
    </div>
  </div>
</div>

<!-- ================== CTA ================== -->
<section class="cta">
  <div class="cta-content">
    <h2>Sẵn sàng sở hữu chiếc xe mơ ước?</h2>
    <p>Hãy để lại thông tin, chúng tôi sẽ liên hệ tư vấn miễn phí và hỗ trợ bạn chọn được chiếc xe phù hợp nhất.</p>
  </div>
</section>

<!-- ================== JS ================== -->
<script>
  // trạng thái đăng nhập từ PHP
  const isLoggedIn = <?= isset($_SESSION['username']) ? 'true' : 'false' ?>;

  // kiểm tra đăng nhập cho form & CTA
  function kiemTraDangNhap() {
    if (!isLoggedIn) {
      document.getElementById("loginModal").style.display = "flex";
      document.body.style.overflow = 'hidden';
      return false;
    }
    return true;
  }
  function closeModal() {
    document.getElementById("loginModal").style.display = "none";
    document.body.style.overflow = '';
  }
  // đóng modal khi click nền
  document.getElementById('loginModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'loginModal') closeModal();
  });
  // ESC đóng modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // Tự ẩn alert sau 3 giây
  window.addEventListener("DOMContentLoaded", function () {
    const alertBox = document.querySelector(".alert");
    if (alertBox) {
      setTimeout(() => {
        alertBox.style.opacity = "0";
        setTimeout(() => alertBox.remove(), 500);
      }, 3000);
    }
  });
</script>

<!-- ================== CSS ================== -->
<style>
  .testimonials {
    background-color: var(--primary);
    color: white;
    position: relative;
    overflow: hidden;
}

.testimonials::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
    opacity: 0.2;
    z-index: 0;
}

.section-title h2.white {
    color: white;
}

/* === Testimonials cards === */
.testimonials-container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}
@media (min-width: 768px) {
  .testimonials-container { grid-template-columns: 1fr; } /* đang hiển thị 3 cái dọc, cần 2-3 cột bạn đổi ở đây */
}

.testimonial-card {
  background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px;
  padding: 18px 20px;
  box-shadow: 0 10px 24px rgba(0,0,0,.18);
  backdrop-filter: blur(4px);
  transition: transform .2s ease, box-shadow .2s ease;
}
.testimonial-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(0,0,0,.25);
}

.testimonial-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.author-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.author-name {
  margin: 0;
  font-weight: 700;
  color: #f7f7f7;
  letter-spacing: .2px;
}
.author-date {
  margin: 0;
  color: #cfcfcf;
  font-size: 13px;
  font-size: clamp(14px, 1.2vw, 16px);
}

.rating-stars { display: inline-flex; gap: 2px; white-space: nowrap; }
.rating-stars i { color: #f1c40f; font-size: 16px; }
.rating-stars .empty { color: #777; opacity: .45; }

/* comment lớn + dấu ngoặc trích */
.testimonial-text {
  position: relative;
  font-size: 27px;
  line-height: 1.7;
  color: #fff;
  padding: 6px 0 4px 0;
}
.testimonial-text:before {
  font-size: clamp(30px, 3vw, 40px);
  top: -8px; left: -12px;
}
.testimonial-text:after {
  content: "“";
  position: absolute;
  top: -6px; left: -10px;
  font-size: 28px;
  color: rgba(241,196,15,.9);
  font-family: Georgia, serif;
  font-size: clamp(26px, 2.4vw, 34px);
  bottom: -12px; right: -10px;
}
.testimonial-text:after {
  content: "”";
  top: auto; bottom: -10px; right: -6px; left: auto;
  font-size: 24px;
  opacity: .8;
}
x
  /* Alert */
  .alert {
    max-width: 600px; margin: 20px auto; padding: 15px 20px;
    border-radius: 8px; font-size: 16px; font-weight: 500;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: center;
    border-left: 5px solid; transition: opacity .3s ease;
  }
  .alert.success { background:#e9f9ec; color:#2ecc71; border-color:#2ecc71; }
  .alert.error   { background:#fdecea; color:#e74c3c; border-color:#e74c3c; }

  /* Modal */
  .modal {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.5); backdrop-filter: blur(4px);
    justify-content:center; align-items:center;
  }
  .modal-content {
    background: rgba(255,255,255,.15); backdrop-filter: blur(8px);
    border-radius:20px; padding:30px 40px; text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.3); color:white;
    width:90%; max-width:400px;
  }
  .modal-buttons { display:flex; justify-content:center; gap:15px; }
  .modal-buttons button {
    padding:10px 20px; border:0; border-radius:8px; font-size:16px; cursor:pointer;
  }
  .modal-buttons button:first-child { background:#f1c40f; color:#000; }
  .modal-buttons button:last-child  { background:transparent; border:1px solid #fff; color:#fff; }

  /* Form feedback */
  .section.testimonials { position:relative; isolation:isolate; z-index:10; }
  .feedback-panel {
    max-width:820px; margin:0 auto 28px; padding:22px 22px 18px;
    background:rgba(0,0,0,.45); border:1px solid rgba(255,255,255,.08);
    border-radius:14px; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
    box-shadow:0 10px 28px rgba(0,0,0,.25); position:relative; z-index:2;
  }
  .feedback-panel label { display:block; margin-bottom:6px; color:#f4f4f4; font-weight:600; letter-spacing:.2px; }
  .feedback-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
  .feedback-col { flex:1 1 160px; min-width:160px; }
  .feedback-col.wide { flex:2 1 220px; min-width:220px; }
  .feedback-input,.feedback-select,.feedback-textarea{
    width:100%; padding:12px 14px; border:1px solid #dcdcdc; border-radius:12px;
    background:rgba(255,255,255,.92); color:#111; font-size:15px; outline:none;
    box-shadow: inset 0 2px 8px rgba(0,0,0,.08);
  }
  .feedback-textarea{ resize:vertical; min-height:110px; }
  .feedback-input:focus,.feedback-select:focus,.feedback-textarea:focus{
    border-color:#bcbcbc; box-shadow:0 0 0 3px rgba(255,255,255,.25);
  }
  .feedback-submit{
    display:inline-block; padding:12px 22px; background:#111; color:#fff; border:0;
    border-radius:12px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
    cursor:pointer; transition:transform .04s ease, background .2s ease; box-shadow:0 6px 20px rgba(0,0,0,.25);
  }
  .feedback-submit:hover{ background:#1a1a1a; }
  .feedback-submit:active{ transform: translateY(1px); }

  @media (max-width:480px){
    .feedback-panel{ padding:18px; border-radius:12px; }
    .feedback-submit{ width:100%; text-align:center; }
  }
</style>

<?php include 'footer.php'; ?>

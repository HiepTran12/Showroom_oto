<?php
ob_start();

include 'auth_check.php';
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/lib_upload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// Helper: URL đã có APP_BASE, chỉ cần escape
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Lấy car_code
$car_code = isset($_GET['code']) ? trim($_GET['code']) : '';
if ($car_code === '') { die('Mã xe không hợp lệ.'); }

// Lấy xe
$st = $conn->prepare("SELECT * FROM cars WHERE car_code=?");
$st->bind_param("s", $car_code);
$st->execute();
$car = $st->get_result()->fetch_assoc();
if (!$car) { die('Không tìm thấy xe.'); }

// Lấy hãng
$brands = $conn->query("SELECT brand_code, name FROM brands ORDER BY name");

// ====== POST actions ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_car') {
        $brand_code  = strtoupper(trim($_POST['brand_code']));
        $name        = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price       = (float)($_POST['price'] ?? 0);
        $engine      = trim($_POST['engine']);
        $power       = trim($_POST['power']);
        $acceleration= trim($_POST['acceleration']);
        $top_speed   = trim($_POST['top_speed']);
        $fuel_type   = trim($_POST['fuel_type']);
        $transmission= trim($_POST['transmission']);
        $seats       = ($_POST['seats'] !== '' && $_POST['seats'] !== null) ? (int)$_POST['seats'] : null;

        $up = $conn->prepare("
            UPDATE cars SET
              brand_code=?, name=?, description=?, price=?, engine=?, power=?, acceleration=?, top_speed=?, fuel_type=?, transmission=?, seats=?
            WHERE car_code=?
        ");
        $up->bind_param(
            "sssdssssssis",
            $brand_code, $name, $description, $price, $engine, $power,
            $acceleration, $top_speed, $fuel_type, $transmission, $seats, $car_code
        );
        $up->execute();

        header("Location: xe.php"); exit;
    }

    if ($action === 'upload_images') {
        if (!empty($_FILES['new_images']['name'][0])) {
            // đã có ảnh chính?
            $hp = $conn->prepare("SELECT 1 FROM car_images WHERE car_code=? AND is_primary=1 LIMIT 1");
            $hp->bind_param("s", $car_code);
            $hp->execute();
            $hasPrimary = $hp->get_result()->num_rows > 0;

            // đếm hiện có
            $cnt = $conn->prepare("SELECT COUNT(*) c FROM car_images WHERE car_code=?");
            $cnt->bind_param("s", $car_code);
            $cnt->execute();
            $count = (int)($cnt->get_result()->fetch_assoc()['c'] ?? 0);

            foreach ($_FILES['new_images']['name'] as $i => $n) {
                $f = [
                    'name'     => $_FILES['new_images']['name'][$i] ?? '',
                    'type'     => $_FILES['new_images']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['new_images']['tmp_name'][$i] ?? '',
                    'error'    => $_FILES['new_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $_FILES['new_images']['size'][$i] ?? 0,
                ];

                // QUAN TRỌNG: ownerCode = $car_code để lưu đúng thư mục
                [$file_code, $url] = upload_and_register_file($conn, $f, 'cars', $car_code);
                if (!$file_code) continue;

                $isPrimary = $hasPrimary ? 0 : 1;
                if ($isPrimary) $hasPrimary = true;

                $order = $count + $i + 1;
                $car_image_code = next_car_image_code($car_code, $order);

                $ins = $conn->prepare("
                    INSERT INTO car_images (car_image_code, car_code, file_code, is_primary, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $ins->bind_param("sssi", $car_image_code, $car_code, $file_code, $isPrimary);
                $ins->execute();
            }
        }
        header("Location: sua_xe.php?code=".urlencode($car_code)); exit;
    }

    if ($action === 'set_primary' && !empty($_POST['car_image_code'])) {
        $cic = $_POST['car_image_code'];
        $off = $conn->prepare("UPDATE car_images SET is_primary=0 WHERE car_code=?");
        $off->bind_param("s", $car_code); $off->execute();

        $on  = $conn->prepare("UPDATE car_images SET is_primary=1 WHERE car_image_code=? AND car_code=?");
        $on->bind_param("ss", $cic, $car_code); $on->execute();

        header("Location: sua_xe.php?code=".urlencode($car_code)); exit;
    }

    if ($action === 'delete_image' && !empty($_POST['car_image_code'])) {
        $cic = $_POST['car_image_code'];

        $q = $conn->prepare("
            SELECT ci.file_code, f.storage_path
            FROM car_images ci JOIN files f ON f.file_code=ci.file_code
            WHERE ci.car_image_code=? AND ci.car_code=? LIMIT 1
        ");
        $q->bind_param("ss", $cic, $car_code);
        $q->execute();
        if ($row = $q->get_result()->fetch_assoc()) {
            $file_code   = $row['file_code'];
            $storage_url = $row['storage_path'];

            // xoá liên kết
            $del = $conn->prepare("DELETE FROM car_images WHERE car_image_code=? AND car_code=?");
            $del->bind_param("ss", $cic, $car_code);
            $del->execute();

            // còn ai dùng file_code không?
            $chk = $conn->prepare("SELECT COUNT(*) c FROM car_images WHERE file_code=?");
            $chk->bind_param("s", $file_code);
            $chk->execute();
            $c = (int)($chk->get_result()->fetch_assoc()['c'] ?? 0);

            if ($c === 0) {
                $abs = storage_to_abs($storage_url);
                if (is_file($abs)) @unlink($abs);

                $delf = $conn->prepare("DELETE FROM files WHERE file_code=?");
                $delf->bind_param("s", $file_code);
                $delf->execute();
            }

            // nếu không còn ảnh chính -> set ảnh cũ nhất làm chính
            $p = $conn->prepare("SELECT 1 FROM car_images WHERE car_code=? AND is_primary=1 LIMIT 1");
            $p->bind_param("s", $car_code); $p->execute();
            if ($p->get_result()->num_rows === 0) {
                $any = $conn->prepare("SELECT car_image_code FROM car_images WHERE car_code=? ORDER BY created_at ASC LIMIT 1");
                $any->bind_param("s", $car_code); $any->execute();
                if ($rowAny = $any->get_result()->fetch_assoc()) {
                    $set1 = $conn->prepare("UPDATE car_images SET is_primary=1 WHERE car_image_code=?");
                    $set1->bind_param("s", $rowAny['car_image_code']); $set1->execute();
                }
            }
        }
        header("Location: sua_xe.php?code=".urlencode($car_code)); exit;
    }
}

// Ảnh để hiển thị
$imgStmt = $conn->prepare("
    SELECT ci.car_image_code, ci.file_code, ci.is_primary, ci.created_at, f.storage_path
    FROM car_images ci JOIN files f ON f.file_code = ci.file_code
    WHERE ci.car_code = ?
    ORDER BY ci.is_primary DESC, ci.created_at ASC
");
$imgStmt->bind_param("s", $car_code);
$imgStmt->execute();
$imgs = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/header.php';
ob_end_flush();
?>
<head>
  <meta charset="UTF-8">
  <title>Sửa thông tin xe</title>
  <style>
    .them-xe-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,.05)}
    .them-xe-form .mb-3{display:flex;flex-direction:column}
    .them-xe-form label{font-weight:600;margin-bottom:5px;color:#333}
    .them-xe-form input,.them-xe-form textarea,.them-xe-form select{padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:14px}
    .them-xe-form textarea{resize:vertical;min-height:60px}
    .btn{display:inline-block;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:600;cursor:pointer}
    .btn-success{background:#28a745;color:#fff;border:none}
    .btn-secondary{background:#6c757d;color:#fff}
    .img-section{margin-top:28px;background:#fff;padding:16px;border-radius:10px;box-shadow:0 4px 6px rgba(0,0,0,.05)}
    .img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
    .img-card{border:1px solid #e5e5e5;border-radius:10px;padding:10px;text-align:center}
    .img-card img{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px}
    .tag{display:inline-block;margin-top:6px;padding:3px 8px;font-size:12px;border-radius:999px;background:#f1f3f5}
    .actions{display:flex;gap:6px;justify-content:center;margin-top:8px}
    .btn-primary{background:#0d6efd;color:#fff;border:none;padding:8px 10px;border-radius:6px;cursor:pointer}
    .btn-danger{background:#dc3545;color:#fff;border:none;padding:8px 10px;border-radius:6px;cursor:pointer}
    .muted{color:#6c757d;font-size:12px;margin-top:4px}
  </style>
</head>

<div class="main-content">
  <div class="container-fluid px-4 py-4">
    <h2>Sửa thông tin xe: <?= h($car['name']) ?></h2>

    <form method="POST" class="them-xe-form">
      <input type="hidden" name="action" value="update_car">
      <div class="mb-3">
        <label>Hãng xe</label>
        <select name="brand_code" required>
          <?php mysqli_data_seek($brands,0); while($r=$brands->fetch_assoc()): $code=strtoupper($r['brand_code']); ?>
            <option value="<?= h($code) ?>" <?= $code===strtoupper($car['brand_code'])?'selected':'' ?>>
              <?= h($r['name']) ?> (<?= h($code) ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3"><label>Tên xe</label><input type="text" name="name" value="<?= h($car['name']) ?>" required></div>
      <div class="mb-3"><label>Mô tả</label><textarea name="description"><?= h($car['description']) ?></textarea></div>
      <div class="mb-3"><label>Giá</label><input type="number" step="0.01" name="price" value="<?= h($car['price']) ?>" required></div>
      <div class="mb-3"><label>Động cơ</label><input type="text" name="engine" value="<?= h($car['engine']) ?>"></div>
      <div class="mb-3"><label>Công suất</label><input type="text" name="power" value="<?= h($car['power']) ?>"></div>
      <div class="mb-3"><label>Gia tốc (0-100)</label><input type="text" name="acceleration" value="<?= h($car['acceleration']) ?>"></div>
      <div class="mb-3"><label>Tốc độ tối đa</label><input type="text" name="top_speed" value="<?= h($car['top_speed']) ?>"></div>
      <div class="mb-3"><label>Nhiên liệu</label><input type="text" name="fuel_type" value="<?= h($car['fuel_type']) ?>"></div>
      <div class="mb-3"><label>Hộp số</label><input type="text" name="transmission" value="<?= h($car['transmission']) ?>"></div>
      <div class="mb-3"><label>Số ghế</label><input type="number" name="seats" value="<?= h((string)$car['seats']) ?>"></div>

      <div class="mb-3" style="grid-column:1/-1;">
        <button class="btn btn-success">Cập nhật</button>
        <a href="xe.php" class="btn btn-secondary">Hủy</a>
      </div>
    </form>

    <div class="img-section">
      <h3>Ảnh của xe</h3>
      <?php if (!$imgs): ?>
        <p class="muted">Chưa có ảnh.</p>
      <?php else: ?>
        <div class="img-grid">
          <?php foreach ($imgs as $im):
              $url = $im['storage_path'];            // URL public (đã có APP_BASE)
              $abs = storage_to_abs($im['storage_path']); // đường dẫn thật
          ?>
            <div class="img-card">
              <img src="<?= h($url) ?>" alt="Ảnh xe">
              <?php if (!is_file($abs)): ?>
                <div class="muted">* Không tìm thấy file trên đĩa: <?= h($abs) ?></div>
              <?php endif; ?>
              <div class="tag"><?= $im['is_primary'] ? 'Ảnh chính' : 'Phụ' ?></div>
              <div class="actions">
                <form method="POST">
                  <input type="hidden" name="action" value="set_primary">
                  <input type="hidden" name="car_image_code" value="<?= h($im['car_image_code']) ?>">
                  <button class="btn-primary" <?= $im['is_primary']?'disabled':'' ?>>Đặt ảnh chính</button>
                </form>
                <form method="POST" onsubmit="return confirm('Xoá ảnh này?')">
                  <input type="hidden" name="action" value="delete_image">
                  <input type="hidden" name="car_image_code" value="<?= h($im['car_image_code']) ?>">
                  <button class="btn-danger">Xoá</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="action" value="upload_images">
        <input type="file" name="new_images[]" accept="image/*" multiple required>
        <button class="btn btn-success">Tải ảnh lên</button>
        <span class="muted">Nếu chưa có ảnh chính, ảnh đầu tiên sẽ là ảnh chính.</span>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

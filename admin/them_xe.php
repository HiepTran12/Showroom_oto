<?php
include 'auth_check.php';
include __DIR__ . '/../connect.php';
require_once __DIR__ . '/lib_upload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// ===== Lấy danh sách hãng =====
$brands = $conn->query("SELECT brand_code, name FROM brands ORDER BY name");

// ===== Hàm sinh mã xe theo hãng =====
function generateCarCode(mysqli $conn, string $brand_code): string {
    $brand_code = strtoupper(trim($brand_code));
    $st = $conn->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(car_code,'-',-1) AS UNSIGNED)) AS m
        FROM cars WHERE brand_code=?
    ");
    $st->bind_param("s", $brand_code);
    $st->execute();
    $m = (int)($st->get_result()->fetch_assoc()['m'] ?? 0);
    $st->close();
    $next = $m + 1;
    return sprintf('CAR-%s-%03d', $brand_code, $next);
}

// ===== API AJAX: lấy mã xe theo hãng =====
if (isset($_GET['brand_code'])) {
    echo generateCarCode($conn, $_GET['brand_code']);
    exit;
}

// ===== Xử lý POST thêm xe =====
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $brand_code  = strtoupper(trim($_POST['brand_code'] ?? ''));
        if ($brand_code === '') {
            throw new RuntimeException('Chưa chọn hãng xe.');
        }

        // luôn sinh CAR-... trên server, KHÔNG lấy từ client
        $car_code    = generateCarCode($conn, $brand_code);

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $engine      = trim($_POST['engine'] ?? '');
        $power       = trim($_POST['power'] ?? '');
        $acceleration= trim($_POST['acceleration'] ?? '');
        $top_speed   = trim($_POST['top_speed'] ?? '');
        $fuel_type   = trim($_POST['fuel_type'] ?? '');
        $transmission= trim($_POST['transmission'] ?? '');
        $seats       = ($_POST['seats'] !== '' && $_POST['seats'] !== null) ? (int)$_POST['seats'] : null;

        if ($name === '' || $price <= 0) {
            throw new RuntimeException('Tên xe và Giá là bắt buộc.');
        }

        $conn->begin_transaction();

        // (1) Thêm xe
        $stmt = $conn->prepare("
            INSERT INTO cars
            (car_code, brand_code, name, description, price, engine, power, acceleration, top_speed, fuel_type, transmission, seats, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "ssssdssssssi",
            $car_code, $brand_code, $name, $description, $price, $engine, $power,
            $acceleration, $top_speed, $fuel_type, $transmission, $seats
        );
        $stmt->execute();
        $stmt->close();

        // (2) Upload ảnh → files → car_images (ảnh đầu là ảnh chính)
        if (!empty($_FILES['car_images']['name'][0])) {
            $primary = 1;
            $cnt = count($_FILES['car_images']['name']);
            for ($i = 0; $i < $cnt; $i++) {
                $f = [
                    'name'     => $_FILES['car_images']['name'][$i] ?? '',
                    'type'     => $_FILES['car_images']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['car_images']['tmp_name'][$i] ?? '',
                    'error'    => $_FILES['car_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $_FILES['car_images']['size'][$i] ?? 0,
                ];

                // ownerCode = $car_code → mỗi xe có thư mục riêng: /uploads/cars/CAR-XXX-YYY/...
                [$file_code, $url] = upload_and_register_file($conn, $f, 'cars', $car_code);

                // mã ảnh theo DB để không trùng khi upload nhiều đợt
                $car_image_code = next_car_image_code_db($conn, $car_code);

                $ins = $conn->prepare("
                    INSERT INTO car_images (car_image_code, car_code, file_code, is_primary, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $ins->bind_param("sssi", $car_image_code, $car_code, $file_code, $primary);
                $ins->execute();
                $ins->close();

                $primary = 0; // chỉ ảnh đầu là ảnh chính
            }
        }

        $conn->commit();
        header("Location: xe.php"); exit;

    } catch (Throwable $e) {
        if ($conn->errno) { $conn->rollback(); }
        $error = $e->getMessage();
    }
}

include __DIR__ . '/header.php';
?>
<head>
    <meta charset="UTF-8">
    <title>Thêm xe mới</title>
    <style>
        .them-xe-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,.06)}
        .them-xe-form .mb-3{display:flex;flex-direction:column;gap:6px}
        .them-xe-form label{font-weight:600;color:#333}
        .them-xe-form input,.them-xe-form textarea,.them-xe-form select{padding:10px 12px;border:1px solid #d7d7d7;border-radius:8px;font-size:14px}
        .them-xe-form textarea{resize:vertical;min-height:70px}
        .btn{display:inline-block;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:600;cursor:pointer}
        .btn-success{background:#28a745;color:#fff;border:none}
        .btn-success:hover{background:#218838}
        .btn-secondary{background:#6c757d;color:#fff}
        .alert{padding:10px 14px;border-radius:8px;margin-bottom:12px}
        .alert-danger{background:#ffe5e5;color:#a30000;border:1px solid #ffbaba}
    </style>
    <script>
        function updateCarCode() {
            const brand = (document.getElementById('brand_select').value || '').toUpperCase();
            const box = document.getElementById('car_code');
            if (!brand) { box.value=''; return; }
            fetch('them_xe.php?brand_code='+encodeURIComponent(brand))
              .then(r=>r.text()).then(code=>box.value=code).catch(()=>box.value='');
        }
    </script>
</head>

<div class="main-content">
  <div class="container-fluid px-4 py-4">
    <h2>Thêm xe mới</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="them-xe-form" enctype="multipart/form-data">
      <div class="mb-3">
        <label>Hãng xe</label>
        <select name="brand_code" id="brand_select" onchange="updateCarCode()" required>
          <option value="">-- Chọn hãng --</option>
          <?php while($r = $brands->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars(strtoupper($r['brand_code'])) ?>">
              <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars(strtoupper($r['brand_code'])) ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="mb-3">
        <label>Mã xe</label>
        <input type="text" id="car_code" name="car_code" readonly required>
      </div>

      <div class="mb-3"><label>Tên xe</label><input type="text" name="name" required></div>
      <div class="mb-3"><label>Mô tả</label><textarea name="description"></textarea></div>
      <div class="mb-3"><label>Giá</label><input type="number" step="0.01" name="price" required></div>
      <div class="mb-3"><label>Động cơ</label><input type="text" name="engine"></div>
      <div class="mb-3"><label>Công suất</label><input type="text" name="power"></div>
      <div class="mb-3"><label>Gia tốc (0-100 km/h)</label><input type="text" name="acceleration"></div>
      <div class="mb-3"><label>Tốc độ tối đa</label><input type="text" name="top_speed"></div>
      <div class="mb-3"><label>Nhiên liệu</label><input type="text" name="fuel_type"></div>
      <div class="mb-3"><label>Hộp số</label><input type="text" name="transmission"></div>
      <div class="mb-3"><label>Số ghế</label><input type="number" name="seats"></div>

      <div class="mb-3">
        <label>Hình ảnh xe</label>
        <input type="file" name="car_images[]" multiple accept="image/*">
        <small>Chọn nhiều ảnh. Ảnh đầu tiên sẽ là ảnh chính.</small>
      </div>

      <div class="mb-3" style="grid-column:1/-1;">
        <button type="submit" class="btn btn-success">Thêm xe</button>
        <a href="xe.php" class="btn btn-secondary">Hủy</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

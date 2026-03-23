<?php
require_once 'connect.php';
include 'header.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* ================== HÀM PHỤ TRỢ ================== */
/**
 * Phát sinh số thứ tự tăng dần an toàn theo từng code trong bảng sequences.
 * Bảng sequences(code PK, cur_no INT NOT NULL).
 * Trả về chuỗi prefix . số pad 4, ví dụ: SRQ0001, COST0002, WRR0003
 */
function next_seq(mysqli $conn, string $code, string $prefix, int $pad = 4): string {
    // Tạo dòng nếu chưa có
    $stmt = $conn->prepare("INSERT INTO sequences(code, cur_no) VALUES(?, 0)
                            ON DUPLICATE KEY UPDATE cur_no = cur_no");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->close();

    // Tăng cur_no và lấy giá trị mới theo cách atomic
    $stmt = $conn->prepare("UPDATE sequences SET cur_no = LAST_INSERT_ID(cur_no + 1) WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->close();

    $row = $conn->query("SELECT LAST_INSERT_ID() AS no")->fetch_assoc();
    return $prefix . str_pad((string)$row['no'], $pad, '0', STR_PAD_LEFT);
}

/* ================== XỬ LÝ SUBMIT ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['username'])) {
        $_SESSION['service_msg'] = 'Bạn cần đăng nhập để gửi yêu cầu dịch vụ.';
    } else {
        // Lấy customer_code từ users
        $stmt = $conn->prepare("SELECT customer_code FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (empty($u['customer_code'])) {
            $_SESSION['service_msg'] = 'Tài khoản chưa liên kết khách hàng. Vui lòng liên hệ quản trị viên.';
        } else {
            $customer_code    = $u['customer_code'];
            $car_code         = $_POST['car_code']          ?? '';
            $service_type     = $_POST['service_type']      ?? '';
            $description      = trim($_POST['description']  ?? '');
            $selected_services= $_POST['selected_services'] ?? [];
            $total_cost       = (float)($_POST['total_cost'] ?? 0);

            if ($car_code === '' || $service_type === '' || $description === '') {
                $_SESSION['service_msg'] = 'Vui lòng điền đầy đủ thông tin.';
            } else {
                try {
                    $conn->begin_transaction();

                    if ($service_type === 'service_request') {
                        // Mã yêu cầu: SRQ0001
                        $request_no = next_seq($conn, 'SRQ', 'SRQ');

                        // Lưu yêu cầu
                        $stmt = $conn->prepare("
                            INSERT INTO service_requests
                                (request_no, customer_code, car_code, request_type, description, status)
                            VALUES
                                (?, ?, ?, 'MAINTENANCE', ?, 'OPEN')
                        ");
                        $stmt->bind_param("ssss", $request_no, $customer_code, $car_code, $description);
                        $stmt->execute();
                        $stmt->close();

                        // Lưu các chi phí đã chọn (mã COST0001…)
                        if (!empty($selected_services)) {
                            foreach ($selected_services as $service_info) {
                                // value là JSON -> cần htmlspecialchars_decode để tránh lỗi khi đã escape HTML
                                $service_json = html_entity_decode($service_info, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $service_data = json_decode($service_json, true) ?: [];

                                $cost_no = next_seq($conn, 'COST', 'COST');
                                $name    = (string)($service_data['name']  ?? 'Dịch vụ');
                                $price   = (float) ($service_data['price'] ?? 0);

                                $stmt = $conn->prepare("
                                    INSERT INTO service_costs
                                        (cost_no, request_no, item_name, quantity, unit_price, amount)
                                    VALUES
                                        (?, ?, ?, 1, ?, ?)
                                ");
                                $stmt->bind_param("sssdd", $cost_no, $request_no, $name, $price, $price);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }

                        $_SESSION['service_msg'] = "Tạo yêu cầu dịch vụ thành công! Mã: $request_no";

} elseif ($service_type === 'warranty') {
    // Mã bảo hành: WRR0001
    $warranty_no = next_seq($conn, 'WRR', 'WRR');
    $start_date  = date('Y-m-d');
    $end_date    = date('Y-m-d', strtotime('+2 years'));

    // THU THẬP GÓI ĐÃ CHỌN
    $packages = [];
    $warranty_total = 0.0;

    // Lưu ý: name="selected_services[]" ở tab Bảo hành
    if (!empty($selected_services) && is_array($selected_services)) {
        foreach ($selected_services as $service_info) {
            // do value đã htmlspecialchars trước đó -> cần decode về JSON thuần
            $service_json = html_entity_decode($service_info, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $s = json_decode($service_json, true);
            if (is_array($s)) {
                $packages[] = (string)($s['name'] ?? 'Gói BH');
                $warranty_total += (float)($s['price'] ?? 0);
            }
        }
    }

    // Nếu người dùng chưa chọn gói nào -> ngắt sớm để tránh tạo record 0 đồng
    if ($warranty_total <= 0) {
        throw new Exception('Bạn chưa chọn gói bảo hành nào.');
    }

    $package_names = implode(', ', $packages);

    // GHI DB (yêu cầu đã thêm 2 cột package_names, total_amount)
    $stmt = $conn->prepare("
        INSERT INTO warranties
            (warranty_no, customer_code, car_code, start_date, end_date, policy, package_names, total_amount)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssssssd",
        $warranty_no, $customer_code, $car_code,
        $start_date, $end_date,
        $description,          // dùng field 'policy' để lưu mô tả / chính sách
        $package_names,
        $warranty_total
    );
    $stmt->execute();
    $stmt->close();

    $_SESSION['service_msg'] = "Tạo bảo hành thành công! Mã: $warranty_no";
}



                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['service_msg'] = 'Có lỗi xảy ra: ' . $e->getMessage();
                }
            }
        }
    }
}

/* ================== DATA ================== */
$cars = [];
$resCars = $conn->query("SELECT car_code, name, price FROM cars ORDER BY name ASC");
if ($resCars) { while ($c = $resCars->fetch_assoc()) $cars[] = $c; $resCars->free(); }

// Danh sách dịch vụ với giá (để render lựa chọn)
$services_list = [
  'maintenance' => [
    ['name' => 'Bảo dưỡng cơ bản', 'price' => 500000],
    ['name' => 'Thay dầu máy',     'price' => 300000],
    ['name' => 'Kiểm tra phanh',   'price' => 200000],
    ['name' => 'Thay lọc gió',     'price' => 150000],
    ['name' => 'Kiểm tra lốp xe',  'price' => 100000],
  ],
  'repair' => [
    ['name' => 'Sửa chữa động cơ',   'price' => 2000000],
    ['name' => 'Sửa hệ thống điện',  'price' => 800000],
    ['name' => 'Sửa hệ thống phanh', 'price' => 1200000],
    ['name' => 'Thay thế linh kiện', 'price' => 600000],
    ['name' => 'Sơn xe',             'price' => 1500000],
  ],
  'warranty' => [
    ['name' => 'Bảo hành 1 năm',    'price' => 1000000],
    ['name' => 'Bảo hành 2 năm',    'price' => 1800000],
    ['name' => 'Bảo hành mở rộng',  'price' => 2500000],
  ],
];

$isLoggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8" />
<title>Dịch Vụ Xe</title>
  <style>
    :root { --primary:#1f3349; --primary-dark:#0f2741; --secondary:#3498db; --accent:#e74c3c; --success:#27ae60; --warning:#f39c12; --light-bg:#f8f9fa; --light-border:#dee2e6; --text-dark:#2c3e50; --text-light:#6c757d; --white:#ffffff; --shadow:0 4px 20px rgba(0,0,0,.08); --shadow-hover:0 8px 25px rgba(0,0,0,.12); --transition:.3s ease; --radius:10px; --radius-sm:6px; }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7f9;color:var(--text-dark);line-height:1.6}
    .service-container{max-width:1200px;margin:40px auto;padding:30px;background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow)}
    .service-header{text-align:center;margin-bottom:32px;padding-bottom:24px;border-bottom:2px solid var(--light-border)}
    .service-header h1{font-size:36px;color:var(--primary);margin:0 0 12px;font-weight:700;letter-spacing:-.5px}
    .service-header p{color:var(--text-light);font-size:18px;margin:0}
    .service-form{padding:0 4px}
    .form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-bottom:24px}
    .form-group{margin-bottom:24px}
    .form-group label{display:block;margin-bottom:10px;font-weight:600;color:var(--text-dark);font-size:16px}
    .form-control{width:100%;padding:14px 16px;border:2px solid var(--light-border);border-radius:var(--radius-sm);font-size:16px;background:var(--light-bg);transition:var(--transition);color:var(--text-dark)}
    .form-control:focus{border-color:var(--primary);background:var(--white);outline:none;box-shadow:0 0 0 3px rgba(31,51,73,.1)}
    select.form-control{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%231f3349' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12L8 12z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 16px center;background-size:16px;padding-right:45px}
    .service-tabs{display:flex;margin-bottom:32px;border-bottom:2px solid var(--light-border);gap:4px}
    .service-tab{padding:16px 28px;background:var(--light-bg);border:none;cursor:pointer;font-size:16px;font-weight:600;color:var(--text-light);border-radius:var(--radius-sm) var(--radius-sm) 0 0;transition:var(--transition);position:relative;overflow:hidden}
    .service-tab::before{content:'';position:absolute;bottom:0;left:0;width:100%;height:3px;background:transparent;transition:var(--transition)}
    .service-tab.active{background:var(--white);color:var(--primary)}
    .service-tab.active::before{background:var(--primary)}
    .service-tab:hover:not(.active){background:var(--white);color:var(--primary)}
    .service-options{display:none;background:var(--light-bg);padding:28px;border-radius:var(--radius);margin-bottom:28px;border:2px solid var(--light-border)}
    .service-options.active{display:block;animation:fadeIn .4s ease}
    .service-options h3{color:var(--primary);margin-bottom:20px;font-size:20px;font-weight:600;padding-bottom:12px;border-bottom:2px solid var(--light-border)}
    .service-options h4{color:var(--text-dark);margin:28px 0 16px 0;font-size:18px;font-weight:600}
    .service-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
    .service-item{background:var(--white);padding:20px;border-radius:var(--radius-sm);border:2px solid var(--light-border);cursor:pointer;transition:var(--transition);position:relative;display:flex;flex-direction:column;justify-content:space-between;min-height:120px}
    .service-item:hover{border-color:var(--primary);box-shadow:var(--shadow-hover);transform:translateY(-2px)}
    .service-item.selected{border-color:var(--primary);background:linear-gradient(135deg,#f0f4ff 0%,#e6eeff 100%);box-shadow:var(--shadow)}
    .service-item input[type="checkbox"]{position:absolute;top:16px;right:16px;transform:scale(1.3);accent-color:var(--primary)}
    .service-name{font-weight:600;color:var(--text-dark);margin-bottom:8px;font-size:16px;padding-right:30px}
    .service-price{font-size:18px;font-weight:700;color:var(--primary);margin-top:auto}
    .total-cost{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;padding:24px;border-radius:var(--radius);text-align:center;margin:32px 0;font-size:20px;font-weight:700;box-shadow:var(--shadow);transition:var(--transition)}
    .total-cost:hover{transform:translateY(-2px);box-shadow:var(--shadow-hover)}
    textarea.form-control{min-height:140px;resize:vertical;line-height:1.5}
    .btn-submit{width:100%;padding:18px;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;font-weight:700;font-size:18px;transition:var(--transition);text-transform:uppercase;letter-spacing:.5px;box-shadow:var(--shadow)}
    .btn-submit:hover{background:linear-gradient(135deg,var(--primary-dark) 0%,#0a1e33 100%);transform:translateY(-2px);box-shadow:var(--shadow-hover)}
    .btn-submit:active{transform:translateY(0)}
    .alert{width:100%;margin:20px 0;padding:18px 20px;text-align:center;border-radius:var(--radius-sm);font-size:16px;font-weight:600;border-left:5px solid;transition:var(--transition);box-shadow:var(--shadow)}
    .alert.success{background:#e9f9ec;color:#2e7d32;border-color:#2e7d32}
    .alert.error{background:#fdecea;color:#c62828;border-color:#c62828}
    @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    @media (max-width:992px){.service-container{margin:24px;padding:24px}.service-header h1{font-size:30px}.service-grid{grid-template-columns:repeat(auto-fill,minmax(250px,1fr))}}
    @media (max-width:768px){.service-container{margin:16px;padding:20px}.service-header h1{font-size:26px}.service-header p{font-size:16px}.form-row{grid-template-columns:1fr;gap:16px}.service-tabs{flex-wrap:wrap}.service-tab{flex:1;min-width:120px;padding:14px 16px;font-size:14px}.service-options{padding:20px}.service-grid{grid-template-columns:1fr}}
    @media (max-width:480px){.service-container{margin:12px;padding:16px}.service-header{margin-bottom:24px}.service-header h1{font-size:22px}.service-tab{font-size:13px;padding:12px}.service-options{padding:16px}.service-item{padding:16px}.total-cost{padding:20px;font-size:18px}.btn-submit{padding:16px;font-size:16px}}
    .modal{display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);justify-content:center;align-items:center}
    .modal-content{background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border-radius:20px;padding:30px;color:#fff;max-width:400px;text-align:center}
    .modal-buttons{display:flex;justify-content:center;gap:15px}
    .modal-buttons button{padding:10px 20px;border:none;border-radius:8px;font-size:16px;cursor:pointer}
    .modal-buttons button:first-child{background:#f1c40f;color:#000}
    .modal-buttons button:last-child{background:transparent;border:1px solid #fff;color:#fff}
  </style>
</head>
<body>
  <div class="service-container">
    <div class="service-header">
      <h1>Dịch Vụ Chăm Sóc Xe</h1>
      <p>Chọn loại dịch vụ và điền thông tin để gửi yêu cầu</p>
    </div>

    <?php if (!empty($_SESSION['service_msg'])): ?>
      <div class="alert <?= $isLoggedIn ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['service_msg'], ENT_QUOTES, 'UTF-8') ?>
      </div>
      <?php unset($_SESSION['service_msg']); ?>
    <?php endif; ?>

    <form method="post" action="services.php" class="service-form" onsubmit="return kiemTraDangNhap();">
      <div class="service-tabs">
        <button type="button" class="service-tab active" onclick="switchServiceType('service_request', this)">Bảo dưỡng / Sửa chữa</button>
        <button type="button" class="service-tab" onclick="switchServiceType('warranty', this)">Bảo hành</button>
      </div>

      <input type="hidden" id="service_type" name="service_type" value="service_request">

      <div class="form-row">
        <div class="form-group">
          <label for="car_code">Chọn Xe</label>
          <select id="car_code" name="car_code" class="form-control" required>
            <option value="">-- Chọn mẫu xe --</option>
            <?php foreach ($cars as $c): ?>
              <option value="<?= htmlspecialchars($c['car_code'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> - <?= number_format($c['price']) ?> VNĐ
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div id="service_options_maintenance" class="service-options active">
        <h3>Chọn dịch vụ bảo dưỡng / sửa chữa:</h3>
        <h4>Bảo dưỡng định kỳ:</h4>
        <div class="service-grid">
          <?php foreach ($services_list['maintenance'] as $service): ?>
            <?php $val = htmlspecialchars(json_encode($service, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
            <div class="service-item" onclick="toggleService(this)">
              <input type="checkbox" name="selected_services[]" value='<?= $val ?>' onchange="updateTotal()">
              <div class="service-name"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="service-price"><?= number_format($service['price']) ?> VNĐ</div>
            </div>
          <?php endforeach; ?>
        </div>

        <h4 style="margin-top:24px;">Sửa chữa:</h4>
        <div class="service-grid">
          <?php foreach ($services_list['repair'] as $service): ?>
            <?php $val = htmlspecialchars(json_encode($service, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
            <div class="service-item" onclick="toggleService(this)">
              <input type="checkbox" name="selected_services[]" value='<?= $val ?>' onchange="updateTotal()">
              <div class="service-name"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="service-price"><?= number_format($service['price']) ?> VNĐ</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="service_options_warranty" class="service-options">
        <h3>Chọn gói bảo hành:</h3>
        <div class="service-grid">
          <?php foreach ($services_list['warranty'] as $service): ?>
            <?php $val = htmlspecialchars(json_encode($service, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
            <div class="service-item" onclick="toggleService(this)">
              <input type="checkbox" name="selected_services[]" value='<?= $val ?>' onchange="updateTotal()">
              <div class="service-name"><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="service-price"><?= number_format($service['price']) ?> VNĐ</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="total-cost">Tổng chi phí ước tính: <span id="total_amount">0</span> VNĐ</div>
      <input type="hidden" id="total_cost" name="total_cost" value="0">

      <div class="form-group">
        <label for="description">Mô tả chi tiết / Ghi chú</label>
        <textarea id="description" name="description" class="form-control" required
          placeholder="Mô tả tình trạng xe, yêu cầu cụ thể, hoặc ghi chú thêm..."></textarea>
      </div>

      <button type="submit" class="btn-submit">Gửi Yêu Cầu Dịch Vụ</button>
    </form>
  </div>

  <!-- Modal đăng nhập (nếu muốn giữ) -->
  <div id="loginModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
    <div class="modal-content">
      <h2 id="loginModalTitle">⚠ Bạn chưa đăng nhập</h2>
      <p>Bạn cần đăng nhập để gửi yêu cầu dịch vụ. Bạn có muốn chuyển đến trang đăng nhập không?</p>
      <?php $current = basename($_SERVER['REQUEST_URI']); ?>
      <div class="modal-buttons">
        <button onclick="window.location.href='Taikhoan/dangnhap.php?next=<?= urlencode($current) ?>'">Đăng nhập</button>
        <button onclick="closeModal()">Hủy</button>
      </div>
    </div>
  </div>

  <script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    function kiemTraDangNhap(){ if(!isLoggedIn){ document.getElementById('loginModal').style.display='flex'; return false;} return true; }
    function closeModal(){ document.getElementById('loginModal').style.display='none'; }

    function switchServiceType(type, btn){
      document.querySelectorAll('.service-tab').forEach(t=>t.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('service_type').value = type;
      document.querySelectorAll('.service-options').forEach(o=>o.classList.remove('active'));
      (type==='service_request'?document.getElementById('service_options_maintenance'):document.getElementById('service_options_warranty')).classList.add('active');
      document.querySelectorAll('input[name="selected_services[]"]').forEach(cb=>{ cb.checked=false; cb.closest('.service-item').classList.remove('selected'); });
      updateTotal();
    }
    function toggleService(item){
      const cb = item.querySelector('input[type="checkbox"]');
      cb.checked = !cb.checked;
      item.classList.toggle('selected', cb.checked);
      updateTotal();
    }
    function updateTotal(){
      let total = 0;
      document.querySelectorAll('input[name="selected_services[]"]:checked').forEach(cb=>{
        try{ const s = JSON.parse(cb.value); total += Number(s.price||0); }catch(e){}
      });
      document.getElementById('total_amount').textContent = new Intl.NumberFormat('vi-VN').format(total);
      document.getElementById('total_cost').value = total;
    }
  </script>
</body>
</html>

<?php include 'footer.php'; ?>

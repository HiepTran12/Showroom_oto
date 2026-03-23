<?php
// --- bootstrap ---
include 'header.php';
include 'connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// === helper: kiểm tra cột có tồn tại trong bảng không ===
function columnExists(mysqli $conn, string $table, string $column): bool {
    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
            LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param("ss", $table, $column);
    $st->execute();
    $st->bind_result($one);
    $ok = $st->fetch();
    $st->close();
    return (bool)$ok;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Ho_Chi_Minh');
@$conn->query("SET time_zone = '+07:00'");

/*=========================
  INPUT & LẤY THÔNG TIN XE
=========================*/
$car_code = isset($_GET['car_code']) ? $conn->real_escape_string($_GET['car_code']) : '';

$car = null;
if ($car_code !== '') {
    // Quyết định biểu thức giá: price / unit_price / cả hai
    $hasPrice = columnExists($conn, 'cars', 'price');
    $hasUnit  = columnExists($conn, 'cars', 'unit_price');

    if ($hasPrice && $hasUnit) {
        $priceExpr = "COALESCE(c.price, c.unit_price)";
    } elseif ($hasPrice) {
        $priceExpr = "c.price";
    } elseif ($hasUnit) {
        $priceExpr = "c.unit_price";
    } else {
        $priceExpr = "NULL"; // không có cột giá -> lát nữa sẽ báo lỗi
    }

    $sql = "
      SELECT  c.car_code,
              c.name,
              b.name AS brand_name,
              {$priceExpr} AS unit_price,
              COALESCE(f.storage_path, 'hinh/no-image.jpg') AS image
      FROM cars c
      JOIN brands b ON c.brand_code = b.brand_code
      LEFT JOIN car_images ci ON ci.car_code = c.car_code AND ci.is_primary = 1
      LEFT JOIN files f ON ci.file_code = f.file_code
      WHERE c.car_code = ?
      LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $car_code);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$bg_url = $car ? (strpos($car['image'],'/')===0 ? $car['image'] : '/'.$car['image']) : '/hinh/no-image.jpg';


/* =========================
   HELPERS
========================= */

// Lấy giá xe an toàn (ưu tiên price rồi unit_price)
function getUnitPrice(mysqli $conn, string $car_code): ?float {
    $unit_price = null;

    if (columnExists($conn, 'cars', 'price')) {
        $st = $conn->prepare("SELECT price AS p FROM cars WHERE car_code=? LIMIT 1");
        $st->bind_param("s", $car_code);
        $st->execute();
        $st->bind_result($unit_price);
        $st->fetch();
        $st->close();
        if ($unit_price !== null) return (float)$unit_price;
    }

    if (columnExists($conn, 'cars', 'unit_price')) {
        $st = $conn->prepare("SELECT unit_price AS p FROM cars WHERE car_code=? LIMIT 1");
        $st->bind_param("s", $car_code);
        $st->execute();
        $st->bind_result($unit_price);
        $st->fetch();
        $st->close();
        if ($unit_price !== null) return (float)$unit_price;
    }

    return null; // không có cột giá nào
}


/** Chọn cột ngày hiện có trong 1 bảng theo thứ tự ưu tiên */
function pickDateColumn(mysqli $conn, string $table, array $candidates): ?string {
    $table  = $conn->real_escape_string($table);
    $in     = "'" . implode("','", array_map('addslashes', $candidates)) . "'";
    $case   = [];
    foreach ($candidates as $i => $c) $case[] = "WHEN '{$c}' THEN ".($i+1);
    $case   = implode(' ', $case);

    $sql = "
      SELECT COLUMN_NAME
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = '{$table}'
        AND COLUMN_NAME IN ($in)
      ORDER BY CASE COLUMN_NAME {$case} ELSE 999 END
      LIMIT 1";
    $r = $conn->query($sql);
    if ($r && ($row = $r->fetch_assoc())) return $row['COLUMN_NAME'];
    return null;
}

/** Sinh số tăng dạng SCxxxx / INV-xxxx / RC-xxxx theo số lớn nhất hiện có */
function nextSC(mysqli $conn): string {
    $r = $conn->query("
        SELECT contract_no FROM sales_contracts
        WHERE contract_no LIKE 'SC%'
        ORDER BY CAST(SUBSTRING(contract_no, 3) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['contract_no']) : 1000;
    return 'SC' . ($n + 1);
}
function nextINV(mysqli $conn): string {
    $r = $conn->query("
        SELECT invoice_no FROM invoices
        WHERE invoice_no LIKE 'INV-%'
        ORDER BY CAST(SUBSTRING(invoice_no, 5) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['invoice_no']) : 1000;
    return 'INV-' . ($n + 1);
}
function nextRC(mysqli $conn): string {
    $r = $conn->query("
        SELECT receipt_no FROM payments
        WHERE receipt_no LIKE 'RC-%'
        ORDER BY CAST(SUBSTRING(receipt_no, 4) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['receipt_no']) : 1000;
    return 'RC-' . ($n + 1);
}


/* =========================
   SUBMIT: ĐẶT MUA
========================= */
$msg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Nhận input
    $contact_name   = trim($_POST['full_name'] ?? '');
    $contact_phone  = trim($_POST['phone'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    // hình thức thanh toán (để ghi vào payments)
    $payment_method = $_POST['payment_method'] ?? null;         // BANK_TRANSFER | CARD | CASH
    $reference_no   = trim($_POST['reference_no'] ?? '');       // có thể để trống
    if ($reference_no === '') $reference_no = null;

    // 2) Lấy giá xe
    $unit_price = getUnitPrice($conn, $car_code);
    if ($unit_price === null) {
        $msg = "❌ Không tìm được giá xe (cần có cột price hoặc unit_price trong bảng cars).";
    } else {
        // 3) Lấy/ tạo customer_code
        $customer_code = $_SESSION['customer_code'] ?? null;

        if (!$customer_code && $contact_phone !== '') {
            $q = $conn->prepare("SELECT customer_code FROM customers WHERE phone=? LIMIT 1");
            $q->bind_param("s", $contact_phone);
            $q->execute();
            $q->bind_result($customer_code);
            $q->fetch();
            $q->close();
        }

        if (!$customer_code) {
            // Tạo CUS-xxxx mới theo số lớn nhất
            $r = $conn->query("
                SELECT customer_code FROM customers
                WHERE customer_code LIKE 'CUS-%'
                ORDER BY CAST(SUBSTRING(customer_code, 5) AS UNSIGNED) DESC
                LIMIT 1
            ");
            $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['customer_code']) + 1 : 15;
            $customer_code = sprintf('CUS-%04d', $n);

            $insCus = $conn->prepare("
                INSERT INTO customers (customer_code, full_name, phone, notes)
                VALUES (?,?,?, 'auto-sync from users')
            ");
            $insCus->bind_param("sss", $customer_code, $contact_name, $contact_phone);
            $insCus->execute();
            $insCus->close();
        }

        // 4) Tính tiền
        $quantity    = 1;
        $amount      = (float)$unit_price * $quantity;  // trước thuế
        $vat_rate    = 10.0;
        $vat_amount  = $amount * $vat_rate / 100.0;
        $total       = $amount + $vat_amount;
        $today       = date('Y-m-d');

        // 5) Transaction: hợp đồng + hóa đơn + (tuỳ chọn) phiếu thu
        $conn->begin_transaction();
        try {
            // 5.1) Hợp đồng
            $contract_no = nextSC($conn);
            $scDateCol   = pickDateColumn($conn, 'sales_contracts', ['contract_date','create_at','created_at']);
            if (!$scDateCol) throw new Exception("Bảng sales_contracts không có cột ngày (contract_date/create_at/created_at).");

            $sqlSC = "
              INSERT INTO sales_contracts
                (contract_no, `{$scDateCol}`, customer_code, car_code,
                 appointment_no, quantity, unit_price, vat_rate, vat_amount,
                 total_amount, status, contract_file, notes)
              VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 'SIGNED', NULL, ?)";
            $st = $conn->prepare($sqlSC);
            $st->bind_param(
                "ssssidddds",
                $contract_no, $today, $customer_code, $car_code,
                $quantity, $unit_price, $vat_rate, $vat_amount, $total, $notes
            );
            $st->execute();
            $st->close();

            // 5.2) Hóa đơn
            $invoice_no = nextINV($conn);
            $ivDateCol  = pickDateColumn($conn, 'invoices', ['invoice_date','create_at','created_at','date']);
            if (!$ivDateCol) throw new Exception("Bảng invoices không có cột ngày (invoice_date/create_at/created_at/date).");

            $sqlINV = "
              INSERT INTO invoices
                (invoice_no, `{$ivDateCol}`, contract_no, amount, vat_rate, vat_amount, total_amount)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
            $st = $conn->prepare($sqlINV);
            $st->bind_param("sssdddd", $invoice_no, $today, $contract_no, $amount, $vat_rate, $vat_amount, $total);
            $st->execute();
            $st->close();

            // 5.3) Phiếu thu (chỉ khi có chọn hình thức thanh toán)
            if (!empty($payment_method)) {
                $receipt_no = nextRC($conn);
                $pmDateCol  = pickDateColumn($conn, 'payments', ['payment_date','create_at','created_at','date']);
                if (!$pmDateCol) throw new Exception("Bảng payments không có cột ngày (payment_date/create_at/created_at/date).");

                $sqlPAY = "
                  INSERT INTO payments
                    (receipt_no, contract_no, `{$pmDateCol}`, method, reference_no, amount)
                  VALUES (?, ?, ?, ?, ?, ?)";
                $st = $conn->prepare($sqlPAY);
                $st->bind_param("sssssd", $receipt_no, $contract_no, $today, $payment_method, $reference_no, $total);
                $st->execute();
                $st->close();
            }

            $conn->commit();
            $success = true;
            $msg = "✅ Đặt mua thành công! HĐ: {$contract_no} – Hóa đơn: {$invoice_no}"
                 . (!empty($payment_method) ? " – Thanh toán: {$payment_method}" : '');
        } catch (Exception $e) {
            $conn->rollback();
            $success = false;
            $msg = "❌ " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đặt mua xe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{--glass-bg: rgba(22,28,45,.55);--glass-border: rgba(255,255,255,.18);--brand:#ffd000;}
    *{box-sizing:border-box} html,body{margin:0;padding:0;height:100%}
    body{
      font-family:system-ui,-apple-system,"Segoe UI",Roboto,Inter,Arial,sans-serif;color:#fff;
      background:url('<?= htmlspecialchars($bg_url) ?>') center/cover no-repeat fixed;
      display:flex;flex-direction:column;min-height:100vh;
    }
    .overlay::before{content:"";position:fixed;inset:0;background:radial-gradient(ellipse at center, rgba(0,0,0,.15) 0%, rgba(0,0,0,.55) 65%, rgba(0,0,0,.75) 100%);pointer-events:none;z-index:0;}
    .glass-form{width:100%;max-width:480px;margin:100px auto;padding:30px;background:rgba(30,47,82,0.1);
               border-radius:20px;backdrop-filter:blur(20px);box-shadow:0 8px 32px rgba(0,0,0,.35);color:#fff}
    h1{margin:.2rem 0 .5rem;text-align:center;font-size:clamp(22px,4vw,28px)}
    .subtitle{text-align:center;color:#e5e7eb;margin-bottom:18px;font-weight:500}
    form{display:grid;gap:14px;margin-top:8px}
    .field{display:flex;flex-direction:column;gap:8px}
    label{font-size:14px;color:#cbd5e1}
    input,select,textarea{padding:12px 14px;border-radius:10px;border:1px solid rgba(255,255,255,.25);
                          background:rgba(255,255,255,.05);color:#fff;font-size:15px;outline:none}
    textarea{resize:vertical;min-height:70px}
    .btn{margin-top:6px;cursor:pointer;background:linear-gradient(135deg,var(--brand),#ffb700);color:#000;font-weight:800;
         padding:13px 16px;border-radius:10px;font-size:16px}
    .msg{padding:10px 12px;border-radius:10px;margin-bottom:10px;font-weight:600;text-align:center}
    .msg.ok{background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.35)}
    .msg.err{background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35)}
  </style>
</head>
<body>
<div class="overlay"></div>

<div class="glass-form">
  <?php if ($car): ?>
    <h1>Đặt mua</h1>
    <div class="subtitle"><?= htmlspecialchars($car['name']) ?> – <?= htmlspecialchars($car['brand_name']) ?></div>

    <?php if ($msg): ?>
      <div class="msg <?= $success ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg) ?></div>
      <?php if ($success): ?><script>setTimeout(()=>location.href='index.php',2200);</script><?php endif; ?>
    <?php endif; ?>

    <?php if (!$success): ?>
      <?php if ($car['unit_price'] === null || $car['unit_price'] === ''): ?>
        <div class="msg err">Xe chưa có giá bán trong hệ thống. Vui lòng liên hệ nhân viên.</div>
      <?php else: ?>
        <div class="field">
          <label>Giá xe</label>
          <input value="<?= number_format((float)$car['unit_price'],0,',','.') ?> ₫" disabled>
        </div>

        <form method="post">
          <div class="field">
            <label>Họ và tên</label>
            <input type="text" name="full_name" placeholder="Nhập họ tên" required>
          </div>

          <div class="field">
            <label>Số điện thoại</label>
            <input type="tel" name="phone" placeholder="Nhập số điện thoại"
                   pattern="^0\d{9,10}$" title="Số điện thoại bắt đầu bằng 0 và 10–11 số" required>
          </div>

          <div class="field">
            <label>Hình thức thanh toán</label>
            <select name="payment_method" required>
              <option value="BANK_TRANSFER">Chuyển khoản</option>
              <option value="CARD">Thẻ</option>
              <option value="CASH">Tiền mặt</option>
            </select>
          </div>

          <!-- tuỳ chọn, nếu bạn muốn lưu kèm mã giao dịch -->
          <div class="field">
            <label>Mã tham chiếu (tuỳ chọn)</label>
            <input type="text" name="reference_no" placeholder="Mã giao dịch/POS/Ủy nhiệm chi">
          </div>

          <div class="field">
            <label>Ghi chú (tuỳ chọn)</label>
            <textarea name="notes" placeholder="Ví dụ: Giao xe trong tuần này..."></textarea>
          </div>

          <button type="submit" class="btn">Đặt mua</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>

  <?php else: ?>
    <h1>Không tìm thấy thông tin xe</h1>
    <div class="subtitle">Vui lòng mở trang theo dạng: <code>datmua.php?car_code=CAR-...</code></div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>

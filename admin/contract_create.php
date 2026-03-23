<?php
include 'auth_check.php';
include 'header.php';
include '../connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Ho_Chi_Minh');
$conn->query("SET time_zone = '+07:00'");

/* ---------- helpers ---------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// cột ngày khả dụng trong 1 bảng
function pickDateColumn(mysqli $conn, string $table, array $cands): ?string {
  $table = $conn->real_escape_string($table);
  $in    = "'" . implode("','", array_map('addslashes', $cands)) . "'";
  $sql = "SELECT COLUMN_NAME
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = '{$table}'
            AND COLUMN_NAME IN ($in)
          LIMIT 1";
  if ($rs = $conn->query($sql)) {
    if ($row = $rs->fetch_assoc()) return $row['COLUMN_NAME'];
  }
  return null;
}

// dò cột giá tồn tại
function carsPriceExpr(mysqli $conn): string {
  $hasPrice = (bool)$conn->query("SHOW COLUMNS FROM cars LIKE 'price'")->num_rows;
  $hasUnit  = (bool)$conn->query("SHOW COLUMNS FROM cars LIKE 'unit_price'")->num_rows;
  if ($hasPrice && $hasUnit) return "COALESCE(c.price, c.unit_price)";
  if ($hasPrice)              return "c.price";
  if ($hasUnit)               return "c.unit_price";
  return "0";
}

// lấy giá chuẩn từ DB theo car_code
function getUnitPrice(mysqli $conn, string $car_code): ?float {
  foreach ([
    "SELECT price AS p FROM cars WHERE car_code=? LIMIT 1",
    "SELECT unit_price AS p FROM cars WHERE car_code=? LIMIT 1",
  ] as $sql) {
    if ($st = $conn->prepare($sql)) {
      $st->bind_param("s", $car_code);
      $st->execute();
      $st->bind_result($p);
      $st->fetch();
      $st->close();
      if ($p !== null) return (float)$p;
    }
  }
  return null;
}

// sinh số SCxxxx
function nextSC(mysqli $conn): string {
  $r = $conn->query("SELECT contract_no FROM sales_contracts
                     WHERE contract_no LIKE 'SC%' 
                     ORDER BY CAST(SUBSTRING(contract_no,3) AS UNSIGNED) DESC
                     LIMIT 1");
  $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['contract_no']) : 1000;
  return 'SC' . ($n + 1);
}
// sinh số INV-xxxx
function nextINV(mysqli $conn): string {
  $r = $conn->query("SELECT invoice_no FROM invoices
                     WHERE invoice_no LIKE 'INV-%'
                     ORDER BY CAST(SUBSTRING(invoice_no,5) AS UNSIGNED) DESC
                     LIMIT 1");
  $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['invoice_no']) : 1000;
  return 'INV-' . ($n + 1);
}
// sinh số RC-xxxx
function nextRC(mysqli $conn): string {
  $r = $conn->query("SELECT receipt_no FROM payments
                     WHERE receipt_no LIKE 'RC-%'
                     ORDER BY CAST(SUBSTRING(receipt_no,4) AS UNSIGNED) DESC
                     LIMIT 1");
  $n = ($r && $r->num_rows) ? (int)preg_replace('/\D+/','', $r->fetch_assoc()['receipt_no']) : 1000;
  return 'RC-' . ($n + 1);
}

/* ---------- dữ liệu form (GET) ---------- */
// Số HĐ gợi ý
$contract_no = nextSC($conn);
// Ngày HĐ gợi ý
$contract_date = date('Y-m-d');

// Khách hàng
$customers = [];
$crs = $conn->query("SELECT customer_code, full_name FROM customers ORDER BY created_at DESC, full_name ASC");
while ($r = $crs->fetch_assoc()) $customers[] = $r;

// Xe (+ giá)
$cars = [];
$priceExpr = carsPriceExpr($conn);
$sqlCars = "SELECT c.car_code, c.name, {$priceExpr} AS unit_price FROM cars c ORDER BY c.name ASC";
$rsCars = $conn->query($sqlCars);
while ($r = $rsCars->fetch_assoc()) $cars[] = $r;

/* ---------- submit ---------- */
$msg = ""; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contract_no  = trim($_POST['contract_no'] ?? nextSC($conn));
  $contract_date= trim($_POST['contract_date'] ?? date('Y-m-d'));
  $customer_code= trim($_POST['customer_code'] ?? '');
  $car_code     = trim($_POST['car_code'] ?? '');
  $qty          = (int)($_POST['quantity'] ?? 1);
  if ($qty < 1) $qty = 1;
  $vat_rate     = (float)($_POST['vat_rate'] ?? 10);
  $payment_method = trim($_POST['payment_method'] ?? '');
  $reference_no   = trim($_POST['reference_no'] ?? '');
  $notes          = trim($_POST['notes'] ?? '');

  // giá chuẩn theo DB (không tin input)
  $unit_price = getUnitPrice($conn, $car_code);
  if ($unit_price === null) {
    $msg = "❌ Không lấy được giá của xe đã chọn.";
  } elseif ($customer_code === '' || $car_code === '') {
    $msg = "❌ Vui lòng chọn khách hàng và xe.";
  } else {
    $amount     = $qty * $unit_price;
    $vat_amount = $amount * $vat_rate / 100.0;
    $total      = $amount + $vat_amount;

    $conn->begin_transaction();
    try {
      // sales_contracts
      $scDateCol = pickDateColumn($conn, 'sales_contracts', ['contract_date','create_at','created_at']);
      if (!$scDateCol) throw new Exception("Bảng sales_contracts thiếu cột ngày.");
      $sqlSC = "INSERT INTO sales_contracts
        (contract_no, `{$scDateCol}`, customer_code, car_code, appointment_no, quantity,
         unit_price, vat_rate, vat_amount, total_amount, status, contract_file, notes)
        VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 'SIGNED', NULL, ?)";
      $st = $conn->prepare($sqlSC);
      $st->bind_param("ssssidddds", $contract_no, $contract_date, $customer_code, $car_code,
                      $qty, $unit_price, $vat_rate, $vat_amount, $total, $notes);
      $st->execute();
      $st->close();

      // invoices
      $invoice_no = nextINV($conn);
      $ivDateCol  = pickDateColumn($conn, 'invoices', ['invoice_date','create_at','created_at','date']);
      if (!$ivDateCol) throw new Exception("Bảng invoices thiếu cột ngày.");
      $sqlINV = "INSERT INTO invoices
        (invoice_no, `{$ivDateCol}`, contract_no, amount, vat_rate, vat_amount, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
      $st = $conn->prepare($sqlINV);
      $st->bind_param("sssdddd", $invoice_no, $contract_date, $contract_no, $amount, $vat_rate, $vat_amount, $total);
      $st->execute();
      $st->close();

      // payments (nếu chọn)
      if ($payment_method !== '') {
        $receipt_no = nextRC($conn);
        $pmDateCol  = pickDateColumn($conn, 'payments', ['payment_date','create_at','created_at','date']);
        if (!$pmDateCol) throw new Exception("Bảng payments thiếu cột ngày.");
        $sqlPAY = "INSERT INTO payments
          (receipt_no, contract_no, `{$pmDateCol}`, method, reference_no, amount)
          VALUES (?, ?, ?, ?, ?, ?)";
        $st = $conn->prepare($sqlPAY);
        $st->bind_param("sssssd", $receipt_no, $contract_no, $contract_date, $payment_method, $reference_no, $total);
        $st->execute();
        $st->close();
      }

      $conn->commit();
      $ok  = true;
      $msg = "✅ Đã tạo hợp đồng {$contract_no} — hoá đơn {$invoice_no}". ($payment_method? " — TT: {$payment_method}":"");
      // suggest next number
      $contract_no = nextSC($conn);
    } catch (Throwable $e) {
      $conn->rollback();
      $msg = "❌ ".$e->getMessage();
    }
  }
}
?>
<style>
.page-wrap{max-width:1100px;margin:24px auto;padding:0 16px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;box-shadow:0 6px 16px rgba(0,0,0,.06)}
h2.title{font-size:28px;margin:8px 0 18px;color:#0f172a}
.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.form-grid .full{grid-column:1/-1}
label{display:block;font-weight:600;font-size:14px;color:#334155;margin-bottom:6px}
input[type=text],input[type=number],input[type=date],select,textarea{
  width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff}
textarea{min-height:84px;resize:vertical}
.badge{display:inline-block;padding:3px 8px;border-radius:8px;background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;font-weight:700}
.actions{margin-top:10px;display:flex;gap:10px}
.btn{border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer}
.btn-primary{background:#2563eb;color:#fff}
.btn-secondary{background:#e5e7eb;color:#111827}
.msg{margin-bottom:14px;padding:10px 12px;border-radius:10px;font-weight:600}
.msg.ok{background:#dcfce7;color:#065f46;border:1px solid #86efac}
.msg.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.small{color:#64748b;font-size:12px}
</style>

<div class="page-wrap">
  <h2 class="title">Tạo hợp đồng mới</h2>

  <?php if ($msg): ?>
    <div class="msg <?= $ok ? 'ok':'err' ?>"><?= h($msg) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" autocomplete="off">
      <div class="form-grid">
        <div>
          <label>Số HĐ</label>
          <input type="text" name="contract_no" value="<?= h($contract_no) ?>" readonly>
        </div>
        <div>
          <label>Ngày HĐ</label>
          <input type="date" name="contract_date" value="<?= h($contract_date) ?>">
        </div>
        <div>
          <label>VAT (%)</label>
          <input type="number" step="0.01" id="vat_rate" name="vat_rate" value="10">
        </div>

        <div class="full">
          <label>Khách hàng</label>
          <select name="customer_code" required>
            <?php if (!$customers): ?>
              <option value="">— Chưa có khách hàng —</option>
            <?php else: ?>
              <?php foreach ($customers as $c): ?>
                <option value="<?= h($c['customer_code']) ?>">
                  <?= h($c['full_name']) ?> — <?= h($c['customer_code']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div>
          <label>Số lượng</label>
          <input type="number" id="qty" name="quantity" min="1" value="1">
        </div>

        <div>
          <label>Xe</label>
          <select id="car_code" name="car_code" required>
            <option value="">-- Chọn xe --</option>
            <?php foreach ($cars as $car): ?>
              <option value="<?= h($car['car_code']) ?>" data-price="<?= h((float)$car['unit_price']) ?>">
                <?= h($car['name']) ?> — <?= number_format((float)$car['unit_price'],0,',','.') ?> ₫
              </option>
            <?php endforeach; ?>
          </select>
          <div class="small">* Giá hiển thị lấy từ bảng <code>cars</code>.</div>
        </div>

        <div>
          <label>Giá (trước thuế)</label>
          <input type="number" id="unit_price" name="unit_price" value="0" step="1" readonly>
        </div>

        <div>
          <label>Tổng tiền (sau VAT)</label>
          <input type="number" id="total_amount" value="0" step="1" readonly>
        </div>

        <div>
          <label>Hình thức thanh toán</label>
          <select name="payment_method" id="payment_method">
            <option value="">-- Chọn --</option>
            <option value="BANK_TRANSFER">BANK_TRANSFER</option>
            <option value="CARD">CARD</option>
            <option value="CASH">CASH</option>
          </select>
        </div>

        <div>
          <label>Mã tham chiếu (tuỳ chọn)</label>
          <input type="text" name="reference_no" placeholder="Số FT/POST/... nếu có">
        </div>

        <div class="full">
          <label>Ghi chú</label>
          <textarea name="notes" placeholder="Ghi chú thêm..."></textarea>
        </div>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Tạo hợp đồng</button>
        <a class="btn btn-secondary" href="invoices.php">Hủy</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const carSel = document.getElementById('car_code');
  const qty    = document.getElementById('qty');
  const vat    = document.getElementById('vat_rate');
  const price  = document.getElementById('unit_price');
  const total  = document.getElementById('total_amount');

  function recalc(){
    const q = parseInt(qty.value || '0', 10);
    const p = parseFloat(price.value || '0');
    const v = parseFloat(vat.value || '0');
    const amount = q * p;
    const grand  = amount + amount * (v/100);
    total.value  = Math.round(grand);
  }

  carSel.addEventListener('change', function(){
    const opt = carSel.options[carSel.selectedIndex];
    price.value = opt ? (opt.getAttribute('data-price') || 0) : 0;
    recalc();
  });

  [qty, vat].forEach(el => el.addEventListener('input', recalc));
})();
</script>
<?php include 'footer.php'; ?>

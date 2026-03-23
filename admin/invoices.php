<?php
include 'auth_check.php';
include 'header.php'; // tạo $conn (mysqli)

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Ho_Chi_Minh');
$conn->query("SET time_zone = '+07:00'");

/* ---------- helpers ---------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_money($v){ return number_format((float)$v, 0, ',', '.') . ' ₫'; }
function fmt_rate($v){
    $s = rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    return $s;
}

/* ---- Helper tạo biểu thức NGÀY chỉ gồm các cột thực sự tồn tại ---- */
function buildDateExpr(mysqli $conn, string $alias, string $table, array $candidates): string {
    $in = "'" . implode("','", $candidates) . "'";
    $sql = "
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '{$conn->real_escape_string($table)}'
          AND COLUMN_NAME IN ($in)";
    $existing = [];
    if ($res = $conn->query($sql)) {
        while ($r = $res->fetch_assoc()) $existing[] = $r['COLUMN_NAME'];
    }
    $found = array_values(array_intersect($candidates, $existing));
    if (!$found) return "NOW()";
    $parts = array_map(fn($c) => "$alias.`$c`", $found);
    return count($parts) === 1 ? $parts[0] : ("COALESCE(" . implode(', ', $parts) . ")");
}

/* ---------- 1) nhận filter từ GET ---------- */
$from_date     = $_GET['from_date']     ?? '';
$to_date       = $_GET['to_date']       ?? '';
$customer_code = $_GET['customer_code'] ?? '';

/* ---------- 2) build where + chọn cột ngày hợp lệ ---------- */
// Chọn cột ngày đang có trong bảng invoices: invoice_date → created_at → create_at → date
$invoiceDateCol = null;
foreach (['invoice_date','created_at','create_at','date'] as $col) {
    $r = $conn->query("SHOW COLUMNS FROM invoices LIKE '{$col}'");
    if ($r && $r->num_rows) { $invoiceDateCol = $col; break; }
}
if (!$invoiceDateCol) { $invoiceDateCol = 'invoice_date'; } // fallback an toàn

$conds  = [];
$types  = '';
$params = [];

// Lọc theo khoảng ngày dựa trên cột ngày đã chọn
if ($from_date !== '') {
    $conds[]   = "i.`{$invoiceDateCol}` >= ?";
    $types    .= 's';
    $params[]  = $from_date;
}
if ($to_date !== '') {
    $conds[]   = "i.`{$invoiceDateCol}` <= ?";
    $types    .= 's';
    $params[]  = $to_date;
}

// Lọc theo mã khách (ép collation để tránh “Illegal mix of collations”)
if ($customer_code !== '') {
    $conds[]   = "sc.customer_code COLLATE utf8mb4_unicode_ci = ?"; 
    $types    .= 's';
    $params[]  = $customer_code;
}
$where_sql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

/* ---------- 3) lấy dữ liệu hóa đơn (fallback tên khách từ users nếu thiếu customers) ---------- */
$sql = "
SELECT 
  i.invoice_no,
  i.contract_no,
  i.`{$invoiceDateCol}` AS invoice_date,
  i.amount,
  i.vat_rate,
  i.vat_amount,
  i.total_amount,

  sc.customer_code,
  sc.car_code,

  -- Ưu tiên tên từ customers, nếu chưa có thì lấy từ users
  COALESCE(c.full_name, u.full_name) AS customer_name,
  car.name AS car_name,

  (
    SELECT p.`method`
    FROM payments p
    WHERE p.contract_no COLLATE utf8mb4_unicode_ci = i.contract_no COLLATE utf8mb4_unicode_ci
    ORDER BY p.payment_date DESC, p.created_at DESC, p.receipt_no DESC
    LIMIT 1
  ) AS payment_method,

  (sc.contract_no IS NOT NULL) AS has_contract

FROM invoices i
LEFT JOIN sales_contracts sc 
  ON i.contract_no COLLATE utf8mb4_unicode_ci = sc.contract_no COLLATE utf8mb4_unicode_ci
LEFT JOIN customers c  
  ON sc.customer_code COLLATE utf8mb4_unicode_ci = c.customer_code COLLATE utf8mb4_unicode_ci
LEFT JOIN users u
  ON u.customer_code COLLATE utf8mb4_unicode_ci = sc.customer_code COLLATE utf8mb4_unicode_ci
LEFT JOIN cars car     
  ON sc.car_code COLLATE utf8mb4_unicode_ci = car.car_code COLLATE utf8mb4_unicode_ci
{$where_sql}
ORDER BY i.`{$invoiceDateCol}` DESC, i.invoice_no DESC
";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* ---------- 4) danh sách khách hàng cho bộ lọc (lấy từ hợp đồng, fallback users) ---------- */
$customers = $conn->query("
  SELECT DISTINCT
    sc.customer_code,
    COALESCE(c.full_name, u.full_name, sc.customer_code) AS full_name
  FROM sales_contracts sc
  LEFT JOIN customers c 
    ON c.customer_code COLLATE utf8mb4_unicode_ci = sc.customer_code COLLATE utf8mb4_unicode_ci
  LEFT JOIN users u
    ON u.customer_code COLLATE utf8mb4_unicode_ci = sc.customer_code COLLATE utf8mb4_unicode_ci
  WHERE sc.customer_code IS NOT NULL
  ORDER BY full_name ASC
");

?>
<head>
  <meta charset="UTF-8">
  <title>Hóa đơn</title>
  <link rel="stylesheet" href="invoices.css">
</head>

<div class="main-content">
  <div class="container-fluid px-4 py-4">
    <h2>Danh sách hóa đơn</h2>

    <div class="mb-3 d-flex gap-2">
      <a href="export_invoices_excel.php?from_date=<?=urlencode($from_date)?>&to_date=<?=urlencode($to_date)?>&customer_code=<?=urlencode($customer_code)?>" class="btn btn-success">📄 Xuất Excel</a>
      <a href="export_invoices_pdf.php?from_date=<?=urlencode($from_date)?>&to_date=<?=urlencode($to_date)?>&customer_code=<?=urlencode($customer_code)?>" class="btn btn-danger" target="_blank">🖨️ In PDF</a>
       <a href="contract_create.php" class="btn btn-primary">＋ Tạo hợp đồng</a>
    </div>

    <!-- Bộ lọc -->
    <form method="GET" class="filter-form mb-4">
      <div class="filter-grid">
        <div>
          <label for="from_date">Từ ngày</label>
          <input type="date" name="from_date" id="from_date" class="form-control" value="<?= h($from_date) ?>">
        </div>
        <div>
          <label for="to_date">Đến ngày</label>
          <input type="date" name="to_date" id="to_date" class="form-control" value="<?= h($to_date) ?>">
        </div>
        <div>
          <label for="customer_code">Khách hàng</label>
          <select name="customer_code" id="customer_code" class="form-control">
            <option value="">-- Tất cả --</option>
            <?php if ($customers && $customers->num_rows): ?>
              <?php while ($c = $customers->fetch_assoc()): ?>
                <option value="<?= h($c['customer_code']) ?>" <?= $customer_code == $c['customer_code'] ? 'selected' : '' ?>>
                  <?= h($c['full_name']) ?>
                </option>
              <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </div>
        <div style="align-self: end;">
          <button type="submit" class="btn btn-primary">Lọc</button>
        </div>
      </div>
    </form>

    <!-- Bảng dữ liệu -->
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="table-dark">
          <tr>
            <th>Số HĐ</th>
            <th>Hợp đồng</th>
            <th>Khách hàng</th>
            <th>Xe</th>
            <th>Ngày HĐ</th>
            <th>Trước thuế</th>
            <th>VAT (%)</th>
            <th>VAT (₫)</th>
            <th>Tổng tiền</th>
            <th>Hình thức thanh toán</th>
            <th>Hợp đồng</th>
          </tr>
        </thead>
        <tbody>
<?php if ($result && $result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td>#<?= h($row['invoice_no']) ?></td>
      <td><?= h($row['contract_no']) ?></td>
      <td><?= h($row['customer_name'] ?? '—') ?></td>
      <td><?= h($row['car_name'] ?? '—') ?></td>
      <td><?= !empty($row['invoice_date']) ? date('d/m/Y', strtotime($row['invoice_date'])) : '' ?></td>
      <!-- Trước thuế (BỊ THIẾU) -->
      <td><?= fmt_money($row['amount']) ?></td>
      <!-- VAT (%) -->
      <td><?= fmt_rate($row['vat_rate']) ?></td>
      <!-- VAT (₫) -->
      <td><?= fmt_money($row['vat_amount']) ?></td>
      <!-- Tổng tiền -->
      <td><strong><?= fmt_money($row['total_amount']) ?></strong></td>
      <!-- Hình thức thanh toán -->
      <td><?= h($row['payment_method'] ?? '—') ?></td>
      <!-- Nút hợp đồng -->
      <td>
        <?php if (!empty($row['customer_code']) && !empty($row['car_code'])): ?>
          <a href="open_contract.php?customer_code=<?= urlencode((string)$row['customer_code']) ?>&car_code=<?= urlencode((string)$row['car_code']) ?>" 
             class="btn btn-primary btn-sm">
            <?= !empty($row['has_contract']) ? 'Xem hợp đồng' : 'Tạo & Xem hợp đồng' ?>
          </a>
        <?php else: ?>
          <span class="text-muted">—</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
<?php else: ?>
  <tr><td colspan="11" class="text-center">Không có hóa đơn phù hợp</td></tr>
<?php endif; ?>
</tbody>

      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

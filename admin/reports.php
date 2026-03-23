<?php
include "header.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once '../connect.php';
$conn->set_charset('utf8mb4');

// === Input ngày (mặc định: từ đầu năm -> hôm nay) ===
$today = date('Y-m-d');
$yearStart = date('Y-01-01');
$from = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : $yearStart;
$to   = isset($_GET['to_date'])   && $_GET['to_date']   !== '' ? $_GET['to_date']   : $today;

// Chuẩn hóa phạm vi (đổi nếu from > to)
if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }

// === Helper: trả về 1 giá trị (scalar) ===
function fetch_scalar(mysqli $conn, string $sql, string $types, array $params, $default = 0) {
    $st = $conn->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result()->fetch_row();
    return $res && $res[0] !== null ? $res[0] : $default;
}

// === KPIs ===
// Tổng hợp đồng theo contract_date
$totalContracts = fetch_scalar(
    $conn,
    "SELECT COUNT(*) FROM sales_contracts WHERE contract_date BETWEEN ? AND ?",
    "ss",
    [$from, $to],
    0
);
// Tổng phiếu thu theo payment_date
$totalReceipts = fetch_scalar(
    $conn,
    "SELECT COUNT(*) FROM payments WHERE payment_date BETWEEN ? AND ?",
    "ss",
    [$from, $to],
    0
);
// Doanh thu (tổng amount theo receipts)
$totalRevenue = fetch_scalar(
    $conn,
    "SELECT SUM(amount) FROM payments WHERE payment_date BETWEEN ? AND ?",
    "ss",
    [$from, $to],
    0.0
);

// === Biểu đồ doanh thu theo tháng (YYYY-MM) ===
$st = $conn->prepare("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY ym
    ORDER BY ym
");
$st->bind_param("ss", $from, $to);
$st->execute();
$rs = $st->get_result();
$months = [];
$revenueByMonth = [];
while ($row = $rs->fetch_assoc()) {
    $months[] = $row['ym'];
    $revenueByMonth[] = (float)$row['total'];
}
$st->close();

// === Cơ cấu doanh thu theo phương thức thanh toán ===
$st = $conn->prepare("
    SELECT method, SUM(amount) AS total
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY method
    ORDER BY total DESC
");
$st->bind_param("ss", $from, $to);
$st->execute();
$rs = $st->get_result();
$methodLabels = [];
$methodTotals = [];
while ($row = $rs->fetch_assoc()) {
    $methodLabels[] = $row['method'];
    $methodTotals[] = (float)$row['total'];
}
$st->close();

// === Danh sách phiếu thu gần nhất (top 10) ===
$st = $conn->prepare("
    SELECT r.receipt_no, r.contract_no, r.payment_date, r.method, r.amount
    FROM payments r
    WHERE r.payment_date BETWEEN ? AND ?
    ORDER BY r.payment_date DESC, r.receipt_no DESC
    LIMIT 10
");
$st->bind_param("ss", $from, $to);
$st->execute();
$recentReceipts = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// === Top 5 hợp đồng theo tổng tiền (trong phạm vi ngày ký) ===
$st = $conn->prepare("
    SELECT contract_no, contract_date, customer_code, total_amount
    FROM sales_contracts
    WHERE contract_date BETWEEN ? AND ?
    ORDER BY total_amount DESC
    LIMIT 5
");
$st->bind_param("ss", $from, $to);
$st->execute();
$topContracts = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Báo cáo - Reports</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap để dùng grid .row .col-md-* -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
  /* === Giao diện chính === */
  .main-content {
      padding: 32px;
      background-color: #f5f7fa;
      font-family: 'Segoe UI', Tahoma, sans-serif;
  }

  .main-content h2 {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 30px;
  }

  /* === Bộ lọc ngày === */
  form.row {
      background-color: #ffffff;
      border: 1px solid #e1e5ec;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 30px;
  }

  form .form-label {
      font-weight: 600;
      color: #333;
  }

  form input[type="date"] {
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
  }

  /* === Thẻ thống kê === */
  .row.g-3 .col-md-4 > div {
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      transition: transform 0.3s ease;
      background: #fff;
  }
  .row.g-3 .col-md-4 > div:hover {
      transform: translateY(-3px);
  }
  .row.g-3 h5 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 10px;
  }
  .row.g-3 h3 {
      font-size: 28px;
      font-weight: bold;
  }

  /* === Biểu đồ === */
  canvas {
      width: 100% !important;
      max-height: 280px;
  }

  .row.g-4 .bg-white {
      border: 1px solid #e1e5ec;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.04);
      background: #fff;
  }

  .row.g-4 h5 {
      font-weight: 600;
      color: #444;
      margin-bottom: 20px;
  }

  /* === Responsive === */
  @media screen and (max-width: 768px) {
      .main-content {
          padding: 20px;
      }

      .row.g-3 h3 {
          font-size: 24px;
      }

      form.row {
          padding: 16px;
      }
  }
  </style>
</head>
<body>
  <div class="main-content container-fluid">
    <h2>Báo cáo tổng hợp</h2>

    <!-- Bộ lọc ngày -->
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-4">
        <label class="form-label" for="from_date">Từ ngày</label>
        <input type="date" id="from_date" name="from_date" class="form-control" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="to_date">Đến ngày</label>
        <input type="date" id="to_date" name="to_date" class="form-control" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div class="col-md-4">
        <button class="btn btn-primary" type="submit">Lọc</button>
        <a class="btn btn-outline-secondary" href="reports.php">Reset</a>
      </div>
    </form>

    <!-- KPIs -->
    <div class="row g-3">
      <div class="col-md-4">
        <div>
          <h5>Tổng hợp đồng</h5>
          <h3><?= number_format((int)$totalContracts) ?></h3>
          <div class="text-muted small">Trong khoảng <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div>
          <h5>Tổng phiếu thu</h5>
          <h3><?= number_format((int)$totalReceipts) ?></h3>
          <div class="text-muted small">Trong khoảng <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div>
          <h5>Tổng doanh thu</h5>
          <h3><?= number_format((float)$totalRevenue, 0, ',', '.') ?> ₫</h3>
          <div class="text-muted small">Theo bảng payments</div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mt-1">
      <div class="col-lg-8">
        <div class="bg-white">
          <h5>Doanh thu theo tháng</h5>
          <canvas id="chartRevenue"></canvas>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="bg-white">
          <h5>Cơ cấu theo phương thức</h5>
          <canvas id="chartMethod"></canvas>
        </div>
      </div>
    </div>

    <!-- Bảng chi tiết -->
    <div class="row g-4 mt-1">
      <div class="col-lg-7">
        <div class="bg-white">
          <h5>Phiếu thu gần nhất</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Hóa đơn</th>
                  <th>Hợp đồng</th>
                  <th>Ngày thanh toán</th>
                  <th>Phương thức</th>
                  <th class="text-end">Số tiền</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($recentReceipts)): ?>
                <tr><td colspan="5" class="text-center text-muted">Không có dữ liệu</td></tr>
              <?php else: foreach ($recentReceipts as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['receipt_no']) ?></td>
                  <td><?= htmlspecialchars($r['contract_no']) ?></td>
                  <td><?= htmlspecialchars($r['payment_date']) ?></td>
                  <td><?= htmlspecialchars($r['method']) ?></td>
                  <td class="text-end"><?= number_format((float)$r['amount'], 0, ',', '.') ?> ₫</td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="bg-white">
          <h5>Top 5 hợp đồng (theo tổng tiền)</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Hợp đồng </th>
                  <th>Ngày ký</th>
                  <th>KH</th>
                  <th class="text-end">Tổng tiền</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($topContracts)): ?>
                <tr><td colspan="4" class="text-center text-muted">Không có dữ liệu</td></tr>
              <?php else: foreach ($topContracts as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c['contract_no']) ?></td>
                  <td><?= htmlspecialchars($c['contract_date']) ?></td>
                  <td><?= htmlspecialchars($c['customer_code']) ?></td>
                  <td class="text-end"><?= number_format((float)$c['total_amount'], 0, ',', '.') ?> ₫</td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Data từ PHP
    const months = <?= json_encode(array_values($months), JSON_UNESCAPED_UNICODE) ?>;
    const revenueByMonth = <?= json_encode(array_map('floatval', $revenueByMonth)) ?>;
    const methodLabels = <?= json_encode(array_values($methodLabels), JSON_UNESCAPED_UNICODE) ?>;
    const methodTotals = <?= json_encode(array_map('floatval', $methodTotals)) ?>;

    // Line chart: doanh thu theo tháng
    const ctxRev = document.getElementById('chartRevenue').getContext('2d');
    new Chart(ctxRev, {
      type: 'line',
      data: {
        labels: months,
        datasets: [{
          label: 'Doanh thu (₫)',
          data: revenueByMonth,
          tension: 0.25,
          borderWidth: 2,
          borderColor: '#3498db',
          backgroundColor: 'rgba(52,152,219,0.2)',
          pointRadius: 3,
          fill: true
        }]
      },
      options: {
        plugins: { legend: { display: true } },
        scales: {
          y: { ticks: { callback: v => Intl.NumberFormat('vi-VN').format(v) + ' ₫' } }
        }
      }
    });

    // Doughnut chart: cơ cấu phương thức
    const ctxMethod = document.getElementById('chartMethod').getContext('2d');
    new Chart(ctxMethod, {
      type: 'doughnut',
      data: {
        labels: methodLabels,
        datasets: [{
          data: methodTotals,
          // màu tự động của Chart.js là đủ, có thể tùy chỉnh nếu cần
        }]
      },
      options: {
        plugins: { legend: { position: 'bottom' } },
        cutout: '60%'
      }
    });
  </script>
</body>
</html>
<?php
include "footer.php";
?>
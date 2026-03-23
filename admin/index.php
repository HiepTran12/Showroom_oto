<?php
include 'auth_check.php';
include 'header.php';
require_once __DIR__ . '/../connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* ===== Timezone VN ===== */
date_default_timezone_set('Asia/Ho_Chi_Minh');
$conn->query("SET time_zone = '+07:00'");

/* ===== Helpers ===== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function time_elapsed_string($datetime, $full = false) {
    $tz  = new DateTimeZone('Asia/Ho_Chi_Minh');
    if (!$datetime) return 'vừa xong';
    $ago = DateTime::createFromFormat('Y-m-d H:i:s', $datetime, $tz)
        ?: DateTime::createFromFormat('Y-m-d', $datetime, $tz)
        ?: new DateTime($datetime, $tz);
    $now = new DateTime('now', $tz);
    $diff = $now->diff($ago);
    $weeks = floor($diff->days / 7);
    $days  = $diff->days - ($weeks * 7);
    $parts = [];
    if ($diff->y) $parts[] = $diff->y.' năm';
    if ($diff->m) $parts[] = $diff->m.' tháng';
    if ($weeks)   $parts[] = $weeks.' tuần';
    if ($days)    $parts[] = $days.' ngày';
    if ($diff->h) $parts[] = $diff->h.' giờ';
    if ($diff->i) $parts[] = $diff->i.' phút';
    if ($diff->s) $parts[] = $diff->s.' giây';
    if (!$parts) return 'vừa xong';
    if (!$full) $parts = array_slice($parts, 0, 1);
    return implode(', ', $parts).' trước';
}

/* ===== Merge helpers (gộp map theo key & sắp tăng) ===== */
function merge_union_sorted_multi(...$seriesMaps) {
    $keys = [];
    foreach ($seriesMaps as $m) $keys = array_merge($keys, array_keys($m));
    $keys = array_values(array_unique($keys));
    sort($keys, SORT_NATURAL);

    $labels = [];
    $sum    = [];
    $each   = array_fill(0, count($seriesMaps), []);
    foreach ($keys as $k) {
        $labels[] = $k;
        $rowVals = [];
        $rowSum  = 0.0;
        foreach ($seriesMaps as $idx => $m) {
            $v = isset($m[$k]) ? (float)$m[$k] : 0.0;
            $rowVals[$idx] = $v;
            $rowSum += $v;
        }
        $sum[] = $rowSum;
        foreach ($rowVals as $i => $v) $each[$i][] = $v;
    }
    return array_merge([$labels, $sum], $each);
}

/* =======================================================
                DOANH THU + BIỂU ĐỒ (4 NGUỒN)
   payments.amount + service_costs.amount
   + warranties.total_amount + warranties.warranty_fee
   ======================================================= */
$selectedMonth = $_GET['month'] ?? '';

$labels         = [];
$combinedTotals = [];

$seriesPayments = [];
$seriesService  = [];
$seriesWTotal   = [];
$seriesWFee     = [];

if ($selectedMonth !== '') {
    // payments theo ngày
    $st = $conn->prepare("
        SELECT DAY(payment_date) AS d, COALESCE(SUM(amount),0) AS total
        FROM payments
        WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?
        GROUP BY d ORDER BY d ASC
    ");
    $st->bind_param("s", $selectedMonth);
    $st->execute();
    $rs = $st->get_result();
    $mapP = [];
    while ($r = $rs->fetch_assoc()) $mapP[(int)$r['d']] = (float)$r['total'];
    $st->close();

    // service_costs theo ngày
    $st = $conn->prepare("
        SELECT DAY(created_at) AS d, COALESCE(SUM(amount),0) AS total
        FROM service_costs
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        GROUP BY d ORDER BY d ASC
    ");
    $st->bind_param("s", $selectedMonth);
    $st->execute();
    $rs = $st->get_result();
    $mapS = [];
    while ($r = $rs->fetch_assoc()) $mapS[(int)$r['d']] = (float)$r['total'];
    $st->close();

    // warranties.total_amount
    $st = $conn->prepare("
        SELECT DAY(created_at) AS d, COALESCE(SUM(total_amount),0) AS total
        FROM warranties
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        GROUP BY d ORDER BY d ASC
    ");
    $st->bind_param("s", $selectedMonth);
    $st->execute();
    $rs = $st->get_result();
    $mapWT = [];
    while ($r = $rs->fetch_assoc()) $mapWT[(int)$r['d']] = (float)$r['total'];
    $st->close();

    // warranties.warranty_fee
    $st = $conn->prepare("
        SELECT DAY(created_at) AS d, COALESCE(SUM(warranty_fee),0) AS total
        FROM warranties
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        GROUP BY d ORDER BY d ASC
    ");
    $st->bind_param("s", $selectedMonth);
    $st->execute();
    $rs = $st->get_result();
    $mapWF = [];
    while ($r = $rs->fetch_assoc()) $mapWF[(int)$r['d']] = (float)$r['total'];
    $st->close();

    list($labels, $combinedTotals, $seriesPayments, $seriesService, $seriesWTotal, $seriesWFee)
        = merge_union_sorted_multi($mapP, $mapS, $mapWT, $mapWF);

} else {
    // payments theo tháng
    $rs = $conn->query("
        SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
        FROM payments
        GROUP BY ym ORDER BY ym ASC
    ");
    $mapP = [];
    while ($r = $rs->fetch_assoc()) $mapP[$r['ym']] = (float)$r['total'];
    $rs->free();

    // service_costs theo tháng
    $rs = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
        FROM service_costs
        GROUP BY ym ORDER BY ym ASC
    ");
    $mapS = [];
    while ($r = $rs->fetch_assoc()) $mapS[$r['ym']] = (float)$r['total'];
    $rs->free();

    // warranties.total_amount theo tháng
    $rs = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(total_amount),0) AS total
        FROM warranties
        GROUP BY ym ORDER BY ym ASC
    ");
    $mapWT = [];
    while ($r = $rs->fetch_assoc()) $mapWT[$r['ym']] = (float)$r['total'];
    $rs->free();

    // warranties.warranty_fee theo tháng
    $rs = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(warranty_fee),0) AS total
        FROM warranties
        GROUP BY ym ORDER BY ym ASC
    ");
    $mapWF = [];
    while ($r = $rs->fetch_assoc()) $mapWF[$r['ym']] = (float)$r['total'];
    $rs->free();

    list($labels, $combinedTotals, $seriesPayments, $seriesService, $seriesWTotal, $seriesWFee)
        = merge_union_sorted_multi($mapP, $mapS, $mapWT, $mapWF);
}

$revenueCurrent = !empty($combinedTotals) ? end($combinedTotals) : 0.0;
$prevRevenue    = (count($combinedTotals) > 1) ? $combinedTotals[count($combinedTotals)-2] : 0.0;
$revenueGrowth  = ($prevRevenue > 0)
    ? round((($revenueCurrent - $prevRevenue)/$prevRevenue)*100, 2)
    : 0.0;

$sum_payments       = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM payments")->fetch_assoc()['s'] ?? 0;
$sum_service        = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM service_costs")->fetch_assoc()['s'] ?? 0;
$sum_warranty_total = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS s FROM warranties")->fetch_assoc()['s'] ?? 0;
$sum_warranty_fee   = $conn->query("SELECT COALESCE(SUM(warranty_fee),0) AS s FROM warranties")->fetch_assoc()['s'] ?? 0;
$totalRevenueAll    = (float)$sum_payments + (float)$sum_service + (float)$sum_warranty_total + (float)$sum_warranty_fee;

/* ======= Khách hàng theo tháng ======= */
$customerLabels = []; $customerCounts = [];
$rsC = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
    FROM customers
    GROUP BY ym ORDER BY ym ASC
");
while ($row = $rsC->fetch_assoc()) { $customerLabels[] = $row['ym']; $customerCounts[] = (int)$row['total']; }
$rsC->free();

$customerCurrent = !empty($customerCounts) ? end($customerCounts) : 0;
$prevCustomer    = (count($customerCounts) > 1) ? $customerCounts[count($customerCounts)-2] : 0;
$customerGrowth  = ($prevCustomer > 0)
    ? round((($customerCurrent - $prevCustomer)/$prevCustomer)*100, 2)
    : 0.0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Luxury Cars - Bảng điều khiển</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <script src="admin.js"></script>
</head>
<body>
<main class="container">
  <section class="dashboard">
    <div class="dashboard-header">
      <h2>Bảng điều khiển hệ thống</h2>
    </div>

    <div class="stats-grid">
      <!-- Doanh thu kỳ -->
      <div class="stat-card">
        <div class="stat-icon bg-orange"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-info">
          <h3>Doanh thu kỳ</h3>
          <p><?= number_format($revenueCurrent, 0, ',', '.') ?> ₫</p>
          <span class="<?= ($revenueGrowth < 0) ? 'down' : '' ?>">
            <?= h(number_format($revenueGrowth, 2)) ?>% <?= ($revenueGrowth >= 0) ? 'tăng trưởng' : 'giảm' ?>
          </span>
        </div>
      </div>

      <!-- Tổng doanh thu -->
      <div class="stat-card">
        <div class="stat-icon bg-green"><i class="fas fa-coins"></i></div>
        <div class="stat-info">
          <h3>Tổng doanh thu</h3>
          <p><?= number_format($totalRevenueAll, 0, ',', '.') ?> ₫</p>
          <span>
            Payment: <?= number_format($sum_payments, 0, ',', '.') ?> ₫ •
            DV: <?= number_format($sum_service, 0, ',', '.') ?> ₫ •
            BH: <?= number_format($sum_warranty_total, 0, ',', '.') ?> ₫ •
            Phí BH: <?= number_format($sum_warranty_fee, 0, ',', '.') ?> ₫
          </span>
        </div>
      </div>

      <!-- Khách hàng -->
      <div class="stat-card">
        <div class="stat-icon bg-red"><i class="fas fa-users"></i></div>
        <div class="stat-info">
          <h3>Khách hàng mới</h3>
          <p><?= h((string)$customerCurrent) ?></p>
          <span class="<?= ($customerGrowth < 0) ? 'down' : '' ?>">
            <?= h(number_format($customerGrowth, 2)) ?>% so với kỳ trước
          </span>
        </div>
      </div>

      <!-- Về trang chính -->
      <div class="stat-card" onclick="window.location='../index.php'" style="cursor:pointer;">
        <div class="stat-icon bg-blue"><i class="fas fa-home"></i></div>
        <div class="stat-info"><p>Về trang chính</p></div>
      </div>
    </div>

    <div class="content-grid">
      <!-- Biểu đồ -->
      <div class="chart-container">
        <div class="section-header">
          <h3 id="bd">Biểu đồ doanh thu</h3>
          <select
            style="padding:8px;border-radius:6px;border:1px solid var(--border);"
            onchange="location.href='index.php?month='+this.value+'#bd'">
            <option value="">Xem tất cả</option>
            <?php
            $months = $conn->query("
              SELECT ym FROM (
                SELECT DISTINCT DATE_FORMAT(payment_date, '%Y-%m') AS ym FROM payments
                UNION
                SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS ym FROM service_costs
                UNION
                SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS ym FROM warranties
              ) t
              ORDER BY ym DESC
            ");
            while ($row = $months->fetch_assoc()):
              $ym   = $row['ym'];
              $sel  = ($selectedMonth === $ym) ? 'selected' : '';
              $mNum = date('m', strtotime($ym));
              $yNum = date('Y', strtotime($ym));
            ?>
              <option value="<?= h($ym) ?>" <?= $sel ?>>Tháng <?= h($mNum) ?>/<?= h($yNum) ?></option>
            <?php endwhile; $months->free(); ?>
          </select>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
      </div>

      <!-- Hoạt động gần đây -->
      <div class="recent-container">
        <div class="section-header"><h3>Hoạt động gần đây</h3></div>
        <ul class="activity-list">
        <?php
          $sqlRecent = "
            SELECT t.type, t.message, t.activity_time
            FROM (
              SELECT 'customer' AS type,
                    CONCAT(full_name, ' đã đăng ký tư vấn') AS message,
                    created_at AS activity_time
              FROM customers

              UNION ALL
              SELECT 'request' AS type,
                    CONCAT('Đơn hàng #', request_no, ' đã tạo') AS message,
                    created_at AS activity_time
              FROM service_requests

              UNION ALL
              SELECT 'user' AS type,
                    CONCAT('Tài khoản ', username, ' đã được tạo') AS message,
                    created_at AS activity_time
              FROM users

              UNION ALL
              SELECT 'car' AS type,
                    CONCAT('Xe mới: ', name, ' đã được thêm') AS message,
                    created_at AS activity_time
              FROM cars
            ) AS t
            ORDER BY t.activity_time DESC
          LIMIT 5
            ";

          $result = $conn->query($sqlRecent);

          $icon_map = [
            'customer'    => ['fas fa-users', 'var(--accent)'],
            'request'     => ['fas fa-file-invoice-dollar', 'var(--success)'],
            'user'        => ['fas fa-user', 'var(--success)'],
            'car'         => ['fas fa-car-side', 'var(--primary)'],
          ];

          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $icon  = $icon_map[$row['type']][0] ?? 'fas fa-bell';
              $color = $icon_map[$row['type']][1] ?? '#777';
              $timeAgo = time_elapsed_string($row['activity_time']);
        ?>
          <li class="activity-item">
            <div class="activity-icon" style="background-color: <?= h($color) ?>;">
              <i class="<?= h($icon) ?>"></i>
            </div>
            <div class="activity-details">
              <p><?= h($row['message']) ?></p>
              <div class="activity-time"><?= h($timeAgo) ?></div>
            </div>
          </li>
        <?php endwhile; else: ?>
          <li>Không có hoạt động nào</li>
        <?php endif; ?>
        </ul>
      </div>
    </div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const dataAll = <?= json_encode($combinedTotals, JSON_NUMERIC_CHECK) ?>;

const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'TỔNG doanh thu (Payment + DV + BH + Phí BH)',
      data: dataAll,
      backgroundColor: 'rgba(75,192,192,0.7)',
      borderColor: 'rgba(75,192,192,1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Doanh thu hệ thống' }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
});
</script>
</body>
</html>

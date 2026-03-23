<?php
// service_dashboard.php
session_start();
include 'auth_check.php';
include 'header.php';
require_once __DIR__ . '/../connect.php';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// tên hiển thị
$loggedInUser = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : ($_SESSION['username'] ?? '');

// ===================== QUERIES =====================

// 1) Yêu cầu bảo dưỡng/dịch vụ
$service_requests = $conn->query("
  SELECT
    sr.request_no,
    sr.customer_code,
    COALESCE(c.full_name, CONCAT('[', sr.customer_code, ']')) AS customer_name,
    sr.car_code,
    COALESCE(car.name, CONCAT('[', sr.car_code, ']')) AS car_name,
    sr.request_type,
    sr.description,
    sr.status,
    sr.created_at
  FROM service_requests sr
  LEFT JOIN customers c ON TRIM(LOWER(sr.customer_code)) = TRIM(LOWER(c.customer_code))
  LEFT JOIN cars car     ON TRIM(LOWER(sr.car_code))     = TRIM(LOWER(car.car_code))
  ORDER BY sr.created_at DESC
");

// 2) Chi phí (từ các dịch vụ đã chọn)
$service_costs = $conn->query("
  SELECT
    sc.cost_no, sc.request_no, sc.item_name, sc.quantity, sc.unit_price, sc.amount, sc.created_at
  FROM service_costs sc
  LEFT JOIN service_requests sr ON sc.request_no = sr.request_no
  ORDER BY sc.created_at DESC
");

// 3) Bảo hành (đã gồm gói & tiền)
$warranties = $conn->query("
  SELECT
    w.warranty_no, w.customer_code,
    COALESCE(c.full_name, CONCAT('[', w.customer_code, ']')) AS customer_name,
    w.car_code, COALESCE(car.name, CONCAT('[', w.car_code, ']')) AS car_name,
    w.vehicle_vin, w.start_date, w.end_date, w.policy,
    w.package_names, w.total_amount, w.created_at
  FROM warranties w
  LEFT JOIN customers c ON TRIM(LOWER(w.customer_code)) = TRIM(LOWER(c.customer_code))
  LEFT JOIN cars car     ON TRIM(LOWER(w.car_code))     = TRIM(LOWER(car.car_code))
  ORDER BY w.created_at DESC
");

// 4) (tuỳ dùng) lịch sử bảo dưỡng
$service_records = $conn->query("
  SELECT r.service_record_no, r.request_no, r.service_dt, r.details, r.cost, r.created_at
  FROM service_records r
  LEFT JOIN service_requests sr ON r.request_no = sr.request_no
  ORDER BY r.created_at DESC
");

// Tổng doanh thu
$sum_service  = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM service_costs")->fetch_assoc()['s'] ?? 0;
$sum_warranty = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS s FROM warranties")->fetch_assoc()['s'] ?? 0;
$total_revenue = (float)$sum_service + (float)$sum_warranty;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý Bảo trì & Dịch vụ</title>
<style>
  .container { width: 95%; margin: auto; }
  h1 { margin: 12px 0 20px; }
  .welcome { margin-bottom: 15px; font-weight: 600; color: #333; }

  .menu-bar { display:flex; gap:10px; align-items:center; margin-bottom: 16px; flex-wrap:wrap; }
  .btn {
    padding: 8px 14px; background:#007bff; color:#fff; border:none; border-radius:6px; cursor:pointer;
  }
  .btn:hover { background:#0056b3; }

  .revenue-pill {
    margin-left:auto; background:#0f2741; color:#fff; padding:10px 16px; border-radius:8px; font-weight:700;
  }
  .revenue-pill small { font-weight:400; opacity:.85; margin-left:8px; }

  .tab-content { display:none; }
  .tab-content.active { display:block; }

  .table-container { padding: 12px 0 28px; }

  .custom-table {
    width: 100%; border-collapse: collapse; font-size: 14px; background:#fff;
    border:1px solid #ddd; border-radius:6px; overflow:hidden;
  }
  .custom-table thead { background:#f4f4f4; border-bottom:2px solid #ddd; }
  .custom-table th, .custom-table td { padding:10px; border-bottom:1px solid #ddd; border-right:1px solid #ddd; text-align:left; }
  .custom-table th:last-child, .custom-table td:last-child { border-right:none; }
  .custom-table tbody tr:nth-child(even) { background:#f9f9f9; }
  .custom-table tbody tr:hover { background:#eef5ff; }

  /* căn phải cột tiền (bảng 2 và bảng 3) */
  /* Bảng 2: Đơn giá (5), Thành tiền (6) */
  .tab-costs .custom-table td:nth-child(5),
  .tab-costs .custom-table td:nth-child(6) { text-align:right; }
  /* Bảng 3: Thành tiền (8) */
  .tab-warranty .custom-table td:nth-child(8) { text-align:right; }
</style>
</head>
<body>
<div class="container">
  <h1>Quản lý Bảo trì & Dịch vụ</h1>
  <div class="welcome">Xin chào, <?= htmlspecialchars($loggedInUser) ?> 👋</div>

  <div class="menu-bar">
    <button class="btn" onclick="showTab(0)">Yêu cầu bảo dưỡng/ dịch vụ</button>
    <button class="btn" onclick="showTab(1)">Chi phí bảo dưỡng & dịch vụ</button>
    <button class="btn" onclick="showTab(2)">Quản lý bảo hành</button>

    <div class="revenue-pill">
      Tổng doanh thu: <?= number_format($total_revenue, 0) ?> VNĐ
      <small>(Bảo dưỡng/sửa chữa: <?= number_format($sum_service, 0) ?> • Bảo hành: <?= number_format($sum_warranty, 0) ?>)</small>
    </div>
  </div>

  <!-- Bảng 1: Yêu cầu -->
  <div class="tab-content active tab-requests">
    <div class="table-container">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Mã yêu cầu</th>
            <th>Khách hàng</th>
            <th>Xe</th>
            <th>Loại</th>
            <th>Mô tả</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $service_requests->fetch_assoc()): ?>
          <tr>
            <td><?= $row['request_no'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['car_name']) ?></td>
            <td><?= $row['request_type'] ?></td>
            <td><?= $row['description'] ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= $row['created_at'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Bảng 2: Chi phí -->
  <div class="tab-content tab-costs">
    <div class="table-container">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Mã chi phí</th>
            <th>Mã yêu cầu</th>
            <th>Hạng mục</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
            <th>Ngày tạo</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $service_costs->fetch_assoc()): ?>
          <tr>
            <td><?= $row['cost_no'] ?></td>
            <td><?= $row['request_no'] ?></td>
            <td><?= $row['item_name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= number_format($row['unit_price'], 0) ?></td>
            <td><?= number_format($row['amount'], 0) ?></td>
            <td><?= $row['created_at'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Bảng 3: Bảo hành -->
  <div class="tab-content tab-warranty">
    <div class="table-container">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Mã bảo hành</th>
            <th>Khách hàng</th>
            <th>Xe</th>
            <th>Ngày bắt đầu</th>
            <th>Ngày kết thúc</th>
            <th>Gói đã chọn</th>
            <th>Thành tiền</th>
            <th>Chính sách</th>
            <th>Ngày tạo</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $warranties->fetch_assoc()): ?>
          <tr>
            <td><?= $row['warranty_no'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['car_name']) ?></td>
            <td><?= $row['start_date'] ?></td>
            <td><?= $row['end_date'] ?></td>
            <td><?= htmlspecialchars($row['package_names'] ?: '-') ?></td>
            <td><?= number_format((float)$row['total_amount'], 0) ?></td>
            <td><?= htmlspecialchars($row['policy']) ?></td>
            <td><?= $row['created_at'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  // tabs
  const tabs = document.querySelectorAll('.tab-content');
  function showTab(i) {
    tabs.forEach(t => t.classList.remove('active'));
    if (tabs[i]) tabs[i].classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
</script>

</body>
</html>

<?php include 'footer.php'; ?>

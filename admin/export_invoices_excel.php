<?php
include 'auth_check.php';
require '../connect.php'; // file kết nối CSDL

// Xuất file Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=hoa_don_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr>
        <th>Mã HĐ</th>
        <th>Khách hàng</th>
        <th>Xe</th>
        <th>Ngày hóa đơn</th>
        <th>Tổng tiền</th>
        <th>Thanh toán</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
      </tr>";

// Query invoices + hợp đồng + khách hàng + xe + thanh toán
$sql = "
    SELECT 
        i.invoice_no, 
        i.invoice_date, 
        i.total_amount, 
        i.created_at,
        c.full_name AS customer_name,
        car.name AS car_name,
        p.`method` AS payment_method,
        sc.`status` AS contract_status
    FROM invoices i
    JOIN sales_contracts sc ON i.contract_no = sc.contract_no
    JOIN customers c ON sc.customer_code = c.customer_code
    JOIN cars car ON sc.car_code = car.car_code
    LEFT JOIN payments p ON p.contract_no = sc.contract_no
    ORDER BY i.invoice_date DESC
";

$result = $conn->query($sql);

// Nếu query lỗi → in ra thông báo SQL để debug
if (!$result) {
    echo "<tr><td colspan='8' style='color:red;'>";
    echo "SQL Error: " . $conn->error . "<br><br>";
    echo "Query: " . htmlspecialchars($sql);
    echo "</td></tr>";
    echo "</table>";
    exit;
}

// Hiển thị dữ liệu
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['invoice_no']}</td>";
    echo "<td>{$row['customer_name']}</td>";
    echo "<td>{$row['car_name']}</td>";
    echo "<td>" . date('d/m/Y', strtotime($row['invoice_date'])) . "</td>";
    echo "<td>" . number_format($row['total_amount'], 0, ',', '.') . " ₫</td>";
    echo "<td>" . ($row['payment_method'] ?? '-') . "</td>";
    echo "<td>{$row['contract_status']}</td>";
    echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>

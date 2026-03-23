<?php
include 'auth_check.php';
require '../connect.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=customers.xls");

echo "<table border='1'>";
echo "<tr>
        <th>Mã KH</th>
        <th>Họ tên</th>
        <th>Điện thoại</th>
        <th>Email</th>
        <th>Địa chỉ</th>
        <th>Ghi chú</th>
        <th>Ngày tạo</th>
      </tr>";

$sql = "SELECT * FROM customers ORDER BY created_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['customer_code']}</td>
            <td>{$row['full_name']}</td>
            <td>{$row['phone']}</td>
            <td>{$row['email']}</td>
            <td>{$row['address']}</td>
            <td>{$row['notes']}</td>
            <td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>
          </tr>";
}
echo "</table>";
?>
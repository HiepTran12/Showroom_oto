<?php
ob_start();

include 'auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connect.php';

// ---- Nhận tham số lọc
$from_date     = $_GET['from_date']     ?? '';
$to_date       = $_GET['to_date']       ?? '';
$customer_code = $_GET['customer_code'] ?? '';

$conds = [];
if ($from_date !== '') {
    $conds[] = "i.invoice_date >= '" . $conn->real_escape_string($from_date) . "'";
}
if ($to_date !== '') {
    $conds[] = "i.invoice_date <= '" . $conn->real_escape_string($to_date) . "'";
}
if ($customer_code !== '') {
    $conds[] = "sc.customer_code COLLATE utf8mb4_unicode_ci = '"
             . $conn->real_escape_string($customer_code) . "' COLLATE utf8mb4_unicode_ci";
}
$where = count($conds) ? ('WHERE ' . implode(' AND ', $conds)) : '';

// ---- Query dữ liệu (THÊM $where và ép COLLATE cho các JOIN khóa text)
$sql = "
  SELECT 
      i.invoice_no,
      i.contract_no,
      i.invoice_date,
      i.amount,
      i.vat_rate,
      i.vat_amount,
      i.total_amount,
      c.full_name AS customer_name,
      car.name    AS car_name
  FROM invoices i
  JOIN sales_contracts sc
       ON i.contract_no COLLATE utf8mb4_unicode_ci
        = sc.contract_no COLLATE utf8mb4_unicode_ci
  JOIN customers c
       ON sc.customer_code COLLATE utf8mb4_unicode_ci
        = c.customer_code  COLLATE utf8mb4_unicode_ci
  JOIN cars car
       ON sc.car_code COLLATE utf8mb4_unicode_ci
        = car.car_code COLLATE utf8mb4_unicode_ci
  $where
  ORDER BY i.invoice_date DESC, i.invoice_no DESC
";
$result = $conn->query($sql);
if (!$result) {
    die('Query failed: ' . $conn->error);
}

// ---- TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// ---- HTML
$html = '<h2 style="text-align:center">Danh sách hóa đơn</h2>';
$html .= '<table border="1" cellpadding="5">
<thead>
<tr style="font-weight:bold; background-color:#f0f0f0">
  <th>#</th><th>Số HĐ</th><th>Số hợp đồng</th><th>Khách hàng</th><th>Xe</th>
  <th>Ngày HĐ</th><th>Trước thuế</th><th>VAT (%)</th><th>Tiền VAT</th><th>Tổng tiền</th>
</tr>
</thead><tbody>';

$counter = 1;
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    $grand_total += (float)$row['total_amount'];
    $html .= '<tr>
      <td style="text-align:center;">' . $counter++ . '</td>
      <td>' . htmlspecialchars($row['invoice_no']) . '</td>
      <td>' . htmlspecialchars($row['contract_no']) . '</td>
      <td>' . htmlspecialchars($row['customer_name']) . '</td>
      <td>' . htmlspecialchars($row['car_name']) . '</td>
      <td style="text-align:center;">' . date('d/m/Y', strtotime($row['invoice_date'])) . '</td>
      <td style="text-align:right;">' . number_format((float)$row['amount'], 0, ',', '.') . ' ₫</td>
      <td style="text-align:center;">' . rtrim(rtrim(number_format((float)$row['vat_rate'], 2, '.', ''), '0'), '.') . '</td>
      <td style="text-align:right;">' . number_format((float)$row['vat_amount'], 0, ',', '.') . ' ₫</td>
      <td style="text-align:right;"><b>' . number_format((float)$row['total_amount'], 0, ',', '.') . ' ₫</b></td>
    </tr>';
}

$html .= '
<tr>
  <td colspan="9" style="text-align:right; font-weight:bold;">TỔNG CỘNG</td>
  <td style="text-align:right; font-weight:bold;">' . number_format($grand_total, 0, ',', '.') . ' ₫</td>
</tr>
</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
ob_end_clean();
$pdf->Output('danh_sach_hoa_don.pdf', 'I');

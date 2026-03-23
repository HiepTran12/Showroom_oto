<?php
// export_customers_pdf.php

// 1) Không hiển thị warning/deprecated ra trình duyệt (chỉ log)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

// 2) Bật buffer để chặn mọi output ngoài ý muốn
ob_start();

include 'auth_check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../connect.php';

// helper: htmlspecialchars an toàn với NULL
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function fmt_dt($v) {
    if (empty($v)) return '';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y H:i', $ts) : '';
}

// Lấy dữ liệu (chỉ những cột cần dùng)
$sql = "SELECT customer_code, full_name, phone, email, address, notes, created_at
        FROM customers
        ORDER BY created_at DESC";
$result = $conn->query($sql);

// 3) Khởi tạo TCPDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCreator('Showroom');
$pdf->SetAuthor('Showroom');
$pdf->SetTitle('Danh sách khách hàng');
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// 4) Render HTML
$html = '<h2 style="text-align:center">Danh sách khách hàng</h2>';
$html .= '<table border="1" cellpadding="5">
            <thead>
                <tr style="font-weight:bold; background-color:#f0f0f0">
                    <th>Mã KH</th>
                    <th>Họ tên</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Ghi chú</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . h($row['customer_code']) . '</td>
                <td>' . h($row['full_name']) . '</td>
                <td>' . h($row['phone']) . '</td>
                <td>' . h($row['email']) . '</td>
                <td>' . h($row['address']) . '</td>
                <td>' . h($row['notes']) . '</td>
                <td>' . h(fmt_dt($row['created_at'])) . '</td>
              </tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// 5) Xóa sạch buffer trước khi gửi PDF (tránh “Some data has already been output”)
ob_end_clean();

// 6) Xuất PDF
$pdf->Output('danh_sach_khach_hang.pdf', 'I');
exit;

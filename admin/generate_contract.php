<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connect.php';

use Mpdf\Mpdf;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
@$conn->query("SET NAMES utf8mb4");
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* ========= INPUT ========= */
$customer_code  = $_GET['customer_code'] ?? '';
$car_code       = $_GET['car_code'] ?? '';
$appointment_no = $_GET['appointment_no'] ?? null;

if ($customer_code === '' || $car_code === '') {
    die("Thiếu tham số bắt buộc.");
}

/* ========= HELPERS ========= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function vnd($n){ return number_format((float)$n, 0, ',', '.') . ' ₫'; }
function rate($r){ $s = rtrim(rtrim(number_format((float)$r, 2, '.', ''), '0'), '.'); return $s; }

/* ========= 1) KHÁCH HÀNG ========= */
/* CHỈ collate ở phía cột */
$stmt = $conn->prepare("
  SELECT customer_code, full_name, phone, email, address
  FROM customers
  WHERE customer_code COLLATE utf8mb4_unicode_ci = ?
");
$stmt->bind_param("s", $customer_code);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$customer) die("Không tìm thấy khách hàng.");

/* ========= 2) XE ========= */
$stmt = $conn->prepare("
  SELECT car_code, name AS car_name, price
  FROM cars
  WHERE car_code COLLATE utf8mb4_unicode_ci = ?
");
$stmt->bind_param("s", $car_code);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$car) die("Không tìm thấy thông tin xe.");

/* ========= 3) TÌM HÓA ĐƠN GẦN NHẤT CHO CẶP KH–XE =========
   - COLLATE chỉ ở cột (không áp vào ?)
   - COLLATE cả hai cột khi JOIN giữa 2 bảng có collation khác nhau
*/
$sqlInv = "
SELECT 
  i.invoice_no, i.invoice_date, i.amount, i.vat_rate, i.vat_amount, i.total_amount,
  sc.contract_no, sc.contract_date, sc.contract_file
FROM invoices i
JOIN sales_contracts sc 
  ON sc.contract_no COLLATE utf8mb4_unicode_ci = i.contract_no COLLATE utf8mb4_unicode_ci
WHERE sc.customer_code COLLATE utf8mb4_unicode_ci = ?
  AND sc.car_code      COLLATE utf8mb4_unicode_ci = ?
ORDER BY i.invoice_date DESC, i.invoice_no DESC
LIMIT 1
";
$stmt = $conn->prepare($sqlInv);
if (!$stmt) die('Prepare failed (inv): '.$conn->error);
$stmt->bind_param("ss", $customer_code, $car_code);
$stmt->execute();
$invRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ========= 4) REUSE contract nếu đã có hóa đơn; không INSERT ========= */
$creating_new_contract = false;

if ($invRow) {
    $contract_no   = $invRow['contract_no'];
    $contract_date = $invRow['contract_date'] ?: date('Y-m-d');

    $unit_price   = (float)$invRow['amount'];
    $vat_rate     = (float)$invRow['vat_rate'];
    $vat_amount   = (float)$invRow['vat_amount'];
    $total_amount = (float)$invRow['total_amount'];

    $invoice_no   = $invRow['invoice_no'];
    $invoice_date = $invRow['invoice_date'];

    // Nếu file hợp đồng đã có & tồn tại -> mở luôn
    if (!empty($invRow['contract_file'])) {
        $abs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $invRow['contract_file'];
        if (is_file($abs)) {
            header("Location: " . $invRow['contract_file']);
            exit;
        }
    }
} else {
    /* ========= 5) CHƯA CÓ HÓA ĐƠN -> tạo hợp đồng mới theo giá xe ========= */
    $creating_new_contract = true;

    $contract_no   = "CT" . date('YmdHis');
    $contract_date = date('Y-m-d');

    $unit_price   = (float)$car['price'];
    $vat_rate     = 10.00;
    $vat_amount   = round($unit_price * $vat_rate / 100, 2);
    $total_amount = round($unit_price + $vat_amount, 2);

    $invoice_no   = null;
    $invoice_date = null;
}


// ... giữ nguyên các phần ở trên (input, truy vấn KH/xe/hóa đơn, biến $contract_no, $unit_price, $vat_rate,...)

require_once __DIR__ . '/../vendor/autoload.php'; // nếu đã cài tcpdf qua composer: tecnickcom/tcpdf
use TCPDF;

// ===== 6) RENDER PDF với TCPDF =====
ob_start(); // chặn mọi output lẻ tẻ

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetCreator('Showroom');
$pdf->SetAuthor('Showroom');
$pdf->SetTitle('Hợp đồng mua bán xe ô tô');

// Lề & font Unicode
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// ===== HTML hợp đồng (giữ nguyên html bạn đã tạo) =====
$nowVN          = date('d/m/Y');
$customer_name  = h($customer['full_name']);
$customer_phone = h($customer['phone'] ?? '');
$customer_email = h($customer['email'] ?? '');
$customer_addr  = h($customer['address'] ?? '');
$car_name       = h($car['car_name']);
$car_code_disp  = h($car['car_code']);

$invoice_block = $invoice_no
  ? "<tr><td style='width:35%'>Số hóa đơn</td><td><b>".h($invoice_no)."</b></td></tr>
     <tr><td>Ngày hóa đơn</td><td>".($invoice_date ? date('d/m/Y', strtotime($invoice_date)) : '')."</td></tr>"
  : "<tr><td colspan='2'><i>Chưa phát hành hóa đơn tại thời điểm ký hợp đồng</i></td></tr>";

$html = "
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
  h1,h3 { margin: 6px 0; }
  .tbl { width: 100%; border-collapse: collapse; }
  .tbl td, .tbl th { border: 1px solid #888; padding: 6px; vertical-align: top; }
  .tbl-nb td { padding: 4px 6px; }
  .muted { color:#666; }
</style>

<h1 style='text-align:center;'>HỢP ĐỒNG MUA BÁN XE Ô TÔ</h1>
<p style='text-align:center' class='muted'>Số: <b>{$contract_no}</b> &nbsp;&nbsp;|&nbsp;&nbsp; Ngày lập: <b>{$nowVN}</b></p>

<h3>I. THÔNG TIN CÁC BÊN</h3>
<table class='tbl'>
  <tr>
    <th style='width:50%'>Bên Bán (Showroom)</th>
    <th>Bên Mua (Khách hàng)</th>
  </tr>
  <tr>
    <td>
      <b>LuxuryCars Showroom</b><br/>
      Địa chỉ: Tân Thạnh, Thanh Bình, Đồng Tháp<br/>
      Điện thoại: 0901 234 567 &nbsp;–&nbsp; Email: kp@gmail.com<br/>
      Đại diện: Ông/Bà ............................................
    </td>
    <td>
      Họ tên: <b>{$customer_name}</b><br/>
      Điện thoại: {$customer_phone}<br/>
      Email: {$customer_email}<br/>
      Địa chỉ: {$customer_addr}
    </td>
  </tr>
</table>

<h3>II. ĐỐI TƯỢNG HỢP ĐỒNG</h3>
<table class='tbl'>
  <tr><td style='width:35%'>Mẫu xe</td><td><b>{$car_name}</b></td></tr>
  <tr><td>Mã xe</td><td>{$car_code_disp}</td></tr>
  <tr><td>Số lượng</td><td>1</td></tr>
</table>

<h3>III. GIÁ TRỊ HỢP ĐỒNG (theo hóa đơn)</h3>
<table class='tbl'>
  <tr><td style='width:35%'>Giá trước thuế (Amount)</td><td><b>".vnd($unit_price)."</b></td></tr>
  <tr><td>Thuế VAT (%)</td><td>".rate($vat_rate)." %</td></tr>
  <tr><td>Tiền VAT (Vat amount)</td><td>".vnd($vat_amount)."</td></tr>
  <tr><td><b>Tổng thanh toán (Total amount)</b></td><td><b>".vnd($total_amount)."</b></td></tr>
  {$invoice_block}
</table>
<p class='muted'>* Nếu đã có hóa đơn, số liệu lấy trực tiếp từ <b>invoices</b>. Nếu chưa có, dùng giá niêm yết từ <b>cars</b> với VAT mặc định 10%.</p>

<h3>IV. PHƯƠNG THỨC VÀ TIẾN ĐỘ THANH TOÁN</h3>
<ol>
  <li>Thanh toán bằng tiền mặt/chuyển khoản/thẻ theo thỏa thuận. Tài khoản nhận: <i>AutoChain Showroom – Vietcombank – 0123456789</i>.</li>
  <li>Đặt cọc 20% khi ký; phần còn lại thanh toán trước khi nhận xe.</li>
  <li>Chậm thanh toán > 07 ngày chịu lãi chậm theo lãi suất ngân hàng tại thời điểm phát sinh.</li>
</ol>

<h3>V. BÀN GIAO – HỒ SƠ – BẢO HÀNH</h3>
<ul>
  <li>Bàn giao trong 03–07 ngày làm việc sau khi nhận đủ tiền.</li>
  <li>Hồ sơ: Hóa đơn GTGT, phiếu xuất xưởng, sổ bảo hành, hướng dẫn sử dụng, biên bản bàn giao.</li>
  <li>Bảo hành 03 năm hoặc 100.000 km (tùy điều kiện nào đến trước) theo chính sách hãng.</li>
</ul>

<h3>VI. ĐIỀU KHOẢN CHUNG</h3>
<p>Hợp đồng có hiệu lực từ ngày ký. Mọi sửa đổi phải lập phụ lục có chữ ký hai bên. Hợp đồng lập 02 bản, mỗi bên giữ 01 bản.</p>

<br/><br/>
<table class='tbl-nb' style='width:100%'>
  <tr>
    <td style='width:50%; text-align:center'>
      <b>ĐẠI DIỆN BÊN BÁN</b><br/><span class='muted'>(Ký, ghi rõ họ tên)</span><br/><br/><br/><br/>..............................
    </td>
    <td style='text-align:center'>
      <b>ĐẠI DIỆN BÊN MUA</b><br/><span class='muted'>(Ký, ghi rõ họ tên)</span><br/><br/><br/><br/>". h($customer['full_name']) ."
    </td>
  </tr>
</table>
";

// Ghi HTML vào TCPDF
$pdf->writeHTML($html, true, false, true, false, '');

// ===== 7) LƯU FILE =====
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/uploads/contracts/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

$filename     = "contract_{$contract_no}.pdf";
$fullPath     = $uploadDir . $filename;
$relativePath = '/uploads/contracts/' . $filename;

// Xóa buffer trước khi ghi file để tránh "Some data has already been output"
ob_end_clean();
$pdf->Output($fullPath, 'F');

// ===== 8) GẮN FILE VÀO HỢP ĐỒNG (y như cũ) =====
if ($invRow) {
    $stmt = $conn->prepare("
        UPDATE sales_contracts
        SET contract_file = COALESCE(NULLIF(contract_file,''), ?)
        WHERE contract_no = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $relativePath, $contract_no);
    $stmt->execute();
    $stmt->close();
} else {
    $status   = 'SIGNED';
    $quantity = 1;

    $stmt = $conn->prepare("
      INSERT INTO sales_contracts
      (contract_no, contract_date, customer_code, car_code, appointment_no, quantity, unit_price, vat_rate, vat_amount, total_amount, status, contract_file, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
      "sssssiidddss",
      $contract_no, $contract_date, $customer_code, $car_code, $appointment_no,
      $quantity, $unit_price, $vat_rate, $vat_amount, $total_amount, $status, $relativePath
    );
    $stmt->execute();
    $stmt->close();
}

// ===== 9) MỞ FILE =====
header("Location: " . $relativePath);
exit;

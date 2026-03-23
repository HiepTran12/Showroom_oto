<?php
// open_contract.php
// Mục tiêu: mở file hợp đồng PDF nếu đã có; nếu chưa có -> chuyển sang generate_contract.php để tạo.

declare(strict_types=1);

include '../connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/* ---------- Input ---------- */
$customer_code  = $_GET['customer_code'] ?? '';
$car_code       = $_GET['car_code'] ?? '';            // có thể rỗng
$appointment_no = $_GET['appointment_no'] ?? null;    // tuỳ chọn, truyền tiếp nếu có

if ($customer_code === '') {
    http_response_code(400);
    exit('Thiếu mã khách hàng.');
}

/* ---------- Helpers ---------- */
function abs_path_from_relative(string $rel): string {
    // đảm bảo có leading slash
    if ($rel !== '' && $rel[0] !== '/') $rel = '/'.$rel;
    return rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $rel;
}
function go(string $url): void {
    header('Location: '.$url);
    exit;
}

/* ---------- 1) Xác thực khách hàng tồn tại ---------- */
$stmt = $conn->prepare("SELECT 1 FROM customers WHERE customer_code = ?");
$stmt->bind_param("s", $customer_code);
$stmt->execute();
$exists = $stmt->get_result()->fetch_row();
$stmt->close();

if (!$exists) {
    http_response_code(404);
    exit('Không tìm thấy khách hàng.');
}

/* ---------- 2) Tìm hợp đồng phù hợp để mở ---------- */
/* Ưu tiên: KH + xe (nếu có) -> KH bất kỳ xe
   Chỉ chọn bản ghi có contract_file khác rỗng (vì mục tiêu là mở file)
   Nếu không có file (null/rỗng) thì sau bước này sẽ rơi về luồng tạo mới. */

$contract = null;

if ($car_code !== '') {
    $stmt = $conn->prepare("
        SELECT contract_no, car_code, contract_file
        FROM sales_contracts
        WHERE customer_code = ? AND car_code = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("ss", $customer_code, $car_code);
    $stmt->execute();
    $contract = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$contract) {
    // Lấy hợp đồng gần nhất của KH (bất kể xe nào)
    $stmt = $conn->prepare("
        SELECT contract_no, car_code, contract_file
        FROM sales_contracts
        WHERE customer_code = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $customer_code);
    $stmt->execute();
    $contract = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // nếu chưa có car_code từ input thì mượn car_code của hợp đồng gần nhất
    if ($contract && $car_code === '') {
        $car_code = (string)$contract['car_code'];
    }
}

/* ---------- 3) Nếu có file hợp đồng và file còn tồn tại -> mở luôn ---------- */
if ($contract && !empty($contract['contract_file'])) {
    $relative = $contract['contract_file'];
    $absolute = abs_path_from_relative($relative);

    if (is_file($absolute)) {
        go($relative); // mở PDF
    }
    // nếu DB có đường dẫn nhưng file đã bị xóa -> rơi qua nhánh tạo mới bên dưới
}

/* ---------- 4) Không mở được file sẵn có -> đi tạo mới ----------
   Cần có car_code để tạo hợp đồng; nếu chưa có thì báo lỗi rõ ràng */
if ($car_code !== '') {
    // chuyển tiếp sang trang sinh hợp đồng, truyền cả appointment_no nếu có
    $url = 'generate_contract.php?customer_code=' . urlencode($customer_code)
         . '&car_code=' . urlencode($car_code);
    if ($appointment_no !== null && $appointment_no !== '') {
        $url .= '&appointment_no=' . urlencode($appointment_no);
    }
    go($url);
}

http_response_code(400);
exit('Không đủ thông tin xe để tạo hợp đồng. Vui lòng chọn car_code.');

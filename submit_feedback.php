<?php
// ===== KẾT NỐI DB & PHIÊN =====
require_once 'connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Chưa đăng nhập -> quay về khu vực feedback
if (!isset($_SESSION['username'])) {
    header("Location: index.php?fb=notlogin#feedback");
    exit();
}

// Chỉ nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php#feedback");
    exit();
}

// ==== INPUT ====
$comment  = isset($_POST['comment']) ? trim($_POST['comment']) : trim($_POST['feedback_content'] ?? '');
$rating   = isset($_POST['rating']) ? (($_POST['rating'] === '' ? null : (int)$_POST['rating'])) : null; // 1..5 hoặc null
$car_code = isset($_POST['car_code']) ? trim($_POST['car_code']) : null; // có thể null
$username = $_SESSION['username'];

// === VALIDATE CƠ BẢN ===
if ($comment === '') {
    header("Location: index.php?fb=empty#feedback"); exit();
}
if ($rating !== null && ($rating < 1 || $rating > 5)) {
    header("Location: index.php?fb=bad_rating#feedback"); exit();
}

// ====== HÀM HỖ TRỢ: đảm bảo bảng auto_sequences tồn tại & sinh mã FB-NNNN ======
function ensure_sequences_table(mysqli $conn) {
    $sql = "CREATE TABLE IF NOT EXISTS auto_sequences (
              seq_key   VARCHAR(50) PRIMARY KEY,
              last_no   INT NOT NULL DEFAULT 0,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB";
    $conn->query($sql);
}

/**
 * Sinh code với key (ví dụ 'FB') -> FB-NNNN, an toàn đồng thời.
 * Dùng mẹo: ON DUPLICATE KEY UPDATE last_no = LAST_INSERT_ID(last_no+1)
 * rồi SELECT LAST_INSERT_ID() để lấy số mới.
 */
function generate_code(mysqli $conn, string $key, string $prefix, int $digits = 4): string {
    ensure_sequences_table($conn);

    // tăng số và lấy ngay số mới
    $stmt = $conn->prepare("
        INSERT INTO auto_sequences (seq_key, last_no) VALUES (?, 1)
        ON DUPLICATE KEY UPDATE
            last_no = LAST_INSERT_ID(last_no + 1),
            updated_at = NOW()
    ");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->close();

    $res = $conn->query("SELECT LAST_INSERT_ID() AS n");
    $row = $res->fetch_assoc();
    $n   = (int)$row['n'];

    return $prefix . str_pad((string)$n, $digits, '0', STR_PAD_LEFT);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

try {
    // Lấy customer_code theo username
    $stmt = $conn->prepare("SELECT customer_code FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u || empty($u['customer_code'])) {
        header("Location: index.php?fb=nocus#feedback"); exit();
    }
    $customer_code = $u['customer_code'];

    // Nếu có car_code thì kiểm tra tồn tại (tùy chọn)
    if ($car_code !== null && $car_code !== '') {
        $chk = $conn->prepare("SELECT 1 FROM cars WHERE car_code = ? LIMIT 1");
        $chk->bind_param("s", $car_code);
        $chk->execute();
        $ok = $chk->get_result()->num_rows === 1;
        $chk->close();
        if (!$ok) { header("Location: index.php?fb=car_not_found#feedback"); exit(); }
    } else {
        $car_code = null; // để DB nhận NULL
    }

    // Sinh mã FB-NNNN theo chuẩn tài liệu
    $feedback_no = generate_code($conn, 'FB', 'FB-'); // <-- có dấu gạch ngang

    // Chèn bản ghi feedback
    $stmt = $conn->prepare("
        INSERT INTO customer_feedback (feedback_no, customer_code, car_code, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    // types: s (feedback_no), s (customer_code), s (car_code nullable), i (rating nullable), s (comment)
    $stmt->bind_param("sssis", $feedback_no, $customer_code, $car_code, $rating, $comment);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?fb=success#feedback");
    exit();

} catch (Throwable $e) {
    // bạn có thể log $e->getMessage() để debug
    header("Location: index.php?fb=fail#feedback");
    exit();
} finally {
    $conn->close();
}

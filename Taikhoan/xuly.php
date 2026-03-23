<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sửa đường dẫn require_once để phù hợp với cấu trúc thư mục của bạn
require_once __DIR__ . "/../connect.php";

$message = "";
$action = $_POST['action'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // XỬ LÝ ĐĂNG NHẬP
    if ($action === "login") {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];

        if (empty($username) || empty($password)) {
            $message = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
        } else {
            $stmt = $conn->prepare("SELECT u.user_code, u.password_hash, u.role, u.customer_code, u.full_name 
                                   FROM users u 
                                   WHERE u.username = ? AND u.is_active = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (!password_verify($password, $user["password_hash"])) {
                    $message = "Mật khẩu không đúng.";
                } else {
                    // Đăng nhập thành công
                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_code"] = $user["user_code"];
                    $_SESSION["username"] = $username;
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["customer_code"] = $user["customer_code"];

                    $redirect = $_POST['redirect'] ?? '';
                    if ($user["role"] === "ADMIN") {
                        header("Location: ../admin/index.php");
                    } elseif ($redirect === 'contact') {
                        header("Location: ../contact.php");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit();
                }
            } else {
                $message = "Tài khoản không tồn tại hoặc chưa được kích hoạt.";
            }
            $stmt->close();
        }
    }
}
?>
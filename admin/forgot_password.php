<?php 
session_start();
include 'header.php';
require_once '../connect.php';

// Xử lý logic nên đặt ở đầu file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ']);
        exit;
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email không tồn tại trong hệ thống']);
        exit;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with token
    $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
    $update->bind_param("sss", $token, $expires, $email);
    $update->execute();
    
    // Send email (in a real application)
    $resetLink = "https://yourdomain.com/reset_password.php?token=$token";
    
    // For demo purposes, we'll just return the link
    echo json_encode([
        'status' => 'success',
        'message' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.'
    ]);
    
    $stmt->close();
    $conn->close();
    exit;
}
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<div class="auth-container" style="max-width: 500px;">
    <form id="forgotForm">
        <h1>Quên mật khẩu</h1>
        <p>Nhập email đăng ký tài khoản để nhận liên kết đặt lại mật khẩu</p>
        <input type="email" name="email" placeholder="Email đăng ký" required>
        <button type="submit">Gửi yêu cầu</button> <br>
        <p>Nếu gặp khó khăn, vui lòng liên hệ bộ phận hỗ trợ khách hàng: 0902777999</p>
    </form>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Đang xử lý...',
        text: 'Vui lòng chờ trong giây lát',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('forgot_password.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.status === 'success' ? 'Thành công' : 'Lỗi',
            text: data.message
        }).then(() => {
            if (data.status === 'success') {
                document.getElementById('forgotForm').reset();
            }
        });
    })
    .catch(() => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Không thể kết nối tới máy chủ. Vui lòng thử lại.'
        });
    });
});
</script>

<?php include 'footer.php'; ?>
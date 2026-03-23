<?php
// 1) Bảo vệ + kết nối DB (KHÔNG xuất HTML ở phần này)
require_once 'auth_check.php';
require_once '../connect.php';

// Bật debug (tạm thời)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== Helpers =====
function redirect_msg($m) {
    header("Location: manage_users.php?msg=" . urlencode($m));
    exit;
}

function nextCode(mysqli $conn, string $table, string $pkField, string $prefix, int $padLen): string {
    // Lấy phần số lớn nhất sau prefix
    // VD UC0001 -> 1, UC0009 -> 9
    $sql = "SELECT MAX(CAST(SUBSTRING($pkField, LENGTH(?) + 1) AS UNSIGNED)) AS max_no
            FROM $table
            WHERE $pkField LIKE CONCAT(?, '%')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $prefix, $prefix);
    $stmt->execute();
    $res = $stmt->get_result();
    $max = 0;
    if ($row = $res->fetch_assoc()) $max = (int)($row['max_no'] ?? 0);
    $stmt->close();

    return $prefix . str_pad((string)($max + 1), $padLen, '0', STR_PAD_LEFT);
}

// ===== Handle POST (LÀM TRƯỚC KHI XUẤT BẤT KỲ HTML NÀO) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1) Tạo tài khoản
    if ($action === 'create_user') {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role      = strtoupper(trim($_POST['role'] ?? 'CUSTOMER'));
        $password  = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            redirect_msg("Vui lòng nhập đủ username, email, mật khẩu.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect_msg("Email không hợp lệ.");
        }
        if (!in_array($role, ['ADMIN','CUSTOMER'], true)) {
            redirect_msg("Vai trò không hợp lệ.");
        }

        // trùng username/email?
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) { $stmt->close(); redirect_msg("Username hoặc Email đã tồn tại."); }
        $stmt->close();

        $user_code = nextCode($conn, "users", "user_code", "UC", 4);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $is_active = 1;
        $customer_code = null;

        $stmt = $conn->prepare("INSERT INTO users
            (user_code, username, email, password_hash, full_name, role, customer_code, is_active)
            VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssi",
            $user_code, $username, $email, $hash, $full_name, $role, $customer_code, $is_active
        );
        if ($stmt->execute()) {
            $stmt->close();
            redirect_msg("Tạo tài khoản $username thành công (mã $user_code).");
        } else {
            $err = $stmt->error; $stmt->close();
            redirect_msg("Lỗi khi tạo tài khoản: $err");
        }
    }

    // 2) Đổi vai trò
    if ($action === 'change_role') {
        $user_code = $_POST['user_code'] ?? '';
        $role = strtoupper(trim($_POST['role'] ?? ''));
        if ($user_code === '' || !in_array($role, ['ADMIN','CUSTOMER'], true)) {
            redirect_msg("Dữ liệu không hợp lệ.");
        }
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE user_code=?");
        $stmt->bind_param("ss", $role, $user_code);
        if ($stmt->execute()) { $stmt->close(); redirect_msg("Đã đổi vai trò."); }
        $err = $stmt->error; $stmt->close(); redirect_msg("Lỗi: $err");
    }

    // 3) Duyệt/Khoá
    if ($action === 'toggle_active') {
        $user_code = $_POST['user_code'] ?? '';
        $value = (int)($_POST['value'] ?? 0);
        if ($user_code === '' || !in_array($value, [0,1], true)) redirect_msg("Dữ liệu không hợp lệ.");
        $stmt = $conn->prepare("UPDATE users SET is_active=? WHERE user_code=?");
        $stmt->bind_param("is", $value, $user_code);
        if ($stmt->execute()) { $stmt->close(); redirect_msg($value ? "Đã duyệt tài khoản." : "Đã khoá tài khoản."); }
        $err = $stmt->error; $stmt->close(); redirect_msg("Lỗi: $err");
    }



    // 5) Xoá
    if ($action === 'delete_user') {
        $user_code = $_POST['user_code'] ?? '';
        if ($user_code === '') redirect_msg("Thiếu user_code.");
        $stmt = $conn->prepare("DELETE FROM users WHERE user_code=?");
        $stmt->bind_param("s", $user_code);
        if ($stmt->execute()) { $stmt->close(); redirect_msg("Đã xoá tài khoản."); }
        $err = $stmt->error; $stmt->close(); redirect_msg("Lỗi: $err");
    }

    redirect_msg("Action không hỗ trợ.");
}

// ===== Load list (không redirect nữa) =====
$kw = trim($_GET['q'] ?? '');
$sql = "SELECT user_code, username, email, full_name, role, customer_code, is_active, created_at FROM users";
$params = []; $types = "";
if ($kw !== "") {
    $sql .= " WHERE username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')";
    $params = [$kw, $kw]; $types = "ss";
}
$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$msg = $_GET['msg'] ?? '';
?>

<?php
// 2) SAU KHI XỬ LÝ XONG HẾT MỚI XUẤT HTML
//    Bây giờ mới include giao diện (header đã tạo HTML nên đặt ở đây là an toàn)
include 'header.php';
?>

<style>
.wrap{max-width:1100px;margin:24px auto;padding:0 16px}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);padding:16px}
h1{margin:0 0 12px;font-size:22px}
.toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
input,select,button{padding:8px 10px;border:1px solid #ddd;border-radius:10px;font-size:14px}
table{width:100%;border-collapse:collapse;margin-top:10px;font-size:14px}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
th{background:#fafafa}
.tag{padding:2px 8px;border-radius:999px;font-size:12px;display:inline-block}
.tag.admin{background:#eef4ff;color:#1e44a8;border:1px solid #dbe6ff}
.tag.customer{background:#f4f7ff;color:#3b4a75;border:1px solid #e7ecff}
.tag.active{background:#e8fff3;color:#0d7a41;border:1px solid #c9f7df}
.tag.lock{background:#fff3f1;color:#b8422c;border:1px solid #ffe2dc}
.actions form{display:inline}
.notice{margin:10px 0;padding:10px;border-radius:10px;background:#f0f7ff;border:1px solid #d6e8ff;color:#194b9b}
</style>

<div class="wrap">
    <div class="card">
        <h1>Quản lý tài khoản</h1>

        <?php if ($msg): ?>
            <div class="notice"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="toolbar">
            <form method="get" style="display:flex;gap:8px;align-items:center">
                <input type="text" name="q" placeholder="Tìm username hoặc email..." value="<?=htmlspecialchars($kw)?>">
                <button type="submit">Tìm</button>
                <?php if ($kw !== ''): ?>
                    <a href="manage_users.php"><button type="button">Xoá lọc</button></a>
                <?php endif; ?>
            </form>
        </div>

        <details class="card" style="margin:8px 0">
            <summary><strong>➕ Tạo tài khoản mới</strong></summary>
            <form method="post" style="margin-top:12px">
                <input type="hidden" name="action" value="create_user">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label>Username</label><br>
                        <input name="username" type="text" required>
                    </div>
                    <div>
                        <label>Email</label><br>
                        <input name="email" type="email" required>
                    </div>
                    <div>
                        <label>Họ tên</label><br>
                        <input name="full_name" type="text">
                    </div>
                    <div>
                        <label>Vai trò</label><br>
                        <select name="role">
                            <option value="CUSTOMER">CUSTOMER</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>
                    <div>
                        <label>Mật khẩu</label><br>
                        <input name="password" type="password" required>
                    </div>
                </div>
                <div style="margin-top:10px">
                    <button type="submit">Tạo tài khoản</button>
                    <small style="opacity:.7;margin-left:8px">Mật khẩu được mã hoá bằng <code>password_hash</code>.</small>
                </div>
            </form>
        </details>

        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users): foreach ($users as $u): ?>
                <tr>
                    <td><?=htmlspecialchars($u['user_code'])?></td>
                    <td><?=htmlspecialchars($u['username'])?></td>
                    <td><?=htmlspecialchars($u['email'])?></td>
                    <td><?=htmlspecialchars($u['full_name'] ?? '')?></td>
                    <td><span class="tag <?=strtolower($u['role'])?>"><?=htmlspecialchars($u['role'])?></span></td>
                    <td>
                        <?php if ((int)$u['is_active'] === 1): ?>
                            <span class="tag active">Đã duyệt</span>
                        <?php else: ?>
                            <span class="tag lock">Khoá</span>
                        <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($u['created_at'])?></td>
                    <td class="actions">
                        <?php if ($u['user_code'] !== $_SESSION['user_code']): ?>
                            <!-- Toggle -->
                            <form method="post">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="user_code" value="<?=htmlspecialchars($u['user_code'])?>">
                                <input type="hidden" name="value" value="<?= (int)$u['is_active'] === 1 ? 0 : 1 ?>">
                                <button type="submit"><?= (int)$u['is_active'] === 1 ? 'Khoá' : 'Duyệt' ?></button>
                            </form>

                            <!-- Change role -->
                            <form method="post">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_code" value="<?=htmlspecialchars($u['user_code'])?>">
                                <input type="hidden" name="role" value="<?= $u['role']==='ADMIN' ? 'CUSTOMER' : 'ADMIN' ?>">
                                <button type="submit"><?= $u['role']==='ADMIN' ? 'Đặt CUSTOMER' : 'Đặt ADMIN' ?></button>
                            </form>

                            <!-- Delete -->
                            <form method="post" onsubmit="return confirm('Xoá tài khoản này?');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_code" value="<?=htmlspecialchars($u['user_code'])?>">
                                <button type="submit">Xoá</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#999;font-size:13px">(Không thể thao tác chính mình)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" style="text-align:center;color:#888">Không có dữ liệu</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

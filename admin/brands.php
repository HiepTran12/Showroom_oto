<?php
// admin/brands.php
include 'auth_check.php';
require_once '../connect.php';
require_once __DIR__ . '/lib_upload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// ==================== HÀM TIỆN ÍCH ====================
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function make_brand_code_from_name(string $name): string {
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    if (!$ascii) $ascii = $name;
    $ascii = strtoupper($ascii);
    $ascii = preg_replace('/[^A-Z0-9]/', '', $ascii);
    $code  = substr($ascii, 0, 3);
    return $code !== '' ? $code : 'XXX';
}

// ==================== XỬ LÝ SUBMIT ====================

// Thêm hãng (brand_code tự sinh từ name)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($name === '') {
        $_SESSION['brand_err'] = 'Vui lòng nhập Tên hãng.';
        header('Location: brands.php'); exit;
    }

    $brand_code = make_brand_code_from_name($name);

    // Check trùng mã
    $chk = $conn->prepare("SELECT 1 FROM brands WHERE brand_code=?");
    $chk->bind_param("s", $brand_code);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) {
        $_SESSION['brand_err'] = "Mã hãng tự sinh ($brand_code) đã tồn tại. Hãy điều chỉnh tên hãng để sinh mã khác.";
        header('Location: brands.php'); exit;
    }
    $chk->close();

    try {
        $logo_code = null;
        if (!empty($_FILES['logo']['name'])) {
            [$logo_code, $_] = upload_and_register_file($conn, $_FILES['logo'], 'brands', $brand_code);
        }

        $st = $conn->prepare("INSERT INTO brands (brand_code, name, country, logo_file_code, created_at)
                              VALUES (?,?,?,?, NOW())");
        $st->bind_param("ssss", $brand_code, $name, $country, $logo_code);
        $st->execute(); $st->close();

        $_SESSION['brand_ok'] = "Đã thêm: $brand_code - $name";
    } catch (Throwable $e) {
        $_SESSION['brand_err'] = 'Lỗi thêm hãng: '.$e->getMessage();
    }
    header('Location: brands.php'); exit;
}

// Cập nhật tổng: tên + quốc gia + (logo nếu có) + có thể đổi brand_code khi tên đổi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_all') {
    $oldCode = strtoupper(substr(trim($_POST['bcode'] ?? ''), 0, 10));
    $name    = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($oldCode === '' || $name === '') {
        $_SESSION['brand_err'] = 'Thiếu mã hãng hoặc tên hãng.';
        header('Location: brands.php'); exit;
    }

    // Mã mới sinh theo tên mới
    $newCode = make_brand_code_from_name($name);

    try {
        $conn->begin_transaction();

        // Nếu đổi mã -> kiểm tra trùng
        if ($newCode !== $oldCode) {
            $chk = $conn->prepare("SELECT 1 FROM brands WHERE brand_code=?");
            $chk->bind_param("s", $newCode);
            $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                throw new RuntimeException("Mã hãng mới ($newCode) đã tồn tại. Hãy điều chỉnh tên để sinh mã khác.");
            }
            $chk->close();

            // Đổi mã cho tất cả xe thuộc hãng
            $uCars = $conn->prepare("UPDATE cars SET brand_code=? WHERE brand_code=?");
            $uCars->bind_param("ss", $newCode, $oldCode);
            $uCars->execute(); $uCars->close();

            // Cập nhật brand (đổi brand_code + các field khác)
            $uBrand = $conn->prepare("
                UPDATE brands
                SET brand_code=?, name=?, country=?, created_at = NOW()
                WHERE brand_code=?");
            $uBrand->bind_param("ssss", $newCode, $name, $country, $oldCode);
            $uBrand->execute(); $uBrand->close();

            // Nếu có logo mới thì lưu với owner là mã MỚI
            if (!empty($_FILES['logo']['name'])) {
                [$logo_code, $_] = upload_and_register_file($conn, $_FILES['logo'], 'brands', $newCode);
                if ($logo_code) {
                    $st2 = $conn->prepare("UPDATE brands SET logo_file_code=? WHERE brand_code=?");
                    $st2->bind_param("ss", $logo_code, $newCode);
                    $st2->execute(); $st2->close();
                }
            }
        } else {
            // Không đổi mã -> cập nhật thông tin + thời gian
            $uBrand = $conn->prepare("
                UPDATE brands
                SET name=?, country=?, created_at = NOW()
                WHERE brand_code=?");
            $uBrand->bind_param("sss", $name, $country, $oldCode);
            $uBrand->execute(); $uBrand->close();

            if (!empty($_FILES['logo']['name'])) {
                [$logo_code, $_] = upload_and_register_file($conn, $_FILES['logo'], 'brands', $oldCode);
                if ($logo_code) {
                    $st2 = $conn->prepare("UPDATE brands SET logo_file_code=? WHERE brand_code=?");
                    $st2->bind_param("ss", $logo_code, $oldCode);
                    $st2->execute(); $st2->close();
                }
            }
        }

        $conn->commit();

        if ($newCode !== $oldCode) {
            $_SESSION['brand_ok'] = "Đã cập nhật hãng: $oldCode → $newCode";
        } else {
            $_SESSION['brand_ok'] = "Đã cập nhật hãng $oldCode";
        }
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['brand_err'] = 'Lỗi cập nhật: ' . $e->getMessage();
    }

    header('Location: brands.php'); exit;
}


// Xoá hãng (và xe thuộc hãng)
if (isset($_GET['delete'])) {
    $bcode = strtoupper(substr(trim($_GET['delete']), 0, 10));
    try {
        if ($bcode === '') throw new RuntimeException('Thiếu mã hãng.');

        $stmt1 = $conn->prepare("DELETE FROM cars WHERE brand_code=?");
        $stmt1->bind_param("s", $bcode);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM brands WHERE brand_code=?");
        $stmt2->bind_param("s", $bcode);
        $stmt2->execute();
        $stmt2->close();

        $_SESSION['brand_ok'] = "Đã xóa hãng $bcode và toàn bộ xe liên quan.";
    } catch (Throwable $e) {
        $_SESSION['brand_err'] = 'Lỗi xóa hãng: '.$e->getMessage();
    }
    header('Location: brands.php'); exit;
}

// ==================== LẤY DANH SÁCH ====================
$rows = [];
$q = "SELECT b.brand_code, b.name, b.country, b.created_at, f.storage_path AS logo
      FROM brands b
      LEFT JOIN files f ON f.file_code = b.logo_file_code
      ORDER BY b.name";
$rs = $conn->query($q);
if ($rs) { $rows = $rs->fetch_all(MYSQLI_ASSOC); $rs->free(); }

$ok  = $_SESSION['brand_ok']  ?? '';
$err = $_SESSION['brand_err'] ?? '';
unset($_SESSION['brand_ok'], $_SESSION['brand_err']);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý Thương hiệu</title>
<link rel="stylesheet" href="admin.css">
<style>
.page{max-width:1100px;margin:24px auto;padding:0 16px}
.alert{margin:10px 0;padding:12px 14px;border-radius:8px}
.alert.ok{background:#e9f9ec;color:#198754}
.alert.err{background:#fdecea;color:#c0392b}
.table{width:100%;border-collapse:collapse;margin-top:12px}
.table th,.table td{border:1px solid #eee;padding:10px;vertical-align:top}
.table th{background:#f8fafc;text-align:left}
.logo-cell img{height:40px;object-fit:contain;background:#fff}
.small{color:#777;font-size:12px}
.btn{padding:8px 12px;border-radius:8px;border:0;cursor:pointer}
.btn-primary{background:#0d6efd;color:#fff}
.btn-secondary{background:#6c757d;color:#fff}
.btn-danger{background:#dc3545;color:#fff}
.form{background:#fff;border:1px dashed #ddd;border-radius:12px;padding:16px;margin:14px 0}
.form .row{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.form input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px}
.input-readonly{background:#f4f6f8;color:#666}
.edit-row{display:none;background:#fbfcfe}
.edit-wrap{padding:12px;border-top:1px dashed #e5e7eb}
.edit-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:12px}
@media (max-width: 768px){.edit-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="page">
  <h2>Thương hiệu</h2>

  <?php if ($ok): ?><div class="alert ok"><?=h($ok)?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert err"><?=h($err)?></div><?php endif; ?>

  <!-- Thêm thương hiệu -->
  <form id="brandForm" class="form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <h3>Thêm thương hiệu</h3>
    <div class="row">
      <div>
        <label for="brand_name">Tên hãng</label>
        <input id="brand_name" name="name" required autocomplete="off">
      </div>
      <div>
        <label for="brand_code">Mã hãng (tự sinh)</label>
        <input id="brand_code" class="input-readonly" type="text" readonly
               placeholder="Sẽ tự sinh từ Tên hãng">
      </div>
      <div>
        <label>Quốc gia</label>
        <input name="country" autocomplete="off">
      </div>
      <div>
        <label>Logo</label>
        <input type="file" name="logo" accept="image/*">
        <div class="small">Không bắt buộc; có thể cập nhật sau.</div>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Lưu</button>
  </form>

  <!-- Danh sách -->
  <table class="table">
    <thead>
      <tr><th>Logo</th><th>Mã</th><th>Tên</th><th>Quốc gia</th><th>Ngày tạo</th><th style="width:180px">Thao tác</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): $rid = 'e_'.$r['brand_code']; ?>
      <tr>
        <td class="logo-cell">
          <?php if (!empty($r['logo'])): ?>
            <img src="<?=h($r['logo'])?>" alt="<?=h($r['name'])?>">
          <?php else: ?><span class="small">Chưa có</span><?php endif; ?>
        </td>
        <td><strong><?=h($r['brand_code'])?></strong></td>
        <td><?=h($r['name'])?></td>
        <td><?=h($r['country'])?></td>
        <td><?=h($r['created_at'])?></td>
        <td>
          <button class="btn btn-primary" type="button" onclick="toggleEdit('<?=$rid?>')">Cập nhật</button>
          <a href="brands.php?delete=<?=h($r['brand_code'])?>"
             onclick="return confirm('Xóa hãng <?=h($r['name'])?> và toàn bộ xe liên quan?')">
            <button class="btn btn-danger" type="button">Xóa</button>
          </a>
        </td>
      </tr>
      <!-- Hàng edit ẩn/hiện -->
      <tr id="<?=$rid?>" class="edit-row">
        <td colspan="6">
          <div class="edit-wrap">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_all">
              <input type="hidden" name="bcode" value="<?=h($r['brand_code'])?>">
              <div class="edit-grid">
                <div>
                  <label>Tên hãng</label>
                  <input type="text" name="name" value="<?=h($r['name'])?>" required>
                </div>
                <div>
                  <label>Quốc gia</label>
                  <input type="text" name="country" value="<?=h($r['country'])?>">
                </div>
                <div>
                  <label>Đổi logo (tùy chọn)</label>
                  <input type="file" name="logo" accept="image/*">
                </div>
              </div>
              <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn-primary" type="submit">Lưu thay đổi</button>
                <button class="btn btn-secondary" type="button" onclick="toggleEdit('<?=$rid?>',true)">Đóng</button>
              </div>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="6">Chưa có thương hiệu.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Auto-generate 3 ký tự mã từ tên (UI hiển thị); backend vẫn tự sinh để an toàn -->
<script>
  function toBrandCode3(name) {
    if (!name) return '';
    let ascii = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    let code  = ascii.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3);
    return code || 'XXX';
  }
  (function(){
    const nameInput = document.getElementById('brand_name');
    const codeInput = document.getElementById('brand_code');
    if (!nameInput || !codeInput) return;
    nameInput.addEventListener('input', function(){
      codeInput.value = toBrandCode3(this.value);
    });
  })();

  function toggleEdit(id, closeOnly=false){
    const row = document.getElementById(id);
    if (!row) return;
    row.style.display = (closeOnly ? 'none' : (row.style.display === 'table-row' ? 'none' : 'table-row'));
  }
</script>

<?php include 'footer.php'; ?>
</body>
</html>

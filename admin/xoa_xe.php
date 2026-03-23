<?php
include 'auth_check.php';
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/lib_upload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function rrmdir_if_empty(string $dir): void {
    if (is_dir($dir)) {
        // chỉ xóa nếu trống
        $h = @opendir($dir);
        if ($h) {
            $empty = true;
            while (($f = readdir($h)) !== false) {
                if ($f === '.' || $f === '..') continue;
                $empty = false; break;
            }
            closedir($h);
            if ($empty) @rmdir($dir);
        }
    }
}

$car_code = strtoupper(trim($_GET['code'] ?? ''));
if ($car_code === '' || stripos($car_code, 'CAR-') !== 0) {
    header('Location: xe.php?err=invalid_code'); exit;
}

$files_to_delete = [];   // lưu URL public để xóa file vật lý sau khi commit
$deleted_rows    = 0;

try {
    $conn->begin_transaction();

    // 1) Lấy danh sách file ảnh của xe
    $st = $conn->prepare("
        SELECT f.file_code, f.storage_path
        FROM car_images ci
        JOIN files f ON f.file_code = ci.file_code
        WHERE ci.car_code = ?
    ");
    $st->bind_param("s", $car_code);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $files_to_delete[] = $row; // giữ lại để xóa file vật lý & có thể xóa bản ghi files nếu mồ côi
    }
    $st->close();

    // 2) Xóa liên kết ảnh của xe trong car_images
    $st = $conn->prepare("DELETE FROM car_images WHERE car_code = ?");
    $st->bind_param("s", $car_code);
    $st->execute();
    $st->close();

    // 3) Xóa các bản ghi files đã mồ côi (không còn ai tham chiếu)
    foreach ($files_to_delete as $row) {
        $fc = $row['file_code'];
        $chk = $conn->prepare("SELECT 1 FROM car_images WHERE file_code=? LIMIT 1");
        $chk->bind_param("s", $fc);
        $chk->execute();
        $exists = $chk->get_result()->fetch_row();
        $chk->close();

        if (!$exists) {
            $del = $conn->prepare("DELETE FROM files WHERE file_code=?");
            $del->bind_param("s", $fc);
            $del->execute();
            $del->close();
        }
    }

    // 4) Xóa xe
    $st = $conn->prepare("DELETE FROM cars WHERE car_code = ?");
    $st->bind_param("s", $car_code);
    $st->execute();
    $deleted_rows = $st->affected_rows;
    $st->close();

    $conn->commit();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();

    // 1451: Cannot delete or update a parent row: a foreign key constraint fails
    if ((int)$e->getCode() === 1451) {
        // Gợi ý: soft delete hoặc xóa dữ liệu phụ thuộc trước
        header("Location: xe.php?err=fk_block&code={$car_code}");
        exit;
    }
    // debug khác
    header("Location: xe.php?err=db&msg=" . urlencode($e->getMessage()));
    exit;
}

// 5) Sau khi commit: xóa file vật lý & thư mục xe (nếu trống)
if (!empty($files_to_delete)) {
    foreach ($files_to_delete as $row) {
        $abs = storage_to_abs($row['storage_path']); // từ lib_upload.php
        @unlink($abs);
    }
}
$car_dir = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cars' . DIRECTORY_SEPARATOR . $car_code;
rrmdir_if_empty($car_dir);

header("Location: xe.php?deleted={$deleted_rows}&code={$car_code}");
exit;

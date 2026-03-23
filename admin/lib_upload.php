<?php
// admin/lib_upload.php
// Quy ước: files.storage_path luôn là URL public: <APP_BASE>/uploads/...,
// dùng storage_to_abs() để đổi URL này thành đường dẫn tuyệt đối trên đĩa.

if (!defined('PROJECT_ROOT')) {
    // ví dụ local: C:\xampp\htdocs\showroom_oto
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Tự tính APP_BASE (ví dụ: "/showroom_oto"; nếu chạy ở root site thì rỗng)
if (!defined('APP_BASE')) {
    $script   = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
    // /showroom_oto/admin/some.php -> dirname(dirname(...)) = /showroom_oto
    $app_base = rtrim(dirname(dirname($script)), '/');
    if ($app_base === '/' || $app_base === '.') $app_base = '';
    define('APP_BASE', $app_base);
}

// URL public của thư mục uploads, ví dụ: /showroom_oto/uploads
if (!defined('UPLOAD_PUBLIC_SUBDIR')) {
    define('UPLOAD_PUBLIC_SUBDIR', (APP_BASE ? APP_BASE : '') . '/uploads');
}

/* ======================== Helpers ======================== */

/**
 * Đổi URL public (files.storage_path) sang đường dẫn tuyệt đối trên đĩa.
 * Ví dụ:
 *   storage_path: /showroom_oto/uploads/cars/CAR-ROL-002/F24...AB.jpg
 *   => PROJECT_ROOT/uploads/cars/CAR-ROL-002/F24...AB.jpg
 */
function storage_to_abs(string $storage_path): string {
    $p = str_replace('\\','/',$storage_path);
    $prefix = rtrim(UPLOAD_PUBLIC_SUBDIR,'/') . '/';

    if (strpos($p, $prefix) !== false) {
        $rel = substr($p, strpos($p, $prefix) + strlen($prefix)); // "cars/.../file.jpg"
    } else {
        // fallback: nếu ai đó lưu "uploads/..." hay "/uploads/..."
        $p2 = ltrim($p, '/');
        if (stripos($p2, 'uploads/') === 0) {
            $rel = substr($p2, strlen('uploads/'));
        } else {
            // cuối cùng: xem như đã bỏ "uploads/"
            $rel = ltrim($p2, '/');
        }
    }

    return PROJECT_ROOT
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
}

/* ================= Upload & Registry (files) ================ */

/**
 * Upload file và ghi bảng files.
 * @return array [$file_code, $public_url]
 * Bảng files: (file_code PK, original_name, mime_type, file_size, storage_path, uploaded_at)
 */
function upload_and_register_file(mysqli $conn, array $file, string $bucket, ?string $ownerCode = null): array
{
    if (
        empty($file['name']) ||
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($file['tmp_name'] ?? '')
    ) {
        throw new RuntimeException('File upload không hợp lệ.');
    }

    // --- Chuẩn hoá bucket/owner ---
    $bucket    = preg_replace('/[^a-z0-9_\-]/i', '', $bucket);
    $ownerSafe = $ownerCode ? preg_replace('/[^a-z0-9_\-]/i', '', strtoupper($ownerCode)) : '';

    // --- GUARD: với bucket 'cars', ownerCode bắt buộc là CAR-... ---
    if ($bucket === 'cars') {
        if (!$ownerSafe || stripos($ownerSafe, 'CAR-') !== 0) {
            throw new RuntimeException(
                'ownerCode không hợp lệ cho bucket "cars": cần dạng CAR-..., nhận: ' . (string)$ownerCode
            );
        }
    }

    // --- Extension hợp lệ ---
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp','gif','svg'];
    if (!in_array($ext, $allow, true)) $ext = 'png';

    // --- Tên file duy nhất theo file_code ---
    // F + yymmddHis + 7 hex (<= 20 ký tự)
    $file_code = 'F' . date('ymdHis') . substr(strtoupper(bin2hex(random_bytes(4))), 0, 7);
    $filename  = $file_code . '.' . $ext;

    // --- Thư mục đích: <project>/uploads/<bucket>/<owner
    $absDir = PROJECT_ROOT
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . $bucket
        . ($ownerSafe ? DIRECTORY_SEPARATOR . $ownerSafe : '');

    if (!is_dir($absDir) && !mkdir($absDir, 0777, true)) {
        throw new RuntimeException('Không tạo được thư mục lưu file.');
    }

    // --- Ghi file lên đĩa ---
    $absPath = $absDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $absPath)) {
        throw new RuntimeException('Không thể lưu file.');
    }
    @chmod($absPath, 0644);

    // --- MIME & kích thước ---
    $mime = function_exists('mime_content_type')
        ? (mime_content_type($absPath) ?: ($file['type'] ?? 'application/octet-stream'))
        : ($file['type'] ?? 'application/octet-stream');
    $size = (int) @filesize($absPath);

    // --- URL public để lưu DB & hiển thị: <APP_BASE>/uploads/<bucket>/<owner>/<file>
    $public_url = rtrim(UPLOAD_PUBLIC_SUBDIR, '/')
        . '/' . $bucket
        . ($ownerSafe ? '/'.$ownerSafe : '')
        . '/' . $filename;

    // --- Ghi bảng files ---
    $orig = substr((string)$file['name'], 0, 255);
    $st = $conn->prepare("
        INSERT INTO files (file_code, original_name, mime_type, file_size, storage_path, uploaded_at)
        VALUES (?,?,?,?,?, NOW())
    ");
    $st->bind_param("sssis", $file_code, $orig, $mime, $size, $public_url);
    $st->execute();
    $st->close();

    return [$file_code, $public_url];
}

/* ===================== car_images code ===================== */

/**
 * Sinh mã ảnh theo DB để không trùng khi upload nhiều đợt.
 * Tạo dạng: IMG-<đuôi CAR_CODE>-NNN
 * (ví dụ: CAR-ROL-002 -> IMG-ROL-002-001, IMG-ROL-002-002, ...)
 */
if (!function_exists('next_car_image_code_db')) {
    function next_car_image_code_db(mysqli $conn, string $car_code): string {
        $up = strtoupper($car_code);
        $st = $conn->prepare("
            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(car_image_code,'-',-1) AS UNSIGNED)), 0) AS m
            FROM car_images
            WHERE car_code = ?
        ");
        $st->bind_param("s", $up);
        $st->execute();
        $m = (int)($st->get_result()->fetch_assoc()['m'] ?? 0);
        $st->close();

        $tail = preg_replace('~^CAR-~', '', $up);
        return 'IMG-' . $tail . '-' . str_pad((string)($m + 1), 3, '0', STR_PAD_LEFT);
    }
}

/**
 * (Fallback cũ) Sinh mã ảnh theo thứ tự vòng lặp nếu cần tương thích.
 * Khuyến nghị: dùng next_car_image_code_db() thay vì hàm này.
 */
if (!function_exists('next_car_image_code')) {
    function next_car_image_code(string $car_code, int $seq): string {
        $tail = preg_replace('~^CAR-~', '', strtoupper($car_code));
        return 'IMG-' . $tail . '-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }
}

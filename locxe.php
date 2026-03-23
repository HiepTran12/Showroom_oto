<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connect.php';
include 'header.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

/** ================== NHẬN BỘ LỌC ================== **/
$filters = [
    'brand_code'   => $_GET['brand_code']   ?? '',
    'seats'        => $_GET['seats']        ?? '',
    'top_speed'    => $_GET['top_speed']    ?? '',
    'transmission' => $_GET['transmission'] ?? '',
    'price'        => $_GET['price']        ?? ''
];
// Nếu có tham số 'brand' (từ logo), thì override filters['brand_code']
if (isset($_GET['brand']) && $_GET['brand'] !== '') {
    $filters['brand_code'] = $_GET['brand'];
}

/** ================== XÂY WHERE + PARAM (SAFE) ================== **/
$conds = [];
$params = [];
$types  = "";

// brand_code
if ($filters['brand_code'] !== '') {
    $conds[] = "c.brand_code = ?";
    $params[] = $filters['brand_code'];
    $types   .= "s";
}

// seats (số ghế =)
if ($filters['seats'] !== '') {
    $conds[] = "c.seats = ?";
    $params[] = (int)$filters['seats'];
    $types   .= "i";
}

// top_speed (>=)
if ($filters['top_speed'] !== '' && is_numeric($filters['top_speed'])) {
    $ts = max(0, (int)$filters['top_speed']);
    $conds[] = "c.top_speed >= ?";
    $params[] = $ts;
    $types   .= "i";
}

// transmission (=)
if ($filters['transmission'] !== '') {
    $conds[] = "c.transmission = ?";
    $params[] = $filters['transmission'];
    $types   .= "s";
}

// price (min-max)
if ($filters['price'] !== '' && strpos($filters['price'], '-') !== false) {
    [$min, $max] = explode('-', $filters['price'], 2);
    $min = (float)$min; $max = (float)$max;
    if ($min > $max) { $tmp = $min; $min = $max; $max = $tmp; }
    $conds[] = "c.price BETWEEN ? AND ?";
    $params[] = $min; $params[] = $max;
    $types   .= "dd";
}

// WHERE chung
$whereSql = "";
if (!empty($conds)) {
    $whereSql = "WHERE " . implode(" AND ", $conds);
}

/** ================== PHÂN TRANG ================== **/
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/** ================== ĐẾM TỔNG ================== **/
$countSql = "SELECT COUNT(*) AS total
             FROM cars c
             JOIN brands b ON c.brand_code = b.brand_code
             $whereSql";
$st = $conn->prepare($countSql);
if ($types !== "") { $st->bind_param($types, ...$params); }
$st->execute();
$totalCars = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);
$st->close();
$totalPages = max(1, (int)ceil($totalCars / $perPage));

/** ================== TRUY VẤN DANH SÁCH XE ================== **/
$listSql = "SELECT c.car_code, c.name, b.name AS brand_name, c.price, c.seats, c.top_speed, c.transmission,
                   COALESCE(f.storage_path, 'hinh/default.jpg') AS image_path
            FROM cars c
            JOIN brands b ON c.brand_code = b.brand_code
            LEFT JOIN car_images ci ON c.car_code = ci.car_code AND ci.is_primary = 1
            LEFT JOIN files f ON ci.file_code = f.file_code
            $whereSql
            ORDER BY c.price DESC
            LIMIT ? OFFSET ?";

$st = $conn->prepare($listSql);

// bind param cho LIMIT/OFFSET
if ($types !== "") {
    $typesList = $types . "ii";
    $paramsList = array_merge($params, [$perPage, $offset]);
    $st->bind_param($typesList, ...$paramsList);
} else {
    $st->bind_param("ii", $perPage, $offset);
}

$st->execute();
$result = $st->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lọc xe</title>
    <style>
        body { margin: 0; font-family: Arial; }
        .main-content { display: flex; gap: 20px; padding: 100px 10px 20px; align-items: flex-start; }
        .filter-sidebar { width: 260px; background-color: #f8f9fa; border-radius: 10px; position: sticky; top: 100px; flex-shrink: 0; padding: 10px 15px; }
        .filter-wrapper form { display: none; flex-direction: column; gap: 16px; margin-top: 10px; }
        .filter-wrapper.active form { display: flex; }
        .toggle { width: 100%; padding: 12px 15px; background-color: #5a54f1; color: white; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; margin-bottom: 10px; }
        .toggle:hover { background-color: #0056b3; }
        .filter-wrapper label { font-size: 14px; font-weight: 600; color: #444; }
        .filter-wrapper select, .filter-wrapper input[type="number"], .filter-wrapper button[type="submit"] { padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; background-color: #fff; }
        .filter-wrapper button[type="submit"] { background-color: #28a745; color: white; font-weight: bold; border: none; margin-top: 10px; cursor: pointer; }
        .filter-wrapper button[type="submit"]:hover { background-color: #218838; }
        .car-list { flex-grow: 1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .car-card { border: 1px solid #ddd; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; transition: 0.3s; background: white; }
        .car-card:hover { transform: translateY(-5px); }
        .car-card img { width: 100%; height: 260px; object-fit: cover; }
        .car-card h4 { padding: 10px; font-size: 16px; }
        .pagination { grid-column: 1 / -1; text-align: center; margin-top: 30px; }
        .pagination a { margin: 0 5px; padding: 8px 14px; background: #eee; color: #333; border-radius: 5px; text-decoration: none; }
        .pagination a:hover { background: #ccc; }
        .pagination a.active { background: #333; color: #fff; }
    </style>
</head>
<body>

<div class="main-content">
    <!-- Bộ lọc -->
    <div class="filter-sidebar">
        <div class="filter-wrapper" id="filterBox">
            <button type="button" class="toggle" onclick="toggleFilter()">Hiện / Ẩn bộ lọc</button>
            <form method="GET">
                <label>Brand:</label>
                <select name="brand_code">
                    <option value="">-- Tất cả --</option>
                    <?php
                    $brands = $conn->query("SELECT brand_code, name FROM brands ORDER BY name ASC");
                    while ($row = $brands->fetch_assoc()) {
                        $sel = $filters['brand_code'] === $row['brand_code'] ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($row['brand_code'], ENT_QUOTES, 'UTF-8')."' $sel>"
                           . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8')
                           . "</option>";
                    }
                    $brands->free();
                    ?>
                </select>

                <label>Seats:</label>
                <select name="seats">
                    <option value="">-- Tất cả --</option>
                    <?php for ($i = 2; $i <= 7; $i++): ?>
                        <option value="<?= $i ?>" <?= ($filters['seats'] !== '' && (int)$filters['seats'] === $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <?php
                $top_speed = $filters['top_speed'] !== '' ? max(0, (int)$filters['top_speed']) : '';
                ?>
                <label>Top Speed ≥ (km/h):</label>
                <input type="number" name="top_speed" min="0" value="<?= htmlspecialchars($top_speed, ENT_QUOTES, 'UTF-8') ?>">

                <label>Transmission:</label>
                <select name="transmission">
                    <option value="">-- Tất cả --</option>
                    <?php
                    $trans = $conn->query("SELECT DISTINCT transmission FROM cars WHERE transmission IS NOT NULL ORDER BY transmission");
                    while ($row = $trans->fetch_assoc()) {
                        $val = $row['transmission'];
                        $sel = ($filters['transmission'] !== '' && $filters['transmission'] === $val) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($val, ENT_QUOTES, 'UTF-8')."' $sel>"
                           . htmlspecialchars($val, ENT_QUOTES, 'UTF-8')
                           . "</option>";
                    }
                    $trans->free();
                    ?>
                </select>

                <label>Price:</label>
                <select name="price">
                    <option value="">-- Tất cả --</option>
                    <?php
                    $ranges = [
                        '0-1000000000'          => 'Dưới 1 tỷ',
                        '1000000000-3000000000' => '1 - 3 tỷ',
                        '3000000000-5000000000' => '3 - 5 tỷ',
                        '5000000000-10000000000'=> '5 - 10 tỷ',
                        '10000000000-20000000000'=> '10 - 20 tỷ'
                    ];
                    foreach ($ranges as $val => $label) {
                        $sel = ($filters['price'] !== '' && $filters['price'] === $val) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($val, ENT_QUOTES, 'UTF-8')."' $sel>"
                           . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                           . "</option>";
                    }
                    ?>
                </select>

                <button type="submit">Lọc xe</button>
            </form>
        </div>
    </div>

    <!-- Danh sách xe -->
    <div class="car-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($car = $result->fetch_assoc()): ?>
                <div class="car-card">
                    <a href="chitiet.php?car_code=<?= urlencode($car['car_code']) ?>">
                        <?php
                        $img = $car['image_path'] ?: 'hinh/default.jpg';
                        if ($img[0] !== '/' && !preg_match('~^https?://~i', $img)) $img = '/'.ltrim($img, '/');
                        ?>
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($car['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </a>
                    <h4><?= htmlspecialchars($car['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($car['brand_name'], ENT_QUOTES, 'UTF-8') ?>)</h4>
                    <p style="color: red;"><b>Giá: <?= number_format((float)$car['price'], 0, ',', '.') ?> VND</b></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style='grid-column:1/-1; text-align:center; color:red;'>Không tìm thấy xe nào.</p>
        <?php endif; ?>

        <!-- Phân trang -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php $query = $_GET; $query['page'] = $i; ?>
                    <a href="?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8') ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
$st->close();
$conn->close();
?>

<script>
    function toggleFilter() {
        document.getElementById('filterBox').classList.toggle('active');
    }
</script>
</body>
</html>

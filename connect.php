<?php
$conn = new mysqli("localhost",  "root", "taolao", "oto");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Thiết lập timezone phiên MySQL đồng bộ với PHP (VN)
date_default_timezone_set('Asia/Ho_Chi_Minh');
$conn->query("SET time_zone = '+07:00'");
?>

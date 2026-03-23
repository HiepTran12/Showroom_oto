<?php
include 'auth_check.php';
include '../connect.php';

$user_code = $_SESSION['user_code'] ?? null;
$user = null;

if ($user_code) {
    $sql = "SELECT full_name, role FROM users WHERE user_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_code); // "s" = string
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

// Random avatar
if (!isset($_SESSION['avatar'])) {
    $rand = rand(1, 99);
    $_SESSION['avatar'] = "https://randomuser.me/api/portraits/men/$rand.jpg";
}

$avatar = $_SESSION['avatar'];

?>


<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .logout-btn {
            margin-left: 15px;
            padding: 8px 15px;
            background: var(--secondary);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background: darkred;
        }
    </style>
</head>

<!-- Header -->
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <div style="background-color: var(--primary); width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <i class="fas fa-car" style="color: white; font-size: 24px;"></i>
                    </div>
                    <h1>Luxury<span>Cars</span></h1>
                </div>
                <div class="user-info">
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['full_name']) ?></h3>
                        <p><?= htmlspecialchars($user['role']) ?></p>
                    </div>
                    <a href="../Taikhoan/dangxuat.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Đăng xuất</a>
                </div>
            </div>
        </div>


       <nav>
            <div class="container">
                <ul class="nav-menu">
                    <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                    ?>
                    <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Trang chủ</a></li>

                    <!-- Quản lý -->
                    <li class="dropdown">
                        <a href="#"class="<?php echo in_array($current_page, ['manage_users.php', 'customers.php', 'brands.php']) ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Quản lý <i class="fas fa-caret-down"></i></a>
<ul class="dropdown-menu">
                            <li><a href="manage_users.php">Tài khoản</a></li>
                            <li><a href="customers.php">Khách hàng</a></li>
                            <li><a href="brands.php">Thương hiệu</a></li>
                        </ul>
                    </li>

                    <!-- Kinh doanh -->
                    <li class="dropdown">
                        <a href="#"class="<?php echo in_array($current_page, ['xe.php', 'invoices.php', 'appointments.php', 'service_dashboard.php']) ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Kinh doanh <i class="fas fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="xe.php">Danh sách xe</a></li>
                            <li><a href="invoices.php">Đơn hàng</a></li>
                            <li><a href="service_dashboard.php">Dịch vụ & Bảo trì</a></li>
                        </ul>
                    </li>

                    <!-- Khác -->
                    <li class="dropdown">
                        <a href="#"class="<?php echo in_array($current_page, ['reports.php', 'logs.php', 'contact_messages.php']) ? 'active' : ''; ?>"><i class="fas fa-ellipsis-h"></i> Khác <i class="fas fa-caret-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="reports.php">Báo cáo</a></li>
                            <li><a href="logs.php">Phản hồi</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>




<style>
      /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            margin-right: 15px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
        }
        
        .logo span {
            color: var(--accent);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--secondary);
        }
        
        .user-details h3 {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .user-details p {
font-size: 0.85rem;
            color: var(--gray);
        }
        
        /* Navigation */
        nav {
            background-color: var(--primary);
            padding: 0;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
        }
        
        .nav-menu li {
            position: relative;
        }
        
        .nav-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-menu a.active {
            background-color: var(--secondary);
        }
        
        .nav-menu a i {
            margin-right: 8px;
        }
        /* Dropdown menu */
        .nav-menu li .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background: white;
            list-style: none;
            padding: 5px 0;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 999;
        }

        .nav-menu li .dropdown-menu li a {
            color: var(--primary);
            padding: 10px 15px;
            display: block;
            font-weight: 500;
        }

        .nav-menu li .dropdown-menu li a:hover {
            background-color: var(--secondary);
            color: white;
        }

        .nav-menu li .dropdown-menu.show {
            display: block;
        }

</style>

<script>
    // dropdown.js
document.addEventListener("DOMContentLoaded", function () {
    const dropdowns = document.querySelectorAll(".nav-menu .dropdown > a");

    dropdowns.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();

            // Đóng tất cả dropdown khác
            document.querySelectorAll(".dropdown-menu.show").forEach(menu => {
                if (menu !== this.nextElementSibling) {
                    menu.classList.remove("show");
                }
            });

            // Toggle dropdown hiện tại
            const menu = this.nextElementSibling;
            menu.classList.toggle("show");
        });
    });

    // Click ngoài để đóng dropdown
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".dropdown")) {
            document.querySelectorAll(".dropdown-menu.show").forEach(menu => {
                menu.classList.remove("show");
            });
        }
    });
});

</script>
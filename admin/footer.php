<!-- Footer Admin -->
<footer>
    <div class="admin-footer">
        <div class="footer-left">
            <div class="logo">
                <i class="fas fa-car"></i>
                <span>AutoChain Admin</span>
            </div>
            <p>&copy; 2025 AutoChain. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="logs.php"><i class="fas fa-file-alt"></i> Logs</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="manage_users.php"><i class="fas fa-cogs"></i> Settings</a></li>
            </ul>
        </div>
    </div>
</footer>
<style>
    footer {
    background-color: #1f2937; /* xám tối */
    color: #fff;
    padding: 15px 30px;
    font-size: 0.9rem;
    position: relative;
    bottom: 0;
    width: 100%;
}

.admin-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.admin-footer .footer-left .logo {
    display: flex;
    align-items: center;
    font-weight: 600;
    font-size: 1rem;
}

.admin-footer .footer-left .logo i {
    margin-right: 8px;
    color: #f59e0b; /* màu vàng nổi bật */
}

.admin-footer .footer-right ul {
    display: flex;
    list-style: none;
    gap: 20px;
    margin: 0;
    padding: 0;
}

.admin-footer .footer-right ul li a {
    color: #9ca3af;
    text-decoration: none;
    transition: color 0.3s;
}

.admin-footer .footer-right ul li a:hover {
    color: #f59e0b;
}

</style>
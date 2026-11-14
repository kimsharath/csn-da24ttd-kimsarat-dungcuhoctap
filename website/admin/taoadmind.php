
<?php
// 1. Khởi tạo Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kiểm tra quyền Admin
if (
    !isset($_SESSION['MaTK']) ||
    ($_SESSION['VaiTro'] ?? '') !== 'Admin'
) {
    header("Location: ../dangnhap.php");
    exit;
}

// 3. Kết nối MySQL
require_once __DIR__ . "/../config/database.php";
?>


<?php
require_once "config/database.php";

// Tạo tài khoản quản trị mặc định.
$tenDN = "admin";
$matKhau = "12345678";

// Mã hóa mật khẩu
$matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);

// Kiểm tra tài khoản đã tồn tại chưa
$sqlCheck = "SELECT MaTK FROM TaiKhoan WHERE TenDN = :tendn";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute([':tendn' => $tenDN]);

if ($stmtCheck->fetch()) {
    exit("Tài khoản admin đã tồn tại!");
}

// Tạo tài khoản Admin
$sql = "INSERT INTO TaiKhoan (MaKH, TenDN, MatKhau, VaiTro)
        VALUES (NULL, :tendn, :matkhau, 'Admin')";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':tendn' => $tenDN,
    ':matkhau' => $matKhauHash
]);

echo "Tạo tài khoản Admin thành công!";
?>

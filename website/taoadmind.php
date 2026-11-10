<?php
require_once "config/database.php";

$tenDN = "admin";
$matKhau = password_hash("123450", PASSWORD_DEFAULT);

$sql = "
    INSERT INTO TaiKhoan (MaKH, TenDN, MatKhau, VaiTro)
    VALUES (NULL, :tendn, :matkhau, 'Admin')
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':tendn' => $tenDN,
    ':matkhau' => $matKhau
]);

echo "Tạo tài khoản Admin thành công!";
?>
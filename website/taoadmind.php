<?php
require_once "config/database.php";

$tenDN = "admin_moi";
$matKhau = password_hash("12112026", PASSWORD_DEFAULT);

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
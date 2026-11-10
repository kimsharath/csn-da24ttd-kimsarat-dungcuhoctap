
<!-- <?php
require_once "config/database.php";

// Mật khẩu mới
$tenDN = "admin";
$matKhauMoi = "123456";

// Mã hóa mật khẩu
$matKhauMaHoa = password_hash(
    $matKhauMoi,
    PASSWORD_DEFAULT
);

// Cập nhật tài khoản Admin đã tồn tại
$sql = "
    UPDATE TaiKhoan
    SET MatKhau = :matkhau
    WHERE TenDN = :tendn
      AND VaiTro = 'Admin'
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':matkhau' => $matKhauMaHoa,
    ':tendn' => $tenDN
]);

if ($stmt->rowCount() > 0) {
    echo "Cập nhật mật khẩu Admin thành công!";
} else {
    echo "Không tìm thấy tài khoản Admin hoặc không có thay đổi.";
}
?> -->

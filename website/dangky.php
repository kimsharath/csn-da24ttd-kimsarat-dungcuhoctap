<?php
require_once "config/database.php";

$thongBao = "";
$loaiThongBao = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Nhận dữ liệu từ form
    $hoTen = trim($_POST['HoTen'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $sdt = trim($_POST['SDT'] ?? '');
    $diaChi = trim($_POST['DiaChi'] ?? '');
    $tenDN = trim($_POST['TenDN'] ?? '');
    $matKhau = $_POST['MatKhau'] ?? '';
    $xacNhanMatKhau = $_POST['XacNhanMatKhau'] ?? '';

    // 2. Kiểm tra dữ liệu
    if (
        $hoTen === '' || $email === '' ||
        $tenDN === '' || $matKhau === '' ||
        $xacNhanMatKhau === ''
    ) {
        $thongBao = "Vui lòng nhập đầy đủ các thông tin bắt buộc.";
        $loaiThongBao = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $thongBao = "Địa chỉ email không hợp lệ.";
        $loaiThongBao = "error";

    } elseif (strlen($matKhau) < 6) {
        $thongBao = "Mật khẩu phải có ít nhất 6 ký tự.";
        $loaiThongBao = "error";

    } elseif ($matKhau !== $xacNhanMatKhau) {
        $thongBao = "Mật khẩu xác nhận không khớp.";
        $loaiThongBao = "error";

    } else {

        try {
            // 3. Bắt đầu giao dịch
            $conn->beginTransaction();

            // 4. Kiểm tra email đã tồn tại
            $sqlKiemTraEmail = "
                SELECT COUNT(*)
                FROM KhachHang
                WHERE Email = :email
            ";

            $stmtEmail = $conn->prepare($sqlKiemTraEmail);
            $stmtEmail->execute([':email' => $email]);

            // 5. Kiểm tra tên đăng nhập đã tồn tại
            $sqlKiemTraTenDN = "
                SELECT COUNT(*)
                FROM TaiKhoan
                WHERE TenDN = :tendn
            ";

            $stmtTenDN = $conn->prepare($sqlKiemTraTenDN);
            $stmtTenDN->execute([':tendn' => $tenDN]);

            if ($stmtEmail->fetchColumn() > 0) {

                $conn->rollBack();
                $thongBao = "Email này đã được sử dụng.";
                $loaiThongBao = "error";

            } elseif ($stmtTenDN->fetchColumn() > 0) {

                $conn->rollBack();
                $thongBao = "Tên đăng nhập đã tồn tại.";
                $loaiThongBao = "error";

            } else {

                // 6. Thêm khách hàng và lấy MaKH
                $sqlKhachHang = "
                    INSERT INTO KhachHang
                    (HoTen, Email, SDT, DiaChi)
                    OUTPUT INSERTED.MaKH
                    VALUES
                    (:hoten, :email, :sdt, :diachi)
                ";

                $stmtKH = $conn->prepare($sqlKhachHang);

                $stmtKH->execute([
                    ':hoten' => $hoTen,
                    ':email' => $email,
                    ':sdt' => $sdt !== '' ? $sdt : null,
                    ':diachi' => $diaChi !== '' ? $diaChi : null
                ]);

                $maKH = $stmtKH->fetchColumn();

                // 7. Mã hóa mật khẩu
                $matKhauMaHoa = password_hash(
                    $matKhau,
                    PASSWORD_DEFAULT
                );

                // 8. Thêm tài khoản
                $sqlTaiKhoan = "
                    INSERT INTO TaiKhoan
                    (MaKH, TenDN, MatKhau, VaiTro)
                    VALUES
                    (:makh, :tendn, :matkhau, N'KhachHang')
                ";

                $stmtTK = $conn->prepare($sqlTaiKhoan);

                $stmtTK->execute([
                    ':makh' => $maKH,
                    ':tendn' => $tenDN,
                    ':matkhau' => $matKhauMaHoa
                ]);

                // 9. Hoàn tất giao dịch
                $conn->commit();

                $thongBao = "Đăng ký tài khoản thành công! Bạn có thể đăng nhập.";
                $loaiThongBao = "success";
            }

        } catch (PDOException $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $thongBao = "Lỗi SQL: " . $e->getMessage();
            $loaiThongBao = "error";
        }
    }
}

    

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - StudyStore</title>

    <link rel="stylesheet" href="css/styledk.css">
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <h1>StudyStore</h1>
            <p>Cửa hàng dụng cụ học tập</p>
        </div>
    </header>

    <!-- MENU -->
    <!-- <nav class="menu">
        <div class="container">
            <a href="index.php">Trang chủ</a>
            <a href="sanpham.php">Sản phẩm</a>
            <a href="dangnhap.php">Đăng nhập</a>
            <a href="dangky.php" class="active">Đăng ký</a>
        </div>
    </nav> -->

    <!-- FORM ĐĂNG KÝ -->
    <main class="register-container">

        <div class="register-box">

            <h2>ĐĂNG KÝ TÀI KHOẢN</h2>

            <p class="register-description">
                Tạo tài khoản để mua sắm tại StudyStore
            </p>

            <?php if ($thongBao !== ''): ?>

                <div class="register-message <?php echo $loaiThongBao; ?>">
                    <?php echo htmlspecialchars($thongBao); ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="dangky.php">

                <div class="register-group">
                    <label for="HoTen">Họ và tên *</label>
                    <input
                        type="text"
                        id="HoTen"
                        name="HoTen"
                        placeholder="Nhập họ và tên"
                        value="<?php echo htmlspecialchars($_POST['HoTen'] ?? ''); ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="register-group">
                    <label for="Email">Email *</label>
                    <input
                        type="email"
                        id="Email"
                        name="Email"
                        placeholder="Nhập địa chỉ email"
                        value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="register-group">
                    <label for="SDT">Số điện thoại</label>
                    <input
                        type="tel"
                        id="SDT"
                        name="SDT"
                        placeholder="Nhập số điện thoại"
                        value="<?php echo htmlspecialchars($_POST['SDT'] ?? ''); ?>"
                        maxlength="15"
                    >
                </div>

                <div class="register-group">
                    <label for="DiaChi">Địa chỉ</label>
                    <input
                        type="text"
                        id="DiaChi"
                        name="DiaChi"
                        placeholder="Nhập địa chỉ"
                        value="<?php echo htmlspecialchars($_POST['DiaChi'] ?? ''); ?>"
                        maxlength="255"
                    >
                </div>

                <div class="register-group">
                    <label for="TenDN">Tên đăng nhập *</label>
                    <input
                        type="text"
                        id="TenDN"
                        name="TenDN"
                        placeholder="Tạo tên đăng nhập"
                        value="<?php echo htmlspecialchars($_POST['TenDN'] ?? ''); ?>"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="register-group">
                    <label for="MatKhau">Mật khẩu *</label>
                    <input
                        type="password"
                        id="MatKhau"
                        name="MatKhau"
                        placeholder="Nhập mật khẩu (ít nhất 6 ký tự)"
                        minlength="6"
                        required
                    >
                </div>

                <div class="register-group">
                    <label for="XacNhanMatKhau">
                        Xác nhận mật khẩu *
                    </label>

                    <input
                        type="password"
                        id="XacNhanMatKhau"
                        name="XacNhanMatKhau"
                        placeholder="Nhập lại mật khẩu"
                        minlength="6"
                        required
                    >
                </div>

                <button type="submit" class="register-button">
                    Đăng ký
                </button>

            </form>

            <p class="login-link">
                Đã có tài khoản?
                <a href="dangnhap.php">Đăng nhập ngay</a>
            </p>

            <p class="back-link">
                <a href="test_products.php">← Quay lại trang chủ</a>
            </p>

        </div>

    </main>

    <!-- FOOTER -->
    <!-- <footer class="footer">
        <p>© 2026 StudyStore - Cửa hàng dụng cụ học tập</p>
    </footer> -->

</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

$thongBao = "";

// Nếu đã đăng nhập thì chuyển theo quyền
if (isset($_SESSION['MaTK'])) {

    if (($_SESSION['VaiTro'] ?? '') === 'Admin') {
        header("Location: admin/index.php");
        exit;
    } else {
        header("Location: trangchu.php");
        exit;
    }
}

// Khi người dùng nhấn nút đăng nhập
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tenDN = trim($_POST['TenDN'] ?? '');
    $matKhau = $_POST['MatKhau'] ?? '';

    if ($tenDN === '' || $matKhau === '') {

        $thongBao = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";

    } else {

        // Tìm tài khoản trong MySQL
        $sql = "
            SELECT MaTK, MaKH, TenDN, MatKhau, VaiTro
            FROM taikhoan
            WHERE TenDN = :tendn
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tendn' => $tenDN
        ]);

        $taiKhoan = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra tài khoản và mật khẩu
        if (
            $taiKhoan &&
            password_verify($matKhau, $taiKhoan['MatKhau'])
        ) {

            session_regenerate_id(true);

            $_SESSION['MaTK'] = $taiKhoan['MaTK'];
            $_SESSION['MaKH'] = $taiKhoan['MaKH'];
            $_SESSION['TenDN'] = $taiKhoan['TenDN'];
            $_SESSION['VaiTro'] = $taiKhoan['VaiTro'];

            // Phân quyền và chuyển trang
            if ($taiKhoan['VaiTro'] === 'Admin') {

                header("Location: admin/index.php");
                exit;

            } elseif ($taiKhoan['VaiTro'] === 'KhachHang') {

                header("Location: trangchu.php");
                exit;

            } else {

                session_unset();
                session_destroy();

                $thongBao = "Tài khoản không có quyền truy cập hợp lệ.";
            }

        } else {

            $thongBao = "Tên đăng nhập hoặc mật khẩu không chính xác.";
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

    <title>Đăng nhập - StudyStore</title>

    <link rel="stylesheet" href="css/dangnhap.css">
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
            <a href="trangchu.php">Trang chủ</a>
            <a href="sanpham.php">Sản phẩm</a>
            <a href="dangnhap.php" class="active">Đăng nhập</a>
        </div>
    </nav> -->

    <!-- FORM ĐĂNG NHẬP -->
    <main class="login-container">

        <div class="login-box">

            <h2>ĐĂNG NHẬP</h2>

            <p class="login-description">
                Chào mừng bạn đến với StudyStore
            </p>

            <?php if ($thongBao !== ''): ?>

                <div class="login-error">
                    <?php echo htmlspecialchars($thongBao); ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="dangnhap.php">

                <div class="login-group">

                    <label for="TenDN">
                        Tên đăng nhập
                    </label>

                    <input
                        type="text"
                        id="TenDN"
                        name="TenDN"
                        placeholder="Nhập tên đăng nhập"
                        value="<?php echo htmlspecialchars($_POST['TenDN'] ?? ''); ?>"
                        required
                    >

                </div>

                <div class="login-group">

                    <label for="MatKhau">
                        Mật khẩu
                    </label>

                    <input
                        type="password"
                        id="MatKhau"
                        name="MatKhau"
                        placeholder="Nhập mật khẩu"
                        required
                    >

                </div>

                <button type="submit" class="login-button">
                    Đăng nhập
                </button>

            </form>

            <p class="register-link">
                Chưa có tài khoản?
                <a href="dangky.php">Đăng ký ngay</a>
            </p>

            <p class="back-link">
                <a href="trangchu.php">← Quay lại trang chủ</a>
            </p>

        </div>

    </main>

    <!-- FOOTER -->
    <!-- <footer class="footer">
        <p>© 2026 StudyStore - Cửa hàng dụng cụ học tập</p>
    </footer> -->

</body>

</html>
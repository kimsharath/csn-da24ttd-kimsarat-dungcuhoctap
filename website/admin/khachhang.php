
<?php
session_start();
require_once "../config/database.php";

// 1. Kiểm tra quyền Admin
if (
    !isset($_SESSION['MaTK']) ||
    ($_SESSION['VaiTro'] ?? '') !== 'Admin'
) {
    header("Location: ../dangnhap.php");
    exit;
}

$thongBao = "";
$loi = "";

// 2. Xử lý cập nhật khách hàng
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $maKH = (int)($_POST['MaKH'] ?? 0);
    $hoTen = trim($_POST['HoTen'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $sdt = trim($_POST['SDT'] ?? '');
    $diaChi = trim($_POST['DiaChi'] ?? '');

    if ($maKH <= 0 || $hoTen === '' || $email === '') {
        $loi = "Vui lòng nhập đầy đủ họ tên và email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = "Địa chỉ email không hợp lệ!";
    } else {

        try {
            // Kiểm tra email có thuộc khách hàng khác không
            $sqlKiemTra = "
                SELECT COUNT(*)
                FROM KhachHang
                WHERE Email = :email
                  AND MaKH <> :makh
            ";

            $stmtKiemTra = $conn->prepare($sqlKiemTra);

            $stmtKiemTra->execute([
                ':email' => $email,
                ':makh' => $maKH
            ]);

            if ((int)$stmtKiemTra->fetchColumn() > 0) {
                throw new Exception("Email này đã được khách hàng khác sử dụng!");
            }

            // Cập nhật thông tin khách hàng
            $sqlSua = "
                UPDATE KhachHang
                SET HoTen = :hoten,
                    Email = :email,
                    SDT = :sdt,
                    DiaChi = :diachi
                WHERE MaKH = :makh
            ";

            $stmtSua = $conn->prepare($sqlSua);

            $stmtSua->execute([
                ':hoten' => $hoTen,
                ':email' => $email,
                ':sdt' => $sdt !== '' ? $sdt : null,
                ':diachi' => $diaChi !== '' ? $diaChi : null,
                ':makh' => $maKH
            ]);

            header("Location: khachhang.php?msg=capnhat");
            exit;

        } catch (PDOException $e) {
            $loi = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
        } catch (Exception $e) {
            $loi = $e->getMessage();
        }
    }
}

// 3. Thông báo cập nhật
if (isset($_GET['msg']) && $_GET['msg'] === 'capnhat') {
    $thongBao = "Cập nhật khách hàng thành công!";
}

// 4. Lấy thông tin khách hàng cần sửa
$khachHangSua = null;

if (isset($_GET['sua'])) {

    $maKH = (int)$_GET['sua'];

    if ($maKH > 0) {
        $stmtSua = $conn->prepare("
            SELECT MaKH, HoTen, Email, SDT, DiaChi
            FROM KhachHang
            WHERE MaKH = :makh
        ");

        $stmtSua->execute([':makh' => $maKH]);

        $khachHangSua = $stmtSua->fetch(PDO::FETCH_ASSOC);
    }
}

$dangSua = is_array($khachHangSua);

// 5. Xem chi tiết khách hàng
$khachHangChiTiet = null;

if (isset($_GET['xem'])) {

    $maKH = (int)$_GET['xem'];

    if ($maKH > 0) {
        $stmtXem = $conn->prepare("
            SELECT
                KH.MaKH,
                KH.HoTen,
                KH.Email,
                KH.SDT,
                KH.DiaChi,
                COUNT(DH.MaDH) AS SoDonHang,
                COALESCE(SUM(DH.TongTien), 0) AS TongChiTieu
            FROM KhachHang KH
            LEFT JOIN DonHang DH
                ON KH.MaKH = DH.MaKH
            WHERE KH.MaKH = :makh
            GROUP BY
                KH.MaKH,
                KH.HoTen,
                KH.Email,
                KH.SDT,
                KH.DiaChi
        ");

        $stmtXem->execute([':makh' => $maKH]);

        $khachHangChiTiet = $stmtXem->fetch(PDO::FETCH_ASSOC);
    }
}

// 6. Tìm kiếm khách hàng
$tuKhoa = trim($_GET['timkiem'] ?? '');

$sql = "
    SELECT
        KH.MaKH,
        KH.HoTen,
        KH.Email,
        KH.SDT,
        KH.DiaChi,
        COUNT(DH.MaDH) AS SoDonHang
    FROM KhachHang KH
    LEFT JOIN DonHang DH
        ON KH.MaKH = DH.MaKH
    WHERE 1 = 1
";

$thamSo = [];

if ($tuKhoa !== '') {
    $sql .= "
        AND (
            CAST(KH.MaKH AS NVARCHAR(20)) LIKE :tukhoa
            OR KH.HoTen LIKE :tukhoa
            OR KH.Email LIKE :tukhoa
            OR KH.SDT LIKE :tukhoa
        )
    ";

    $thamSo[':tukhoa'] = '%' . $tuKhoa . '%';
}

$sql .= "
    GROUP BY
        KH.MaKH,
        KH.HoTen,
        KH.Email,
        KH.SDT,
        KH.DiaChi
    ORDER BY KH.MaKH DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($thamSo);

$danhSachKhachHang = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý khách hàng - StudyStore</title>

    <link rel="stylesheet" href="../css/styleadmin.css">
</head>

<body>

<div class="admin-container">

    <!-- MENU BÊN TRÁI -->
    <aside class="sidebar">

        <div class="logo">
            <h2>StudyStore</h2>
            <p>ADMIN PANEL</p>
        </div>

        <nav class="menu">
            <a href="index.php">🏠 Tổng quan</a>
            <a href="sanpham.php">📦 Quản lý sản phẩm</a>
            <a href="danhmuc.php">📁 Quản lý danh mục</a>
            <a href="donhang.php">🛒 Quản lý đơn hàng</a>
            <a href="khachhang.php" class="active">👥 Quản lý khách hàng</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../test_products.php">🌐 Xem website</a>
            <a href="../dangxuat.php">🚪 Đăng xuất</a>
        </div>

    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Quản lý khách hàng</h1>
                <p>Xem, tìm kiếm và cập nhật thông tin khách hàng.</p>
            </div>

            <div class="admin-info">
                👤 <?= htmlspecialchars($_SESSION['TenDN'] ?? 'Admin') ?>
                <span class="admin-role">Quản trị viên</span>
            </div>

        </header>

        <!-- THÔNG BÁO -->
        <?php if ($thongBao !== ''): ?>
            <div class="success-message">
                <?= htmlspecialchars($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($loi !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($loi) ?>
            </div>
        <?php endif; ?>

        <!-- CHI TIẾT KHÁCH HÀNG -->
        <?php if (isset($_GET['xem'])): ?>

            <section class="customer-detail-section">

                <div class="customer-detail-heading">
                    <h2>Thông tin khách hàng</h2>

                    <a href="khachhang.php" class="btn-cancel">
                        Quay lại
                    </a>
                </div>

                <?php if ($khachHangChiTiet): ?>

                    <div class="customer-info">

                        <p>
                            <strong>Mã khách hàng:</strong>
                            <?= (int)$khachHangChiTiet['MaKH'] ?>
                        </p>

                        <p>
                            <strong>Họ tên:</strong>
                            <?= htmlspecialchars($khachHangChiTiet['HoTen']) ?>
                        </p>

                        <p>
                            <strong>Email:</strong>
                            <?= htmlspecialchars($khachHangChiTiet['Email']) ?>
                        </p>

                        <p>
                            <strong>Số điện thoại:</strong>
                            <?= htmlspecialchars($khachHangChiTiet['SDT'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Địa chỉ:</strong>
                            <?= htmlspecialchars($khachHangChiTiet['DiaChi'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Số đơn hàng:</strong>
                            <?= (int)$khachHangChiTiet['SoDonHang'] ?>
                        </p>

                        <p>
                            <strong>Tổng chi tiêu:</strong>
                            <span class="customer-money">
                                <?= number_format((float)$khachHangChiTiet['TongChiTieu'], 0, ',', '.') ?> đ
                            </span>
                        </p>

                    </div>

                <?php else: ?>

                    <p>Không tìm thấy khách hàng này.</p>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <!-- FORM CẬP NHẬT KHÁCH HÀNG -->
        <?php if ($dangSua): ?>

            <section class="customer-form-section">

                <h2>Cập nhật thông tin khách hàng</h2>

                <form method="POST" action="khachhang.php">

                    <input
                        type="hidden"
                        name="MaKH"
                        value="<?= (int)$khachHangSua['MaKH'] ?>"
                    >

                    <div class="customer-form-grid">

                        <div class="form-group">
                            <label>Họ và tên</label>

                            <input
                                type="text"
                                name="HoTen"
                                required
                                value="<?= htmlspecialchars($khachHangSua['HoTen']) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Email</label>

                            <input
                                type="email"
                                name="Email"
                                required
                                value="<?= htmlspecialchars($khachHangSua['Email']) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Số điện thoại</label>

                            <input
                                type="text"
                                name="SDT"
                                value="<?= htmlspecialchars($khachHangSua['SDT'] ?? '') ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>

                            <input
                                type="text"
                                name="DiaChi"
                                value="<?= htmlspecialchars($khachHangSua['DiaChi'] ?? '') ?>"
                            >
                        </div>

                    </div>

                    <div class="form-actions">

                        <button type="submit" class="btn-primary">
                            Lưu thay đổi
                        </button>

                        <a href="khachhang.php" class="btn-cancel">
                            Hủy
                        </a>

                    </div>

                </form>

            </section>

        <?php endif; ?>

        <!-- DANH SÁCH KHÁCH HÀNG -->
        <section class="customer-list-section">

            <div class="section-header">

                <div>
                    <h2>Danh sách khách hàng</h2>

                    <p>
                        Có <?= count($danhSachKhachHang) ?> khách hàng được hiển thị.
                    </p>
                </div>

            </div>

            <!-- TÌM KIẾM -->
            <form method="GET" action="khachhang.php" class="customer-search-form">

                <input
                    type="text"
                    name="timkiem"
                    placeholder="Mã KH, họ tên, email hoặc số điện thoại..."
                    value="<?= htmlspecialchars($tuKhoa) ?>"
                >

                <button type="submit" class="btn-primary">
                    Tìm kiếm
                </button>

                <a href="khachhang.php" class="btn-cancel">
                    Làm mới
                </a>

            </form>

            <!-- BẢNG KHÁCH HÀNG -->
            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>Mã KH</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Số đơn hàng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($danhSachKhachHang) > 0): ?>

                            <?php foreach ($danhSachKhachHang as $kh): ?>

                                <tr>

                                    <td>
                                        <?= (int)$kh['MaKH'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($kh['HoTen']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($kh['Email']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($kh['SDT'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($kh['DiaChi'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= (int)$kh['SoDonHang'] ?>
                                    </td>

                                    <td class="customer-actions">

                                        <a
                                            href="khachhang.php?xem=<?= (int)$kh['MaKH'] ?>"
                                            class="btn-edit"
                                        >
                                            Chi tiết
                                        </a>

                                        <a
                                            href="khachhang.php?sua=<?= (int)$kh['MaKH'] ?>"
                                            class="btn-primary"
                                        >
                                            Sửa
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty">
                                    Chưa có khách hàng hoặc không tìm thấy kết quả.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <footer class="footer">
            © 2026 StudyStore - Quản lý khách hàng
        </footer>

    </main>

</div>

</body>
</html>

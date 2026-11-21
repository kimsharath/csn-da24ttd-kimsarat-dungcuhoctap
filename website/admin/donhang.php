<?php
require_once "taoadmind.php";



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

// Các trạng thái đơn hàng được phép cập nhật
$cacTrangThai = [
    "Chờ xác nhận",
    "Đang giao",
    "Đã giao",
    "Đã hủy"
];

// 2. Cập nhật trạng thái đơn hàng
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $maDH = (int)($_POST['MaDH'] ?? 0);
    $trangThaiMoi = $_POST['TrangThai'] ?? '';

    if (
        $maDH <= 0 ||
        !in_array($trangThaiMoi, $cacTrangThai, true)
    ) {
        $loi = "Thông tin cập nhật không hợp lệ!";
    } else {
        try {
            $sql = "
                UPDATE DonHang
                SET TrangThai = :trangthai
                WHERE MaDH = :madh
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':trangthai' => $trangThaiMoi,
                ':madh' => $maDH
            ]);

            header("Location: donhang.php?msg=capnhat");
            exit;

        } catch (PDOException $e) {
            $loi = "Không thể cập nhật đơn hàng: " . $e->getMessage();
        }
    }
}

// 3. Thông báo
if (isset($_GET['msg']) && $_GET['msg'] === 'capnhat') {
    $thongBao = "Cập nhật trạng thái đơn hàng thành công!";
}

// 4. Lấy chi tiết đơn hàng nếu người dùng nhấn Xem
$donHangChiTiet = null;
$chiTietSanPham = [];

if (isset($_GET['xem'])) {

    $maDHXem = (int)$_GET['xem'];

    if ($maDHXem > 0) {

        $sqlDon = "
            SELECT
                DH.MaDH,
                DH.NgayDat,
                DH.TongTien,
                DH.TrangThai,
                DH.DiaChiGiaoHang,
                KH.HoTen,
                KH.Email,
                KH.SDT
            FROM DonHang DH
            INNER JOIN KhachHang KH
                ON DH.MaKH = KH.MaKH
            WHERE DH.MaDH = :madh
        ";

        $stmtDon = $conn->prepare($sqlDon);
        $stmtDon->execute([':madh' => $maDHXem]);

        $donHangChiTiet = $stmtDon->fetch(PDO::FETCH_ASSOC);

        if ($donHangChiTiet) {

            $sqlCT = "
                SELECT
                    SP.TenSP,
                    CT.SL,
                    CT.DonGia,
                    (CT.SL * CT.DonGia) AS ThanhTien
                FROM ChiTietDonHang CT
                INNER JOIN SanPham SP
                    ON CT.MaSP = SP.MaSP
                WHERE CT.MaDH = :madh
            ";

            $stmtCT = $conn->prepare($sqlCT);
            $stmtCT->execute([':madh' => $maDHXem]);

            $chiTietSanPham = $stmtCT->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// 5. Tìm kiếm và lọc đơn hàng
$tuKhoa = trim($_GET['timkiem'] ?? '');
$locTrangThai = $_GET['trangthai'] ?? '';

$sql = "
    SELECT
        DH.MaDH,
        DH.NgayDat,
        DH.TongTien,
        DH.TrangThai,
        DH.DiaChiGiaoHang,
        KH.HoTen,
        KH.SDT
    FROM DonHang DH
    INNER JOIN KhachHang KH
        ON DH.MaKH = KH.MaKH
    WHERE 1 = 1
";

$thamSo = [];

if ($tuKhoa !== '') {
    $sql .= "
        AND (
            CAST(DH.MaDH AS CHAR) LIKE :tukhoa1
            OR KH.HoTen LIKE :tukhoa2
        )
    ";

    $thamSo[':tukhoa1'] = '%' . $tuKhoa . '%';
    $thamSo[':tukhoa2'] = '%' . $tuKhoa . '%';
}

if (in_array($locTrangThai, $cacTrangThai, true)) {
    $sql .= " AND DH.TrangThai = :trangthai";
    $thamSo[':trangthai'] = $locTrangThai;
}

$sql .= " ORDER BY DH.MaDH DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($thamSo);

$danhSachDonHang = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý đơn hàng - StudyStore</title>

    <link rel="stylesheet" href="../css/quantrichung.css">
</head>

<body>

<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            <div class="logo-icon">S</div>
            <div>
                <h2>StudyStore</h2>
                <p>QUẢN TRỊ</p>
            </div>
        </div>

        <div class="menu-label">QUẢN LÝ HỆ THỐNG</div>

        <nav class="menu">
            <a href="index.php"><span class="menu-icon">▦</span><span>Tổng quan</span></a>
            <a href="sanpham.php"><span class="menu-icon">▣</span><span>Quản lý sản phẩm</span></a>
            <a href="danhmuc.php"><span class="menu-icon">▤</span><span>Quản lý danh mục</span></a>
            <a href="donhang.php" class="active"><span class="menu-icon">🛒</span><span>Quản lý đơn hàng</span></a>
            <a href="khachhang.php"><span class="menu-icon">♙</span><span>Quản lý khách hàng</span></a>
            <a href="quanlythanhtoan.php"><span class="menu-icon">▣</span><span>Quản lý thanh toán</span></a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../trangchu.php"><span class="menu-icon">🌐</span><span>Xem website</span></a>
            <a href="../dangxuat.php" class="logout-link"><span class="menu-icon">⇥</span><span>Đăng xuất</span></a>
        </div>

        <div class="sidebar-footer">
            <p>StudyStore © 2026</p>
            <span>Hệ thống quản lý cửa hàng</span>
        </div>

    </aside>

    <!-- NỘI DUNG CHÍNH -->
    <main class="main-content">

        <header class="topbar">
            <div class="page-heading">
                <div class="breadcrumb">Trang quản trị <span>/</span> Đơn hàng</div>
                <h1>Quản lý đơn hàng</h1>
                <p>Theo dõi và cập nhật đơn hàng của khách hàng.</p>
            </div>

            <div class="admin-info">
                <div class="admin-avatar">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['TenDN'] ?? 'Admin', 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="admin-text">
                    <strong><?= htmlspecialchars($_SESSION['TenDN'] ?? 'Admin') ?></strong>
                    <span>Quản trị viên</span>
                </div>
            </div>
        </header>

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

        <!-- CHI TIẾT ĐƠN HÀNG -->
        <?php if (isset($_GET['xem'])): ?>

            <section class="order-detail-section">

                <div class="order-detail-heading">
                    <h2>Chi tiết đơn hàng #<?= (int)$_GET['xem'] ?></h2>
                    <a href="donhang.php" class="btn-cancel">
                        Quay lại danh sách
                    </a>
                </div>

                <?php if ($donHangChiTiet): ?>

                    <div class="order-customer-info">

                        <h3>Thông tin khách hàng</h3>

                        <p>
                            <strong>Họ tên:</strong>
                            <?= htmlspecialchars($donHangChiTiet['HoTen']) ?>
                        </p>

                        <p>
                            <strong>Email:</strong>
                            <?= htmlspecialchars($donHangChiTiet['Email'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Số điện thoại:</strong>
                            <?= htmlspecialchars($donHangChiTiet['SDT'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Địa chỉ giao hàng:</strong>
                            <?= htmlspecialchars($donHangChiTiet['DiaChiGiaoHang'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Ngày đặt:</strong>
                            <?= htmlspecialchars((string)$donHangChiTiet['NgayDat']) ?>
                        </p>

                        <p>
                            <strong>Trạng thái:</strong>
                            <?= htmlspecialchars($donHangChiTiet['TrangThai']) ?>
                        </p>

                    </div>

                    <h3>Sản phẩm trong đơn hàng</h3>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($chiTietSanPham as $i => $ct): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>

                                        <td>
                                            <?= htmlspecialchars($ct['TenSP']) ?>
                                        </td>

                                        <td><?= (int)$ct['SL'] ?></td>

                                        <td>
                                            <?= number_format((float)$ct['DonGia'], 0, ',', '.') ?> đ
                                        </td>

                                        <td>
                                            <?= number_format((float)$ct['ThanhTien'], 0, ',', '.') ?> đ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (count($chiTietSanPham) === 0): ?>
                                    <tr>
                                        <td colspan="5">
                                            Đơn hàng chưa có chi tiết sản phẩm.
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="order-total">
                        Tổng tiền:
                        <strong>
                            <?= number_format((float)$donHangChiTiet['TongTien'], 0, ',', '.') ?> đ
                        </strong>
                    </div>

                <?php else: ?>

                    <p>Không tìm thấy đơn hàng này.</p>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <!-- DANH SÁCH ĐƠN HÀNG -->
        <section class="order-list-section">

            <div class="section-header">
                <div>
                    <h2>Danh sách đơn hàng</h2>
                    <p>
                        Có <?= count($danhSachDonHang) ?> đơn hàng được hiển thị.
                    </p>
                </div>
            </div>

            <!-- TÌM KIẾM VÀ LỌC -->
            <form method="GET" action="donhang.php" class="order-search-form">

                <input
                    type="text"
                    name="timkiem"
                    placeholder="Nhập mã đơn hoặc tên khách hàng..."
                    value="<?= htmlspecialchars($tuKhoa) ?>"
                >

                <select name="trangthai">
                    <option value="">-- Tất cả trạng thái --</option>

                    <?php foreach ($cacTrangThai as $tt): ?>
                        <option
                            value="<?= htmlspecialchars($tt) ?>"
                            <?= $locTrangThai === $tt ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($tt) ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <button type="submit" class="btn-primary">
                    Tìm kiếm
                </button>

                <a href="donhang.php" class="btn-cancel">
                    Làm mới
                </a>

            </form>

            <!-- BẢNG ĐƠN HÀNG -->
            <div class="table-responsive">

                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($danhSachDonHang) > 0): ?>

                            <?php foreach ($danhSachDonHang as $dh): ?>

                                <tr>
                                    <td>
                                        #<?= (int)$dh['MaDH'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($dh['HoTen']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($dh['SDT'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string)$dh['NgayDat']) ?>
                                    </td>

                                    <td class="price">
                                        <?= number_format((float)$dh['TongTien'], 0, ',', '.') ?> đ
                                    </td>

                                    <td>
                                        <span class="order-status">
                                            <?= htmlspecialchars($dh['TrangThai']) ?>
                                        </span>
                                    </td>

                                    <td class="order-actions">

                                        <a
                                            href="donhang.php?xem=<?= (int)$dh['MaDH'] ?>"
                                            class="btn-edit"
                                        >
                                            Chi tiết
                                        </a>

                                        <form method="POST" action="donhang.php">

                                            <input
                                                type="hidden"
                                                name="MaDH"
                                                value="<?= (int)$dh['MaDH'] ?>"
                                            >

                                            <select name="TrangThai" required>
                                                <?php foreach ($cacTrangThai as $tt): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($tt) ?>"
                                                        <?= $dh['TrangThai'] === $tt ? 'selected' : '' ?>
                                                    >
                                                        <?= htmlspecialchars($tt) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button type="submit" class="btn-primary">
                                                Cập nhật
                                            </button>

                                        </form>

                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty">
                                    Chưa có đơn hàng hoặc không tìm thấy kết quả.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>

        <footer class="footer">
            © 2026 StudyStore - Quản lý đơn hàng
        </footer>

    </main>

</div>

</body>
</html>

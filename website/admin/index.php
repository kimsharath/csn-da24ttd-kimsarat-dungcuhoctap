
<?php
// 1. Khởi tạo session
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

// 3. Kết nối database
require_once "../config/database.php";

$tenAdmin = $_SESSION['TenDN'] ?? 'Quản trị viên';

// 4. Khởi tạo dữ liệu thống kê
$tongSanPham = 0;
$tongDanhMuc = 0;
$tongKhachHang = 0;
$tongDonHang = 0;
$tongGiaTriDonHang = 0;
$donHangMoi = [];

$loi = "";

try {

    // Tổng sản phẩm
    $tongSanPham = (int)$conn->query(
        "SELECT COUNT(*) FROM sanpham"
    )->fetchColumn();

    // Tổng danh mục
    $tongDanhMuc = (int)$conn->query(
        "SELECT COUNT(*) FROM danhmuc"
    )->fetchColumn();

    // Tổng khách hàng
    $tongKhachHang = (int)$conn->query(
        "SELECT COUNT(*) FROM khachhang"
    )->fetchColumn();

    // Tổng đơn hàng
    $tongDonHang = (int)$conn->query(
        "SELECT COUNT(*) FROM donhang"
    )->fetchColumn();

    // Tổng giá trị đơn hàng
    $tongGiaTriDonHang = (float)$conn->query(
        "SELECT COALESCE(SUM(TongTien), 0) FROM donhang"
    )->fetchColumn();

    // 5 đơn hàng mới nhất
    $sql = "
        SELECT
            DH.MaDH,
            KH.HoTen,
            DH.NgayDat,
            DH.TongTien,
            DH.TrangThai
        FROM donhang AS DH
        INNER JOIN khachhang AS KH
            ON DH.MaKH = KH.MaKH
        ORDER BY DH.NgayDat DESC, DH.MaDH DESC
        LIMIT 5
    ";

    $stmt = $conn->query($sql);
    $donHangMoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $loi = "Không thể tải dữ liệu thống kê. Vui lòng kiểm tra kết nối database.";

}

// Hàm hiển thị dữ liệu an toàn
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Hàm định dạng trạng thái đơn hàng
function trangThaiClass($trangThai) {
    $trangThai = mb_strtolower(trim((string)$trangThai), 'UTF-8');

    if (str_contains($trangThai, 'hoàn thành') ||
        str_contains($trangThai, 'đã giao')) {
        return 'status-success';
    }

    if (str_contains($trangThai, 'hủy')) {
        return 'status-danger';
    }

    if (str_contains($trangThai, 'chờ')) {
        return 'status-warning';
    }

    return 'status-default';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tổng quan quản trị | StudyStore</title>

    <link rel="stylesheet" href="../css/tongquanadmin.css">
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

            <a href="index.php" class="active">
                <span class="menu-icon">▦</span>
                <span>Tổng quan</span>
            </a>

            <a href="sanpham.php">
                <span class="menu-icon">▣</span>
                <span>Quản lý sản phẩm</span>
            </a>

            <a href="danhmuc.php">
                <span class="menu-icon">▤</span>
                <span>Quản lý danh mục</span>
            </a>

            <a href="donhang.php">
                <span class="menu-icon">🛒</span>
                <span>Quản lý đơn hàng</span>
            </a>

            <a href="khachhang.php">
                <span class="menu-icon">♙</span>
                <span>Quản lý khách hàng</span>
            </a>

            <a href="quanlythanhtoan.php">
                <span class="menu-icon">▣</span>
                <span>Quản lý thanh toán</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../trangchu.php">
                <span class="menu-icon">🌐</span>
                <span>Xem website</span>
            </a>

            <a href="../dangxuat.php" class="logout-link">
                <span class="menu-icon">⇥</span>
                <span>Đăng xuất</span>
            </a>

        </div>

        <div class="sidebar-footer">
            <p>StudyStore © 2026</p>
            <span>Hệ thống quản lý cửa hàng</span>
        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="page-heading">
                <div class="breadcrumb">
                    Trang quản trị <span>/</span> Tổng quan
                </div>

                <h1>Tổng quan hệ thống</h1>

                <p>
                    Xin chào! Đây là tình hình hoạt động của cửa hàng StudyStore.
                </p>
            </div>

            <div class="admin-info">

                <div class="admin-avatar">
                    <?= e(mb_strtoupper(mb_substr($tenAdmin, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                </div>

                <div class="admin-text">
                    <strong><?= e($tenAdmin) ?></strong>
                    <span>Quản trị viên</span>
                </div>

            </div>

        </header>


        <!-- THÔNG BÁO LỖI -->
        <?php if ($loi !== ""): ?>

            <div class="error-message">
                <?= e($loi) ?>
            </div>

        <?php endif; ?>


        <!-- WELCOME BANNER -->
        <section class="welcome-banner">

            <div class="welcome-content">

                <span class="welcome-label">QUẢN TRỊ STUDYSTORE</span>

                <h2>Chào mừng trở lại, <?= e($tenAdmin) ?>!</h2>

                <p>
                    Theo dõi và quản lý hoạt động kinh doanh
                    của cửa hàng dụng cụ học tập tại đây.
                </p>

                <a href="sanpham.php" class="welcome-button">
                    Quản lý sản phẩm →
                </a>

            </div>

            <div class="welcome-decoration">
                <div class="decoration-circle circle-one"></div>
                <div class="decoration-circle circle-two"></div>
                <img
                    class="decoration-icon"
                    src="../../images/sach.jfif"
                    alt="Sách"
                >
            </div>

        </section>


        <!-- STATISTICS -->
        <section class="statistics">

            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon blue-icon">▣</div>
                    <span class="stat-tag">Sản phẩm</span>
                </div>

                <p>Tổng sản phẩm</p>

                <h2><?= number_format($tongSanPham) ?></h2>

                <a href="sanpham.php">Xem chi tiết →</a>

            </div>


            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon purple-icon">▤</div>
                    <span class="stat-tag">Danh mục</span>
                </div>

                <p>Tổng danh mục</p>

                <h2><?= number_format($tongDanhMuc) ?></h2>

                <a href="danhmuc.php">Xem chi tiết →</a>

            </div>


            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon green-icon">♙</div>
                    <span class="stat-tag">Khách hàng</span>
                </div>

                <p>Tổng khách hàng</p>

                <h2><?= number_format($tongKhachHang) ?></h2>

                <a href="khachhang.php">Xem chi tiết →</a>

            </div>


            <div class="stat-card">

                <div class="stat-top">
                    <div class="stat-icon orange-icon">🛒</div>
                    <span class="stat-tag">Đơn hàng</span>
                </div>

                <p>Tổng đơn hàng</p>

                <h2><?= number_format($tongDonHang) ?></h2>

                <a href="donhang.php">Xem chi tiết →</a>

            </div>

        </section>


        <!-- REVENUE -->
        <section class="revenue-card">

            <div class="revenue-left">

                <div class="revenue-icon">💰</div>

                <div>
                    <p>TỔNG GIÁ TRỊ ĐƠN HÀNG</p>

                    <h2>
                        <?= number_format($tongGiaTriDonHang, 0, ',', '.') ?> đ
                    </h2>

                    <span>
                        Tổng giá trị của tất cả đơn hàng trong hệ thống
                    </span>
                </div>

            </div>

            <a href="donhang.php" class="revenue-link">
                Xem đơn hàng →
            </a>

        </section>


        <!-- ORDER TABLE -->
        <section class="order-section">

            <div class="section-header">

                <div>
                    <h2>Đơn hàng mới nhất</h2>

                    <p>
                        Theo dõi 5 đơn hàng được đặt gần đây
                    </p>
                </div>

                <a href="donhang.php" class="view-all">
                    Xem tất cả đơn hàng →
                </a>

            </div>


            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>MÃ ĐƠN HÀNG</th>
                            <th>KHÁCH HÀNG</th>
                            <th>NGÀY ĐẶT</th>
                            <th>TỔNG TIỀN</th>
                            <th>TRẠNG THÁI</th>
                            <th>CHI TIẾT</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($donHangMoi) > 0): ?>

                            <?php foreach ($donHangMoi as $dh): ?>

                                <tr>

                                    <td>
                                        <strong class="order-code">
                                            #<?= (int)$dh['MaDH'] ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="customer-name">
                                            <div class="customer-avatar">
                                                <?= e(mb_strtoupper(mb_substr($dh['HoTen'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                            </div>

                                            <?= e($dh['HoTen']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= !empty($dh['NgayDat'])
                                            ? date('d/m/Y H:i', strtotime($dh['NgayDat']))
                                            : '—' ?>
                                    </td>

                                    <td class="price">
                                        <?= number_format((float)$dh['TongTien'], 0, ',', '.') ?> đ
                                    </td>

                                    <td>
                                        <span class="status <?= e(trangThaiClass($dh['TrangThai'])) ?>">
                                            <?= e($dh['TrangThai']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="donhang.php" class="detail-link">
                                            Xem →
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty">
                                    <div class="empty-icon">📦</div>
                                    <strong>Chưa có đơn hàng nào</strong>
                                    <p>Đơn hàng mới sẽ xuất hiện tại đây.</p>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="footer">
            <span>© 2026 StudyStore</span>
            <span>Hệ thống quản lý cửa hàng dụng cụ học tập</span>
        </footer>

    </main>

</div>

</body>
</html>

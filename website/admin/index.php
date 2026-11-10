
<?php
session_start();

require_once "../config/database.php";

// 1. Kiểm tra đăng nhập và quyền Admin
if (!isset($_SESSION['MaTK']) || ($_SESSION['VaiTro'] ?? '') !== 'Admin') {
    header("Location: ../dangnhap.php");
    exit;
}

$tenAdmin = $_SESSION['TenDN'] ?? 'Quản trị viên';

// 2. Khởi tạo số liệu
$tongSanPham = 0;
$tongDanhMuc = 0;
$tongKhachHang = 0;
$tongDonHang = 0;
$tongGiaTriDonHang = 0;
$donHangMoi = [];

try {
    // 3. Thống kê dữ liệu
    $tongSanPham = (int)$conn->query(
        "SELECT COUNT(*) FROM SanPham"
    )->fetchColumn();

    $tongDanhMuc = (int)$conn->query(
        "SELECT COUNT(*) FROM DanhMuc"
    )->fetchColumn();

    $tongKhachHang = (int)$conn->query(
        "SELECT COUNT(*) FROM KhachHang"
    )->fetchColumn();

    $tongDonHang = (int)$conn->query(
        "SELECT COUNT(*) FROM DonHang"
    )->fetchColumn();

    $tongGiaTriDonHang = (float)$conn->query(
        "SELECT COALESCE(SUM(TongTien), 0) FROM DonHang"
    )->fetchColumn();

    // 4. Lấy 5 đơn hàng mới nhất
    $sql = "
        SELECT TOP 5
            DH.MaDH,
            KH.HoTen,
            DH.NgayDat,
            DH.TongTien,
            DH.TrangThai
        FROM DonHang DH
        INNER JOIN KhachHang KH ON DH.MaKH = KH.MaKH
        ORDER BY DH.NgayDat DESC, DH.MaDH DESC
    ";

    $stmt = $conn->query($sql);
    $donHangMoi = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $loi = "Không thể tải dữ liệu thống kê: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - StudyStore</title>
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
            <a href="index.php" class="active">🏠 Tổng quan</a>
            <a href="sanpham.php">📦 Quản lý sản phẩm</a>
            <a href="danhmuc.php">📁 Quản lý danh mục</a>
            <a href="donhang.php">🛒 Quản lý đơn hàng</a>
            <a href="khachhang.php">👥 Quản lý khách hàng</a>
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
                <h1>Tổng quan</h1>
                <p>Chào mừng bạn đến với trang quản trị StudyStore.</p>
            </div>

            <div class="admin-info">
                <span>👤 <?= htmlspecialchars($tenAdmin) ?></span>
                <span class="admin-role">Quản trị viên</span>
            </div>
        </header>

        <?php if (isset($loi)): ?>
            <div class="error-message">
                <?= htmlspecialchars($loi) ?>
            </div>
        <?php endif; ?>

        <!-- CÁC THẺ THỐNG KÊ -->
        <section class="statistics">

            <div class="stat-card blue">
                <div>
                    <p>Tổng sản phẩm</p>
                    <h2><?= number_format($tongSanPham) ?></h2>
                </div>
                <span>📦</span>
            </div>

            <div class="stat-card purple">
                <div>
                    <p>Tổng danh mục</p>
                    <h2><?= number_format($tongDanhMuc) ?></h2>
                </div>
                <span>📁</span>
            </div>

            <div class="stat-card green">
                <div>
                    <p>Tổng khách hàng</p>
                    <h2><?= number_format($tongKhachHang) ?></h2>
                </div>
                <span>👥</span>
            </div>

            <div class="stat-card orange">
                <div>
                    <p>Tổng đơn hàng</p>
                    <h2><?= number_format($tongDonHang) ?></h2>
                </div>
                <span>🛒</span>
            </div>

        </section>

        <!-- TỔNG GIÁ TRỊ ĐƠN HÀNG -->
        <section class="revenue-card">
            <div>
                <p>Tổng giá trị các đơn hàng</p>
                <h2><?= number_format($tongGiaTriDonHang, 0, ',', '.') ?> đ</h2>
            </div>
            <span>💰</span>
        </section>

        <!-- DANH SÁCH ĐƠN HÀNG MỚI -->
        <section class="order-section">

            <div class="section-header">
                <div>
                    <h2>Đơn hàng mới nhất</h2>
                    <p>Danh sách 5 đơn hàng gần đây</p>
                </div>

                <a href="donhang.php" class="view-all">
                    Xem tất cả →
                </a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($donHangMoi) > 0): ?>

                            <?php foreach ($donHangMoi as $dh): ?>
                                <tr>
                                    <td>#<?= (int)$dh['MaDH'] ?></td>

                                    <td>
                                        <?= htmlspecialchars($dh['HoTen']) ?>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($dh['NgayDat'])) ?>
                                    </td>

                                    <td class="price">
                                        <?= number_format((float)$dh['TongTien'], 0, ',', '.') ?> đ
                                    </td>

                                    <td>
                                        <span class="status">
                                            <?= htmlspecialchars($dh['TrangThai']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty">
                                    Chưa có đơn hàng nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>

        <footer class="footer">
            © 2026 StudyStore - Hệ thống quản lý cửa hàng dụng cụ học tập
        </footer>

    </main>
</div>

</body>
</html>

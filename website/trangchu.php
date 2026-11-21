<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

// Hàm hiển thị dữ liệu an toàn
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function anhTrangChu($duongDan) {
    $duongDan = trim((string)$duongDan);

    if ($duongDan === '' || preg_match('/^https?:\/\//i', $duongDan)) {
        return $duongDan;
    }

    $duongDan = ltrim($duongDan, '/');
    if (strpos($duongDan, 'images/') === 0) {
        return '../' . $duongDan;
    }

    return $duongDan;
}

function anhDanhMuc($tenDanhMuc, $viTri) {
    $anhMacDinh = ['but.jfif', 'gom.jfif', 'ho.jfif', 'sach.jfif', 'thuoc.jfif', 'balo.jfif'];
    $tuKhoaAnh = [
        'bút' => 'but.jfif',
        'gôm' => 'gom.jfif',
        'hồ dán' => 'ho.jfif',
        'keo dán' => 'ho.jfif',
        'keo' => 'ho.jfif',
        'hoa' => 'ho.jfif',
        'sách' => 'sach.jfif',
        'thước' => 'thuoc.jfif',
        'balo' => 'balo.jfif',
        'ba lô' => 'balo.jfif'
    ];
    $tenDanhMuc = mb_strtolower((string)$tenDanhMuc, 'UTF-8');

    foreach ($tuKhoaAnh as $tuKhoa => $tenAnh) {
        if (mb_strpos($tenDanhMuc, $tuKhoa, 0, 'UTF-8') !== false) {
            return '../images/' . $tenAnh;
        }
    }

    return '../images/' . $anhMacDinh[$viTri % count($anhMacDinh)];
}

$danhMuc = [];
$sanPham = [];
$loi = null;

try {
    // Danh mục kèm số lượng sản phẩm
    $sqlDanhMuc = "
        SELECT DM.MaDM, DM.TenDM, COUNT(SP.MaSP) AS SoSP
        FROM DanhMuc DM
        LEFT JOIN SanPham SP ON SP.MaDM = DM.MaDM
        GROUP BY DM.MaDM, DM.TenDM
        ORDER BY DM.MaDM DESC
    ";
    $danhMuc = $conn->query($sqlDanhMuc)->fetchAll(PDO::FETCH_ASSOC);

    // 8 sản phẩm mới nhất cho trang chủ
    $sqlSanPham = "
        SELECT SP.MaSP, SP.TenSP, SP.DonGia, SP.SoLuongTon, SP.HinhAnh, DM.TenDM
        FROM SanPham SP
        INNER JOIN DanhMuc DM ON SP.MaDM = DM.MaDM
        ORDER BY SP.MaSP DESC
        LIMIT 8
    ";
    $sanPham = $conn->query($sqlSanPham)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $loi = "Không thể tải dữ liệu sản phẩm. Vui lòng thử lại sau.";
}

$daDangNhap   = isset($_SESSION['MaTK']);
$laAdmin      = $daDangNhap && ($_SESSION['VaiTro'] ?? '') === 'Admin';
$tenNguoiDung = $_SESSION['TenDN'] ?? 'Người dùng';

// Số mặt hàng trong giỏ (đổi 'GioHang' cho đúng tên session của bạn)
$soGioHang = (isset($_SESSION['GioHang']) && is_array($_SESSION['GioHang']))
    ? count($_SESSION['GioHang'])
    : 0;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyStore - Dụng cụ học tập cho học sinh, sinh viên</title>
    <link rel="stylesheet" href="css/trangchu.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="container header-inner">

        <a href="trangchu.php" class="logo" aria-label="StudyStore - Trang chủ">
            <span class="logo-mark">S</span>
            <span class="logo-name">StudyStore</span>
        </a>

        <form class="search" action="sanpham.php" method="get" role="search">
            <input type="search" name="q" placeholder="Tìm bút, vở, balo, thước kẻ..."
                   aria-label="Tìm kiếm sản phẩm">
            <button type="submit">Tìm kiếm</button>
        </form>

        <div class="header-actions">
            <?php if ($daDangNhap): ?>
                <div class="account">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($tenNguoiDung, 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                    <span class="account-text">
                        <small>Xin chào</small>
                        <strong><?= e($tenNguoiDung) ?></strong>
                    </span>
                </div>
                <?php if ($laAdmin): ?>
                    <a href="admin/index.php" class="btn btn-soft">Quản trị</a>
                <?php endif; ?>
                <a href="dangxuat.php" class="btn btn-outline">Đăng xuất</a>
            <?php else: ?>
                <a href="dangnhap.php" class="btn btn-outline">Đăng nhập</a>
                <a href="dangky.php" class="btn btn-primary">Đăng ký</a>
            <?php endif; ?>

            <a href="giohang.php" class="cart" aria-label="Giỏ hàng">
                <span class="cart-icon">🛒</span>
                <span class="cart-label">Giỏ hàng</span>
                <?php if ($soGioHang > 0): ?>
                    <span class="cart-count"><?= (int)$soGioHang ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- MENU DANH MỤC -->
    <nav class="menu" aria-label="Danh mục">
        <div class="container menu-inner">
            <a href="trangchu.php" class="active">Trang chủ</a>
            <a href="sanpham.php">Tất cả sản phẩm</a>
            <?php foreach (array_slice($danhMuc, 0, 5) as $dm): ?>
                <a href="sanpham.php?madm=<?= (int)$dm['MaDM'] ?>"><?= e($dm['TenDM']) ?></a>
            <?php endforeach; ?>
            <a href="#lienhe">Liên hệ</a>
        </div>
    </nav>
</header>

<main>

    <!-- BANNER -->
    <section class="container hero">
        <div class="hero-main">
            <p class="hero-tag">Mùa tựu trường</p>
            <h1>Đầy đủ dụng cụ,<br>sẵn sàng vào năm học mới</h1>
            <p class="hero-desc">
                Bút, vở, balo, máy tính và nhiều hơn nữa, giá hợp lý cho học sinh và sinh viên.
            </p>
            <div class="hero-buttons">
                <a href="sanpham.php" class="btn btn-accent btn-lg">Mua sắm ngay</a>
                <a href="#danhmuc" class="btn btn-ghost btn-lg">Xem danh mục</a>
            </div>
            <img class="hero-photo" src="../images/balo.jfif" alt="" aria-hidden="true">
        </div>

        <div class="hero-side">
            <a href="sanpham.php" class="promo promo-yellow">
                <strong>Hàng mới về</strong>
                <span>Xem sản phẩm vừa cập nhật</span>
                <img class="promo-image" src="../images/but.jfif" alt="" aria-hidden="true">
            </a>
            <a href="sanpham.php" class="promo promo-blue">
                <strong>Giá cho học sinh</strong>
                <span>Tiết kiệm cho cả năm học</span>
                <img class="promo-image" src="../images/sach.jfif" alt="" aria-hidden="true">
            </a>
        </div>
    </section>

    <!-- DANH MỤC -->
    <section class="container block" id="danhmuc">
        <div class="block-head">
            <h2>Mua theo danh mục</h2>
            <a href="sanpham.php" class="link-more">Xem tất cả</a>
        </div>

        <?php if (!empty($danhMuc)): ?>
            <div class="cat-grid">
                <?php foreach ($danhMuc as $i => $dm): ?>
                    <a href="sanpham.php?madm=<?= (int)$dm['MaDM'] ?>" class="cat-card">
                        <img
                            class="cat-icon"
                            src="<?= e(anhDanhMuc($dm['TenDM'], $i)) ?>"
                            alt=""
                            loading="lazy"
                        >
                        <strong><?= e($dm['TenDM']) ?></strong>
                        <small><?= (int)$dm['SoSP'] ?> sản phẩm</small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">Chưa có danh mục nào.</p>
        <?php endif; ?>
    </section>

    <!-- SẢN PHẨM MỚI -->
    <section class="container block" id="sanpham">
        <div class="block-head">
            <h2>Sản phẩm mới</h2>
            <a href="sanpham.php" class="link-more">Xem tất cả</a>
        </div>

        <?php if ($loi !== null): ?>
            <p class="empty"><?= e($loi) ?></p>

        <?php elseif (!empty($sanPham)): ?>
            <div class="product-grid">
                <?php foreach ($sanPham as $sp):
                    $ton  = (int)$sp['SoLuongTon'];
                    $het  = $ton <= 0;
                    $sap  = !$het && $ton <= 5;
                    $link = 'chitietsanpham.php?masp=' . (int)$sp['MaSP'];
                ?>
                    <article class="card <?= $het ? 'is-out' : '' ?>">
                        <a href="<?= $link ?>" class="card-media">
                            <?php if (!empty($sp['HinhAnh'])): ?>
                                <img src="<?= e(anhTrangChu($sp['HinhAnh'])) ?>" alt="<?= e($sp['TenSP']) ?>" loading="lazy">
                            <?php else: ?>
                                <img
                                    class="card-noimg"
                                    src="<?= e(anhDanhMuc($sp['TenSP'] . ' ' . $sp['TenDM'], 0)) ?>"
                                    alt=""
                                    loading="lazy"
                                >
                            <?php endif; ?>

                            <?php if ($het): ?>
                                <span class="tag tag-out">Hết hàng</span>
                            <?php elseif ($sap): ?>
                                <span class="tag tag-low">Sắp hết</span>
                            <?php endif; ?>
                        </a>

                        <div class="card-body">
                            <p class="card-cat"><?= e($sp['TenDM']) ?></p>
                            <h3 class="card-title"><a href="<?= $link ?>"><?= e($sp['TenSP']) ?></a></h3>

                            <p class="card-price">
                                <?= number_format((float)$sp['DonGia'], 0, ',', '.') ?><span>₫</span>
                            </p>

                            <div class="card-foot">
                                <small class="card-stock">
                                    <?= $het ? 'Tạm hết hàng' : 'Còn ' . $ton . ' sản phẩm' ?>
                                </small>
                                <a href="<?= $link ?>" class="btn btn-primary btn-sm">
                                    <?= $het ? 'Xem chi tiết' : 'Mua ngay' ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="center">
                <a href="sanpham.php" class="btn btn-outline btn-lg">Xem thêm sản phẩm</a>
            </div>

        <?php else: ?>
            <p class="empty">Cửa hàng chưa có sản phẩm nào.</p>
        <?php endif; ?>
    </section>

    <!-- BANNER CUỐI -->
    <section class="container block">
        <div class="cta">
            <div>
                <h2>Chưa có tài khoản?</h2>
                <p>Đăng ký để đặt hàng nhanh hơn và theo dõi đơn của bạn.</p>
            </div>
            <?php if (!$daDangNhap): ?>
                <a href="dangky.php" class="btn btn-accent btn-lg">Đăng ký miễn phí</a>
            <?php else: ?>
                <a href="sanpham.php" class="btn btn-accent btn-lg">Tiếp tục mua sắm</a>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- FOOTER -->
<footer class="footer" id="lienhe">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="trangchu.php" class="logo logo-light">
                <span class="logo-mark">S</span>
                <span class="logo-name">StudyStore</span>
            </a>
            <p>Cửa hàng dụng cụ học tập dành cho học sinh, sinh viên và những người yêu thích học tập.</p>
        </div>

        <div>
            <h3>Liên hệ</h3>
            <ul>
                <li>Điện thoại: 0358564323</li>
                <li>Email: kimsarat0354399075@gmail.com</li>
                <li>Địa chỉ: Vĩnh Long, Việt Nam</li>
            </ul>
        </div>

        <div>
            <h3>Mua sắm</h3>
            <ul>
                <li><a href="sanpham.php">Tất cả sản phẩm</a></li>
                <li><a href="giohang.php">Giỏ hàng</a></li>
                <li><a href="dangnhap.php">Tài khoản</a></li>
            </ul>
        </div>

        <div>
            <h3>Hỗ trợ</h3>
            <ul>
                <li><a href="#">Chính sách mua hàng</a></li>
                <li><a href="#">Chính sách đổi trả</a></li>
                <li><a href="#">Hướng dẫn mua hàng</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>© 2026 StudyStore - Đồ án cơ sở ngành</p>
        </div>
    </div>
</footer>

</body>
</html>
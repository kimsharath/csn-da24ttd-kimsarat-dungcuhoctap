<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Tạo link giữ nguyên bộ lọc hiện tại, chỉ đổi phần cần thay
function taoLink(array $base, array $them = []) {
    $p = array_merge($base, $them);
    $p = array_filter($p, function ($v) {
        return $v !== '' && $v !== 0 && $v !== null && $v !== false;
    });
    return 'sanpham.php' . ($p ? '?' . http_build_query($p) : '');
}

// 1. Tham số lọc / sắp xếp
$tuKhoa  = trim($_GET['q'] ?? '');
$maDM    = isset($_GET['madm']) ? (int)$_GET['madm'] : 0;
$conHang = ($_GET['conhang'] ?? '') === '1';

$cachSapXep = [
    'moi'     => ['label' => 'Mới nhất',        'sql' => 'SP.MaSP DESC'],
    'giatang' => ['label' => 'Giá thấp đến cao', 'sql' => 'SP.DonGia ASC'],
    'giagiam' => ['label' => 'Giá cao đến thấp', 'sql' => 'SP.DonGia DESC'],
    'ten'     => ['label' => 'Tên A - Z',        'sql' => 'SP.TenSP ASC'],
];
$sapXep = $_GET['sapxep'] ?? 'moi';
if (!isset($cachSapXep[$sapXep])) {
    $sapXep = 'moi';
}

$baseParams = [
    'q'       => $tuKhoa,
    'madm'    => $maDM,
    'sapxep'  => $sapXep === 'moi' ? '' : $sapXep,
    'conhang' => $conHang ? 1 : '',
];

$danhMuc = [];
$sanPham = [];
$tongSP  = 0;
$loi     = null;
$tenNguoiDung = ($_SESSION['VaiTro'] ?? '') === 'KhachHang'
    ? 'Khách hàng'
    : ($_SESSION['TenDN'] ?? 'Người dùng');

try {
    // 2. Danh mục kèm số sản phẩm
    $danhMuc = $conn->query("
        SELECT DM.MaDM, DM.TenDM, COUNT(SP.MaSP) AS SoSP
        FROM DanhMuc DM
        LEFT JOIN SanPham SP ON SP.MaDM = DM.MaDM
        GROUP BY DM.MaDM, DM.TenDM
        ORDER BY DM.MaDM
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($danhMuc as $dm) {
        $tongSP += (int)$dm['SoSP'];
    }

    // 3. Truy vấn sản phẩm
    $sql = "
        SELECT SP.MaSP, SP.TenSP, SP.DonGia, SP.SoLuongTon, SP.HinhAnh, DM.TenDM
        FROM SanPham AS SP
        INNER JOIN DanhMuc AS DM ON SP.MaDM = DM.MaDM
        WHERE 1 = 1
    ";
    $params = [];

    if ($tuKhoa !== '') {
        $sql .= " AND SP.TenSP LIKE :tukhoa";
        $params[':tukhoa'] = '%' . $tuKhoa . '%';
    }
    if ($maDM > 0) {
        $sql .= " AND SP.MaDM = :madm";
        $params[':madm'] = $maDM;
    }
    if ($conHang) {
        $sql .= " AND SP.SoLuongTon > 0";
    }

    $sql .= " ORDER BY " . $cachSapXep[$sapXep]['sql'];   // giá trị lấy từ danh sách cố định

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $sanPham = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($_SESSION['MaKH'])) {
        $stmtTenKH = $conn->prepare("
            SELECT HoTen
            FROM KhachHang
            WHERE MaKH = :makh
        ");
        $stmtTenKH->execute([':makh' => (int)$_SESSION['MaKH']]);
        $hoTenKhachHang = $stmtTenKH->fetchColumn();

        if (is_string($hoTenKhachHang) && trim($hoTenKhachHang) !== '') {
            $tenNguoiDung = trim($hoTenKhachHang);
        }
    }

} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $loi = "Không thể tải dữ liệu sản phẩm. Vui lòng thử lại sau.";
}

// Tên danh mục đang chọn
$tenDMChon = '';
foreach ($danhMuc as $dm) {
    if ((int)$dm['MaDM'] === $maDM) {
        $tenDMChon = $dm['TenDM'];
    }
}

// 4. Tài khoản, giỏ hàng
$daDangNhap   = isset($_SESSION['MaTK']);
$laAdmin      = $daDangNhap && ($_SESSION['VaiTro'] ?? '') === 'Admin';

// Đổi 'GioHang' cho đúng tên session giỏ hàng của bạn
$soGioHang = (isset($_SESSION['GioHang']) && is_array($_SESSION['GioHang']))
    ? count($_SESSION['GioHang'])
    : 0;

$coBoLoc = ($tuKhoa !== '' || $maDM > 0 || $conHang);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - StudyStore</title>

    <link rel="stylesheet" href="css/trangchu.css">
    <link rel="stylesheet" href="css/danhsachsanpham.css">
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
            <input type="search" name="q" value="<?= e($tuKhoa) ?>"
                   placeholder="Tìm bút, vở, balo, thước kẻ..."
                   aria-label="Tìm kiếm sản phẩm">
            <?php if ($maDM > 0): ?>
                <input type="hidden" name="madm" value="<?= $maDM ?>">
            <?php endif; ?>
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

    <nav class="menu" aria-label="Danh mục">
        <div class="container menu-inner">
            <a href="trangchu.php">Trang chủ</a>
            <a href="sanpham.php" class="active">Tất cả sản phẩm</a>
            <?php foreach (array_slice($danhMuc, 0, 5) as $dm): ?>
                <a href="sanpham.php?madm=<?= (int)$dm['MaDM'] ?>"><?= e($dm['TenDM']) ?></a>
            <?php endforeach; ?>
            <a href="#lienhe">Liên hệ</a>
        </div>
    </nav>
</header>

<main class="container">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-head">
        <nav class="breadcrumb" aria-label="Đường dẫn">
            <a href="trangchu.php">Trang chủ</a>
            <span>/</span>
            <?php if ($tenDMChon !== ''): ?>
                <a href="sanpham.php">Sản phẩm</a>
                <span>/</span>
                <strong><?= e($tenDMChon) ?></strong>
            <?php else: ?>
                <strong>Sản phẩm</strong>
            <?php endif; ?>
        </nav>
        <h1><?= $tenDMChon !== '' ? e($tenDMChon) : 'Tất cả sản phẩm' ?></h1>
    </div>

    <div class="shop">

        <!-- SIDEBAR -->
        <aside class="sidebar" aria-label="Bộ lọc">
            <h2>Danh mục</h2>
            <ul class="filter-list">
                <li>
                    <a href="<?= e(taoLink($baseParams, ['madm' => 0])) ?>"
                       class="<?= $maDM === 0 ? 'selected' : '' ?>">
                        <span>Tất cả</span>
                        <small><?= (int)$tongSP ?></small>
                    </a>
                </li>
                <?php foreach ($danhMuc as $dm): ?>
                    <li>
                        <a href="<?= e(taoLink($baseParams, ['madm' => (int)$dm['MaDM']])) ?>"
                           class="<?= $maDM === (int)$dm['MaDM'] ? 'selected' : '' ?>">
                            <span><?= e($dm['TenDM']) ?></span>
                            <small><?= (int)$dm['SoSP'] ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- DANH SÁCH -->
        <section class="listing">

            <!-- THANH CÔNG CỤ -->
            <form method="get" action="sanpham.php" class="toolbar">
                <p class="result-count">
                    <strong><?= count($sanPham) ?></strong> sản phẩm
                    <?php if ($tuKhoa !== ''): ?>
                        cho "<?= e($tuKhoa) ?>"
                    <?php endif; ?>
                </p>

                <?php if ($tuKhoa !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($tuKhoa) ?>">
                <?php endif; ?>
                <?php if ($maDM > 0): ?>
                    <input type="hidden" name="madm" value="<?= $maDM ?>">
                <?php endif; ?>

                <label class="check">
                    <input type="checkbox" name="conhang" value="1"
                           <?= $conHang ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    Chỉ hiện hàng còn
                </label>

                <label class="sort">
                    <span>Sắp xếp</span>
                    <select name="sapxep" onchange="this.form.submit()">
                        <?php foreach ($cachSapXep as $khoa => $cs): ?>
                            <option value="<?= e($khoa) ?>" <?= $sapXep === $khoa ? 'selected' : '' ?>>
                                <?= e($cs['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <noscript><button type="submit" class="btn btn-primary btn-sm">Áp dụng</button></noscript>
            </form>

            <!-- BỘ LỌC ĐANG DÙNG -->
            <?php if ($coBoLoc): ?>
                <div class="chips">
                    <?php if ($tuKhoa !== ''): ?>
                        <a class="chip" href="<?= e(taoLink($baseParams, ['q' => ''])) ?>">
                            Từ khóa: <?= e($tuKhoa) ?> <b aria-label="Bỏ lọc">×</b>
                        </a>
                    <?php endif; ?>
                    <?php if ($tenDMChon !== ''): ?>
                        <a class="chip" href="<?= e(taoLink($baseParams, ['madm' => 0])) ?>">
                            <?= e($tenDMChon) ?> <b aria-label="Bỏ lọc">×</b>
                        </a>
                    <?php endif; ?>
                    <?php if ($conHang): ?>
                        <a class="chip" href="<?= e(taoLink($baseParams, ['conhang' => ''])) ?>">
                            Còn hàng <b aria-label="Bỏ lọc">×</b>
                        </a>
                    <?php endif; ?>
                    <a class="chip-clear" href="sanpham.php">Xóa tất cả</a>
                </div>
            <?php endif; ?>

            <!-- LƯỚI SẢN PHẨM -->
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
                                    <img src="<?= e($sp['HinhAnh']) ?>" alt="<?= e($sp['TenSP']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="card-noimg">📦</span>
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

                                <p class="card-stock">
                                    <?= $het ? 'Tạm hết hàng' : 'Còn ' . $ton . ' sản phẩm' ?>
                                </p>

                                <div class="card-actions">
                                    <?php if (!$het): ?>
                                        <form method="post" action="giohang.php">
                                            <input type="hidden" name="hanhDong" value="them">
                                            <input type="hidden" name="MaSP" value="<?= (int)$sp['MaSP'] ?>">
                                            <input type="hidden" name="SL" value="1">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                Thêm vào giỏ hàng
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-disabled btn-block" disabled>
                                            Hết hàng
                                        </button>
                                    <?php endif; ?>

                                    <a href="<?= $link ?>" class="detail-link">Xem chi tiết</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <div class="no-result">
                    <span aria-hidden="true">🔍</span>
                    <h3>Không tìm thấy sản phẩm phù hợp</h3>
                    <p>Hãy thử từ khóa khác, bỏ bớt bộ lọc hoặc chọn danh mục khác.</p>
                    <a href="sanpham.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
                </div>

            <?php endif; ?>

        </section>
    </div>
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
<?php
// 1. Khởi tạo session và kết nối database
require_once __DIR__ . "/taoadmind.php";
require_once __DIR__ . "/../config/database.php";

// 2. Kiểm tra quyền Admin
if (
    !isset($_SESSION['MaTK']) ||
    ($_SESSION['VaiTro'] ?? '') !== 'Admin'
) {
    header("Location: ../dangnhap.php");
    exit;
}

$tenAdmin = $_SESSION['TenDN'] ?? 'Admin';

$thongBao = "";
$loi = "";
$sanPhamSua = null;

// Hàm xuất dữ liệu an toàn
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Hàm xử lý đường dẫn hình ảnh
function duongDanAnh($hinhAnh) {
    $hinhAnh = trim((string)$hinhAnh);

    if ($hinhAnh === '') {
        return '';
    }

    // Nếu là đường dẫn ảnh trực tuyến
    if (preg_match('/^https?:\/\//i', $hinhAnh)) {
        return $hinhAnh;
    }

    // Chuẩn hóa đường dẫn tương đối từ thư mục website
    $hinhAnh = preg_replace('#^(\.\./)+#', '', $hinhAnh);

    return '../' . ltrim($hinhAnh, '/');
}


// 3. Xử lý thêm, sửa, xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hanhDong = $_POST['hanhDong'] ?? '';

    try {

        // =========================
        // XÓA SẢN PHẨM
        // =========================
        if ($hanhDong === 'xoa') {

            $maSP = (int)($_POST['MaSP'] ?? 0);

            if ($maSP <= 0) {
                throw new Exception("Mã sản phẩm không hợp lệ!");
            }

            $conn->beginTransaction();

            // Kiểm tra sản phẩm đã được đặt hàng chưa
            $sqlKiemTra = "
                SELECT COUNT(*)
                FROM ChiTietDonHang
                WHERE MaSP = :masp
            ";

            $stmt = $conn->prepare($sqlKiemTra);
            $stmt->execute([':masp' => $maSP]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception(
                    "Không thể xóa sản phẩm vì sản phẩm đã có trong đơn hàng!"
                );
            }

            // Xóa sản phẩm khỏi chi tiết giỏ hàng
            $sqlXoaGioHang = "
                DELETE FROM ChiTietGioHang
                WHERE MaSP = :masp
            ";

            $stmt = $conn->prepare($sqlXoaGioHang);
            $stmt->execute([':masp' => $maSP]);

            // Xóa sản phẩm
            $sqlXoaSP = "
                DELETE FROM SanPham
                WHERE MaSP = :masp
            ";

            $stmt = $conn->prepare($sqlXoaSP);
            $stmt->execute([':masp' => $maSP]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Không tìm thấy sản phẩm cần xóa!");
            }

            $conn->commit();

            header("Location: sanpham.php?msg=xoa");
            exit;
        }


        // =========================
        // LẤY DỮ LIỆU FORM
        // =========================
        if ($hanhDong === 'them' || $hanhDong === 'sua') {

            $tenSP = trim($_POST['TenSP'] ?? '');
            $maDM = (int)($_POST['MaDM'] ?? 0);
            $donGia = (float)($_POST['DonGia'] ?? 0);
            $soLuong = (int)($_POST['SoLuongTon'] ?? 0);
            $hinhAnh = trim($_POST['HinhAnh'] ?? '');
            $moTa = trim($_POST['MoTa'] ?? '');

            if (
                $tenSP === '' ||
                $maDM <= 0 ||
                $donGia < 0 ||
                $soLuong < 0
            ) {
                throw new Exception(
                    "Vui lòng nhập đầy đủ và đúng thông tin sản phẩm!"
                );
            }

            // Kiểm tra danh mục có tồn tại không
            $stmtDMCheck = $conn->prepare(
                "SELECT COUNT(*) FROM DanhMuc WHERE MaDM = :madm"
            );

            $stmtDMCheck->execute([':madm' => $maDM]);

            if ((int)$stmtDMCheck->fetchColumn() === 0) {
                throw new Exception("Danh mục được chọn không tồn tại!");
            }


            // =========================
            // THÊM SẢN PHẨM
            // =========================
            if ($hanhDong === 'them') {

                $sql = "
                    INSERT INTO SanPham
                    (
                        TenSP,
                        MaDM,
                        DonGia,
                        SoLuongTon,
                        HinhAnh,
                        MoTa,
                        NgayTao
                    )
                    VALUES
                    (
                        :tensp,
                        :madm,
                        :dongia,
                        :soluong,
                        :hinhanh,
                        :mota,
                        NOW()
                    )
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ':tensp' => $tenSP,
                    ':madm' => $maDM,
                    ':dongia' => $donGia,
                    ':soluong' => $soLuong,
                    ':hinhanh' => $hinhAnh !== '' ? $hinhAnh : null,
                    ':mota' => $moTa !== '' ? $moTa : null
                ]);

                header("Location: sanpham.php?msg=them");
                exit;
            }


            // =========================
            // SỬA SẢN PHẨM
            // =========================
            if ($hanhDong === 'sua') {

                $maSP = (int)($_POST['MaSP'] ?? 0);

                if ($maSP <= 0) {
                    throw new Exception("Mã sản phẩm không hợp lệ!");
                }

                $sql = "
                    UPDATE SanPham
                    SET
                        TenSP = :tensp,
                        MaDM = :madm,
                        DonGia = :dongia,
                        SoLuongTon = :soluong,
                        HinhAnh = :hinhanh,
                        MoTa = :mota
                    WHERE MaSP = :masp
                ";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ':tensp' => $tenSP,
                    ':madm' => $maDM,
                    ':dongia' => $donGia,
                    ':soluong' => $soLuong,
                    ':hinhanh' => $hinhAnh !== '' ? $hinhAnh : null,
                    ':mota' => $moTa !== '' ? $moTa : null,
                    ':masp' => $maSP
                ]);

                header("Location: sanpham.php?msg=sua");
                exit;
            }
        }

        throw new Exception("Thao tác không hợp lệ!");

    } catch (Throwable $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $loi = $e instanceof PDOException
            ? "Lỗi cơ sở dữ liệu: " . $e->getMessage()
            : $e->getMessage();
    }
}


// 4. Thông báo sau khi thêm, sửa, xóa
if (isset($_GET['msg'])) {

    if ($_GET['msg'] === 'them') {
        $thongBao = "Thêm sản phẩm thành công!";
    } elseif ($_GET['msg'] === 'sua') {
        $thongBao = "Cập nhật sản phẩm thành công!";
    } elseif ($_GET['msg'] === 'xoa') {
        $thongBao = "Xóa sản phẩm thành công!";
    }
}


// 5. Lấy danh sách danh mục
$stmtDM = $conn->query("
    SELECT MaDM, TenDM
    FROM DanhMuc
    ORDER BY MaDM
");

$danhMuc = $stmtDM->fetchAll(PDO::FETCH_ASSOC);


// 6. Lấy sản phẩm cần sửa
if (isset($_GET['sua'])) {

    $maSP = (int)$_GET['sua'];

    $stmtSua = $conn->prepare("
        SELECT *
        FROM SanPham
        WHERE MaSP = :masp
    ");

    $stmtSua->execute([':masp' => $maSP]);

    $sanPhamSua = $stmtSua->fetch(PDO::FETCH_ASSOC);

    if (!$sanPhamSua) {
        $loi = "Không tìm thấy sản phẩm cần chỉnh sửa!";
    }
}

$dangSua = is_array($sanPhamSua);


// 7. Tìm kiếm sản phẩm
$tuKhoa = trim($_GET['timkiem'] ?? '');

$sqlSP = "
    SELECT
        SP.MaSP,
        SP.TenSP,
        SP.DonGia,
        SP.SoLuongTon,
        SP.HinhAnh,
        DM.TenDM
    FROM SanPham AS SP
    INNER JOIN DanhMuc AS DM
        ON SP.MaDM = DM.MaDM
";

if ($tuKhoa !== '') {
    $sqlSP .= " WHERE SP.TenSP LIKE :tukhoa";
}

$sqlSP .= " ORDER BY SP.MaSP DESC";

$stmtSP = $conn->prepare($sqlSP);

if ($tuKhoa !== '') {
    $stmtSP->execute([
        ':tukhoa' => '%' . $tuKhoa . '%'
    ]);
} else {
    $stmtSP->execute();
}

$sanPham = $stmtSP->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý sản phẩm | StudyStore</title>

    <link rel="stylesheet" href="/StudyStore/website/css/sanpham1.css">
</head>

<body>

<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            <div class="logo-icon">S</div>

            <div>
                <h2>StudyStore</h2>
                <p>ADMINISTRATION</p>
            </div>
        </div>

        <div class="menu-label">QUẢN LÝ HỆ THỐNG</div>

        <nav class="menu">

            <a href="index.php">
                <span class="menu-icon">▦</span>
                <span>Tổng quan</span>
            </a>

            <a href="sanpham.php" class="active">
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

            <a href="../test_products.php">
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
                    Trang quản trị <span>/</span> Sản phẩm
                </div>

                <h1>Quản lý sản phẩm</h1>

                <p>
                    Quản lý thông tin, giá bán và số lượng tồn kho sản phẩm.
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


        <!-- THÔNG BÁO -->
        <?php if ($thongBao !== ''): ?>
            <div class="success-message">
                <span>✓</span>
                <?= e($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($loi !== ''): ?>
            <div class="error-message">
                <?= e($loi) ?>
            </div>
        <?php endif; ?>


        <!-- TIÊU ĐỀ TRANG -->
        <section class="product-page-banner">

            <div>
                <span class="product-banner-label">PRODUCT MANAGEMENT</span>
                <h2>Danh sách sản phẩm cửa hàng</h2>
                <p>
                    Thêm mới, cập nhật và quản lý các mặt hàng StudyStore.
                </p>
            </div>

            <div class="product-banner-icon">📦</div>

        </section>


        <!-- FORM THÊM / SỬA -->
        <section class="product-form-section">

            <div class="product-section-heading">

                <div>
                    <h2>
                        <?= $dangSua ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?>
                    </h2>

                    <p>
                        <?= $dangSua
                            ? 'Cập nhật thông tin sản phẩm trong cửa hàng.'
                            : 'Nhập thông tin để thêm sản phẩm vào hệ thống.' ?>
                    </p>
                </div>

                <span class="form-heading-icon">
                    <?= $dangSua ? '✎' : '+' ?>
                </span>

            </div>


            <form method="POST" action="sanpham.php" class="product-form">

                <input
                    type="hidden"
                    name="hanhDong"
                    value="<?= $dangSua ? 'sua' : 'them' ?>"
                >

                <?php if ($dangSua): ?>
                    <input
                        type="hidden"
                        name="MaSP"
                        value="<?= (int)$sanPhamSua['MaSP'] ?>"
                    >
                <?php endif; ?>


                <div class="form-group">

                    <label for="TenSP">Tên sản phẩm <span>*</span></label>

                    <input
                        type="text"
                        id="TenSP"
                        name="TenSP"
                        placeholder="Nhập tên sản phẩm"
                        required
                        value="<?= e($sanPhamSua['TenSP'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="MaDM">Danh mục <span>*</span></label>

                    <select id="MaDM" name="MaDM" required>

                        <option value="">-- Chọn danh mục --</option>

                        <?php foreach ($danhMuc as $dm): ?>

                            <option
                                value="<?= (int)$dm['MaDM'] ?>"
                                <?= isset($sanPhamSua['MaDM']) &&
                                    (int)$sanPhamSua['MaDM'] === (int)$dm['MaDM']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($dm['TenDM']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="DonGia">Đơn giá (VNĐ) <span>*</span></label>

                    <input
                        type="number"
                        id="DonGia"
                        name="DonGia"
                        min="0"
                        step="1000"
                        placeholder="Ví dụ: 15000"
                        required
                        value="<?= e($sanPhamSua['DonGia'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="SoLuongTon">Số lượng tồn kho <span>*</span></label>

                    <input
                        type="number"
                        id="SoLuongTon"
                        name="SoLuongTon"
                        min="0"
                        placeholder="Nhập số lượng"
                        required
                        value="<?= e($sanPhamSua['SoLuongTon'] ?? '') ?>"
                    >

                </div>


                <div class="form-group full-width">

                    <label for="HinhAnh">Đường dẫn hình ảnh</label>

                    <input
                        type="text"
                        id="HinhAnh"
                        name="HinhAnh"
                        placeholder="Ví dụ: images/but-bi.jpg"
                        value="<?= e($sanPhamSua['HinhAnh'] ?? '') ?>"
                    >

                    <small>
                        Nhập đường dẫn ảnh tính từ thư mục website, ví dụ:
                        images/but-bi.jpg
                    </small>

                    <?php if (!empty($sanPhamSua['HinhAnh'])): ?>
                        <div class="current-image">
                            <span>Ảnh hiện tại:</span>

                            <img
                                src="<?= e(duongDanAnh($sanPhamSua['HinhAnh'])) ?>"
                                alt="Ảnh sản phẩm hiện tại"
                            >
                        </div>
                    <?php endif; ?>

                </div>


                <div class="form-group full-width">

                    <label for="MoTa">Mô tả sản phẩm</label>

                    <textarea
                        id="MoTa"
                        name="MoTa"
                        rows="4"
                        placeholder="Nhập thông tin mô tả sản phẩm..."
                    ><?= e($sanPhamSua['MoTa'] ?? '') ?></textarea>

                </div>


                <div class="form-actions">

                    <button type="submit" class="btn-primary">
                        <?= $dangSua ? '✓ Lưu thay đổi' : '+ Thêm sản phẩm' ?>
                    </button>

                    <?php if ($dangSua): ?>

                        <a href="sanpham.php" class="btn-cancel">
                            Hủy chỉnh sửa
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>


        <!-- DANH SÁCH SẢN PHẨM -->
        <section class="product-list-section">

            <div class="product-section-heading">

                <div>
                    <h2>Danh sách sản phẩm</h2>

                    <p>
                        Hiện có
                        <strong><?= count($sanPham) ?></strong>
                        sản phẩm phù hợp.
                    </p>
                </div>

                <div class="product-count">
                    <?= count($sanPham) ?> sản phẩm
                </div>

            </div>


            <!-- TÌM KIẾM -->
            <form method="GET" action="sanpham.php" class="search-form">

                <div class="search-input-wrap">
                    <span>⌕</span>

                    <input
                        type="text"
                        name="timkiem"
                        placeholder="Tìm kiếm theo tên sản phẩm..."
                        value="<?= e($tuKhoa) ?>"
                    >
                </div>

                <button type="submit" class="btn-primary">
                    Tìm kiếm
                </button>

                <a href="sanpham.php" class="btn-cancel">
                    Làm mới
                </a>

            </form>


            <!-- BẢNG SẢN PHẨM -->
            <div class="table-responsive">

                <table class="product-table">

                    <thead>
                        <tr>
                            <th>MÃ SP</th>
                            <th>HÌNH ẢNH</th>
                            <th>TÊN SẢN PHẨM</th>
                            <th>DANH MỤC</th>
                            <th>ĐƠN GIÁ</th>
                            <th>TỒN KHO</th>
                            <th>THAO TÁC</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($sanPham) > 0): ?>

                            <?php foreach ($sanPham as $sp): ?>

                                <tr>

                                    <td>
                                        <strong class="order-code">
                                            #<?= (int)$sp['MaSP'] ?>
                                        </strong>
                                    </td>


                                    <td>

                                        <?php if (!empty($sp['HinhAnh'])): ?>

                                            <img
                                                src="<?= e(duongDanAnh($sp['HinhAnh'])) ?>"
                                                alt="<?= e($sp['TenSP']) ?>"
                                                class="product-thumb"
                                                loading="lazy"
                                            >

                                        <?php else: ?>

                                            <div class="no-product-image">
                                                📦
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <strong class="product-name">
                                            <?= e($sp['TenSP']) ?>
                                        </strong>
                                    </td>


                                    <td>
                                        <span class="category-label">
                                            <?= e($sp['TenDM']) ?>
                                        </span>
                                    </td>


                                    <td class="price">
                                        <?= number_format((float)$sp['DonGia'], 0, ',', '.') ?> đ
                                    </td>


                                    <td>

                                        <?php if ((int)$sp['SoLuongTon'] <= 0): ?>

                                            <span class="stock-badge stock-out">
                                                Hết hàng
                                            </span>

                                        <?php elseif ((int)$sp['SoLuongTon'] <= 5): ?>

                                            <span class="stock-badge stock-low">
                                                <?= (int)$sp['SoLuongTon'] ?> sản phẩm
                                            </span>

                                        <?php else: ?>

                                            <span class="stock-badge stock-normal">
                                                <?= (int)$sp['SoLuongTon'] ?> sản phẩm
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <div class="action-buttons">

                                            <a
                                                href="sanpham.php?sua=<?= (int)$sp['MaSP'] ?>"
                                                class="btn-edit"
                                            >
                                                Sửa
                                            </a>


                                            <form
                                                method="POST"
                                                action="sanpham.php"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="hanhDong"
                                                    value="xoa"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="MaSP"
                                                    value="<?= (int)$sp['MaSP'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-delete"
                                                >
                                                    Xóa
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty">

                                    <div class="empty-icon">📦</div>

                                    <strong>Không tìm thấy sản phẩm</strong>

                                    <p>
                                        Hãy thử từ khóa khác hoặc thêm sản phẩm mới.
                                    </p>

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
            <span>Quản lý sản phẩm cửa hàng dụng cụ học tập</span>
        </footer>

    </main>

</div>

</body>
</html>
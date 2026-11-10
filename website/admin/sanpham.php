
<?php
session_start();
require_once "../config/database.php";

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['MaTK']) || ($_SESSION['VaiTro'] ?? '') !== 'Admin') {
    header("Location: ../dangnhap.php");
    exit;
}

$thongBao = "";
$loi = "";

// 2. Xử lý thêm, sửa, xóa
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hanhDong = $_POST['hanhDong'] ?? '';

    try {

        // XÓA SẢN PHẨM
       
// XÓA SẢN PHẨM
if ($hanhDong === 'xoa') {

    $maSP = (int)($_POST['MaSP'] ?? 0);

    if ($maSP <= 0) {
        throw new Exception("Mã sản phẩm không hợp lệ!");
    }

    try {
        // Bắt đầu giao dịch
        $conn->beginTransaction();

        // 1. Kiểm tra sản phẩm đã có trong đơn hàng chưa
        $sqlKiemTra = "
            SELECT COUNT(*)
            FROM ChiTietDonHang
            WHERE MaSP = :masp
        ";

        $stmt = $conn->prepare($sqlKiemTra);
        $stmt->execute([':masp' => $maSP]);

        $soDonHang = (int)$stmt->fetchColumn();

        if ($soDonHang > 0) {
            throw new Exception(
                "Không thể xóa sản phẩm vì sản phẩm đã được đặt hàng!"
            );
        }

        // 2. Xóa sản phẩm khỏi giỏ hàng
        $sqlXoaGioHang = "
            DELETE FROM ChiTietGioHang
            WHERE MaSP = :masp
        ";

        $stmt = $conn->prepare($sqlXoaGioHang);
        $stmt->execute([':masp' => $maSP]);

        // 3. Xóa sản phẩm khỏi bảng SanPham
        $sqlXoaSP = "
            DELETE FROM SanPham
            WHERE MaSP = :masp
        ";

        $stmt = $conn->prepare($sqlXoaSP);
        $stmt->execute([':masp' => $maSP]);

        // Hoàn tất giao dịch
        $conn->commit();

        header("Location: sanpham.php?msg=xoa");
        exit;

    } catch (Exception $e) {

        // Nếu xảy ra lỗi thì hoàn tác
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        throw $e;
    }
}


        // LẤY DỮ LIỆU FORM
        $tenSP = trim($_POST['TenSP'] ?? '');
        $maDM = (int)($_POST['MaDM'] ?? 0);
        $donGia = (float)($_POST['DonGia'] ?? 0);
        $soLuong = (int)($_POST['SoLuongTon'] ?? 0);
        $hinhAnh = trim($_POST['HinhAnh'] ?? '');
        $moTa = trim($_POST['MoTa'] ?? '');

        // KIỂM TRA DỮ LIỆU
        if ($tenSP === '' || $maDM <= 0 || $donGia < 0 || $soLuong < 0) {
            throw new Exception("Vui lòng nhập đầy đủ và đúng thông tin sản phẩm!");
        }

        if ($hanhDong === 'them') {

            $sql = "
                INSERT INTO SanPham
                (TenSP, MaDM, DonGia, SoLuongTon, HinhAnh, MoTa, NgayTao)
                VALUES
                (:tensp, :madm, :dongia, :soluong, :hinhanh, :mota, GETDATE())
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

        if ($hanhDong === 'sua') {

            $maSP = (int)($_POST['MaSP'] ?? 0);

            $sql = "
                UPDATE SanPham
                SET TenSP = :tensp,
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

    } catch (Exception $e) {
        $loi = $e->getMessage();
    } catch (PDOException $e) {
        $loi = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
    }
}

// 3. Thông báo sau khi thêm, sửa, xóa
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'them') {
        $thongBao = "Thêm sản phẩm thành công!";
    } elseif ($_GET['msg'] === 'sua') {
        $thongBao = "Cập nhật sản phẩm thành công!";
    } elseif ($_GET['msg'] === 'xoa') {
        $thongBao = "Xóa sản phẩm thành công!";
    }
}

// 4. Lấy danh sách danh mục
$stmtDM = $conn->query("SELECT MaDM, TenDM FROM DanhMuc ORDER BY MaDM");
$danhMuc = $stmtDM->fetchAll(PDO::FETCH_ASSOC);

// 5. Lấy thông tin sản phẩm cần sửa
$sanPhamSua = null;
if (isset($_GET['sua'])) {
    $maSP = (int)$_GET['sua'];

    $stmtSua = $conn->prepare(
        "SELECT * FROM SanPham WHERE MaSP = :masp"
    );
    $stmtSua->execute([':masp' => $maSP]);
    $sanPhamSua = $stmtSua->fetch(PDO::FETCH_ASSOC);
}

// 6. Tìm kiếm sản phẩm
$tuKhoa = trim($_GET['timkiem'] ?? '');

$sqlSP = "
    SELECT SP.MaSP, SP.TenSP, SP.DonGia, SP.SoLuongTon,
           SP.HinhAnh, DM.TenDM
    FROM SanPham SP
    INNER JOIN DanhMuc DM ON SP.MaDM = DM.MaDM
";

if ($tuKhoa !== '') {
    $sqlSP .= " WHERE SP.TenSP LIKE :tukhoa";
}

$sqlSP .= " ORDER BY SP.MaSP DESC";

$stmtSP = $conn->prepare($sqlSP);

if ($tuKhoa !== '') {
    $stmtSP->execute([':tukhoa' => '%' . $tuKhoa . '%']);
} else {
    $stmtSP->execute();
}

$sanPham = $stmtSP->fetchAll(PDO::FETCH_ASSOC);

// 7. Xác định đang thêm hay sửa
$dangSua = $sanPhamSua !== false && $sanPhamSua !== null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - StudyStore</title>
    <link rel="stylesheet" href="../css/styleadmin.css">
</head>
<body>

<div class="admin-container">

    <!-- MENU -->
    <aside class="sidebar">
        <div class="logo">
            <h2>StudyStore</h2>
            <p>ADMIN PANEL</p>
        </div>

        <nav class="menu">
            <a href="index.php">🏠 Tổng quan</a>
            <a href="sanpham.php" class="active">📦 Quản lý sản phẩm</a>
            <a href="danhmuc.php">📁 Quản lý danh mục</a>
            <a href="donhang.php">🛒 Quản lý đơn hàng</a>
            <a href="khachhang.php">👥 Quản lý khách hàng</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../test_products.php">🌐 Xem website</a>
            <a href="../dangxuat.php">🚪 Đăng xuất</a>
        </div>
    </aside>

    <!-- NỘI DUNG -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Quản lý sản phẩm</h1>
                <p>Thêm, sửa, xóa và tìm kiếm sản phẩm.</p>
            </div>

            <div class="admin-info">
                👤 <?= htmlspecialchars($_SESSION['TenDN'] ?? 'Admin') ?>
                <span class="admin-role">Quản trị viên</span>
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

        <!-- FORM THÊM / SỬA -->
        <section class="product-form-section">

            <h2>
                <?= $dangSua ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm mới' ?>
            </h2>

            <form method="POST" action="sanpham.php" class="product-form">

                <input type="hidden"
                       name="hanhDong"
                       value="<?= $dangSua ? 'sua' : 'them' ?>">

                <?php if ($dangSua): ?>
                    <input type="hidden" name="MaSP"
                           value="<?= (int)$sanPhamSua['MaSP'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Tên sản phẩm</label>
                    <input type="text" name="TenSP" required
                           value="<?= htmlspecialchars($sanPhamSua['TenSP'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Danh mục</label>
                    <select name="MaDM" required>
                        <option value="">-- Chọn danh mục --</option>

                        <?php foreach ($danhMuc as $dm): ?>
                            <option value="<?= (int)$dm['MaDM'] ?>"
                                <?= isset($sanPhamSua['MaDM']) &&
                                    (int)$sanPhamSua['MaDM'] === (int)$dm['MaDM']
                                    ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dm['TenDM']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label>Đơn giá (VNĐ)</label>
                    <input type="number" name="DonGia"
                           min="0" step="1000" required
                           value="<?= htmlspecialchars((string)($sanPhamSua['DonGia'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label>Số lượng tồn</label>
                    <input type="number" name="SoLuongTon"
                           min="0" required
                           value="<?= htmlspecialchars((string)($sanPhamSua['SoLuongTon'] ?? '')) ?>">
                </div>

                <div class="form-group full-width">
                    <label>Đường dẫn hình ảnh</label>
                    <input type="text" name="HinhAnh"
                           placeholder="../images/but-bi.jpg"
                           value="<?= htmlspecialchars($sanPhamSua['HinhAnh'] ?? '') ?>">
                    <small>Ví dụ: ../images/but-bi.jpg</small>
                </div>

                <div class="form-group full-width">
                    <label>Mô tả sản phẩm</label>
                    <textarea name="MoTa" rows="4"><?= htmlspecialchars($sanPhamSua['MoTa'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <?= $dangSua ? 'Lưu thay đổi' : 'Thêm sản phẩm' ?>
                    </button>

                    <?php if ($dangSua): ?>
                        <a href="sanpham.php" class="btn-cancel">Hủy sửa</a>
                    <?php endif; ?>
                </div>

            </form>
        </section>

        <!-- TÌM KIẾM -->
        <section class="product-list-section">

            <div class="section-header">
                <div>
                    <h2>Danh sách sản phẩm</h2>
                    <p>Có <?= count($sanPham) ?> sản phẩm được hiển thị.</p>
                </div>
            </div>

            <form method="GET" action="sanpham.php" class="search-form">
                <input type="text" name="timkiem"
                       placeholder="Nhập tên sản phẩm cần tìm..."
                       value="<?= htmlspecialchars($tuKhoa) ?>">

                <button type="submit" class="btn-primary">Tìm kiếm</button>
                <a href="sanpham.php" class="btn-cancel">Làm mới</a>
            </form>

            <!-- BẢNG SẢN PHẨM -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Mã SP</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Đơn giá</th>
                            <th>Tồn kho</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($sanPham) > 0): ?>

                            <?php foreach ($sanPham as $sp): ?>
                                <tr>
                                    <td><?= (int)$sp['MaSP'] ?></td>

                                    <td>
                                        <?php if (!empty($sp['HinhAnh'])): ?>
                                            <img
                                                src="../<?= htmlspecialchars($sp['HinhAnh']) ?>"                                                  alt="Ảnh sản phẩm"
                                                class="product-thumb">
                                        <?php else: ?>
                                            <span>Chưa có ảnh</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($sp['TenSP']) ?></td>

                                    <td><?= htmlspecialchars($sp['TenDM']) ?></td>

                                    <td class="price">
                                        <?= number_format((float)$sp['DonGia'], 0, ',', '.') ?> đ
                                    </td>

                                    <td><?= (int)$sp['SoLuongTon'] ?></td>

                                    <td class="action-buttons">
                                        <a href="sanpham.php?sua=<?= (int)$sp['MaSP'] ?>"
                                           class="btn-edit">Sửa</a>

                                        <form method="POST" action="sanpham.php"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">

                                            <input type="hidden" name="hanhDong" value="xoa">
                                            <input type="hidden" name="MaSP"
                                                   value="<?= (int)$sp['MaSP'] ?>">

                                            <button type="submit" class="btn-delete">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty">
                                    Không tìm thấy sản phẩm.
                                </td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

        <footer class="footer">
            © 2026 StudyStore - Quản lý sản phẩm
        </footer>

    </main>
</div>

</body>
</html>

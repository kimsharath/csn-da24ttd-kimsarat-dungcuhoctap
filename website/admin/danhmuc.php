
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

// 2. Xử lý thêm, sửa, xóa danh mục
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hanhDong = $_POST['hanhDong'] ?? '';

    try {

        // XÓA DANH MỤC
        if ($hanhDong === 'xoa') {

            $maDM = (int)($_POST['MaDM'] ?? 0);

            if ($maDM <= 0) {
                throw new Exception("Mã danh mục không hợp lệ!");
            }

            // Kiểm tra danh mục có sản phẩm hay không
            $stmtKiemTra = $conn->prepare(
                "SELECT COUNT(*) FROM SanPham WHERE MaDM = :madm"
            );

            $stmtKiemTra->execute([':madm' => $maDM]);

            if ((int)$stmtKiemTra->fetchColumn() > 0) {
                throw new Exception(
                    "Không thể xóa danh mục vì đang có sản phẩm thuộc danh mục này!"
                );
            }

            $stmtXoa = $conn->prepare(
                "DELETE FROM DanhMuc WHERE MaDM = :madm"
            );

            $stmtXoa->execute([':madm' => $maDM]);

            header("Location: danhmuc.php?msg=xoa");
            exit;
        }

        // LẤY DỮ LIỆU FORM
        $tenDM = trim($_POST['TenDM'] ?? '');
        $moTa = trim($_POST['MoTa'] ?? '');

        if ($tenDM === '') {
            throw new Exception("Vui lòng nhập tên danh mục!");
        }

        // KIỂM TRA TÊN DANH MỤC TRÙNG
        if ($hanhDong === 'them') {

            $stmtTrung = $conn->prepare(
                "SELECT COUNT(*) FROM DanhMuc WHERE TenDM = :tendm"
            );

            $stmtTrung->execute([':tendm' => $tenDM]);

            if ((int)$stmtTrung->fetchColumn() > 0) {
                throw new Exception("Tên danh mục đã tồn tại!");
            }

            // THÊM DANH MỤC
            $sql = "
                INSERT INTO DanhMuc (TenDM, MoTa)
                VALUES (:tendm, :mota)
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':tendm' => $tenDM,
                ':mota' => $moTa !== '' ? $moTa : null
            ]);

            header("Location: danhmuc.php?msg=them");
            exit;
        }

        // CẬP NHẬT DANH MỤC
        if ($hanhDong === 'sua') {

            $maDM = (int)($_POST['MaDM'] ?? 0);

            if ($maDM <= 0) {
                throw new Exception("Mã danh mục không hợp lệ!");
            }

            $stmtTrung = $conn->prepare("
                SELECT COUNT(*)
                FROM DanhMuc
                WHERE TenDM = :tendm AND MaDM <> :madm
            ");

            $stmtTrung->execute([
                ':tendm' => $tenDM,
                ':madm' => $maDM
            ]);

            if ((int)$stmtTrung->fetchColumn() > 0) {
                throw new Exception("Tên danh mục đã được sử dụng!");
            }

            $sql = "
                UPDATE DanhMuc
                SET TenDM = :tendm,
                    MoTa = :mota
                WHERE MaDM = :madm
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':tendm' => $tenDM,
                ':mota' => $moTa !== '' ? $moTa : null,
                ':madm' => $maDM
            ]);

            header("Location: danhmuc.php?msg=sua");
            exit;
        }

    } catch (PDOException $e) {
        $loi = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
    } catch (Exception $e) {
        $loi = $e->getMessage();
    }
}

// 3. Thông báo kết quả
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'them') {
        $thongBao = "Thêm danh mục thành công!";
    } elseif ($_GET['msg'] === 'sua') {
        $thongBao = "Cập nhật danh mục thành công!";
    } elseif ($_GET['msg'] === 'xoa') {
        $thongBao = "Xóa danh mục thành công!";
    }
}

// 4. Lấy danh mục cần sửa
$danhMucSua = null;

if (isset($_GET['sua'])) {
    $maDM = (int)$_GET['sua'];

    $stmtSua = $conn->prepare(
        "SELECT * FROM DanhMuc WHERE MaDM = :madm"
    );

    $stmtSua->execute([':madm' => $maDM]);

    $danhMucSua = $stmtSua->fetch(PDO::FETCH_ASSOC);
}

$dangSua = is_array($danhMucSua);

// 5. Tìm kiếm danh mục
$tuKhoa = trim($_GET['timkiem'] ?? '');

$sql = "
    SELECT
        DM.MaDM,
        DM.TenDM,
        DM.MoTa,
        (SELECT COUNT(*)
         FROM SanPham SP
         WHERE SP.MaDM = DM.MaDM) AS SoSanPham
    FROM DanhMuc DM
";

if ($tuKhoa !== '') {
    $sql .= " WHERE DM.TenDM LIKE :tukhoa";
}

$sql .= " ORDER BY DM.MaDM DESC";

$stmtDM = $conn->prepare($sql);

if ($tuKhoa !== '') {
    $stmtDM->execute([
        ':tukhoa' => '%' . $tuKhoa . '%'
    ]);
} else {
    $stmtDM->execute();
}

$danhSachDM = $stmtDM->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý danh mục - StudyStore</title>

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
            <a href="danhmuc.php" class="active">📁 Quản lý danh mục</a>
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
                <h1>Quản lý danh mục</h1>
                <p>Thêm, sửa, xóa và tìm kiếm danh mục sản phẩm.</p>
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

        <!-- FORM THÊM / SỬA -->
        <section class="category-form-section">

            <h2>
                <?= $dangSua ? 'Cập nhật danh mục' : 'Thêm danh mục mới' ?>
            </h2>

            <form method="POST" action="danhmuc.php" class="category-form">

                <input type="hidden"
                       name="hanhDong"
                       value="<?= $dangSua ? 'sua' : 'them' ?>">

                <?php if ($dangSua): ?>
                    <input type="hidden"
                           name="MaDM"
                           value="<?= (int)$danhMucSua['MaDM'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Tên danh mục</label>

                    <input
                        type="text"
                        name="TenDM"
                        required
                        maxlength="100"
                        placeholder="Ví dụ: Bút bi, Vở, Thước..."
                        value="<?= htmlspecialchars($danhMucSua['TenDM'] ?? '') ?>"
                    >
                </div>

                <div class="form-group full-width">
                    <label>Mô tả danh mục</label>

                    <textarea
                        name="MoTa"
                        rows="4"
                        placeholder="Nhập mô tả danh mục..."
                    ><?= htmlspecialchars($danhMucSua['MoTa'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">

                    <button type="submit" class="btn-primary">
                        <?= $dangSua ? 'Lưu thay đổi' : 'Thêm danh mục' ?>
                    </button>

                    <?php if ($dangSua): ?>
                        <a href="danhmuc.php" class="btn-cancel">
                            Hủy sửa
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- DANH SÁCH DANH MỤC -->
        <section class="category-list-section">

            <div class="section-header">

                <div>
                    <h2>Danh sách danh mục</h2>

                    <p>
                        Có <?= count($danhSachDM) ?> danh mục được hiển thị.
                    </p>
                </div>

            </div>

            <!-- TÌM KIẾM -->
            <form method="GET" action="danhmuc.php" class="search-form">

                <input
                    type="text"
                    name="timkiem"
                    placeholder="Nhập tên danh mục cần tìm..."
                    value="<?= htmlspecialchars($tuKhoa) ?>"
                >

                <button type="submit" class="btn-primary">
                    Tìm kiếm
                </button>

                <a href="danhmuc.php" class="btn-cancel">
                    Làm mới
                </a>

            </form>

            <!-- BẢNG DANH MỤC -->
            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>Mã danh mục</th>
                            <th>Tên danh mục</th>
                            <th>Mô tả</th>
                            <th>Số sản phẩm</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($danhSachDM) > 0): ?>

                            <?php foreach ($danhSachDM as $dm): ?>

                                <tr>

                                    <td>
                                        <?= (int)$dm['MaDM'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($dm['TenDM']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($dm['MoTa'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= (int)$dm['SoSanPham'] ?>
                                    </td>

                                    <td class="action-buttons">

                                        <a
                                            href="danhmuc.php?sua=<?= (int)$dm['MaDM'] ?>"
                                            class="btn-edit"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="POST"
                                            action="danhmuc.php"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="hanhDong"
                                                value="xoa"
                                            >

                                            <input
                                                type="hidden"
                                                name="MaDM"
                                                value="<?= (int)$dm['MaDM'] ?>"
                                            >

                                            <button type="submit" class="btn-delete">
                                                Xóa
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="empty">
                                    Không tìm thấy danh mục nào.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <footer class="footer">
            © 2026 StudyStore - Quản lý danh mục
        </footer>

    </main>

</div>

</body>
</html>

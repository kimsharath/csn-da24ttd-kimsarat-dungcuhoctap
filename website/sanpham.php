<?php
require_once "config/database.php";

// 1. Lấy từ khóa tìm kiếm
$tuKhoa = trim($_GET['q'] ?? '');

// 2. Lấy mã danh mục được chọn
$maDM = isset($_GET['madm']) ? (int)$_GET['madm'] : 0;

// 3. Lấy danh sách danh mục
$sqlDanhMuc = "SELECT MaDM, TenDM FROM DanhMuc ORDER BY MaDM";
$stmtDanhMuc = $conn->query($sqlDanhMuc);
$danhMuc = $stmtDanhMuc->fetchAll(PDO::FETCH_ASSOC);

// 4. Tạo câu truy vấn lấy sản phẩm
$sql = "
    SELECT
        SP.MaSP,
        SP.TenSP,
        SP.DonGia,
        SP.SoLuongTon,
        SP.HinhAnh,
        SP.MoTa,
        DM.TenDM
    FROM SanPham SP
    INNER JOIN DanhMuc DM
        ON SP.MaDM = DM.MaDM
    WHERE 1 = 1
";

$params = [];

// 5. Nếu có từ khóa thì tìm theo tên sản phẩm
if ($tuKhoa !== '') {
    $sql .= " AND SP.TenSP LIKE :tukhoa";
    $params[':tukhoa'] = '%' . $tuKhoa . '%';
}

// 6. Nếu chọn danh mục thì lọc theo mã danh mục
if ($maDM > 0) {
    $sql .= " AND SP.MaDM = :madm";
    $params[':madm'] = $maDM;
}

$sql .= " ORDER BY SP.MaSP DESC";

// 7. Thực hiện truy vấn
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$sanPham = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sản phẩm - StudyStore</title>

    <link rel="stylesheet" href="css/stylesp.css">
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
    <nav class="navbar">
        <div class="container">
            <a href="test_products.php">Trang chủ</a>
            <a href="sanpham.php" class="active">Sản phẩm</a>
            <a href="giohang.php" class="active">Giỏ hàng</a>
            <a href="dangnhap.php">Đăng nhập</a>
        </div>
    </nav>

    <!-- NỘI DUNG -->
    <main class="container product-page">

        <h2>SẢN PHẨM</h2>
        <p>Khám phá các dụng cụ học tập của chúng tôi</p>

        <!-- TÌM KIẾM -->
        <form method="GET" action="sanpham.php" class="search-form">

            <input
                type="text"
                name="q"
                placeholder="Nhập tên sản phẩm cần tìm..."
                value="<?php echo htmlspecialchars($tuKhoa); ?>"
            >

            <button type="submit">Tìm kiếm</button>

        </form>

        <!-- DANH MỤC -->
        <h3>Danh mục sản phẩm</h3>

        <div class="category-list">

            <a href="sanpham.php"
               class="<?php echo $maDM === 0 ? 'selected' : ''; ?>">
                Tất cả
            </a>

            <?php foreach ($danhMuc as $dm): ?>

                <a
                    href="sanpham.php?madm=<?php echo (int)$dm['MaDM']; ?>"
                    class="<?php echo $maDM === (int)$dm['MaDM'] ? 'selected' : ''; ?>"
                >
                    <?php echo htmlspecialchars($dm['TenDM']); ?>
                </a>

            <?php endforeach; ?>

        </div>

        <!-- DANH SÁCH SẢN PHẨM -->
        <h3>Danh sách sản phẩm</h3>

        <div class="product-list">

            <?php if (count($sanPham) > 0): ?>

                <?php foreach ($sanPham as $sp): ?>

                    <div class="product-card">

                        <?php if (!empty($sp['HinhAnh'])): ?>

                            <img
                                src="<?php echo htmlspecialchars($sp['HinhAnh']); ?>"
                                alt="<?php echo htmlspecialchars($sp['TenSP']); ?>"
                                onerror="this.style.display='none';"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                Chưa có hình ảnh
                            </div>

                        <?php endif; ?>

                        <div class="product-info">

                            <p class="product-category">
                                <?php echo htmlspecialchars($sp['TenDM']); ?>
                            </p>

                            <h4>
                                <?php echo htmlspecialchars($sp['TenSP']); ?>
                            </h4>

                            <p class="product-price">
                                <?php echo number_format((float)$sp['DonGia'], 0, ',', '.'); ?>
                                VNĐ
                            </p>

                           <p>
    Số lượng:
    <?php echo (int)$sp['SoLuongTon']; ?>
</p>

<!-- NÚT THÊM VÀO GIỎ HÀNG -->
<?php if ((int)$sp['SoLuongTon'] > 0): ?>

    <form method="POST" action="giohang.php">

        <input type="hidden" name="hanhDong" value="them">

        <input type="hidden" name="MaSP"
               value="<?php echo (int)$sp['MaSP']; ?>">

        <input type="hidden" name="SL" value="1">

        <button type="submit" class="btn-cart">
            Thêm vào giỏ hàng
        </button>

    </form>

<?php else: ?>

    <p class="out-of-stock">Hết hàng</p>

<?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>Không tìm thấy sản phẩm nào.</p>

            <?php endif; ?>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="footer">
    <div class="footer-container">
        <!-- Cột 1: Thương hiệu -->
        <div class="footer-col">
            <h3><i class="fa-solid fa-book-open"></i> StudyStore</h3>
            <p>Cửa hàng dụng cụ học tập dành cho mọi người.</p>
        </div>

        <!-- Cột 2: Thông tin liên hệ -->
        <div class="footer-col">
            <h4>Thông tin liên hệ</h4>
            <p><strong>Điện thoại:</strong> 0123 456 789</p>
            <p><strong>Email:</strong> studystore@example.com</p>
            <p><strong>Địa chỉ:</strong> Trà Vinh, Việt Nam</p>
        </div>

        <!-- Cột 3: Hỗ trợ khách hàng -->
        <div class="footer-col">
            <h4>Hỗ trợ khách hàng</h4>
            <ul>
                <li><a href="#">Chính sách mua hàng</a></li>
                <li><a href="#">Chính sách đổi trả</a></li>
                <li><a href="#">Hướng dẫn mua hàng</a></li>
            </ul>
        </div>
    </div>

    <!-- Bản quyền bên dưới -->
    <div class="footer-bottom">
        <p>&copy; 2026 StudyStore - Đồ án cơ sở ngành</p>
    </div>
</footer>

</body>
</html>
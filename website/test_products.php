
<?php
require_once "config/database.php";

// Lấy danh mục
$sqlDanhMuc = "SELECT * FROM DanhMuc";
$stmtDanhMuc = $conn->query($sqlDanhMuc);
$danhMuc = $stmtDanhMuc->fetchAll();

// Lấy sản phẩm
$sqlSanPham = "
    SELECT
        SanPham.MaSP,
        SanPham.TenSP,
        SanPham.DonGia,
        SanPham.SoLuongTon,
        SanPham.HinhAnh,
        DanhMuc.TenDM
    FROM SanPham
    INNER JOIN DanhMuc
    ON SanPham.MaDM = DanhMuc.MaDM
";
$stmtSanPham = $conn->query($sqlSanPham);

$sanPham = $stmtSanPham->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyStore - Dụng cụ học tập</title>

    <link rel="stylesheet" href="css/styletc.css">
</head>
<body>

<header class="header">
    <div class="container">
        <h1>StudyStore</h1>
        <p>Cửa hàng dụng cụ học tập</p>
    </div>
    <div class="logout">
        <a href="dangsuat.php">Đăng xuất</a>
    </div>
    <div class="dangky">
        <a href="dangky.php">Đăng ký</a></div>
    <div class="dangnhap">
        <a href="dangnhap.php">Đăng nhập</a>

</header>

<nav class="navbar">
    <div class="container">
        <a href="test_products.php">Trang chủ</a>
        <a href="sanpham.php">Sản phẩm</a>
        <!-- <a href="#">Giới thiệu</a> -->
        <a href="#lienhe">Liên hệ</a>
        <a href="giohang.php">Giỏ hàng</a>
    </div>
</nav>

<main class="container">

    <section class="category-section">
        <h2>Danh mục sản phẩm</h2>

        <div class="category-list">
            <?php foreach ($danhMuc as $dm) { ?>
                <div class="category-item">
                    <?php echo htmlspecialchars($dm['TenDM']); ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <section class="product-section">
        <h2>Sản phẩm nổi bật</h2>

        <div class="product-list">

            <?php foreach ($sanPham as $sp) { ?>

                <div class="product-card">

                    <div class="product-image">
                        <?php if (!empty($sp['HinhAnh'])) { ?>
                            <img
                                src="<?php echo htmlspecialchars($sp['HinhAnh']); ?>"
                                alt="<?php echo htmlspecialchars($sp['TenSP']); ?>"
                            >
                        <?php } else { ?>
                            <div class="no-image">Chưa có hình ảnh</div>
                        <?php } ?>
                    </div>

                    <div class="product-info">

                        <h3>
                            <?php echo htmlspecialchars($sp['TenSP']); ?>
                        </h3>

                        <p class="category-name">
                            <?php echo htmlspecialchars($sp['TenDM']); ?>
                        </p>

                        <p class="price">
                            <?php echo number_format($sp['DonGia']); ?> VNĐ
                        </p>

                        <p class="stock">
                            Số lượng: <?php echo $sp['SoLuongTon']; ?>
                        </p>

                        <!-- <button class="btn-buy" type="button">
                            Xem sản phẩm
                        </button> -->
                      
<a href="chitietsp.php?masp=<?php echo (int)$sp['MaSP']; ?>"
   class="btn-product">
    Xem sản phẩm
</a>


                    </div>
                </div>

            <?php } ?>

        </div>
    </section>

</main>

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
            <p id="lienhe"><strong>Điện thoại:</strong> 0123 456 789</p>
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

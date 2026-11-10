
<?php
require_once "config/database.php";

// 1. Lấy mã sản phẩm từ URL
$maSP = isset($_GET['masp']) ? (int)$_GET['masp'] : 0;

// 2. Kiểm tra mã sản phẩm
if ($maSP <= 0) {
    die("Mã sản phẩm không hợp lệ!");
}

// 3. Lấy thông tin chi tiết sản phẩm
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
    WHERE SP.MaSP = :masp
";

$stmt = $conn->prepare($sql);
$stmt->execute([':masp' => $maSP]);

$sp = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Kiểm tra sản phẩm có tồn tại không
if (!$sp) {
    die("Không tìm thấy sản phẩm!");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Chi tiết sản phẩm - StudyStore</title>

    <link rel="stylesheet" href="css/stylect.css">
  
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
<nav class="menu">
    <div class="container">
        <a href="test_products.php">Trang chủ</a>
        <a href="sanpham.php">Sản phẩm</a>
        <a href="giohang.php">Giỏ hàng</a>
        <a href="dangnhap.php">Đăng nhập</a>
    </div>
</nav>

<!-- CHI TIẾT SẢN PHẨM -->
<main class="detail-container">

    <h2>CHI TIẾT SẢN PHẨM</h2>

    <div class="detail-box">

        <!-- HÌNH ẢNH -->
        <div class="detail-image">

            <?php if (!empty($sp['HinhAnh'])): ?>

                <img
                    src="<?php echo htmlspecialchars($sp['HinhAnh']); ?>"
                    alt="<?php echo htmlspecialchars($sp['TenSP']); ?>"
                >

            <?php else: ?>

                <p>Chưa có hình ảnh sản phẩm</p>

            <?php endif; ?>

        </div>

        <!-- THÔNG TIN -->
        <div class="detail-info">

            <p class="detail-category">
                Danh mục:
                <?php echo htmlspecialchars($sp['TenDM']); ?>
            </p>

            <h2>
                <?php echo htmlspecialchars($sp['TenSP']); ?>
            </h2>

            <p class="detail-price">
                <?php
                echo number_format(
                    (float)$sp['DonGia'],
                    0,
                    ',',
                    '.'
                );
                ?> VNĐ
            </p>

            <p>
                Số lượng còn:
                <?php echo (int)$sp['SoLuongTon']; ?>
            </p>

            <div class="detail-description">
                <h3>Mô tả sản phẩm</h3>

                <p>
                    <?php
                    echo !empty($sp['MoTa'])
                        ? nl2br(htmlspecialchars($sp['MoTa']))
                        : "Chưa có mô tả cho sản phẩm này.";
                    ?>
                </p>
            </div>

            <!-- THÊM VÀO GIỎ HÀNG -->
            <?php if ((int)$sp['SoLuongTon'] > 0): ?>

                <form method="POST" action="giohang.php">

                    <input type="hidden"
                           name="hanhDong"
                           value="them">

                    <input type="hidden"
                           name="MaSP"
                           value="<?php echo (int)$sp['MaSP']; ?>">

                    <label for="SL">Số lượng mua:</label>

                    <input
                        type="number"
                        id="SL"
                        name="SL"
                        class="quantity-input"
                        value="1"
                        min="1"
                        max="<?php echo (int)$sp['SoLuongTon']; ?>"
                        required
                    >

                    <button type="submit" class="btn-cart">
                        Thêm vào giỏ hàng
                    </button>

                </form>

            <?php else: ?>

                <p style="color:red;font-weight:bold;">
                    Sản phẩm đã hết hàng
                </p>

            <?php endif; ?>

            <a href="sanpham.php" class="btn-back">
                ← Quay lại danh sách sản phẩm
            </a>

        </div>

    </div>

</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">

        <div class="footer-col">
            <h3>StudyStore</h3>
            <p>Cửa hàng dụng cụ học tập dành cho mọi người.</p>
        </div>

        <div class="footer-col">
            <h4>Thông tin liên hệ</h4>
            <p>Điện thoại: 0123 456 789</p>
            <p>Email: studystore@example.com</p>
            <p>Địa chỉ: Trà Vinh, Việt Nam</p>
        </div>

        <div class="footer-col">
            <h4>Hỗ trợ khách hàng</h4>
            <p>Chính sách mua hàng</p>
            <p>Chính sách đổi trả</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 StudyStore - Đồ án cơ sở ngành</p>
    </div>
</footer>

</body>
</html>

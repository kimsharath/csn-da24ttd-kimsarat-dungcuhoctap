
<?php
require_once "ketnoi.php";

// Lấy danh sách sản phẩm
$sql = "SELECT * FROM SanPham";
$stmt = $conn->query($sql);
$sanpham = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục
$sqlDM = "SELECT * FROM DanhMuc";
$stmtDM = $conn->query($sqlDM);
$danhmuc = $stmtDM->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyStore - Dụng cụ học tập</title>
    <!-- <link rel="stylesheet" href="style1.css"> -->
    <link rel="stylesheet" href="style1.css">
</head>
<body>

<header class="header">
    <div class="logo">
        <h1>StudyStore</h1>
        <p>Dụng cụ học tập - Chắp cánh tri thức</p>
    </div>

    <form action="index.php" method="get" class="search">
        <input type="text" name="timkiem"
               placeholder="Tìm dụng cụ học tập...">
        <button type="submit">Tìm</button>
    </form>
</header>

<nav class="navbar">
    <a href="index.php">Trang chủ</a>
    <a href="#danhmuc">Danh mục</a>
    <a href="#sanpham">Sản phẩm</a>
    <a href="#lienhe">Liên hệ</a>
</nav>

<section class="banner">
    <div>
        <p>CHÀO MỪNG BẠN ĐẾN VỚI STUDYSTORE</p>
        <h2>Dụng cụ học tập<br>Cho hành trình tri thức</h2>
        <p>Đầy đủ đồ dùng học tập, giá tốt cho mọi học sinh và sinh viên.</p>
        <a href="#sanpham" class="btn">Mua sắm ngay</a>
    </div>

    <img src="images/balo.jfif" alt="Balo học sinh">
</section>

<section class="section" id="danhmuc">
    <h2>Danh mục sản phẩm</h2>

    <div class="category-list">
        <?php foreach ($danhmuc as $dm): ?>
            <a class="category"
               href="index.php?madanhmuc=<?= (int)$dm['MaDanhMuc'] ?>">
                <h3>
                    <?= htmlspecialchars($dm['TenDanhMuc']) ?>
                </h3>
                <p>Khám phá sản phẩm</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" id="sanpham">
    <h2>Sản phẩm nổi bật</h2>

    <div class="product-list">
        <?php
        $timkiem = trim($_GET['timkiem'] ?? '');
        $madanhmuc = filter_input(
            INPUT_GET, 'madanhmuc', FILTER_VALIDATE_INT
        );

        foreach ($sanpham as $sp):
            if ($timkiem !== '' &&
                stripos($sp['TenSanPham'], $timkiem) === false) {
                continue;
            }

            if ($madanhmuc !== null && $madanhmuc !== false &&
                (int)$sp['MaDanhMuc'] !== $madanhmuc) {
                continue;
            }

            $anh = trim($sp['HinhAnh'] ?? '');
            if ($anh === '') {
                $anh = 'images/thuoc.jfif';
            } elseif (strpos($anh, '/') === false &&
                      strpos($anh, '\\') === false) {
                $anh = 'images/balo.jfif';
            }
        ?>
            <article class="product">
                <img src="<?= htmlspecialchars($anh) ?>"
                     alt="<?= htmlspecialchars($sp['TenSanPham']) ?>">

                <div class="product-info">
                    <h3><?= htmlspecialchars($sp['TenSanPham']) ?></h3>

                    <p class="price">
                        <?= number_format((float)$sp['Gia'], 0, ',', '.') ?> VNĐ
                    </p>

                    <p>Số lượng: <?= (int)$sp['SoLuong'] ?></p>

                    <p>
                        <?= htmlspecialchars($sp['MoTa'] ?? '') ?>
                    </p>

                    <button type="button"
                            onclick="alert('Bạn đã chọn <?= htmlspecialchars(
                                $sp['TenSanPham'], ENT_QUOTES
                            ) ?>. Chức năng đặt hàng sẽ được xây dựng tiếp.')">
                        Mua hàng
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<footer id="lienhe">
    <div>
        <h3>StudyStore</h3>
        <p>Cửa hàng dụng cụ học tập</p>
    </div>

    <div>
        <h3>Thông tin liên hệ</h3>
        <p>Email: studystore@example.com</p>
        <p>Hotline: 0123 456 789</p>
    </div>

    <div>
        <h3>Hỗ trợ khách hàng</h3>
        <p>Hướng dẫn mua hàng</p>
        <p>Chính sách đổi trả</p>
    </div>

    <p class="copyright">
        © 2026 StudyStore
    </p>
</footer>

</body>
</html>

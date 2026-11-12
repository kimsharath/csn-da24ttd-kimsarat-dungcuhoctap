
<?php
session_start();
require_once "config/database.php";

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['MaKH'])) {
    header("Location: dangnhap.php");
    exit;
}

$maKH = (int)$_SESSION['MaKH'];
$thongBao = "";
$maDHThanhCong = isset($_GET['thanhcong'])
    ? (int)$_GET['thanhcong'] : 0;

// 2. Lấy thông tin khách hàng
$sqlKH = "SELECT MaKH, HoTen, Email, SDT, DiaChi
          FROM KhachHang
          WHERE MaKH = :makh";

$stmtKH = $conn->prepare($sqlKH);
$stmtKH->execute([':makh' => $maKH]);
$khachHang = $stmtKH->fetch(PDO::FETCH_ASSOC);

if (!$khachHang) {
    die("Không tìm thấy thông tin khách hàng!");
}

// 3. Lấy giỏ hàng của khách hàng
$sqlGH = "SELECT TOP 1 MaGH
          FROM GioHang
          WHERE MaKH = :makh
          ORDER BY MaGH DESC";

$stmtGH = $conn->prepare($sqlGH);
$stmtGH->execute([':makh' => $maKH]);
$gioHang = $stmtGH->fetch(PDO::FETCH_ASSOC);

if (!$gioHang) {
    die("Giỏ hàng của bạn đang trống!");
}

$maGH = (int)$gioHang['MaGH'];

// 4. Lấy sản phẩm trong giỏ hàng
function laySanPhamGioHang($conn, $maGH) {
    $sql = "SELECT
                CT.MaCTGH,
                CT.MaSP,
                CT.SL,
                SP.TenSP,
                SP.DonGia,
                SP.SoLuongTon,
                SP.HinhAnh,
                (CT.SL * SP.DonGia) AS ThanhTien
            FROM ChiTietGioHang CT
            INNER JOIN SanPham SP ON CT.MaSP = SP.MaSP
            WHERE CT.MaGH = :magh";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':magh' => $maGH]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sanPham = laySanPhamGioHang($conn, $maGH);

if (empty($sanPham) && $maDHThanhCong <= 0) {
    die("Giỏ hàng đang trống!");
}

// 5. Tính tổng tiền
$tongTien = 0;

foreach ($sanPham as $sp) {
    $tongTien += (float)$sp['ThanhTien'];
}

// 6. Xử lý khi khách hàng xác nhận đặt hàng
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $diaChi = trim($_POST['DiaChiGiaoHang'] ?? '');
    $sdt = trim($_POST['SDT'] ?? '');
    $phuongThuc = $_POST['PhuongThuc'] ?? '';

    $cacPhuongThuc = ['COD', 'Chuyển khoản'];

    if ($diaChi === '' || $sdt === '' ||
        !in_array($phuongThuc, $cacPhuongThuc, true)) {

        $thongBao = "Vui lòng nhập đầy đủ thông tin đặt hàng!";

    } else {

        try {
            $conn->beginTransaction();

            // Lấy lại giỏ hàng để kiểm tra dữ liệu mới nhất
            $sanPham = laySanPhamGioHang($conn, $maGH);

            if (empty($sanPham)) {
                throw new Exception("Giỏ hàng đã trống!");
            }

            $tongTien = 0;

            foreach ($sanPham as $sp) {
                if ((int)$sp['SL'] <= 0 ||
                    (int)$sp['SL'] > (int)$sp['SoLuongTon']) {
                    throw new Exception(
                        "Sản phẩm " . $sp['TenSP'] .
                        " không đủ số lượng tồn kho!"
                    );
                }

                $tongTien += (float)$sp['DonGia'] * (int)$sp['SL'];
            }

            // Cập nhật thông tin khách hàng
            $sqlCapNhatKH = "UPDATE KhachHang
                             SET SDT = :sdt, DiaChi = :diachi
                             WHERE MaKH = :makh";

            $stmtCapNhatKH = $conn->prepare($sqlCapNhatKH);
            $stmtCapNhatKH->execute([
                ':sdt' => $sdt,
                ':diachi' => $diaChi,
                ':makh' => $maKH
            ]);

            // Tạo đơn hàng
            $sqlDH = "INSERT INTO DonHang
                      (MaKH, NgayDat, TongTien, TrangThai, DiaChiGiaoHang)
                      OUTPUT INSERTED.MaDH
                      VALUES
                      (:makh, GETDATE(), :tongtien,
                       N'Chờ xác nhận', :diachi)";

            $stmtDH = $conn->prepare($sqlDH);
            $stmtDH->execute([
                ':makh' => $maKH,
                ':tongtien' => $tongTien,
                ':diachi' => $diaChi
            ]);

            $maDH = $stmtDH->fetchColumn();

            if (!$maDH) {
                throw new Exception("Không tạo được đơn hàng!");
            }

            // Thêm chi tiết đơn hàng và trừ tồn kho
            foreach ($sanPham as $sp) {

                $sqlCT = "INSERT INTO ChiTietDonHang
                          (MaDH, MaSP, SL, DonGia)
                          VALUES (:madh, :masp, :sl, :dongia)";

                $stmtCT = $conn->prepare($sqlCT);
                $stmtCT->execute([
                    ':madh' => $maDH,
                    ':masp' => $sp['MaSP'],
                    ':sl' => $sp['SL'],
                    ':dongia' => $sp['DonGia']
                ]);

                // Chỉ trừ kho nếu vẫn đủ hàng
                $sqlKho = "UPDATE SanPham
                           SET SoLuongTon = SoLuongTon - :sl1
                           WHERE MaSP = :masp
                           AND SoLuongTon >= :sl2";

                $stmtKho = $conn->prepare($sqlKho);
                $stmtKho->execute([
                    ':sl1' => $sp['SL'],
                    ':sl2' => $sp['SL'],
                    ':masp' => $sp['MaSP']
                ]);

                if ($stmtKho->rowCount() !== 1) {
                    throw new Exception(
                        "Số lượng tồn kho của " . $sp['TenSP'] .
                        " vừa thay đổi. Vui lòng kiểm tra lại giỏ hàng!"
                    );
                }
            }

            // Lưu thông tin thanh toán
            $sqlTT = "INSERT INTO ThanhToan
                      (MaDH, PhuongThuc, SoTien,
                       NgayThanhToan, TrangThai)
                      VALUES
                      (:madh, :phuongthuc, :sotien,
                       GETDATE(), N'Chưa thanh toán')";

            $stmtTT = $conn->prepare($sqlTT);
            $stmtTT->execute([
                ':madh' => $maDH,
                ':phuongthuc' => $phuongThuc,
                ':sotien' => $tongTien
            ]);

            // Xóa sản phẩm khỏi giỏ hàng
            $sqlXoa = "DELETE FROM ChiTietGioHang
                       WHERE MaGH = :magh";

            $stmtXoa = $conn->prepare($sqlXoa);
            $stmtXoa->execute([':magh' => $maGH]);

            // Hoàn tất giao dịch
            $conn->commit();

            header("Location: dathang.php?thanhcong=" . (int)$maDH);
            exit;

        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $thongBao = $e->getMessage();
        }
    }
}

// 7. Thông báo đặt hàng thành công
// $maDHThanhCong = isset($_GET['thanhcong'])
//     ? (int)$_GET['thanhcong'] : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng - StudyStore</title>
    <link rel="stylesheet" href="css/dathang.css">
</head>

<body>

<header class="header">
    <div class="container">
        <h1>StudyStore</h1>
        <p>Cửa hàng dụng cụ học tập</p>
    </div>
</header>

<nav class="menu">
    <div class="container">
        <a href="test_products.php">Trang chủ</a>
        <a href="sanpham.php">Sản phẩm</a>
        <a href="giohang.php" class="active">Giỏ hàng</a>
    </div>
</nav>

<main class="checkout-container">

    <?php if ($maDHThanhCong > 0): ?>

        <div class="success-box">
            <h2>Đặt hàng thành công!</h2>
            <p>Mã đơn hàng của bạn:
                <strong><?= $maDHThanhCong ?></strong>
            </p>
            <p>Cảm ơn bạn đã mua hàng tại StudyStore.</p>
            <a href="sanpham.php" class="btn-submit">
                Tiếp tục mua hàng
            </a>
        </div>

    <?php else: ?>

        <h2 class="page-title">XÁC NHẬN ĐẶT HÀNG</h2>

        <?php if ($thongBao !== ''): ?>
            <div class="error-message">
                <?= htmlspecialchars($thongBao) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="dathang.php">

            <div class="checkout-box">

                <section class="customer-info">
                    <h3>Thông tin nhận hàng</h3>

                    <label>Họ và tên</label>
                    <input type="text"
                           value="<?= htmlspecialchars($khachHang['HoTen']) ?>"
                           readonly>

                    <label>Email</label>
                    <input type="email"
                           value="<?= htmlspecialchars($khachHang['Email']) ?>"
                           readonly>

                    <label>Số điện thoại</label>
                    <input type="text"
                           name="SDT"
                           value="<?= htmlspecialchars($khachHang['SDT'] ?? '') ?>"
                           required>

                    <label>Địa chỉ giao hàng</label>
                    <textarea name="DiaChiGiaoHang"
                              rows="3"
                              required><?= htmlspecialchars($khachHang['DiaChi'] ?? '') ?></textarea>
                </section>

                <section class="order-info">
                    <h3>Sản phẩm đặt mua</h3>

                    <?php foreach ($sanPham as $sp): ?>
                        <div class="order-product">

                            <div>
                                <strong>
                                    <?= htmlspecialchars($sp['TenSP']) ?>
                                </strong>

                                <p>
                                    Số lượng: <?= (int)$sp['SL'] ?>
                                </p>
                            </div>

                            <strong>
                                <?= number_format(
                                    $sp['ThanhTien'], 0, ',', '.'
                                ) ?> đ
                            </strong>

                        </div>
                    <?php endforeach; ?>

                    <div class="total-price">
                        Tổng tiền:
                        <?= number_format($tongTien, 0, ',', '.') ?> đ
                    </div>

                    <h3>Phương thức thanh toán</h3>

                    <label class="payment-option">
                        <input type="radio"
                               name="PhuongThuc"
                               value="COD"
                               checked>
                        Thanh toán khi nhận hàng (COD)
                    </label>

                    <label class="payment-option">
                        <input type="radio"
                               name="PhuongThuc"
                               value="Chuyển khoản">
                        Chuyển khoản
                    </label>

                </section>

            </div>

            <div class="checkout-actions">
                <a href="giohang.php" class="btn-back">
                    Quay lại giỏ hàng
                </a>

                <button type="submit" class="btn-submit">
                    Xác nhận đặt hàng
                </button>
            </div>

        </form>

    <?php endif; ?>

</main>

<footer class="footer">
    <h3>StudyStore</h3>
    <p>Cửa hàng dụng cụ học tập dành cho mọi người.</p>
    <p>© 2026 StudyStore - Đồ án cơ sở ngành</p>
</footer>

</body>
</html>

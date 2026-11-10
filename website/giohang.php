<?php
session_start();

require_once "config/database.php";

// Chỉ khách hàng đã đăng nhập mới được sử dụng giỏ hàng
if (!isset($_SESSION['MaKH'])) {
    header("Location: dangnhaps.php");
    exit;
}

$maKH = $_SESSION['MaKH'];
$thongBao = "";

// 1. Lấy giỏ hàng của khách hàng
$sqlGH = "SELECT MaGH FROM GioHang WHERE MaKH = :makh";
$stmtGH = $conn->prepare($sqlGH);
$stmtGH->execute([':makh' => $maKH]);
$maGH = $stmtGH->fetchColumn();

// Nếu chưa có giỏ hàng thì tạo mới
if (!$maGH) {
    $sqlTaoGH = "
        INSERT INTO GioHang (MaKH, NgayTao)
        OUTPUT INSERTED.MaGH
        VALUES (:makh, GETDATE())
    ";

    $stmtTaoGH = $conn->prepare($sqlTaoGH);
    $stmtTaoGH->execute([':makh' => $maKH]);
    $maGH = $stmtTaoGH->fetchColumn();
}

// 2. Xử lý các thao tác trong giỏ hàng
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hanhDong = $_POST['hanhDong'] ?? '';

    // THÊM SẢN PHẨM
    if ($hanhDong === 'them') {

        $maSP = (int)($_POST['MaSP'] ?? 0);
        $soLuong = max(1, (int)($_POST['SL'] ?? 1));

        // Kiểm tra sản phẩm và số lượng tồn kho
        $sqlSP = "SELECT SoLuongTon FROM SanPham WHERE MaSP = :masp";
        $stmtSP = $conn->prepare($sqlSP);
        $stmtSP->execute([':masp' => $maSP]);
        $tonKho = $stmtSP->fetchColumn();

        if ($tonKho === false) {
            $thongBao = "Sản phẩm không tồn tại.";
        } elseif ($tonKho < $soLuong) {
            $thongBao = "Số lượng sản phẩm trong kho không đủ.";
        } else {
            // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
            $sqlKiemTra = "
                SELECT MaCTGH, SL
                FROM ChiTietGioHang
                WHERE MaGH = :magh AND MaSP = :masp
            ";

            $stmtKiemTra = $conn->prepare($sqlKiemTra);
            $stmtKiemTra->execute([
                ':magh' => $maGH,
                ':masp' => $maSP
            ]);

            $chiTiet = $stmtKiemTra->fetch(PDO::FETCH_ASSOC);

            if ($chiTiet) {
                $soLuongMoi = $chiTiet['SL'] + $soLuong;

                if ($soLuongMoi > $tonKho) {
                    $thongBao = "Số lượng vượt quá số lượng tồn kho.";
                } else {
                    $sqlCapNhat = "
                        UPDATE ChiTietGioHang
                        SET SL = :sl
                        WHERE MaCTGH = :mactgh
                    ";

                    $stmtCapNhat = $conn->prepare($sqlCapNhat);
                    $stmtCapNhat->execute([
                        ':sl' => $soLuongMoi,
                        ':mactgh' => $chiTiet['MaCTGH']
                    ]);

                    $thongBao = "Đã cập nhật sản phẩm trong giỏ hàng.";
                }
            } else {
                $sqlThem = "
                    INSERT INTO ChiTietGioHang (MaGH, MaSP, SL)
                    VALUES (:magh, :masp, :sl)
                ";

                $stmtThem = $conn->prepare($sqlThem);
                $stmtThem->execute([
                    ':magh' => $maGH,
                    ':masp' => $maSP,
                    ':sl' => $soLuong
                ]);

                $thongBao = "Đã thêm sản phẩm vào giỏ hàng.";
            }
        }
    }

    // CẬP NHẬT SỐ LƯỢNG
    elseif ($hanhDong === 'capnhat') {

        $maCTGH = (int)($_POST['MaCTGH'] ?? 0);
        $soLuong = (int)($_POST['SL'] ?? 1);

        if ($soLuong < 1) {
            $thongBao = "Số lượng phải lớn hơn 0.";
        } else {
            $sqlKiemTra = "
                SELECT SP.SoLuongTon
                FROM ChiTietGioHang CT
                JOIN SanPham SP ON CT.MaSP = SP.MaSP
                WHERE CT.MaCTGH = :mactgh AND CT.MaGH = :magh
            ";

            $stmtKiemTra = $conn->prepare($sqlKiemTra);
            $stmtKiemTra->execute([
                ':mactgh' => $maCTGH,
                ':magh' => $maGH
            ]);

            $tonKho = $stmtKiemTra->fetchColumn();

            if ($tonKho === false) {
                $thongBao = "Không tìm thấy sản phẩm trong giỏ hàng.";
            } elseif ($soLuong > $tonKho) {
                $thongBao = "Số lượng vượt quá số lượng tồn kho.";
            } else {
                $sqlCapNhat = "
                    UPDATE ChiTietGioHang
                    SET SL = :sl
                    WHERE MaCTGH = :mactgh AND MaGH = :magh
                ";

                $stmtCapNhat = $conn->prepare($sqlCapNhat);
                $stmtCapNhat->execute([
                    ':sl' => $soLuong,
                    ':mactgh' => $maCTGH,
                    ':magh' => $maGH
                ]);

                $thongBao = "Cập nhật số lượng thành công.";
            }
        }
    }

    // XÓA SẢN PHẨM
    elseif ($hanhDong === 'xoa') {

        $maCTGH = (int)($_POST['MaCTGH'] ?? 0);

        $sqlXoa = "
            DELETE FROM ChiTietGioHang
            WHERE MaCTGH = :mactgh AND MaGH = :magh
        ";

        $stmtXoa = $conn->prepare($sqlXoa);
        $stmtXoa->execute([
            ':mactgh' => $maCTGH,
            ':magh' => $maGH
        ]);

        $thongBao = "Đã xóa sản phẩm khỏi giỏ hàng.";
    }
}

// 3. Lấy danh sách sản phẩm trong giỏ hàng
$sqlGioHang = "
    SELECT
        CT.MaCTGH,
        CT.SL,
        SP.MaSP,
        SP.TenSP,
        SP.DonGia,
        SP.HinhAnh,
        SP.SoLuongTon,
        (CT.SL * SP.DonGia) AS ThanhTien
    FROM ChiTietGioHang CT
    JOIN SanPham SP ON CT.MaSP = SP.MaSP
    WHERE CT.MaGH = :magh
    ORDER BY CT.MaCTGH DESC
";

$stmtGioHang = $conn->prepare($sqlGioHang);
$stmtGioHang->execute([':magh' => $maGH]);
$dsGioHang = $stmtGioHang->fetchAll(PDO::FETCH_ASSOC);

// 4. Tính tổng tiền
$tongTien = 0;

foreach ($dsGioHang as $item) {
    $tongTien += $item['ThanhTien'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - StudyStore</title>
    <link rel="stylesheet" href="css/stylegh.css">
  
</head>

<body>

<div class="cart-container">

    <h2>Giỏ hàng của bạn</h2>

    <?php if ($thongBao !== ""): ?>
        <div class="cart-message">
            <?= htmlspecialchars($thongBao) ?>
        </div>
    <?php endif; ?>

    <?php if (count($dsGioHang) > 0): ?>

        <table class="cart-table">
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Tên sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($dsGioHang as $item): ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['HinhAnh'])): ?>
                                <img
                                    src="<?= htmlspecialchars($item['HinhAnh']) ?>"
                                    alt="<?= htmlspecialchars($item['TenSP']) ?>"
                                >
                            <?php else: ?>
                                Chưa có ảnh
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($item['TenSP']) ?>
                        </td>

                        <td>
                            <?= number_format($item['DonGia'], 0, ',', '.') ?> đ
                        </td>

                        <td>
                            <form method="POST" action="giohang.php">
                                <input type="hidden" name="hanhDong" value="capnhat">
                                <input type="hidden" name="MaCTGH"
                                       value="<?= $item['MaCTGH'] ?>">

                                <input type="number" name="SL"
                                       value="<?= $item['SL'] ?>"
                                       min="1"
                                       max="<?= $item['SoLuongTon'] ?>">

                                <button type="submit"
                                        class="cart-button btn-update">
                                    Cập nhật
                                </button>
                            </form>
                        </td>

                        <td>
                            <?= number_format($item['ThanhTien'], 0, ',', '.') ?> đ
                        </td>

                        <td>
                            <form method="POST" action="giohang.php"
                                  onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">

                                <input type="hidden" name="hanhDong" value="xoa">
                                <input type="hidden" name="MaCTGH"
                                       value="<?= $item['MaCTGH'] ?>">

                                <button type="submit"
                                        class="cart-button btn-delete">
                                    Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

       
<div class="cart-total">
    Tổng tiền:
    <?= number_format($tongTien, 0, ',', '.') ?> đ
</div>

<div class="cart-actions">
    <a href="sanpham.php" class="continue-shopping">
        Tiếp tục mua hàng
    </a>

    <a href="dathang.php" class="btn-checkout">
        Tiến hành đặt hàng
    </a>
</div>


    <?php else: ?>

        <div class="empty-cart">
            <h3>Giỏ hàng đang trống!</h3>
            <p>Bạn chưa thêm sản phẩm nào vào giỏ hàng.</p>

            <a href="sanpham.php" class="continue-shopping">
                Xem sản phẩm
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>

<?php
session_start();

require_once "config/database.php";
require_once "config/thanhtoan.php";

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['MaKH'])) {
    header("Location: dangnhap.php");
    exit;
}

$maKH = (int)$_SESSION['MaKH'];

// 2. Lấy mã đơn hàng từ URL
$maDH = isset($_GET['madh']) ? (int)$_GET['madh'] : 0;

if ($maDH <= 0) {
    die("Mã đơn hàng không hợp lệ!");
}

// 3. Lấy thông tin đơn hàng và thanh toán
$sql = "SELECT
            DH.MaDH,
            DH.TongTien,
            TT.SoTien,
            TT.TrangThai AS TrangThaiTT
        FROM DonHang DH
        INNER JOIN ThanhToan TT ON DH.MaDH = TT.MaDH
        WHERE DH.MaDH = :madh
          AND DH.MaKH = :makh
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':madh' => $maDH,
    ':makh' => $maKH
]);

$donHang = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donHang) {
    die("Không tìm thấy đơn hàng!");
}

// 4. Tạo mã QR VietQR
$soTien = (int)$donHang['SoTien'];
$noiDung = "STUDYSTORE DH" . $maDH;

$qrUrl = "https://img.vietqr.io/image/"
    . $bankId . "-"
    . $accountNo
    . "-compact2.png"
    . "?amount=" . $soTien
    . "&addInfo=" . rawurlencode($noiDung)
    . "&accountName=" . rawurlencode($accountName);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán QR - StudyStore</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 30px;
        }

        .payment-box {
            max-width: 480px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        h2 {
            color: #1e3a8a;
        }

        .qr-image {
            width: 280px;
            max-width: 100%;
            margin: 15px auto;
        }

        .info {
            text-align: left;
            line-height: 1.8;
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
        }

        .amount {
            color: #dc2626;
            font-size: 22px;
            font-weight: bold;
        }

        .note {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        .back {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #1e3a8a;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="payment-box">

    <h2>Thanh toán qua Sacombank</h2>

    <p>Đơn hàng số:
        <strong>#<?= htmlspecialchars((string)$maDH) ?></strong>
    </p>

    <div class="amount">
        <?= number_format($soTien, 0, ',', '.') ?> đ
    </div>

    <?php if ($donHang['TrangThaiTT'] === 'Đã thanh toán'): ?>

        <h3>Đơn hàng đã được thanh toán!</h3>

    <?php else: ?>

        <p>Quét mã QR bằng ứng dụng ngân hàng để chuyển khoản.</p>

        <img
            class="qr-image"
            src="<?= htmlspecialchars($qrUrl) ?>"
            alt="Mã QR thanh toán Sacombank"
        >

        <div class="info">
            <strong>Ngân hàng:</strong> Sacombank<br>

            <strong>Số tài khoản:</strong>
            <?= htmlspecialchars($accountNo) ?><br>

            <strong>Chủ tài khoản:</strong>
            <?= htmlspecialchars($accountName) ?><br>

            <strong>Số tiền:</strong>
            <?= number_format($soTien, 0, ',', '.') ?> đ<br>

            <strong>Nội dung chuyển khoản:</strong>
            <?= htmlspecialchars($noiDung) ?>
        </div>

        <p class="note">
            Vui lòng kiểm tra đúng người nhận, số tiền và nội dung
            trước khi xác nhận chuyển khoản trong ứng dụng ngân hàng.
        </p>

        <p>
            <strong>Trạng thái:</strong>
            <?= htmlspecialchars($donHang['TrangThaiTT']) ?>
        </p>

    <?php endif; ?>

    <a class="back" href="admin/donhang.php">Xem đơn hàng</a>

</div>

</body>
</html>

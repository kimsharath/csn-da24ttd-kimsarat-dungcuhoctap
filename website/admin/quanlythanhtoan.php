
<?php
require_once "taoadmind.php";

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['MaTK']) ||
    ($_SESSION['VaiTro'] ?? '') !== 'Admin') {
    header("Location: ../dangnhap.php");
    exit;
}

// 2. Xử lý xác nhận thanh toán
$thongBao = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['xacnhan'])) {

    $maDH = (int)$_POST['madh'];

    try {
        $sqlUpdate = "UPDATE ThanhToan
                      SET TrangThai = 'Đã thanh toán',
                          NgayThanhToan = NOW()
                      WHERE MaDH = :madh
                        AND TrangThai = 'Chờ thanh toán'";

        $stmtUpdate = $conn->prepare($sqlUpdate);

        $stmtUpdate->execute([
            ':madh' => $maDH
        ]);

        if ($stmtUpdate->rowCount() > 0) {
            $thongBao = "Đã xác nhận thanh toán đơn hàng #".$maDH;
        } else {
            $thongBao = "Đơn hàng không tồn tại hoặc đã được xác nhận.";
        }

    } catch (PDOException $e) {
        $thongBao = "Lỗi: " . $e->getMessage();
    }
}

// 3. Lấy danh sách thanh toán
$sql = "SELECT
            TT.MaTT,
            TT.MaDH,
            TT.PhuongThuc,
            TT.SoTien,
            TT.NgayThanhToan,
            TT.TrangThai,
            DH.NgayDat,
            KH.HoTen
        FROM ThanhToan TT
        INNER JOIN DonHang DH ON TT.MaDH = DH.MaDH
        INNER JOIN KhachHang KH ON DH.MaKH = KH.MaKH
        ORDER BY TT.MaTT DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$danhSach = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thanh toán</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 25px;
        }

        h2 {
            color: #1e3a8a;
        }

        .message {
            padding: 12px;
            background: #dbeafe;
            color: #1e40af;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .table-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #1e3a8a;
            color: white;
        }

        .paid {
            color: green;
            font-weight: bold;
        }

        .pending {
            color: #d97706;
            font-weight: bold;
        }

        .btn-confirm {
            background: #16a34a;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background: #15803d;
        }
    </style>
</head>

<body>

<h2>QUẢN LÝ THANH TOÁN</h2>

<?php if ($thongBao !== ''): ?>
    <div class="message">
        <?= htmlspecialchars($thongBao) ?>
    </div>
<?php endif; ?>

<div class="table-box">

<table>
    <thead>
        <tr>
            <th>Mã thanh toán</th>
            <th>Mã đơn hàng</th>
            <th>Khách hàng</th>
            <th>Phương thức</th>
            <th>Số tiền</th>
            <th>Ngày đặt</th>
            <th>Ngày thanh toán</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>

    <tbody>

    <?php if (count($danhSach) > 0): ?>

        <?php foreach ($danhSach as $tt): ?>

        <tr>
            <td><?= (int)$tt['MaTT'] ?></td>

            <td>#<?= (int)$tt['MaDH'] ?></td>

            <td>
                <?= htmlspecialchars($tt['HoTen']) ?>
            </td>

            <td>
                <?= htmlspecialchars($tt['PhuongThuc']) ?>
            </td>

            <td>
                <?= number_format($tt['SoTien'], 0, ',', '.') ?> đ
            </td>

            <td>
                <?= htmlspecialchars($tt['NgayDat']) ?>
            </td>

            <td>
                <?= $tt['NgayThanhToan']
                    ? htmlspecialchars($tt['NgayThanhToan'])
                    : 'Chưa thanh toán' ?>
            </td>

            <td>
                <?php if ($tt['TrangThai'] === 'Đã thanh toán'): ?>
                    <span class="paid">Đã thanh toán</span>
                <?php else: ?>
                    <span class="pending">
                        <?= htmlspecialchars($tt['TrangThai']) ?>
                    </span>
                <?php endif; ?>
            </td>

            <td>
                <?php if ($tt['TrangThai'] === 'Chờ thanh toán'): ?>

                    <form method="POST"
                          onsubmit="return confirm('Bạn đã kiểm tra tiền vào tài khoản và muốn xác nhận đơn hàng này?');">

                        <input type="hidden"
                               name="madh"
                               value="<?= (int)$tt['MaDH'] ?>">

                        <button type="submit"
                                name="xacnhan"
                                class="btn-confirm">
                            Xác nhận
                        </button>

                    </form>

                <?php else: ?>
                    <span>Đã xác nhận</span>
                <?php endif; ?>
            </td>

        </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>
            <td colspan="9">Chưa có dữ liệu thanh toán.</td>
        </tr>

    <?php endif; ?>

    </tbody>
</table>

</div>

</body>
</html>

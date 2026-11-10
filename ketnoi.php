



<?php

$serverName = ".\\SQLEXPRESS";
$database = "qldungcuhoctap";
try {
    $conn = new PDO(
        "sqlsrv:Server=$serverName;Database=$database;Encrypt=no;TrustServerCertificate=yes",
        null,
        null
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo "Kết nối SQL Server thành công!";
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>




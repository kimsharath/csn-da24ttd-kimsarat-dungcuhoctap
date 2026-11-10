<?php

require_once "config/database.php";

try {

    $sql = "SELECT DB_NAME() AS TenDatabase";

    $stmt = $conn->query($sql);

    $result = $stmt->fetch();

    echo "Kết nối thành công!<br>";

    echo "Database: " . $result["TenDatabase"];

} catch (PDOException $e) {

    echo "Lỗi: " . $e->getMessage();

}
?>
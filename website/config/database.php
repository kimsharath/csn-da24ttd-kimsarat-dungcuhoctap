<?php

$server = ".\\SQLEXPRESS";
$database = "QuanLyDungCuHocTap_Moi";
$username = "sa";
$password = "Sarat@2026New";

try {
    $conn = new PDO(
        "sqlsrv:Server=$server;Database=$database;TrustServerCertificate=true",
        $username,
        $password
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $conn->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
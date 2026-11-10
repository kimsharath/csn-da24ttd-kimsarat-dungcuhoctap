<?php
echo "<h2>Kiểm tra PHP</h2>";

echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";
?>
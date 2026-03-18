<?php
require "csdl.php";

$sql = "ALTER TABLE khachhang MODIFY COLUMN MaKH bigint(20) NOT NULL AUTO_INCREMENT;";

try {
    $connection->exec($sql);
    echo "Thanh cong AUTO_INCREMENT";
} catch (PDOException $e) {
    echo "Loi: " . $e->getMessage();
}

<?php
require "csdl.php";

$sql = "ALTER TABLE khachhang 
        ADD COLUMN IF NOT EXISTS TenDangNhap VARCHAR(50) UNIQUE,
        ADD COLUMN IF NOT EXISTS MatKhau VARCHAR(255);";

try {
    $connection->exec($sql);
    echo "Thanh cong";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') { // Duplicate column
        echo "Column already exists.";
    } else {
         // MariaDB might not support ADD COLUMN IF NOT EXISTS, let's just run it
         try {
             $sql1 = "ALTER TABLE khachhang ADD COLUMN TenDangNhap VARCHAR(50) UNIQUE";
             $connection->exec($sql1);
         } catch(Exception $e) {}
         
         try {
             $sql2 = "ALTER TABLE khachhang ADD COLUMN MatKhau VARCHAR(255)";
             $connection->exec($sql2);
         } catch(Exception $e) {}
         
         echo "Done fallback";
    }
}

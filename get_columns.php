<?php
require "csdl.php";

$tables = ['taikhoan', 'khachhang', 'users'];
foreach ($tables as $t) {
    try {
        $st = $connection->query("DESCRIBE $t");
        $cols = $st->fetchAll(PDO::FETCH_COLUMN);
        echo "Table $t:\n";
        print_r($cols);
    } catch (Exception $e) {
        echo "Table $t not found or error.\n";
    }
}

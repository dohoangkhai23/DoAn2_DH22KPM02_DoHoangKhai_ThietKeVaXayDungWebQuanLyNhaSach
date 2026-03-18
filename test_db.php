<?php
require "csdl.php";

echo "--- sanpham ---\n";
$stmt = $connection->query('SELECT * FROM sanpham');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- sach ---\n";
$stmt = $connection->query('SELECT * FROM sach LIMIT 2');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

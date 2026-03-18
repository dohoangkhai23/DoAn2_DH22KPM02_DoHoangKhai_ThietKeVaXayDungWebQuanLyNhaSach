<?php
require "csdl.php";
$st = $connection->query("DESCRIBE khachhang");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

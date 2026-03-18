<?php
require "csdl.php";
$st = $connection->query("DESCRIBE taikhoan");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

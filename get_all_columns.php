<?php
require "csdl.php";

try {
    $st = $connection->query("SHOW TABLES");
    $tables = $st->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $st = $connection->query("DESCRIBE $t");
        $cols = $st->fetchAll(PDO::FETCH_COLUMN);
        echo "Table $t: " . implode(", ", $cols) . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

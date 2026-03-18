<?php
    require "csdl.php";
    header('Content-Type: application/json; charset=utf-8');

    if ($connection === null) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Không thể kết nối đến cơ sở dữ liệu.'
        ]);
        exit;
    }

    try {
        $sql = "SELECT MaSach, TenSach, GiaBan, HinhAnh FROM sach";
        $st = $connection->prepare($sql);
        $st->execute();
        $data = $st->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'DSSach' => $data
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()
        ]);
    }
?>
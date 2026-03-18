<?php
    require "../csdl.php";

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_hot_products':
            // Lấy 5 sản phẩm có giá bán cao nhất làm sản phẩm hot.
            // Đồng thời, sử dụng alias (AS) để đổi tên cột cho phù hợp với yêu cầu của JavaScript.
            $sql = "SELECT TenSach AS tensach, GiaBan AS dongia, HinhAnh AS hinh FROM sach ORDER BY GiaBan DESC LIMIT 5";
            $st = $connection->prepare($sql);
            $st->execute();
            $data = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['hot_products' => $data]);
            break;
        default:
            $sql = "SELECT * FROM sach";
            $st = $connection->prepare($sql);
            $st->execute();
            $data = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['DSSach' => $data]);
            break;
    }
?>
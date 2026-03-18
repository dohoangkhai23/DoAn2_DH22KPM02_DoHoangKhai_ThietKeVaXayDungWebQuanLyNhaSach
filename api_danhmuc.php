<?php
/**
 * api_danhmuc.php
 * Trả về danh sách sản phẩm theo thể loại (dùng cho mega menu hover)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/csdl.php';

$maTheLoai = isset($_GET['theloai']) ? (int)$_GET['theloai'] : 0;

if ($maTheLoai <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $connection->prepare(
        "SELECT MaSach, TenSach, GiaBan, HinhAnh, TacGia 
         FROM sach 
         WHERE MaTheLoai = :id 
         ORDER BY MaSach DESC 
         LIMIT 8"
    );
    $stmt->execute([':id' => $maTheLoai]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode([]);
}

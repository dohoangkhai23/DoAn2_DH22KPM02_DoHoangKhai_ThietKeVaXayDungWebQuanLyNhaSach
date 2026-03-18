<?php
session_start();
require_once "csdl.php";

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$maDH = (int)$_POST['madh'];
$username = $_SESSION['user']['username'];

try {
    // Kiểm tra xem đơn hàng có thuộc về user này và đang ở trạng thái Chờ Xử Lý không
    $stmt = $connection->prepare("
        SELECT dh.MaDH FROM DonHang dh 
        JOIN KhachHang kh ON dh.MaKH = kh.MaKH 
        WHERE dh.MaDH = ? AND kh.TenDangNhap = ? AND dh.TrangThai = 'Chờ Xử Lý'
    ");
    $stmt->execute([$maDH, $username]);
    
    if ($stmt->rowCount() > 0) {
        // Hủy đơn hàng
        $updateStmt = $connection->prepare("UPDATE DonHang SET TrangThai = 'Đã Hủy' WHERE MaDH = ?");
        $updateStmt->execute([$maDH]);
        echo "<script>alert('Đã hủy đơn hàng thành công!'); window.location.href='lichsudonhang.php';</script>";
    } else {
        echo "<script>alert('Không thể hủy đơn hàng này. Vui lòng kiểm tra lại!'); window.location.href='lichsudonhang.php';</script>";
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>

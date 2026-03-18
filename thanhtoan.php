<?php
session_start();
require_once "csdl.php";

// Yêu cầu đăng nhập để thanh toán
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành thanh toán!'); window.location.href='dangnhap.php';</script>";
    exit();
}

$user = $_SESSION['user'];
$username = $user['username'];

// Lấy thông tin khách hàng từ DB
$maKH = null;
$tenKH = '';
$sdt = '';
$diaChi = '';

try {
    $stmt = $connection->prepare("SELECT * FROM KhachHang WHERE TenDangNhap = ?");
    $stmt->execute([$username]);
    if ($kh = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $maKH = $kh['MaKH'];
        $tenKH = $kh['TenKH'] ?? '';
        $sdt = $kh['SDT'] ?? '';
        $diaChi = $kh['DiaChi'] ?? '';
    }
} catch (PDOException $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}

// Xử lý khi Submit Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dathang'])) {
    $hoTenNguoiNhan = $_POST['hoten'];
    $sdtNguoiNhan = $_POST['sdt'];
    $diaChiGiaoHang = $_POST['diachi'];
    $phuongThucTT = $_POST['phuongthuc'];
    $ghiChu = $_POST['ghichu'] ?? '';
    
    // Nhận chuỗi JSON từ giỏ hàng (LocalStorage)
    $cartData = $_POST['cart_data'] ?? '[]';
    $cart = json_decode($cartData, true);
    
    if (empty($cart)) {
        echo "<script>alert('Giỏ hàng trống!'); window.location.href='index.php';</script>";
        exit();
    }
    
    $tongTien = 0;
    foreach ($cart as $item) {
        $tongTien += ($item['price'] * $item['quantity']);
    }
    
    try {
        $connection->beginTransaction();
        
        // 1. Thêm vào bảng DonHang
        $stmtDH = $connection->prepare("INSERT INTO DonHang (MaKH, TongTien, PhuongThucTT, HoTenNguoiNhan, SDTNguoiNhan, DiaChiGiaoHang, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtDH->execute([$maKH, $tongTien, $phuongThucTT, $hoTenNguoiNhan, $sdtNguoiNhan, $diaChiGiaoHang, $ghiChu]);
        $maDH = $connection->lastInsertId();
        
        // 2. Thêm vào bảng ChiTietDonHang
        $stmtCT = $connection->prepare("INSERT INTO ChiTietDonHang (MaDH, MaSanPham, SoLuong, DonGia, ThanhTien) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($cart as $item) {
            $maSP = $item['id'];
            $soLuong = $item['quantity'];
            $donGia = $item['price'];
            $thanhTien = $donGia * $soLuong;
            $stmtCT->execute([$maDH, $maSP, $soLuong, $donGia, $thanhTien]);
        }
        
        $connection->commit();
        
        // Xóa giỏ hàng LocalStorage và chuyển hướng
        echo "<script>
            localStorage.removeItem('cart');
            alert('Đặt hàng thành công! Cảm ơn bạn đã mua sắm.');
            window.location.href='lichsudonhang.php';
        </script>";
        exit();
        
    } catch (Exception $e) {
        $connection->rollBack();
        echo "<script>alert('Có lỗi xảy ra trong quá trình đặt hàng: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh Toán - Nhà Sách</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; color: #333; }
        .checkout-container { max-width: 1000px; margin: 40px auto; display: flex; gap: 20px; padding: 0 15px; }
        .checkout-form { flex: 2; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .order-summary { flex: 1; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content; }
        
        h2 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { border-color: #3498db; outline: none; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        
        .summary-item { display: flex; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .summary-item img { width: 60px; height: 80px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
        .summary-item-info { flex: 1; }
        .summary-item-name { font-weight: 600; font-size: 14px; margin: 0 0 5px; }
        .summary-item-price { color: #e74c3c; font-weight: bold; font-size: 14px; }
        .summary-item-qty { font-size: 13px; color: #7f8c8d; }
        
        .total-row { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-top: 20px; padding-top: 15px; border-top: 2px solid #eee; }
        
        .btn-submit { display: block; width: 100%; padding: 15px; background: #e74c3c; color: white; text-align: center; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 20px; transition: background 0.3s; }
        .btn-submit:hover { background: #c0392b; }
        
        .back-link { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="checkout-container">
    <!-- Form thông tin giao hàng -->
    <div class="checkout-form">
        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm</a>
        <h2>Thông Tin Thanh Toán</h2>
        
        <form method="POST" id="checkoutForm">
            <input type="hidden" name="cart_data" id="cartDataInput">
            
            <div class="form-group">
                <label>Họ và Tên người nhận</label>
                <input type="text" name="hoten" value="<?php echo htmlspecialchars($tenKH); ?>" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="sdt" value="<?php echo htmlspecialchars($sdt); ?>" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ giao hàng chi tiết</label>
                <textarea name="diachi" required><?php echo htmlspecialchars($diaChi); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Phương thức thanh toán</label>
                <select name="phuongthuc" required>
                    <option value="Thanh toán khi nhận hàng (COD)">Thanh toán khi nhận hàng (COD)</option>
                    <option value="Chuyển khoản ngân hàng">Chuyển khoản qua Ngân hàng</option>
                    <option value="Ví điện tử MoMo">Thanh toán qua Momo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Ghi chú đơn hàng (Tùy chọn)</label>
                <textarea name="ghichu" placeholder="Ghi chú thêm về đơn hàng..."></textarea>
            </div>
            
            <button type="submit" name="dathang" class="btn-submit">ĐẶT HÀNG</button>
        </form>
    </div>
    
    <!-- Tóm tắt đơn hàng -->
    <div class="order-summary">
        <h2>Giỏ Hàng Của Bạn</h2>
        <div id="summaryItems">
            <!-- Render từ JS -->
        </div>
        <div class="total-row">
            <span>Tổng cộng:</span>
            <span id="summaryTotal" style="color:#e74c3c;">0 đ</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (cart.length === 0) {
            alert("Giỏ hàng của bạn đang trống!");
            window.location.href = "index.php";
            return;
        }

        // Đổ dữ liệu vào hidden input để PHP xử lý
        document.getElementById('cartDataInput').value = JSON.stringify(cart);

        const summaryItems = document.getElementById('summaryItems');
        const summaryTotal = document.getElementById('summaryTotal');
        
        let total = 0;
        
        cart.forEach(item => {
            const row = document.createElement('div');
            row.className = 'summary-item';
            
            // Lấy ảnh gốc (bỏ bớt images/ nếu cần hoặc giữ nguyên)
            let imgSrc = item.img;
            
            const thanhTien = item.price * item.quantity;
            total += thanhTien;
            
            row.innerHTML = `
                <img src="${imgSrc}" alt="${item.name}">
                <div class="summary-item-info">
                    <p class="summary-item-name">${item.name}</p>
                    <span class="summary-item-qty">Số lượng: ${item.quantity}</span>
                    <p class="summary-item-price">${thanhTien.toLocaleString('vi-VN')} đ</p>
                </div>
            `;
            summaryItems.appendChild(row);
        });
        
        summaryTotal.innerText = total.toLocaleString('vi-VN') + " đ";
    });
</script>

</body>
</html>

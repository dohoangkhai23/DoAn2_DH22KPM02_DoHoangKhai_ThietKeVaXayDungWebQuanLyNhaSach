<?php
session_start();
require_once "../csdl.php";

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: quanlysanpham.php");
    exit();
}

$id = (int)$_GET['id'];
$msg = '';

// Lấy thông tin sách hiện tại
try {
    $stmt = $connection->prepare("SELECT * FROM sach WHERE MaSach = ?");
    $stmt->execute([$id]);
    $sach = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sach) {
        die("Không tìm thấy sách!");
    }
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Lấy danh sách thể loại
try {
    $stmtTL = $connection->query("SELECT * FROM theloai WHERE (TrangThaiXoa IS NULL OR TrangThaiXoa = 0)");
    $theloais = $stmtTL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $theloais = []; }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $tensach = trim($_POST['tensach']);
    $tacgia = trim($_POST['tacgia']);
    $ngayxb = $_POST['ngayxb'];
    $nxb = trim($_POST['nxb']);
    $giaban = $_POST['giaban'];
    $soluong = $_POST['soluong'];
    $matheloai = $_POST['matheloai'];
    
    // Xử lý upload ảnh (nếu có chọn ảnh mới)
    $hinhanh = $sach['HinhAnh']; // Giữ ảnh cũ
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $uploadDir = '../images/';
        $fileName = time() . '_' . basename($_FILES['hinhanh']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadFile)) {
            $hinhanh = $fileName;
            // Xóa ảnh cũ nếu có lệnh
            // if ($sach['HinhAnh'] && file_exists("../images/".$sach['HinhAnh'])) {
            //     unlink("../images/".$sach['HinhAnh']);
            // }
        } else {
            $msg = "Lỗi upload ảnh!";
        }
    }
    
    if (empty($msg)) {
        try {
            $stmtUpdate = $connection->prepare("
                UPDATE sach SET TenSach=?, TacGia=?, NgayXuatBan=?, NhaXuatBan=?, GiaBan=?, SoLuong=?, HinhAnh=?, MaTheLoai=?
                WHERE MaSach=?
            ");
            $stmtUpdate->execute([$tensach, $tacgia, $ngayxb, $nxb, $giaban, $soluong, $hinhanh, $matheloai, $id]);
            echo "<script>alert('Cập nhật sách thành công!'); window.location.href='quanlysanpham.php';</script>";
            exit();
        } catch (PDOException $e) {
            $msg = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Thông Tin Sách</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; font-weight: 500; }
        h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-top: 0; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-family: inherit; }
        .form-group input:focus, .form-group select:focus { border-color: #3498db; outline: none; }
        
        .btn-submit { background: #f39c12; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: bold; margin-top: 10px; display: inline-block; }
        .btn-submit:hover { background: #d68910; }
        .msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
        .img-preview { max-height: 150px; border-radius: 5px; margin-top: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="container">
    <a href="quanlysanpham.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
    
    <h2><i class="fa-solid fa-edit"></i> Sửa Thông Tin Sách #<?php echo $id; ?></h2>
    
    <?php if(!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Tên Sách <span style="color:red">*</span></label>
                <input type="text" name="tensach" required value="<?php echo htmlspecialchars($sach['TenSach']); ?>">
            </div>
            
            <div class="form-group">
                <label>Tác Giả</label>
                <input type="text" name="tacgia" value="<?php echo htmlspecialchars($sach['TacGia']); ?>">
            </div>
            
            <div class="form-group">
                <label>Thể Loại <span style="color:red">*</span></label>
                <select name="matheloai" required>
                    <option value="">-- Chọn thể loại --</option>
                    <?php foreach($theloais as $tl): ?>
                        <option value="<?php echo $tl['MaTheLoai']; ?>" <?php if($tl['MaTheLoai'] == $sach['MaTheLoai']) echo "selected"; ?>>
                            <?php echo htmlspecialchars($tl['TenTheLoai']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Giá Bán (VNĐ) <span style="color:red">*</span></label>
                <input type="number" name="giaban" required min="0" value="<?php echo (int)$sach['GiaBan']; ?>">
            </div>
            
            <div class="form-group">
                <label>Số Lượng Tồn Kho <span style="color:red">*</span></label>
                <input type="number" name="soluong" required min="0" value="<?php echo $sach['SoLuong']; ?>">
            </div>
            
            <div class="form-group">
                <label>Nhà Xuất Bản</label>
                <input type="text" name="nxb" value="<?php echo htmlspecialchars($sach['NhaXuatBan']); ?>">
            </div>
            
            <div class="form-group">
                <label>Ngày Xuất Bản</label>
                <input type="date" name="ngayxb" value="<?php echo $sach['NgayXuatBan']; ?>">
            </div>
            
            <div class="form-group full-width">
                <label>Ảnh Bìa Hiện Tại</label>
                <?php if(!empty($sach['HinhAnh'])): ?>
                    <img src="../images/<?php echo htmlspecialchars($sach['HinhAnh']); ?>" class="img-preview" alt="Ảnh sách">
                <?php else: ?>
                    <p style="color:#777; font-style:italic;">Chưa có ảnh</p>
                <?php endif; ?>
                
                <label style="margin-top: 15px;">Thay đổi ảnh bề (Để trống nếu muốn giữ nguyên ảnh cũ)</label>
                <input type="file" name="hinhanh" accept="image/*">
            </div>
        </div>
        
        <button type="submit" name="update" class="btn-submit"><i class="fa-solid fa-save"></i> Cập Nhật Lên Hệ Thống</button>
    </form>
</div>

</body>
</html>

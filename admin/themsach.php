<?php
session_start();
require_once "../csdl.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../dangnhap.php");
    exit();
}

$msg = '';

// Lấy danh sách thể loại
try {
    $stmtTL = $connection->query("SELECT * FROM theloai WHERE (TrangThaiXoa IS NULL OR TrangThaiXoa = 0)");
    $theloais = $stmtTL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $theloais = []; }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $tensach = trim($_POST['tensach']);
    $tacgia = trim($_POST['tacgia']);
    $ngayxb = $_POST['ngayxb'];
    $nxb = trim($_POST['nxb']);
    $giaban = $_POST['giaban'];
    $soluong = $_POST['soluong'];
    $matheloai = $_POST['matheloai'];
    
    // Xử lý upload ảnh
    $hinhanh = '';
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $uploadDir = '../images/';
        $fileName = time() . '_' . basename($_FILES['hinhanh']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadFile)) {
            $hinhanh = $fileName;
        } else {
            $msg = "Lỗi upload ảnh!";
        }
    }
    
    if (empty($msg)) {
        try {
            $stmt = $connection->prepare("
                INSERT INTO sach (TenSach, TacGia, NgayXuatBan, NhaXuatBan, GiaBan, SoLuong, HinhAnh, MaTheLoai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tensach, $tacgia, $ngayxb, $nxb, $giaban, $soluong, $hinhanh, $matheloai]);
            echo "<script>alert('Thêm sách thành công!'); window.location.href='quanlysanpham.php';</script>";
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
    <title>Thêm Sách Mới</title>
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
        
        .btn-submit { background: #2ecc71; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: bold; margin-top: 10px; display: inline-block; }
        .btn-submit:hover { background: #27ae60; }
        .msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="quanlysanpham.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
    
    <h2><i class="fa-solid fa-plus-circle"></i> Thêm Sách Mới</h2>
    
    <?php if(!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Tên Sách <span style="color:red">*</span></label>
                <input type="text" name="tensach" required>
            </div>
            
            <div class="form-group">
                <label>Tác Giả</label>
                <input type="text" name="tacgia">
            </div>
            
            <div class="form-group">
                <label>Thể Loại <span style="color:red">*</span></label>
                <select name="matheloai" required>
                    <option value="">-- Chọn thể loại --</option>
                    <?php foreach($theloais as $tl): ?>
                        <option value="<?php echo $tl['MaTheLoai']; ?>"><?php echo htmlspecialchars($tl['TenTheLoai']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Giá Bán (VNĐ) <span style="color:red">*</span></label>
                <input type="number" name="giaban" required min="0">
            </div>
            
            <div class="form-group">
                <label>Số Lượng Tồn Kho <span style="color:red">*</span></label>
                <input type="number" name="soluong" required min="0" value="0">
            </div>
            
            <div class="form-group">
                <label>Nhà Xuất Bản</label>
                <input type="text" name="nxb">
            </div>
            
            <div class="form-group">
                <label>Ngày Xuất Bản</label>
                <input type="date" name="ngayxb">
            </div>
            
            <div class="form-group full-width">
                <label>Hình Ảnh Bìa Sách (Chọn file từ máy tính) <span style="color:red">*</span></label>
                <input type="file" name="hinhanh" accept="image/*" required>
            </div>
        </div>
        
        <button type="submit" name="add" class="btn-submit"><i class="fa-solid fa-save"></i> Lưu Sách Mới</button>
    </form>
</div>

</body>
</html>

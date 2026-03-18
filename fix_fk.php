<?php
require_once "csdl.php";

echo "<pre style='font-family:monospace;font-size:14px;'>";

try {
    // Bước 1: Kiểm tra các foreign key hiện có trong bảng ChiTietDonHang
    $stmt = $connection->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'chitietdonhang'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== FOREIGN KEYS HIỆN TẠI ===\n";
    foreach ($fks as $fk) {
        echo "Tên FK: {$fk['CONSTRAINT_NAME']}, Cột: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
    echo "\n";
    
    // Bước 2: Xóa tất cả FK liên quan đến MaSanPham trỏ sang sai bảng
    foreach ($fks as $fk) {
        if ($fk['COLUMN_NAME'] == 'MaSanPham' && $fk['REFERENCED_TABLE_NAME'] != 'sach') {
            $fkName = $fk['CONSTRAINT_NAME'];
            $connection->exec("ALTER TABLE chitietdonhang DROP FOREIGN KEY `$fkName`");
            echo "✓ Đã xóa FK: $fkName\n";
        }
    }
    
    // Bước 3: Kiểm tra xem sach.MaSach có tồn tại không
    $check = $connection->query("SHOW COLUMNS FROM sach LIKE 'MaSach'");
    if ($check->rowCount() == 0) {
        echo "❌ Lỗi: Bảng 'sach' không có cột 'MaSach'\n";
        exit;
    }
    
    // Bước 4: Kiểm tra kiểu dữ liệu để khớp
    $typeCheck = $connection->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sach' AND COLUMN_NAME = 'MaSach'
    ")->fetch();
    $typeSach = $typeCheck['COLUMN_TYPE'];
    
    $typeCheck2 = $connection->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chitietdonhang' AND COLUMN_NAME = 'MaSanPham'
    ")->fetch();
    $typeCT = $typeCheck2 ? $typeCheck2['COLUMN_TYPE'] : 'N/A';
    
    echo "Kiểu 'sach.MaSach': $typeSach\n";
    echo "Kiểu 'chitietdonhang.MaSanPham': $typeCT\n\n";
    
    // Bước 5: Đồng bộ kiểu dữ liệu nếu cần
    if (strpos($typeSach, 'bigint') !== false) {
        $connection->exec("ALTER TABLE chitietdonhang MODIFY COLUMN MaSanPham BIGINT(20) NOT NULL");
        echo "✓ Đã sửa kiểu MaSanPham thành BIGINT(20)\n";
    } else {
        $connection->exec("ALTER TABLE chitietdonhang MODIFY COLUMN MaSanPham INT(11) NOT NULL");
        echo "✓ Đã sửa kiểu MaSanPham thành INT(11)\n";
    }
    
    // Bước 6: Thêm FK mới trỏ đúng sang sach.MaSach
    // Kiểm tra xem FK đúng đã có chưa
    $existCheck = $connection->query("
        SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'chitietdonhang'
          AND COLUMN_NAME = 'MaSanPham'
          AND REFERENCED_TABLE_NAME = 'sach'
    ")->fetch();
    
    if (!$existCheck) {
        $connection->exec("
            ALTER TABLE chitietdonhang 
            ADD CONSTRAINT fk_chitiet_sach 
            FOREIGN KEY (MaSanPham) REFERENCES sach(MaSach) ON DELETE CASCADE
        ");
        echo "✓ Đã thêm FK mới: MaSanPham -> sach.MaSach\n";
    } else {
        echo "ℹ FK đúng đã tồn tại: {$existCheck['CONSTRAINT_NAME']}\n";
    }
    
    echo "\n=== XONG! Bạn có thể thử đặt hàng lại bây giờ. ===\n";
    
} catch (PDOException $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

<?php
session_start();
require_once __DIR__ . "/csdl.php";

// === Nhận tham số lọc từ URL ===
$keyword    = isset($_GET['keyword'])   ? trim($_GET['keyword'])        : '';
$maTheLoai  = isset($_GET['theloai'])  ? (int)$_GET['theloai']         : 0;
$thuongHieu = isset($_GET['thuonghieu']) ? trim($_GET['thuonghieu'])   : '';
$giaRange   = isset($_GET['gia'])      ? trim($_GET['gia'])            : '';

// === Lấy danh sách thể loại ===
try {
    $stmtTL = $connection->query("SELECT * FROM theloai WHERE (TrangThaiXoa IS NULL OR TrangThaiXoa = 0) ORDER BY MaTheLoai ASC");
    $danhMucList = $stmtTL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $danhMucList = []; }

// === Lấy danh sách Nhà Xuất Bản distinct từ bảng sach ===
try {
    $stmtNXB = $connection->query("SELECT DISTINCT NhaXuatBan FROM sach WHERE NhaXuatBan IS NOT NULL AND NhaXuatBan != '' ORDER BY NhaXuatBan ASC");
    $danhSachNXB = $stmtNXB->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $danhSachNXB = []; }

// === Lấy sản phẩm HOT (5 sản phẩm mới nhất / giá cao nhất) ===
try {
    $stmtHot = $connection->query("SELECT MaSach, TenSach, GiaBan, HinhAnh FROM sach ORDER BY MaSach DESC LIMIT 5");
    $hotProducts = $stmtHot->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $hotProducts = []; }

// === Xây dựng query lọc sản phẩm chính ===
try {
    $conditions = [];
    $params = [];

    if ($keyword) {
        $conditions[] = "(TenSach LIKE ? OR TacGia LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    if ($maTheLoai) {
        $conditions[] = "MaTheLoai = ?";
        $params[] = $maTheLoai;
    }
    if ($thuongHieu) {
        $conditions[] = "NhaXuatBan = ?";
        $params[] = $thuongHieu;
    }
    if ($giaRange) {
        switch ($giaRange) {
            case 'duoi100':    $conditions[] = "GiaBan < 100000"; break;
            case '100-200':    $conditions[] = "GiaBan BETWEEN 100000 AND 200000"; break;
            case '200-300':    $conditions[] = "GiaBan BETWEEN 200000 AND 300000"; break;
            case '300-500':    $conditions[] = "GiaBan BETWEEN 300000 AND 500000"; break;
            case '500-1000':   $conditions[] = "GiaBan BETWEEN 500000 AND 1000000"; break;
            case 'tren1000':   $conditions[] = "GiaBan > 1000000"; break;
        }
    }

    $sql = "SELECT * FROM sach";
    if (!empty($conditions)) $sql .= " WHERE " . implode(" AND ", $conditions);
    $sql .= " ORDER BY MaSach DESC";

    $stmt = $connection->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// Label giá hiển thị
$giaLabels = [
    'duoi100'  => 'Giá dưới 100.000đ',
    '100-200'  => '100.000đ - 200.000đ',
    '200-300'  => '200.000đ - 300.000đ',
    '300-500'  => '300.000đ - 500.000đ',
    '500-1000' => '500.000đ - 1.000.000đ',
    'tren1000' => 'Giá trên 1.000.000đ',
];

// Tên thể loại hiện tại
$tenTheLoaiHienTai = '';
foreach ($danhMucList as $dm) {
    if ($dm['MaTheLoai'] == $maTheLoai) { $tenTheLoaiHienTai = $dm['TenTheLoai']; break; }
}

// Helper: xây URL giữ nguyên param, chỉ thay 1 param
function buildUrl($params) {
    $base = [];
    foreach ($params as $k => $v) { if ($v !== '' && $v !== 0 && $v !== null) $base[$k] = $v; }
    return 'SanPham.php?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sản Phẩm - Nhà Sách</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body { padding-top: 100px; }
        header { background: #333; }

        /* ===== Layout 3 cột ===== */
        .sp-layout {
            display: flex;
            gap: 20px;
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
            align-items: flex-start;
        }

        /* ===== Sidebar Trái ===== */
        .sp-sidebar-left {
            flex: 0 0 220px;
            min-width: 0;
        }
        .filter-box {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .filter-box-header {
            background: #6b8e3e;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
        }
        .filter-box-header i { font-size: 11px; }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            color: #444;
            cursor: pointer;
            transition: background 0.15s;
        }
        .filter-item:last-child { border-bottom: none; }
        .filter-item:hover { background: #f9fff3; }
        .filter-item.active {
            color: #4a7a1e;
            font-weight: 700;
            background: #f0fdf0;
        }
        .filter-item input[type="checkbox"] {
            accent-color: #6b8e3e;
            width: 15px;
            height: 15px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .filter-item a {
            color: inherit;
            text-decoration: none;
            flex: 1;
        }
        .filter-item:hover a { color: #4a7a1e; }

        /* ===== Cột giữa ===== */
        .sp-main {
            flex: 1;
            min-width: 0;
        }
        .sp-main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .sp-main-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        .sp-count {
            font-size: 13px;
            color: #888;
        }

        /* Breadcrumb lọc đang áp dụng */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }
        .filter-tag {
            background: #eaf4df;
            color: #4a7a1e;
            border: 1px solid #c5e09b;
            border-radius: 20px;
            padding: 3px 12px 3px 10px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tag a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
            line-height: 1;
        }
        .filter-tag a:hover { color: #c0392b; }

        /* Product grid trong trang sp */
        .sp-product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        @media (max-width: 900px) {
            .sp-product-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .sp-product-grid { grid-template-columns: 1fr; }
            .sp-layout { flex-direction: column; }
            .sp-sidebar-left, .sp-sidebar-right { flex: none; width: 100%; }
        }

        /* ===== Sidebar Phải - Sản Phẩm Hot ===== */
        .sp-sidebar-right {
            flex: 0 0 200px;
            min-width: 0;
        }
        .hot-box {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .hot-box-header {
            background: #6b8e3e;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
        }
        .hot-product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            transition: background 0.15s;
        }
        .hot-product-item:last-child { border-bottom: none; }
        .hot-product-item:hover { background: #f9fff3; }
        .hot-product-item img {
            width: 48px;
            height: 65px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
            border: 1px solid #eee;
        }
        .hot-item-info { flex: 1; min-width: 0; }
        .hot-item-name {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .hot-item-price {
            font-size: 13px;
            color: #e74c3c;
            font-weight: 700;
        }

        /* Card sản phẩm */
        .product-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #ececec;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s, transform 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .product-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.13);
            transform: translateY(-3px);
        }
        .product-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; flex: 1; }
        .product-image-container {
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 180px;
            overflow: hidden;
        }
        .product-image {
            max-height: 165px;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        .product-card:hover .product-image { transform: scale(1.05); }
        .product-info { padding: 12px; flex: 1; }
        .product-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }
        .product-price {
            color: #e74c3c;
            font-weight: 700;
            font-size: 14px;
        }
        .add-to-cart-btn {
            background: #6b8e3e;
            color: #fff;
            border: none;
            width: 100%;
            padding: 9px 0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .add-to-cart-btn:hover { background: #4a7a1e; }

        .empty-products {
            grid-column: span 3;
            text-align: center;
            padding: 60px 0;
            color: #888;
        }
        .empty-products i { font-size: 48px; color: #ddd; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

<header>
    <div class="logo">📚 Nhà Sách</div>
    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li class="nav-dropdown">
                <a href="SanPham.php" style="display:flex;align-items:center;gap:6px;">Danh mục <i class="fa-solid fa-chevron-down" style="font-size:11px;"></i></a>
                <div class="category-mega-menu">
                    <div class="category-panel">
                        <div class="category-panel-header"><span>Danh Mục Sản Phẩm</span><i class="fa-solid fa-bars"></i></div>
                        <ul class="category-list">
                            <?php foreach($danhMucList as $idx => $dm): ?>
                            <li><a href="SanPham.php?theloai=<?php echo $dm['MaTheLoai']; ?>">
                                <span class="cat-left">
                                    <i class="fa-solid fa-book-open" style="color:#2ecc71;font-size:13px;"></i>
                                    <?php echo htmlspecialchars($dm['TenTheLoai']); ?>
                                </span>
                                <i class="fa-solid fa-chevron-right cat-arrow"></i>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </li>
            <li><a href="SanPham.php">Sản phẩm</a></li>
            <li><a href="Gioithieu.php">Giới thiệu</a></li>
            <li><a href="lienhe.php">Liên hệ</a></li>
        </ul>
    </nav>
    <div class="search-bar">
        <form action="SanPham.php" method="GET">
            <input type="text" name="keyword" placeholder="Tìm kiếm sách..." value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>
    <div class="header-right">
        <div class="icon-box">
            <a href="#" onclick="toggleCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="badge side-cart-count">0</span>
            </a>
        </div>
        <div class="icon-box"><a href="#"><i class="fa-solid fa-user"></i></a></div>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-dropdown">
                <span class="club-btn user-trigger" style="background:#2ecc71; cursor:pointer;">
                    Xin chào, <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?> <i class="fa-solid fa-caret-down"></i>
                </span>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fa-solid fa-user-pen"></i> Hồ sơ cá nhân</a>
                    <a href="lichsudonhang.php"><i class="fa-solid fa-receipt"></i> Đơn hàng của tôi</a>
                    <a href="doimatkhau.php"><i class="fa-solid fa-key"></i> Đổi mật khẩu</a>
                    <a href="dangxuat.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                </div>
            </div>
        <?php else: ?>
            <a href="dangky.php" class="club-btn" style="margin-right:10px;background:#3498db;">Đăng ký</a>
            <a href="dangnhap.php" class="club-btn">Đăng nhập</a>
        <?php endif; ?>
    </div>
</header>

<!-- ===== LAYOUT 3 CỘT ===== -->
<div class="sp-layout">

    <!-- ===== SIDEBAR TRÁI ===== -->
    <div class="sp-sidebar-left">

        <!-- SÁCH TRONG NƯỚC (tiêu đề khu vực) -->
        <div style="font-weight:700; font-size:14px; color:#333; margin-bottom:14px; padding-left:4px; text-transform:uppercase; letter-spacing:0.5px;">
            SÁCH TRONG NƯỚC
        </div>

        <!-- Bộ lọc: THƯƠNG HIỆU (NXB) -->
        <div class="filter-box">
            <div class="filter-box-header">
                <i class="fa-solid fa-tag"></i> THƯƠNG HIỆU
            </div>
            <?php if (!empty($danhSachNXB)): ?>
                <?php foreach ($danhSachNXB as $nxb): 
                    $isActive = ($thuongHieu === $nxb);
                    $href = $isActive 
                        ? buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'gia'=>$giaRange])
                        : buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'gia'=>$giaRange,'thuonghieu'=>$nxb]);
                ?>
                <div class="filter-item <?php echo $isActive ? 'active' : ''; ?>">
                    <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?>
                           onchange="window.location='<?php echo htmlspecialchars($href); ?>'">
                    <a href="<?php echo htmlspecialchars($href); ?>">
                        <?php echo htmlspecialchars($nxb); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:10px 14px; font-size:13px; color:#aaa;">Không có dữ liệu</div>
            <?php endif; ?>
        </div>

        <!-- Bộ lọc: MỨC GIÁ -->
        <div class="filter-box">
            <div class="filter-box-header">
                <i class="fa-solid fa-money-bill-wave"></i> MỨC GIÁ
            </div>
            <?php foreach ($giaLabels as $key => $label): 
                $isActive = ($giaRange === $key);
                $href = $isActive
                    ? buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'thuonghieu'=>$thuongHieu])
                    : buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'thuonghieu'=>$thuongHieu,'gia'=>$key]);
            ?>
            <div class="filter-item <?php echo $isActive ? 'active' : ''; ?>">
                <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?>
                       onchange="window.location='<?php echo htmlspecialchars($href); ?>'">
                <a href="<?php echo htmlspecialchars($href); ?>">
                    <?php echo $label; ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Bộ lọc: TÌM LOẠI SẢN PHẨM -->
        <?php if (!empty($danhMucList)): ?>
        <div class="filter-box">
            <div class="filter-box-header">
                <i class="fa-solid fa-list"></i> TÌM LOẠI SẢN PHẨM
            </div>
            <?php foreach ($danhMucList as $dm): 
                $isActive = ($maTheLoai == $dm['MaTheLoai']);
                $href = $isActive
                    ? buildUrl(['keyword'=>$keyword,'thuonghieu'=>$thuongHieu,'gia'=>$giaRange])
                    : buildUrl(['keyword'=>$keyword,'thuonghieu'=>$thuongHieu,'gia'=>$giaRange,'theloai'=>$dm['MaTheLoai']]);
            ?>
            <div class="filter-item <?php echo $isActive ? 'active' : ''; ?>">
                <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?>
                       onchange="window.location='<?php echo htmlspecialchars($href); ?>'">
                <a href="<?php echo htmlspecialchars($href); ?>">
                    <?php echo htmlspecialchars($dm['TenTheLoai']); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- end sidebar left -->

    <!-- ===== CỘT GIỮA: SẢN PHẨM ===== -->
    <div class="sp-main">

        <!-- Header cột giữa -->
        <div class="sp-main-header">
            <div>
                <?php if ($keyword): ?>
                    <div class="sp-main-title">Kết quả: "<?php echo htmlspecialchars($keyword); ?>"</div>
                <?php elseif ($tenTheLoaiHienTai): ?>
                    <div class="sp-main-title">Thể Loại: <?php echo htmlspecialchars($tenTheLoaiHienTai); ?></div>
                <?php else: ?>
                    <div class="sp-main-title">Tất Cả Sản Phẩm</div>
                <?php endif; ?>
                <div class="sp-count">Hiển thị <strong><?php echo count($products); ?></strong> sản phẩm</div>
            </div>
            <!-- Sắp xếp -->
            <div>
                <select onchange="sortProducts(this.value)" style="padding:6px 12px; border:1px solid #ddd; border-radius:4px; font-size:13px; font-family:'Poppins',sans-serif; cursor:pointer;">
                    <option value="">Sắp xếp mặc định</option>
                    <option value="price-asc">Giá: Thấp → Cao</option>
                    <option value="price-desc">Giá: Cao → Thấp</option>
                    <option value="name-asc">Tên A → Z</option>
                </select>
            </div>
        </div>

        <!-- Tags bộ lọc đang áp dụng -->
        <?php 
        $hasFilter = $maTheLoai || $thuongHieu || $giaRange || $keyword;
        if ($hasFilter): ?>
        <div class="active-filters">
            <?php if ($maTheLoai && $tenTheLoaiHienTai): ?>
                <span class="filter-tag">
                    <?php echo htmlspecialchars($tenTheLoaiHienTai); ?>
                    <a href="<?php echo htmlspecialchars(buildUrl(['keyword'=>$keyword,'thuonghieu'=>$thuongHieu,'gia'=>$giaRange])); ?>" title="Xóa lọc">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($thuongHieu): ?>
                <span class="filter-tag">
                    <?php echo htmlspecialchars($thuongHieu); ?>
                    <a href="<?php echo htmlspecialchars(buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'gia'=>$giaRange])); ?>" title="Xóa lọc">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($giaRange && isset($giaLabels[$giaRange])): ?>
                <span class="filter-tag">
                    <?php echo $giaLabels[$giaRange]; ?>
                    <a href="<?php echo htmlspecialchars(buildUrl(['keyword'=>$keyword,'theloai'=>$maTheLoai,'thuonghieu'=>$thuongHieu])); ?>" title="Xóa lọc">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($keyword): ?>
                <span class="filter-tag">
                    "<?php echo htmlspecialchars($keyword); ?>"
                    <a href="<?php echo htmlspecialchars(buildUrl(['theloai'=>$maTheLoai,'thuonghieu'=>$thuongHieu,'gia'=>$giaRange])); ?>" title="Xóa lọc">✕</a>
                </span>
            <?php endif; ?>
            <a href="SanPham.php" style="font-size:12px; color:#888; text-decoration:underline; align-self:center;">Xóa tất cả</a>
        </div>
        <?php endif; ?>

        <!-- Grid sản phẩm -->
        <div class="sp-product-grid" id="productGrid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card" 
                     data-price="<?php echo $product['GiaBan']; ?>"
                     data-name="<?php echo htmlspecialchars($product['TenSach']); ?>">
                    <a href="chitietsanpham.php?id=<?php echo $product['MaSach']; ?>" class="product-link">
                        <div class="product-image-container">
                            <img src="images/<?php echo htmlspecialchars($product['HinhAnh']); ?>"
                                 alt="<?php echo htmlspecialchars($product['TenSach']); ?>"
                                 class="product-image"
                                 onerror="this.src='images/default-book.png'">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['TenSach']); ?></h3>
                            <p class="product-price"><?php echo number_format($product['GiaBan'], 0, ',', '.'); ?>đ</p>
                        </div>
                    </a>
                    <button class="add-to-cart-btn"
                        onclick="addToCart(<?php echo $product['MaSach']; ?>,
                            '<?php echo addslashes(htmlspecialchars($product['TenSach'])); ?>',
                            <?php echo $product['GiaBan']; ?>,
                            'images/<?php echo htmlspecialchars($product['HinhAnh']); ?>')">
                        <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-products">
                    <i class="fa-solid fa-book-open"></i>
                    Không có sản phẩm nào phù hợp.<br>
                    <a href="SanPham.php" style="color:#6b8e3e; text-decoration:none; font-weight:600;">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- end sp-main -->

    <!-- ===== SIDEBAR PHẢI: SẢN PHẨM HOT ===== -->
    <div class="sp-sidebar-right">
        <div class="hot-box">
            <div class="hot-box-header">
                <i class="fa-solid fa-fire"></i> SẢN PHẨM HOT
            </div>
            <?php if (!empty($hotProducts)): ?>
                <?php foreach ($hotProducts as $hp): ?>
                <a href="chitietsanpham.php?id=<?php echo $hp['MaSach']; ?>" class="hot-product-item">
                    <img src="images/<?php echo htmlspecialchars($hp['HinhAnh']); ?>"
                         alt="<?php echo htmlspecialchars($hp['TenSach']); ?>"
                         onerror="this.src='images/default-book.png'">
                    <div class="hot-item-info">
                        <div class="hot-item-name"><?php echo htmlspecialchars($hp['TenSach']); ?></div>
                        <div class="hot-item-price"><?php echo number_format($hp['GiaBan'], 0, ',', '.'); ?>đ</div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:16px; text-align:center; font-size:13px; color:#aaa;">Chưa có sản phẩm</div>
            <?php endif; ?>
        </div>
    </div><!-- end sidebar right -->

</div><!-- end sp-layout -->

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="newsletter-content">
        <p class="subtitle">CẬP NHẬT MỚI NHẤT</p>
        <h2>Tham gia Câu lạc bộ Đọc sách</h2>
        <p class="description">Nhận gợi ý sách được tuyển chọn, bản xem trước độc quyền, và quyền truy cập sớm vào các sự kiện tác giả.</p>
        <form class="newsletter-form">
            <input type="email" placeholder="Địa chỉ email của bạn" required>
            <button type="submit">Đăng ký</button>
        </form>
    </div>
</section>

<footer>
    <div class="footer-container">
        <div class="footer-col brand-col">
            <div class="footer-logo">📚 Nhà Sách</div>
            <p>Nhà sách độc lập tận tâm kết nối độc giả với những câu chuyện ý nghĩa.</p>
        </div>
        <div class="footer-col">
            <h4>Cửa hàng</h4>
            <ul>
                <li><a href="#">Sách mới</a></li>
                <li><a href="#">Bán chạy</a></li>
                <li><a href="#">Nhân viên chọn</a></li>
                <li><a href="#">Thẻ quà tặng</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Về chúng tôi</h4>
            <ul>
                <li><a href="#">Câu chuyện</a></li>
                <li><a href="#">Sự kiện</a></li>
                <li><a href="#">Câu lạc bộ sách</a></li>
                <li><a href="#">Tuyển dụng</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="#">Vận chuyển</a></li>
                <li><a href="#">Đổi trả</a></li>
                <li><a href="#">Câu hỏi thường gặp</a></li>
                <li><a href="#">Liên hệ</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Nhà Sách. Bảo lưu mọi quyền.</p>
    </div>
</footer>

<!-- Side Cart -->
<div id="sideCartOverlay" class="overlay"></div>
<div id="sideCart" class="side-cart">
    <div class="side-cart-header">
        <h3>Giỏ hàng của bạn</h3>
        <button class="close-cart" onclick="toggleCart()">&times;</button>
    </div>
    <div id="sideCartBody" class="side-cart-body"></div>
    <div id="sideCartFooter" class="side-cart-footer">
        <div class="total-row">
            <span>Tổng tiền:</span>
            <span id="sideCartTotal">0 đ</span>
        </div>
        <div class="btn-group">
            <button class="btn btn-clear" onclick="clearCart()">Xóa hết</button>
            <a href="thanhtoan.php" class="btn btn-checkout">Thanh toán</a>
        </div>
    </div>
</div>

<div class="contact-float">
    <i class="fa-solid fa-phone"></i> Hãy liên hệ với chúng tôi
</div>

<script src="js/cart.js"></script>
<script>
// Sắp xếp sản phẩm phía client
function sortProducts(type) {
    const grid = document.getElementById('productGrid');
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    cards.sort((a, b) => {
        if (type === 'price-asc')  return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
        if (type === 'price-desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
        if (type === 'name-asc')   return a.dataset.name.localeCompare(b.dataset.name, 'vi');
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}
</script>
</body>
</html>
<?php
session_start();
require_once __DIR__ . "/csdl.php"; // Kết nối CSDL

try {
    // Lấy 8 sản phẩm mới nhất
    $stmt = $connection->query("SELECT * FROM sach ORDER BY MaSach DESC LIMIT 8");
    $hot_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $hot_products = [];
}

// Lấy danh sách thể loại cho sidebar
try {
    $stmtTL = $connection->query("SELECT * FROM theloai WHERE (TrangThaiXoa IS NULL OR TrangThaiXoa = 0) ORDER BY MaTheLoai ASC");
    $danhMucList = $stmtTL->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $danhMucList = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhà Sách</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/chatbot.css">
</head>
<body>

<header>
    <div class="logo">📚 Nhà Sách</div>

    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li class="nav-dropdown mega-cat-wrapper">
                <a href="SanPham.php" style="display:flex;align-items:center;gap:6px;">Danh mục <i class="fa-solid fa-chevron-down" style="font-size:11px;"></i></a>
                <div class="mega-menu-panel">
                    <!-- CỘT TRÁI: DANH MỤC -->
                    <div class="mega-cat-left">
                        <div class="mega-cat-header">
                            <i class="fa-solid fa-bars"></i> DANH MỤC SẢN PHẨM
                        </div>
                        <ul class="mega-cat-list">
                            <li>
                                <a href="SanPham.php" class="mega-cat-item" data-id="0">
                                    <span class="mega-cat-name">
                                        <i class="fa-solid fa-layer-group"></i> Tất cả sản phẩm
                                    </span>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php foreach($danhMucList as $idx => $dm): ?>
                            <li>
                                <a href="SanPham.php?theloai=<?php echo $dm['MaTheLoai']; ?>"
                                   class="mega-cat-item"
                                   data-id="<?php echo $dm['MaTheLoai']; ?>">
                                    <span class="mega-cat-name">
                                        <i class="fa-solid fa-book-open"></i>
                                        <?php echo htmlspecialchars($dm['TenTheLoai']); ?>
                                        <?php if($idx===0): ?><span class="mega-badge new">New</span><?php endif; ?>
                                        <?php if($idx===1): ?><span class="mega-badge hot">Hot</span><?php endif; ?>
                                        <?php if($idx===2): ?><span class="mega-badge sale">Sale</span><?php endif; ?>
                                    </span>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- CỘT PHẢI: SẢN PHẨM CỦA DANH MỤC -->
                    <div class="mega-cat-right" id="megaCatRight">
                        <div class="mega-right-placeholder">
                            <i class="fa-solid fa-hand-point-left" style="font-size:28px;color:#ccc;margin-bottom:10px;display:block;"></i>
                            <span>Chọn danh mục để xem sản phẩm</span>
                        </div>
                    </div>
                </div>
            </li>
            <li><a href="SanPham.php">Sản phẩm</a></li>
            <li><a href="Gioithieu.php">Giới thiệu</a></li>
            <li><a href="lienhe.php">Liên hệ</a></li>
        </ul>
    </nav>

    <div class="search-bar">
        <form action="timkiem.php" method="GET">
            <input type="text" name="keyword" placeholder="Tìm kiếm sách...">
            <button type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>

    <div class="header-right">
        
        <!-- Giỏ hàng -->
        <div class="icon-box">
            <a href="#" onclick="toggleCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="badge side-cart-count">0</span>
            </a>
        </div>

        <!-- Tài khoản -->
        <div class="icon-box">
            <a href="#"><i class="fa-solid fa-user"></i></a>
        </div>

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

<section class="hero">
    <div class="hero-content">
        <h1>
            Nơi những câu chuyện <br>
            trở nên sống động <br>
            mỗi ngày
        </h1>

        <p>
            Hãy khám phá cuốn sách tuyệt vời tiếp theo của bạn trong bộ sưu tập <br>
            được tuyển chọn kỹ lưỡng của chúng tôi.
        </p>

        <a href="SanPham.php" class="explore-btn">Khám phá bộ sưu tập</a>
    </div>
</section>

<!-- Products + Sidebar Section -->
<div class="products-with-sidebar">

    <!-- Sidebar Danh Mục -->
    <div class="sidebar-col">
        <div class="category-panel">
            <div class="category-panel-header">
                <span>Danh Mục Sản Phẩm</span>
                <i class="fa-solid fa-bars"></i>
            </div>
            <ul class="category-list">
                <?php foreach($danhMucList as $idx => $dm): ?>
                <li>
                    <a href="timkiem.php?theloai=<?php echo $dm['MaTheLoai']; ?>">
                        <span class="cat-left">
                            <i class="fa-solid fa-book-open" style="color:#2ecc71;font-size:13px;"></i>
                            <?php echo htmlspecialchars($dm['TenTheLoai']); ?>
                            <?php if($idx === 0): ?><span class="cat-badge new">New</span><?php endif; ?>
                            <?php if($idx === 1): ?><span class="cat-badge hot">Hot</span><?php endif; ?>
                            <?php if($idx === 2): ?><span class="cat-badge sale">Sale</span><?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-right cat-arrow"></i>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Cột Sản Phẩm -->
    <div class="products-col">
        <h2 class="section-title">Sản phẩm nổi bật</h2>
        <div id="hot-products-grid" class="product-grid">
            <?php if (!empty($hot_products)): ?>
                <?php foreach ($hot_products as $product): ?>
                    <div class="product-card">
                        <a href="chitietsanpham.php?id=<?php echo $product['MaSach']; ?>" class="product-link">
                            <div class="product-image-container">
                                <img src="images/<?php echo htmlspecialchars($product['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($product['TenSach']); ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['TenSach']); ?></h3>
                                <p class="product-price"><?php echo number_format($product['GiaBan'], 0, ',', '.'); ?>đ</p>
                            </div>
                        </a>
                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['MaSach']; ?>, '<?php echo addslashes(htmlspecialchars($product['TenSach'])); ?>', <?php echo $product['GiaBan']; ?>, 'images/<?php echo htmlspecialchars($product['HinhAnh']); ?>')">Thêm vào giỏ</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#888;">Không có sản phẩm nổi bật nào để hiển thị.</p>
            <?php endif; ?>
        </div>
        <div style="text-align:center; margin-top:30px;">
            <a href="SanPham.php" class="explore-btn" style="display:inline-block;">Xem tất cả sản phẩm →</a>
        </div>
    </div>

</div>

<!-- Newsletter & Footer -->
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
    <div id="sideCartBody" class="side-cart-body">
        <!-- Cart items will be rendered here by JavaScript -->
    </div>
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

<?php include 'chatbot_ui.php'; ?>
<script src="js/cart.js"></script>
<script src="js/chatbot.js"></script>
</body>
</html>


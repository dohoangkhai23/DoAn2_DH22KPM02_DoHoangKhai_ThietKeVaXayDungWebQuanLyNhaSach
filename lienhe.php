<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Liên hệ - Nhà Sách</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/lienhe.css">
</head>
<body>

<header>
    <div class="logo">📚 Nhà Sách</div>

    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="#">Danh mục</a></li>
            <li><a href="SanPham.php">Sản phẩm</a></li>
            <li><a href="#">Về chúng tôi</a></li>
            <li><a href="lienhe.php">Liên hệ</a></li>
        </ul>
    </nav>

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
            <span class="club-btn" style="background:#2ecc71; cursor:default;">
                Xin chào, <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>
            </span>
        <?php else: ?>
            <a href="dangky.php" class="club-btn" style="margin-right:10px;background:#3498db;">Đăng ký</a>
            <a href="dangnhap.php" class="club-btn">Đăng nhập</a>
        <?php endif; ?>

    </div>
</header>

<section class="contact-section">
    <div class="container">
        <div class="contact-title">
            <h2>Liên hệ với chúng tôi</h2>
            <p>Chúng tôi rất mong nhận được phản hồi từ bạn. Vui lòng điền vào biểu mẫu dưới đây.</p>
        </div>
        <div class="contact-content">
            <div class="contact-info">
                <h3>Thông tin liên hệ</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Đường Sách, Quận Tri Thức, TP. Kiến Thức</p>
                <p><i class="fas fa-envelope"></i> support@nhasach.com</p>
                <p><i class="fas fa-phone"></i> (028) 1234 5678</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="contact-form">
                <form action="#" method="post">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Họ và Tên" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email của bạn" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Nội dung tin nhắn" required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </div>
</section>

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
            <a href="#" class="btn btn-checkout">Thanh toán</a>
        </div>
    </div>
</div>

<div class="contact-float">
    <i class="fa-solid fa-phone"></i> Hãy liên hệ với chúng tôi
</div>

<script src="js/cart.js"></script>
</body>
</html>

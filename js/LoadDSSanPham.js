document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector(".container");

    // Load products
    if (container) {
        fetch('LoadDSSanPham.php')
            .then(res => {
                if (!res.ok) {
                    // Nếu server trả về lỗi (vd: 500), ném ra lỗi để catch xử lý
                    throw new Error(`Lỗi server: ${res.status} ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.ok === false) {
                    // Nếu JSON trả về có chứa thông báo lỗi từ phía PHP
                    throw new Error(data.error);
                }
                let html = '';
                const ds = data.DSSach;
                if (!ds || ds.length === 0) {
                    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">Không có sản phẩm nào.</p>';
                    return;
                }
                ds.forEach(d => {
                    const id = d.MaSach;
                    const hinhAnh = d.HinhAnh ? `images/${d.HinhAnh}` : 'images/default.jpg';
                    const giaBan = Number(d.GiaBan || 0);
                    
                    html += `<div class="card">
                        <img src="${hinhAnh}" onerror="this.src='images/default.jpg'">
                        <p>${d.TenSach}</p>
                        <p>${giaBan.toLocaleString('vi-VN')} đ</p>
                        <button class="add-to-cart" 
                            onclick="addToCart('${id}', '${d.TenSach}', ${giaBan}, '${hinhAnh}')">
                            Xem thêm
                        </button>
                    </div>`;
                });
                container.innerHTML = html;
            })
            .catch(e => {
                console.error("Lỗi tải sản phẩm:", e);
                const errorMessage = e.message.includes("Failed to fetch") 
                    ? "Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng hoặc trạng thái máy chủ."
                    : `Lỗi tải dữ liệu: ${e.message}`;
                container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 20px; background-color: #ffe0e0; color: #d32f2f; border: 1px solid #d32f2f; border-radius: 8px; margin-top: 20px;">
                                            <p style="font-weight: bold; font-size: 1.1em;">Đã xảy ra lỗi!</p>
                                            <p>${errorMessage}</p>
                                            <p style="margin-top: 10px; font-size: 0.9em;">Vui lòng thử tải lại trang hoặc liên hệ hỗ trợ.</p>
                                        </div>`;
            });
    }

    // Initial cart load
    loadCart();
});

// Cart functions
async function addToCart(id, name, price, img) {
    const body = new URLSearchParams({ action: 'add', id, name, price, img, qty: 1 });
    const res = await fetch('control/GioHangController.php', { method: 'POST', body });
    const data = await res.json();
    updateCartUI(data.cart);
    openCart();
}

async function loadCart() {
    const res = await fetch('control/GioHangController.php?action=list');
    const data = await res.json();
    updateCartUI(data.cart);
}

async function updateQty(id, qty) {
    if (qty < 1) return removeItem(id);
    const body = new URLSearchParams({ action: 'update', id, qty });
    const res = await fetch('control/GioHangController.php', { method: 'POST', body });
    const data = await res.json();
    updateCartUI(data.cart);
}

async function removeItem(id) {
    const res = await fetch(`control/GioHangController.php?action=remove&id=${encodeURIComponent(id)}`);
    const data = await res.json();
    updateCartUI(data.cart);
}

async function clearCart() {
    if (!confirm('Bạn có chắc muốn xóa hết giỏ hàng?')) return;
    const res = await fetch('control/GioHangController.php?action=clear');
    const data = await res.json();
    updateCartUI(data.cart);
}

function updateCartUI(cart) {
    const badges = document.querySelectorAll('.side-cart-count');
    badges.forEach(b => b.textContent = cart.count || 0);

    const body = document.getElementById('sideCartBody');
    if (!cart || !cart.items || cart.items.length === 0) {
        body.innerHTML = '<div class="empty-cart-msg">Giỏ hàng của bạn đang trống.</div>';
    } else {
        body.innerHTML = cart.items.map(item => `
            <div class="side-cart-item">
                <img src="${item.img}" alt="${item.name}">
                <div class="side-cart-item-info">
                    <h4>${item.name}</h4>
                    <div class="price">${Number(item.price).toLocaleString('vi-VN')} đ</div>
                    <div class="qty-box">
                        <button onclick="updateQty('${item.id}', ${item.qty - 1})">-</button>
                        <input type="text" readonly value="${item.qty}">
                        <button onclick="updateQty('${item.id}', ${item.qty + 1})">+</button>
                        <button class="remove-btn" onclick="removeItem('${item.id}')" style="margin-left:auto; border:none; background:none; color:#888; cursor:pointer;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    document.getElementById('sideCartTotal').textContent = Number(cart.total || 0).toLocaleString('vi-VN') + ' đ';
}

function toggleCart() {
    const cartEl = document.getElementById('sideCart');
    const overlay = document.getElementById('sideCartOverlay');
    cartEl.classList.toggle('active');
    overlay.classList.toggle('active');
    if (cartEl.classList.contains('active')) {
        loadCart();
    }
}

function openCart() {
    document.getElementById('sideCart').classList.add('active');
    document.getElementById('sideCartOverlay').classList.add('active');
}

document.getElementById('sideCartOverlay').onclick = toggleCart;

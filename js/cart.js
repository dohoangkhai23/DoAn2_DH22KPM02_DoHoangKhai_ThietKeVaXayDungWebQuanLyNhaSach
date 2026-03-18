// Initialize cart from localStorage or as an empty array
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function toggleCart() {
    const sideCart = document.getElementById('sideCart');
    const overlay = document.getElementById('sideCartOverlay');
    if (sideCart) {
        if (sideCart.classList.contains('active')) {
            sideCart.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        } else {
            sideCart.classList.add('active');
            if (overlay) overlay.classList.add('active');
        }
    }
}

function addToCart(id, name, price, img, quantity = 1) {
    price = parseFloat(price);
    const existingItem = cart.find(item => item.id === id || item.name === name);
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({ id, name, price, img, quantity: quantity });
    }
    updateCart();
    
    // Open the side cart
    const sideCart = document.getElementById('sideCart');
    const overlay = document.getElementById('sideCartOverlay');
    if (sideCart && !sideCart.classList.contains('active')) {
        sideCart.classList.add('active');
        if (overlay) overlay.classList.add('active');
    }
}

// Keep old addCart just in case other pages use it
function addCart(name, price, img, quantity = 1) {
    addToCart(null, name, price, img, quantity);
}

function clearCart() {
    if (confirm('Bạn có chắc muốn xóa hết giỏ hàng?')) {
        cart = [];
        updateCart();
    }
}

function updateCart() {
    // Save to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    // Update main cart UI if exists
    const cartItems = document.getElementById('cart-items');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total');

    let total = 0;
    let count = 0;

    cart.forEach(item => {
        total += item.price * item.quantity;
        count += item.quantity;
    });

    if (cartItems && cartCount && cartTotal) {
        cartItems.innerHTML = '';
        if (cart.length === 0) {
            cartItems.innerHTML = '<p style="text-align:center; color:#888; margin-top:50px;">Giỏ hàng trống</p>';
        } else {
            cart.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.classList.add('cart-item');
                itemElement.innerHTML = `
                    <img src="${item.img}">
                    <div class="cart-item-info">
                        <p>${item.name}</p>
                        <p>${item.price.toLocaleString('vi-VN')} đ x ${item.quantity}</p>
                    </div>
                `;
                cartItems.appendChild(itemElement);
            });
        }
        cartCount.innerText = count;
        cartTotal.innerText = total.toLocaleString('vi-VN');
    }

    // Update side cart UI if exists
    const sideCartBody = document.getElementById('sideCartBody');
    const sideCartTotal = document.getElementById('sideCartTotal');
    const sideCartCounts = document.querySelectorAll('.side-cart-count'); // might be multiple icons
    
    sideCartCounts.forEach(el => el.innerText = count);

    if (sideCartBody && sideCartTotal) {
        sideCartBody.innerHTML = '';
        if (cart.length === 0) {
            sideCartBody.innerHTML = '<p style="text-align:center; color:#888; margin-top:50px;">Giỏ hàng trống</p>';
        } else {
            cart.forEach((item, index) => {
                const itemElement = document.createElement('div');
                itemElement.classList.add('side-cart-item');
                itemElement.innerHTML = `
                    <img src="${item.img}">
                    <div class="side-cart-item-info" style="flex:1;">
                        <h4>${item.name}</h4>
                        <div class="price">${item.price.toLocaleString('vi-VN')} đ</div>
                        <div class="qty-box">
                            <button onclick="changeQuantity(${index}, -1)">-</button>
                            <input type="text" value="${item.quantity}" readonly>
                            <button onclick="changeQuantity(${index}, 1)">+</button>
                            <button onclick="removeItem(${index})" style="margin-left:auto; background:none; border:none; color:red; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                `;
                sideCartBody.appendChild(itemElement);
            });
        }
        sideCartTotal.innerText = total.toLocaleString('vi-VN') + ' đ';
    }
}

function changeQuantity(index, delta) {
    if (cart[index]) {
        cart[index].quantity += delta;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        updateCart();
    }
}

function removeItem(index) {
    if (cart[index]) {
        cart.splice(index, 1);
        updateCart();
    }
}

function checkout() {
    if (cart.length === 0) {
        alert('Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.');
        return;
    }
    window.location.href = 'thanhtoan.php';
}

// Clicks on overlay closes cart
document.addEventListener('DOMContentLoaded', () => {
    updateCart();
    const overlay = document.getElementById('sideCartOverlay');
    if (overlay) {
        overlay.addEventListener('click', toggleCart);
    }
});

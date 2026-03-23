// Cart JavaScript
let cart = [
    {
        id: 1,
        name: "Assassin Creed Valhalla",
        category: "Action Game",
        price: 24,
        quantity: 1,
        image: "assets/images/trending-01.jpg"
    },
    {
        id: 2,
        name: "Call of Duty: Modern Warfare",
        category: "Action Game",
        price: 22,
        quantity: 2,
        image: "assets/images/trending-02.jpg"
    },
    {
        id: 3,
        name: "Cyberpunk 2077",
        category: "RPG Game",
        price: 30,
        quantity: 1,
        image: "assets/images/trending-03.jpg"
    },
    {
        id: 4,
        name: "God of War Ragnarök",
        category: "Adventure Game",
        price: 18,
        quantity: 1,
        image: "assets/images/trending-04.jpg"
    }
];

// Function to render cart
function renderCart() {
    const cartContent = document.getElementById('cart-content');
    
    if (cart.length === 0) {
        cartContent.innerHTML = `
            <div class="empty-cart">
                <i class="fa fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="shop_page1.php" class="continue-shopping">Continue Shopping</a>
            </div>
        `;
        return;
    }
    
    let subtotal = 0;
    let cartHtml = `
        <div class="cart-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    cart.forEach((item) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        cartHtml += `
            <tr data-id="${item.id}">
                <td data-label="Product">
                    <div style="display: flex; align-items: center;">
                        <div class="cart-product-img">
                            <img src="${item.image}" alt="${item.name}">
                        </div>
                        <div class="cart-product-info">
                            <div class="cart-product-title">${item.name}</div>
                            <div class="cart-product-category">${item.category}</div>
                        </div>
                    </div>
                </td>
                <td data-label="Price" class="cart-price">$${item.price}</td>
                <td data-label="Quantity">
                    <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="99" onchange="updateQuantity(${item.id}, this.value)">
                </td>
                <td data-label="Total" class="cart-price">$${itemTotal.toFixed(2)}</td>
                <td data-label="Remove">
                    <i class="fa fa-trash cart-remove" onclick="removeItem(${item.id})"></i>
                </td>
            </tr>
        `;
    });
    
    const tax = subtotal * 0.1;
    const shipping = subtotal > 0 ? 5 : 0;
    const total = subtotal + tax + shipping;
    
    cartHtml += `
                </tbody>
            </table>
        </div>
        
        <div class="cart-summary">
            <div class="row">
                <div class="col-lg-6 offset-lg-6">
                    <h4>Cart Summary</h4>
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span>$${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="summary-item">
                        <span>Tax (10%)</span>
                        <span>$${tax.toFixed(2)}</span>
                    </div>
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span>$${shipping.toFixed(2)}</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>$${total.toFixed(2)}</span>
                    </div>
                    <button class="checkout-btn" onclick="checkout()">
                        <i class="fa fa-credit-card"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    `;
    
    cartContent.innerHTML = cartHtml;
}

// Function to update quantity
function updateQuantity(productId, newQuantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        newQuantity = parseInt(newQuantity);
        if (newQuantity > 0 && newQuantity <= 99) {
            item.quantity = newQuantity;
            renderCart();
        } else if (newQuantity <= 0) {
            removeItem(productId);
        }
    }
}

// Function to remove item
function removeItem(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        cart = cart.filter(item => item.id !== productId);
        renderCart();
        showNotification('Item removed from cart!');
    }
}

// Function to checkout
function checkout() {
    if (cart.length > 0) {
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = total * 0.1;
        const shipping = 5;
        const grandTotal = total + tax + shipping;
        
        alert(`Thank you for shopping with DYNA Shop!\n\nOrder Summary:\nTotal Items: ${cart.reduce((sum, item) => sum + item.quantity, 0)}\nSubtotal: $${total.toFixed(2)}\nTax: $${tax.toFixed(2)}\nShipping: $${shipping.toFixed(2)}\nTotal: $${grandTotal.toFixed(2)}\n\nThis is a demo. Payment integration coming soon!`);
    }
}

// Function to show notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', () => {
    renderCart();
});
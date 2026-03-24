<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Ensure we have a user_id (adjust this based on your actual login session variable)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$receipt_data = null; // Variable to hold receipt data if a checkout happens

// Handle POST requests (AJAX & Form Submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Update quantity (AJAX)
    if ($action == 'update') {
        $id = $_POST['id'];
        $qty = $_POST['qty'];

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = $qty;
                break;
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }

    // Remove item (AJAX)
    if ($action == 'remove') {
        $id = $_POST['id'];

        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        echo json_encode(['success' => true]);
        exit();
    }

    // Clear cart (AJAX)
    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit();
    }

    // Handle Checkout (Standard Form Submission)
    if ($action == 'checkout' && isset($_POST['selected_ids'])) {
        $selected_ids = json_decode($_POST['selected_ids'], true);

        $purchased_items = [];
        $subtotal = 0;

        // Find the selected items in the cart
        foreach ($_SESSION['cart'] as $key => $item) {
            if (in_array($item['id'], $selected_ids)) {
                $purchased_items[] = $item;
                $subtotal += ($item['price'] * $item['quantity']);
            }
        }

        if (!empty($purchased_items)) {
            $tax = $subtotal * 0.10; // 10% Tax from your cart logic
            $shipping = $subtotal > 0 ? 5.00 : 0; // Flat 5 shipping
            $total = $subtotal + $tax + $shipping;

            // 1. Save the main order to the database
            $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, tax, shipping, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idddd", $user_id, $subtotal, $tax, $shipping, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // 2. Save each item & remove them from the session cart
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($purchased_items as $item) {
                $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt->execute();

                // Remove purchased item
                foreach ($_SESSION['cart'] as $key => $cart_item) {
                    if ($cart_item['id'] == $item['id']) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
            }
            $stmt->close();
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index

            // Prepare receipt data to display
            $receipt_data = [
                'order_id' => $order_id,
                'items' => $purchased_items,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'date' => date('F j, Y, g:i a')
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dyna Shop - Shopping Cart</title>

    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />

    <style>
        /* Cart Page Specific Styles */
        .cart-page {
            padding: 80px 0;
        }

        .cart-table {
            background: #fff;
            border-radius: 23px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .cart-table th {
            background: #ee626b;
            color: #fff;
            padding: 15px 20px;
        }

        .cart-table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }

        .checkbox-cell {
            width: 50px;
            text-align: center;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #ee626b;
        }

        .cart-product-img {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            overflow: hidden;
            display: inline-block;
            margin-right: 15px;
        }

        .cart-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-product-title {
            font-weight: 600;
            color: #1e1e1e;
        }

        .cart-price {
            font-weight: 700;
            color: #ee626b;
            font-size: 18px;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 8px;
        }

        .update-btn {
            background: #ee626b;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            margin-left: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .update-btn:hover {
            background: #d94c1a;
            transform: translateY(-2px);
        }

        .cart-remove {
            color: #ff4444;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 18px;
            transition: all 0.3s;
        }

        .cart-remove:hover {
            color: #cc0000;
            transform: scale(1.1);
        }

        .cart-summary {
            background: #f7f7f7;
            border-radius: 23px;
            padding: 30px;
            margin-top: 30px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 20px;
            font-weight: 700;
            color: #ee626b;
        }

        .checkout-btn,
        .clear-cart-btn {
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 25px;
            width: 100%;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkout-btn {
            background: #ee626b;
        }

        .checkout-btn:hover {
            background: #d94c1a;
            transform: translateY(-2px);
        }

        .clear-cart-btn {
            background: #666;
            margin-top: 10px;
        }

        .clear-cart-btn:hover {
            background: #555;
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 60px;
            background: #fff;
            border-radius: 23px;
        }

        .empty-cart i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .continue-shopping {
            background: #ee626b;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .continue-shopping:hover {
            background: #d94c1a;
            transform: translateY(-2px);
            color: #fff;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ee626b;
            color: #fff;
            padding: 12px 24px;
            border-radius: 25px;
            z-index: 9999;
            animation: slideIn 0.3s;
        }

        /* Receipt Specific Styles */
        .receipt-wrapper {
            background: #fff;
            padding: 40px;
            border-radius: 23px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .receipt-header h2 {
            color: #ee626b;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .item-name {
            flex: 2;
            font-weight: 500;
        }

        .item-qty {
            flex: 1;
            text-align: center;
            color: #888;
        }

        .item-price {
            flex: 1;
            text-align: right;
        }

        .receipt-totals {
            border-top: 2px dashed #ddd;
            padding-top: 20px;
            margin-top: 20px;
        }

        .receipt-totals .receipt-item {
            color: #666;
            font-size: 15px;
        }

        .grand-total {
            font-size: 22px !important;
            font-weight: 700;
            color: #ee626b;
            margin-top: 15px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        /* --- Print Specific Styles for the Receipt --- */
        @media print {

            /* Hide everything in the body by default */
            body * {
                visibility: hidden;
            }

            /* Make only the receipt wrapper and its children visible */
            .receipt-wrapper,
            .receipt-wrapper * {
                visibility: visible;
            }

            /* Reset the layout to fit neatly on a printed page */
            .receipt-wrapper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 100%;
                /* Use full width of the paper */
                margin: 0;
                padding: 15px;
                /* Reduce padding to save space */
                box-shadow: none;
                /* Remove shadows for clean printing */
                border-radius: 0;
                page-break-inside: avoid;
                /* Prevent page breaks inside the receipt */
            }

            /* Shrink text sizes and margins slightly to ensure a 1-page fit */
            .receipt-header h2 {
                font-size: 24px;
                margin-bottom: 2px;
            }

            .receipt-header {
                padding-bottom: 10px;
                margin-bottom: 15px;
            }

            .receipt-item {
                font-size: 14px;
                margin-bottom: 8px;
            }

            .receipt-totals {
                padding-top: 15px;
                margin-top: 15px;
            }

            .grand-total {
                font-size: 18px !important;
                margin-top: 10px;
                padding-top: 10px;
            }

            /* Hide buttons inside the receipt during print */
            .continue-shopping,
            .clear-cart-btn {
                display: none !important;
            }

            /* Optionally hide URL headers and footers printed by the browser */
            @page {
                margin: 0.5cm;
                /* Small margins */
            }
        }
    </style>
</head>

<body>

    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="home.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                            <li><a href="cart.php" class="active">Cart</a></li>
                            <li><a href="index.php">Logout</a></li>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="page-heading header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3><?php echo $receipt_data ? 'Order Confirmation' : 'Shopping Cart'; ?></h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > <?php echo $receipt_data ? 'Receipt' : 'Cart'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">

                    <?php if ($receipt_data): ?>
                        <div class="receipt-wrapper">
                            <div class="receipt-header">
                                <h2>DYNA Shop</h2>
                                <p>Thank you for your purchase!</p>
                                <span style="color:#888;">Order ID: #<?php echo str_pad($receipt_data['order_id'], 6, "0", STR_PAD_LEFT); ?></span><br>
                                <span style="color:#888;">Date: <?php echo $receipt_data['date']; ?></span>
                            </div>

                            <div class="receipt-body">
                                <?php foreach ($receipt_data['items'] as $item): ?>
                                    <div class="receipt-item">
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="item-qty">x<?php echo htmlspecialchars($item['quantity']); ?></span>
                                        <span class="item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="receipt-totals">
                                <div class="receipt-item">
                                    <span>Subtotal</span>
                                    <span>₱<?php echo number_format($receipt_data['subtotal'], 2); ?></span>
                                </div>
                                <div class="receipt-item">
                                    <span>Tax (10%)</span>
                                    <span>₱<?php echo number_format($receipt_data['tax'], 2); ?></span>
                                </div>
                                <div class="receipt-item">
                                    <span>Shipping</span>
                                    <span>₱<?php echo number_format($receipt_data['shipping'], 2); ?></span>
                                </div>
                                <div class="receipt-item grand-total">
                                    <span>Total Paid</span>
                                    <span>₱<?php echo number_format($receipt_data['total'], 2); ?></span>
                                </div>
                            </div>

                            <a href="shop_page1.php" class="continue-shopping" style="width: 100%; text-align: center;">Continue Shopping</a>
                            <button onclick="window.print()" class="clear-cart-btn" style="width: 100%;">Print Receipt</button>
                        </div>

                    <?php else: ?>
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="empty-cart">
                                <i class="fa fa-shopping-cart"></i>
                                <h3>Your cart is empty</h3>
                                <p>Looks like you haven't added any items to your cart yet.</p>
                                <a href="shop_page1.php" class="continue-shopping">Continue Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="cart-table">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="checkbox-cell">
                                                <input type="checkbox" id="selectAll" class="custom-checkbox" checked title="Select All">
                                            </th>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-items">
                                        <?php
                                        $subtotal = 0;
                                        foreach ($_SESSION['cart'] as $item):
                                            $item_total = $item['price'] * $item['quantity'];
                                            $subtotal += $item_total;
                                        ?>
                                            <tr id="item-<?php echo $item['id']; ?>">
                                                <td class="checkbox-cell">
                                                    <input type="checkbox" class="item-select custom-checkbox" value="<?php echo $item['id']; ?>" checked>
                                                </td>
                                                <td>
                                                    <div style="display: flex; align-items: center;">
                                                        <div class="cart-product-img">
                                                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        </div>
                                                        <div class="cart-product-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                                    </div>
                                                </td>
                                                <td class="cart-price" data-price="<?php echo $item['price']; ?>">₱<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <input type="number" class="quantity-input" id="qty-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                                    <button class="update-btn" onclick="updateItem(<?php echo $item['id']; ?>, <?php echo $item['price']; ?>)">Update</button>
                                                </td>
                                                <td class="cart-price" id="total-<?php echo $item['id']; ?>">₱<?php echo number_format($item_total, 2); ?></td>
                                                <td>
                                                    <button class="cart-remove" onclick="removeItem(<?php echo $item['id']; ?>)">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="cart-summary">
                                <div class="row">
                                    <div class="col-lg-6 offset-lg-6">
                                        <h4>Cart Summary</h4>
                                        <?php
                                        $tax = $subtotal * 0.10;
                                        $shipping = $subtotal > 0 ? 5 : 0;
                                        $total = $subtotal + $tax + $shipping;
                                        ?>
                                        <div class="summary-item">
                                            <span>Subtotal</span>
                                            <span id="subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                                        </div>
                                        <div class="summary-item">
                                            <span>Tax (10%)</span>
                                            <span id="tax">₱<?php echo number_format($tax, 2); ?></span>
                                        </div>
                                        <div class="summary-item">
                                            <span>Shipping</span>
                                            <span id="shipping">₱<?php echo number_format($shipping, 2); ?></span>
                                        </div>
                                        <div class="summary-total">
                                            <span>Total</span>
                                            <span id="total">₱<?php echo number_format($total, 2); ?></span>
                                        </div>
                                        <button onclick="checkout()" class="checkout-btn">Proceed to Checkout</button>
                                        <button onclick="clearCart()" class="clear-cart-btn">Clear Entire Cart</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="col-lg-12">
                <p>Copyright © 2024 DYNA Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>

    <script>
        // Show notification
        function showMessage(msg) {
            let div = document.createElement('div');
            div.className = 'notification';
            div.innerHTML = msg;
            document.body.appendChild(div);

            setTimeout(() => {
                div.style.animation = 'fadeOut 0.5s';
                setTimeout(() => div.remove(), 500);
            }, 3000);
        }

        // Recalculate Totals
        function recalculateSelectedTotals() {
            let subtotal = 0;
            let selectedCheckboxes = document.querySelectorAll('.item-select:checked');

            selectedCheckboxes.forEach(function(checkbox) {
                let row = checkbox.closest('tr');
                let price = parseFloat(row.querySelector('.cart-price').getAttribute('data-price'));
                let qty = parseInt(row.querySelector('.quantity-input').value);
                subtotal += (price * qty);
            });

            let tax = subtotal * 0.10;
            let shipping = (subtotal > 0 && selectedCheckboxes.length > 0) ? 5 : 0;
            let total = subtotal + tax + shipping;

            document.getElementById('subtotal').innerHTML = '₱' + subtotal.toFixed(2);
            document.getElementById('tax').innerHTML = '₱' + tax.toFixed(2);
            document.getElementById('shipping').innerHTML = '₱' + shipping.toFixed(2);
            document.getElementById('total').innerHTML = '₱' + total.toFixed(2);
        }

        // Handle "Select All"
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                let isChecked = this.checked;
                document.querySelectorAll('.item-select').forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                });
                recalculateSelectedTotals();
            });
        }

        // Handle individual checkboxes
        document.querySelectorAll('.item-select').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    document.getElementById('selectAll').checked = false;
                } else {
                    let allChecked = document.querySelectorAll('.item-select:not(:checked)').length === 0;
                    document.getElementById('selectAll').checked = allChecked;
                }
                recalculateSelectedTotals();
            });
        });

        // Update item quantity
        function updateItem(id, price) {
            let qty = document.getElementById('qty-' + id).value;

            if (qty < 1) {
                qty = 1;
                document.getElementById('qty-' + id).value = 1;
            }
            if (qty > 99) {
                qty = 99;
                document.getElementById('qty-' + id).value = 99;
            }

            fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=update&id=' + id + '&qty=' + qty
                })
                .then(response => response.json())
                .then(data => {
                    let newTotal = price * qty;
                    document.getElementById('total-' + id).innerHTML = '₱' + newTotal.toFixed(2);
                    recalculateSelectedTotals();
                    showMessage('Quantity updated!');
                })
                .catch(error => console.error('Error:', error));
        }

        // Remove item
        function removeItem(id) {
            if (confirm('Remove this item from your cart?')) {
                fetch('cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=remove&id=' + id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('item-' + id).remove();
                            showMessage('Item removed from cart!');
                            if (document.querySelectorAll('#cart-items tr').length === 0) {
                                location.reload();
                            } else {
                                recalculateSelectedTotals();
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        // Clear cart
        function clearCart() {
            if (confirm('Clear entire cart? This action cannot be undone.')) {
                fetch('cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=clear'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        }

        // Checkout function modified to POST to this exact page
        function checkout() {
            let selectedItems = [];
            document.querySelectorAll('.item-select:checked').forEach(function(checkbox) {
                selectedItems.push(parseInt(checkbox.value));
            });

            if (selectedItems.length === 0) {
                alert('Please select at least one item to checkout.');
                return;
            }

            if (confirm('Proceed to checkout with ' + selectedItems.length + ' selected item(s)?')) {
                // Dynamically create a form and submit it to cart.php to generate the receipt
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cart.php'; // Submitting to itself

                let actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'checkout';
                form.appendChild(actionInput);

                let idsInput = document.createElement('input');
                idsInput.type = 'hidden';
                idsInput.name = 'selected_ids';
                idsInput.value = JSON.stringify(selectedItems);
                form.appendChild(idsInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Add enter key support
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const id = this.id.split('-')[1];
                    const price = parseFloat(this.closest('tr').querySelector('.cart-price').getAttribute('data-price'));
                    updateItem(id, price);
                }
            });
        });
    </script>

</body>

</html>

<?php
$conn->close();
?>
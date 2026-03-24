<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in to make a purchase. 
// Note: Make sure your login system sets $_SESSION['user_id']!
// If not set, we will temporarily use user_id = 1 (mark) so your code doesn't break during testing.
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$purchased_items = [];
$subtotal = 0;
$tax = 0;
$shipping = 0;
$total = 0;
$order_id = 0;

// Process the order ONLY if data was sent from the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'])) {
    $selected_ids = json_decode($_POST['selected_ids'], true);

    // Check if the cart exists in the session
    if (isset($_SESSION['cart']) && !empty($selected_ids)) {

        // Find the selected items in the cart
        foreach ($_SESSION['cart'] as $key => $item) {
            if (in_array($item['id'], $selected_ids)) {
                $purchased_items[] = $item;
                $subtotal += ($item['price'] * $item['quantity']);
            }
        }

        // If items were found, process the transaction
        if (!empty($purchased_items)) {
            $tax = $subtotal * 0.12; // 12% VAT
            $shipping = $subtotal > 0 ? 50.00 : 0; // Flat 50 PHP shipping fee
            $total = $subtotal + $tax + $shipping;

            // 1. Save the main order to the database
            $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, tax, shipping, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idddd", $user_id, $subtotal, $tax, $shipping, $total);
            $stmt->execute();
            $order_id = $conn->insert_id; // Retrieve the new Order ID
            $stmt->close();

            // 2. Save each individual item and remove them from the cart
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($purchased_items as $item) {
                // Save to DB
                $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
                $stmt->execute();

                // Remove the purchased item from the session cart
                foreach ($_SESSION['cart'] as $key => $cart_item) {
                    if ($cart_item['id'] == $item['id']) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
            }
            $stmt->close();

            // Re-index the cart array to keep it clean
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
    }
} else {
    // If someone visits receipt.php directly without checking out, send them away
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Dyna Shop - Receipt</title>

    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css" />

    <style>
        .receipt-wrapper {
            margin: 60px auto;
            max-width: 600px;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
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

        .btn-shop {
            display: inline-block;
            width: 100%;
            text-align: center;
            background-color: #ee626b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 30px;
        }

        .btn-shop:hover {
            background-color: #d94c1a;
            color: #fff;
        }
    </style>
</head>

<body>

    <div id="js-preloader" class="js-preloader">
        <div class="preloader-inner">
            <span class="dot"></span>
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="index.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="home.php">Home</a></li>
                            <li><a href="shop_page1.php">Shop</a></li>
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
                    <h3>Order Confirmation</h3>
                    <span class="breadcrumb"><a href="home.php">Home</a> > Receipt</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="receipt-wrapper">
                    <?php if ($order_id > 0): ?>
                        <div class="receipt-header">
                            <h2>DYNA Shop</h2>
                            <p>Thank you for your purchase!</p>
                            <span style="color:#888;">Order ID: #<?php echo str_pad($order_id, 6, "0", STR_PAD_LEFT); ?></span><br>
                            <span style="color:#888;">Date: <?php echo date('F j, Y, g:i a'); ?></span>
                        </div>

                        <div class="receipt-body">
                            <?php foreach ($purchased_items as $item): ?>
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
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="receipt-item">
                                <span>VAT (12%)</span>
                                <span>₱<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="receipt-item">
                                <span>Shipping</span>
                                <span>₱<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            <div class="receipt-item grand-total">
                                <span>Total Paid</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <h4 class="text-center text-danger">There was an error processing your order.</h4>
                    <?php endif; ?>

                    <a href="shop_page1.php" class="btn-shop">Return to Shop</a>
                    <button onclick="window.print()" class="btn-shop" style="background-color: #333; margin-top: 10px;">Print Receipt</button>
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
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>

</body>

</html>

<?php
$conn->close();
?>
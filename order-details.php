<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header("Location: home.php");
    exit();
}

// Determine if user is admin
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

// Fetch order details
$order_query = "
    SELECT o.*, u.username, u.email, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ?
";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    // Order not found
    header("Location: " . ($is_admin ? "admin_orders.php" : "my_orders.php"));
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Check if user has permission to view this order
if (!$is_admin && $order['user_id'] != $_SESSION['user_id']) {
    header("Location: my_orders.php");
    exit();
}

// Fetch order items
$items_query = "
    SELECT oi.*, p.name as product_name, p.image_url 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}
$items_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Order Details - DYNA Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        .order-summary {
            background: #fff;
            border-radius: 23px;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .order-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .order-header h4 {
            font-size: 24px;
            font-weight: 600;
            color: #1e1e1e;
        }
        .order-header p {
            color: #7a7a7a;
            margin: 5px 0 0;
        }
        .order-detail-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-detail-label {
            width: 180px;
            font-weight: 600;
            color: #1e1e1e;
        }
        .order-detail-value {
            flex: 1;
            color: #4a4a4a;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3e0; color: #f39c12; }
        .status-shipped { background: #e3f2fd; color: #3498db; }
        .status-completed { background: #e8f5e9; color: #27ae60; }
        .status-cancelled { background: #fee; color: #e74c3c; }
        .items-table {
            width: 100%;
            margin: 20px 0;
        }
        .items-table th {
            background: #f8f9fa;
            padding: 12px;
            font-weight: 600;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .items-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 15px;
        }
        .totals {
            margin-top: 20px;
            text-align: right;
        }
        .totals .total-line {
            padding: 8px 0;
            font-size: 16px;
        }
        .totals .grand-total {
            font-size: 20px;
            font-weight: 700;
            color: #ee626b;
            border-top: 2px solid #eee;
            margin-top: 10px;
            padding-top: 15px;
        }
        .btn-print {
            background: #6c757d;
            color: #fff;
            padding: 10px 25px;
            border-radius: 25px;
            border: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        .btn-print:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #ee626b;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #c4456b;
        }
        @media print {
            .back-link, .btn-print, header, footer, .page-heading {
                display: none;
            }
            .order-summary {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <!-- ***** Preloader Start ***** -->
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
    <!-- ***** Preloader End ***** -->

    <!-- ***** Header Area Start ***** -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'home.php'; ?>" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <?php if ($is_admin): ?>
                                <li><a href="admin_dashboard.php">Dashboard</a></li>
                                <li><a href="admin_orders.php">Orders</a></li>
                                <li><a href="admin_users.php">Users</a></li>
                                <li><a href="admin_products.php">Products</a></li>
                                <li><a href="admin_logout.php">Logout</a></li>
                            <?php else: ?>
                                <li><a href="home.php">Home</a></li>
                                <li><a href="shop_page1.php">Our Shop</a></li>
                                <li><a href="my_orders.php">My Orders</a></li>
                                <li><a href="contact.php">Contact Us</a></li>
                                <li><a href="cart.php">Cart</a></li>
                                <li><a href="logout.php">Logout</a></li>
                            <?php endif; ?>
                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- ***** Header Area End ***** -->

    <div class="page-heading-1 header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3>Order Details</h3>
                    <span class="breadcrumb">
                        <a href="<?php echo $is_admin ? 'admin_orders.php' : 'my_orders.php'; ?>">Orders</a> > Order #<?php echo $order['order_id']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="single-product section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <a href="<?php echo $is_admin ? 'admin_orders.php' : 'my_orders.php'; ?>" class="back-link">
                        <i class="fa fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="order-summary">
                        <div class="order-header">
                            <h4>Order #<?php echo $order['order_id']; ?></h4>
                            <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></p>
                        </div>

                        <div class="order-detail-row">
                            <div class="order-detail-label">Order Status</div>
                            <div class="order-detail-value">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <?php if ($order['status'] == 'shipped'): ?>
                                    <small class="text-muted ms-2">Awaiting your confirmation</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="order-detail-row">
                            <div class="order-detail-label">Payment Method</div>
                            <div class="order-detail-value">
                                <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                                <?php if ($order['reference_number']): ?>
                                    <br><small>Reference: <?php echo htmlspecialchars($order['reference_number']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($order['tracking_number']): ?>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Tracking Number</div>
                            <div class="order-detail-value">
                                <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong>
                                <?php if ($order['status'] == 'shipped'): ?>
                                    <br><small>Track your package using this number</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="order-detail-row">
                            <div class="order-detail-label">Shipping Address</div>
                            <div class="order-detail-value">
                                <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                            </div>
                        </div>

                        <div class="order-detail-row">
                            <div class="order-detail-label">Customer</div>
                            <div class="order-detail-value">
                                <?php echo htmlspecialchars($order['username']); ?><br>
                                <?php echo htmlspecialchars($order['email']); ?>
                            </div>
                        </div>

                        <div class="order-detail-row">
                            <div class="order-detail-label">Order Summary</div>
                            <div class="order-detail-value">
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                        </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="totals">
                                    <div class="total-line">
                                        <strong>Subtotal:</strong> ₱<?php echo number_format($order['subtotal'], 2); ?>
                                    </div>
                                    <div class="total-line">
                                        <strong>Tax (10%):</strong> ₱<?php echo number_format($order['tax'], 2); ?>
                                    </div>
                                    <div class="total-line">
                                        <strong>Shipping:</strong> ₱<?php echo number_format($order['shipping'], 2); ?>
                                    </div>
                                    <div class="grand-total">
                                        <strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button onclick="window.print()" class="btn-print">
                                <i class="fa fa-print"></i> Print Order
                            </button>
                        </div>
                    </div>
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

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/owl-carousel.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>
</body>

</html>
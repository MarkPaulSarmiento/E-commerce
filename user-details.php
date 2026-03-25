<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'dyna_shop');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Verify admin session is still valid using admin_sessions table
$check_stmt = $conn->prepare("SELECT is_active FROM admin_sessions WHERE session_token = ? AND admin_id = ? AND is_active = 1");
$check_stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    session_destroy();
    header("Location: index.php");
    exit();
}
$check_stmt->close();

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header("Location: admin_users.php");
    exit();
}

// Fetch user details
$user_stmt = $conn->prepare("SELECT user_id, username, email, address, created_at FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    header("Location: admin_users.php");
    exit();
}
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Fetch user's orders
$orders_stmt = $conn->prepare("
    SELECT order_id, total_amount, payment_method, reference_number, order_date, status
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders_stmt->close();

// Calculate total spent (sum of all non-cancelled orders)
$total_spent_stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND status != 'cancelled'");
$total_spent_stmt->bind_param("i", $user_id);
$total_spent_stmt->execute();
$total_spent_result = $total_spent_stmt->get_result();
$total_spent = $total_spent_result->fetch_assoc()['total'] ?? 0;
$total_spent_stmt->close();

// Get order counts by status
$order_counts_stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$order_counts_stmt->bind_param("i", $user_id);
$order_counts_stmt->execute();
$order_counts_result = $order_counts_stmt->get_result();
$order_counts = [];
while ($row = $order_counts_result->fetch_assoc()) {
    $order_counts[$row['status']] = $row['count'];
}
$order_counts_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>User Details - DYNA Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    
    <!-- Admin Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <style>
        .user-profile {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #e75e8d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
        }
        .profile-info h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .profile-info p {
            margin: 0;
            color: #7a7a7a;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .detail-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }
        .detail-box label {
            font-size: 12px;
            color: #7a7a7a;
            margin-bottom: 5px;
            display: block;
        }
        .detail-box .value {
            font-size: 16px;
            font-weight: 500;
            color: #1e1e1e;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card-small {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card-small h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 5px;
            color: #e75e8d;
        }
        .stat-card-small p {
            margin: 0;
            font-size: 14px;
            color: #7a7a7a;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #e75e8d;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #c4456b;
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
                        <a href="admin_dashboard.php" class="logo">
                            <img src="assets/images/logo.png" alt="" style="width: 158px;">
                        </a>
                        <ul class="nav">
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                            <li><a href="admin_orders.php">Orders</a></li>
                            <li><a href="admin_users.php" class="active">Users</a></li>
                            <li><a href="admin_products.php">Products</a></li>
                            <li><a href="admin_logout.php">Logout</a></li>
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
                    <h3>User Details</h3>
                    <span class="breadcrumb"><a href="admin_users.php">Users</a> > <?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="container">
            <a href="admin_users.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Users</a>
            
            <!-- User Profile Section -->
            <div class="user-profile">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p>Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                <div class="profile-details">
                    <div class="detail-box">
                        <label>Email Address</label>
                        <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="detail-box">
                        <label>Shipping Address</label>
                        <div class="value"><?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : '<span class="text-muted">Not provided</span>'; ?></div>
                    </div>
                    <div class="detail-box">
                        <label>User ID</label>
                        <div class="value">#<?php echo $user['user_id']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Order Statistics -->
            <div class="stats-cards">
                <div class="stat-card-small">
                    <h3><?php echo $order_counts['pending'] ?? 0; ?></h3>
                    <p>Pending Orders</p>
                </div>
                <div class="stat-card-small">
                    <h3><?php echo $order_counts['shipped'] ?? 0; ?></h3>
                    <p>Shipped Orders</p>
                </div>
                <div class="stat-card-small">
                    <h3><?php echo $order_counts['completed'] ?? 0; ?></h3>
                    <p>Completed Orders</p>
                </div>
                <div class="stat-card-small">
                    <h3><?php echo $order_counts['cancelled'] ?? 0; ?></h3>
                    <p>Cancelled Orders</p>
                </div>
                <div class="stat-card-small">
                    <h3>₱<?php echo number_format($total_spent, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
            
            <!-- Orders List -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fa fa-shopping-cart"></i> Order History</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                                <th>Reference #</th>
                                <th>Status</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                                <?php while($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?>
                                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                                        <td><?php echo htmlspecialchars($order['reference_number']); ?>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm btn-primary">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>
                            <?php else: ?>
                               
                                    <td colspan="7" style="text-align: center;">No orders found for this user.</td>
                                
                            <?php endif; ?>
                        </tbody>
                     </table>
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
<?php
$conn->close();
?>
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

// Optional search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query to fetch all users with order stats
$query = "
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.address,
        u.created_at,
        COUNT(o.order_id) AS order_count,
        COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE 1=1
";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (u.username LIKE '%$search%' OR u.email LIKE '%$search%' OR u.address LIKE '%$search%')";
}

$query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$users_result = $conn->query($query);

// Get total number of users for stats
$total_users = $users_result ? $users_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Manage Users - Dyna Shop</title>

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
        /* Additional styles for the users page */
        .user-stats {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .stats-item {
            text-align: center;
            flex: 1;
        }
        .stats-item h3 {
            font-size: 28px;
            font-weight: 700;
            color: #e75e8d;
            margin-bottom: 5px;
        }
        .stats-item p {
            color: #7a7a7a;
            margin: 0;
        }
        .search-box {
            margin-bottom: 30px;
        }
        .search-box input {
            width: 300px;
            height: 45px;
            border-radius: 25px;
            border: 1px solid #e0e0e0;
            padding: 0 20px;
            font-size: 14px;
        }
        .search-box button {
            height: 45px;
            padding: 0 25px;
            border-radius: 25px;
            background: #e75e8d;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .search-box button:hover {
            background: #c4456b;
        }
        .address-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-view-user {
            background: #3498db;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-view-user:hover {
            background: #2980b9;
            color: #fff;
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
                    <h3>Manage Users</h3>
                    <span class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> > Users</span>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="container">
            <!-- Quick Stats -->
            <div class="user-stats">
                <div class="stats-row">
                    <div class="stats-item">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stats-item">
                        <h3><?php 
                            $active_orders = $conn->query("SELECT COUNT(DISTINCT user_id) as active FROM orders WHERE status IN ('pending','shipped')")->fetch_assoc()['active'];
                            echo $active_orders;
                        ?></h3>
                        <p>Active Shoppers</p>
                    </div>
                </div>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by username, email, or address..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fa fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="admin_users.php" class="btn-link" style="margin-left: 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fa fa-users"></i> All Users</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Joined</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php if ($users_result && $users_result->num_rows > 0): ?>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="address-preview" title="<?php echo htmlspecialchars($user['address']); ?>">
                                        <?php echo !empty($user['address']) ? htmlspecialchars(substr($user['address'], 0, 50)) . (strlen($user['address']) > 50 ? '…' : '') : '—'; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo $user['order_count']; ?></td>
                                    <td>₱<?php echo number_format($user['total_spent'], 2); ?></td>
                                    <td>
                                        <a href="user-details.php?id=<?php echo $user['user_id']; ?>" class="btn-view-user">
                                            <i class="fa fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fa fa-users" style="font-size: 48px; color: #ccc;"></i>
                                        <p style="margin-top: 10px;">No users found.</p>
                                    </td>
                                </tr>
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
<?php $conn->close(); ?>
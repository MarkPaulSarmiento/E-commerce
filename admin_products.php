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

// Handle product addition
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Sanitize and validate inputs
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $original_price = floatval($_POST['original_price']);
        $discounted_price = isset($_POST['discounted_price']) && $_POST['discounted_price'] !== '' ? floatval($_POST['discounted_price']) : $original_price;
        $image_url = trim($_POST['image_url']);
        $filter_tags = trim($_POST['filter_tags']);
        $product_link = 'product-details.php'; // default link

        if (empty($name) || empty($category) || $original_price <= 0) {
            $error_message = "Please fill in all required fields correctly.";
        } else {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO products (name, category, original_price, discounted_price, image_url, filter_tags, product_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddsss", $name, $category, $original_price, $discounted_price, $image_url, $filter_tags, $product_link);
            if ($stmt->execute()) {
                $success_message = "Product added successfully!";
                // Clear form values (optional) – we'll keep them for demo
            } else {
                $error_message = "Failed to add product: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id']);
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        if ($delete_stmt->execute()) {
            $success_message = "Product deleted successfully.";
        } else {
            $error_message = "Failed to delete product.";
        }
        $delete_stmt->close();
    }
}

// Fetch all products
$products_result = $conn->query("SELECT * FROM products ORDER BY product_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Manage Products - DYNA Shop</title>

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
        .form-container {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .form-container h3 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn-submit {
            background: #e75e8d;
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: #c4456b;
        }
        .btn-edit {
            background: #3498db;
            color: #fff;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .alert-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-img-preview {
            max-width: 80px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <!-- Preloader -->
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

    <!-- Header -->
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
                            <li><a href="admin_users.php">Users</a></li>
                            <li><a href="admin_products.php" class="active">Products</a></li>
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

    <!-- Page Heading -->
    <div class="page-heading-1 header-text">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h3>Manage Products</h3>
                    <span class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> > Products</span>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-wrapper">
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert-message alert-success">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert-message alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add New Product Form -->
            <div class="form-container">
                <h3><i class="fa fa-plus-circle"></i> Add New Product</h3>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category *</label>
                                <input type="text" name="category" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Original Price (₱) *</label>
                                <input type="number" step="0.01" name="original_price" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Discounted Price (₱)</label>
                                <input type="number" step="0.01" name="discounted_price">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Filter Tags</label>
                                <input type="text" name="filter_tags" placeholder="e.g., casual, str, adv">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Image URL *</label>
                                <input type="text" name="image_url" placeholder="e.g., assets/images/product.jpg" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_product" class="btn-submit">
                        <i class="fa fa-save"></i> Add Product
                    </button>
                </form>
            </div>

            <!-- Products List -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fa fa-box"></i> All Products</h2>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Original</th>
                                <th>Discounted</th>
                                <th>Tags</th>
                                <th>Actions</th>
                            </thead>
                        <tbody>
                            <?php if ($products_result && $products_result->num_rows > 0): ?>
                                <?php while($product = $products_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $product['product_id']; ?></td>
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="" class="product-img-preview">
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>₱<?php echo number_format($product['original_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($product['discounted_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($product['filter_tags']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-product-details.php?id=<?php echo $product['product_id']; ?>" class="btn-sm btn-edit">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <button type="submit" name="delete_product" class="btn-sm btn-delete">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No products found.</td>
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

<?php
$conn->close();
?>
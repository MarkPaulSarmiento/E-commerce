<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dyna_shop';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get category filter from AJAX request
if (isset($_POST['action']) && $_POST['action'] == 'filter') {
    $category_filter = isset($_POST['category']) ? $_POST['category'] : '';
    
    if (!empty($category_filter)) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = ?");
        $stmt->bind_param("s", $category_filter);
        $stmt->execute();
        $products_result = $stmt->get_result();
    } else {
        $products_sql = "SELECT * FROM products";
        $products_result = $conn->query($products_sql);
    }
    
    // Return HTML for products
    $html = '';
    if ($products_result && $products_result->num_rows > 0) {
        while($product = $products_result->fetch_assoc()) {
            $html .= '<div class="col-lg-3 col-md-6 align-self-center mb-30 trending-items col-md-6">
                        <div class="item">
                            <div class="thumb">
                                <a href="product-details.php?id=' . $product['product_id'] . '">
                                    <img src="' . $product['image_url'] . '" alt="' . htmlspecialchars($product['name']) . '">
                                </a>';
            if($product['discounted_price'] < $product['original_price']) {
                $html .= '<span class="price">
                                <em>$' . $product['original_price'] . '</em>
                                $' . $product['discounted_price'] . '
                            </span>';
            } else {
                $html .= '<span class="price">$' . $product['original_price'] . '</span>';
            }
            $html .= '    </div>
                            <div class="down-content">
                                <span class="category">' . htmlspecialchars($product['category']) . '</span>
                                <h4>' . htmlspecialchars($product['name']) . '</h4>
                                <a href="product-details.php?id=' . $product['product_id'] . '">
                                    <i class="fa fa-shopping-bag"></i>
                                </a>
                            </div>
                        </div>
                    </div>';
        }
    } else {
        $html = '<div class="col-12">
                    <div class="empty-cart" style="text-align: center; padding: 60px 20px;">
                        <i class="fa fa-shopping-bag" style="font-size: 80px; color: #ddd;"></i>
                        <h3>No products found</h3>
                        <p>Sorry, no products match your filter criteria.</p>
                        <a href="shop_page1.php" class="btn btn-primary" style="background: #ee626b; border: none; padding: 12px 30px; border-radius: 25px; text-decoration: none; color: white; display: inline-block;">View All Products</a>
                    </div>
                </div>';
    }
    
    echo json_encode(['html' => $html]);
    exit();
}

// Get all distinct categories for filter buttons
$categories_sql = "SELECT DISTINCT category FROM products ORDER BY category";
$categories_result = $conn->query($categories_sql);

// Initial load - show all products
$initial_sql = "SELECT * FROM products";
$initial_result = $conn->query($initial_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Dyna Shop - Our Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="assets/css/admin.css">

</head>

<body>

    <div class="main-content">
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
                            <!-- ***** Logo Start ***** -->
                            <a href="home.php" class="logo">
                                <img src="assets/images/logo.png" alt="" style="width: 158px;">
                            </a>
                            <!-- ***** Logo End ***** -->
                            <!-- ***** Menu Start ***** -->
                            <ul class="nav">
                                <li><a href="home.php">Home</a></li>
                                <li><a href="shop_page1.php" class="active">Our Shop</a></li>
                                <li><a href="my_orders.php">My Orders</a></li>
                                <li><a href="user-profile.php">My Profile</a></li>
                                <li><a href="contact.php">Contact Us</a></li>
                                <li><a href="cart.php">Cart</a></li>
                                <li><a href="logout.php">Logout</a></li>
                            </ul>
                            <a class='menu-trigger'>
                                <span>Menu</span>
                            </a>
                            <!-- ***** Menu End ***** -->
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
                        <h3>Our Shop</h3>
                        <span class="breadcrumb"><a href="home.php">Home</a> > Our Shop</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section trending">
            <div class="container">
                <ul class="trending-filter" id="filter-buttons">
                    <li>
                        <a class="is_active" data-category="">Show All</a>
                    </li>
                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                        <?php while($category = $categories_result->fetch_assoc()): ?>
                            <li>
                                <a data-category="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Current filter info -->
                <div id="filter-info" style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f7f7f7; border-radius: 10px; display: none;">
                    <p style="color: #ee626b; margin: 0;">Showing products in category: <strong id="current-category"></strong> <span id="product-count"></span></p>
                </div>
                
                <!-- Products container -->
                <div id="products-container" class="row trending-box">
                    <?php if ($initial_result && $initial_result->num_rows > 0): ?>
                        <?php while($product = $initial_result->fetch_assoc()): ?>
                            <div class="col-lg-3 col-md-6 align-self-center mb-30 trending-items col-md-6">
                                <div class="item">
                                    <div class="thumb">
                                        <a href="product-details.php?id=<?php echo $product['product_id']; ?>">
                                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </a>
                                        <?php if($product['discounted_price'] < $product['original_price']): ?>
                                            <span class="price">
                                                <em>₱<?php echo $product['original_price']; ?></em>
                                                ₱<?php echo $product['discounted_price']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="price">₱<?php echo $product['original_price']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="down-content">
                                        <span class="category"><?php echo htmlspecialchars($product['category']); ?></span>
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <a href="product-details.php?id=<?php echo $product['product_id']; ?>">
                                            <i class="fa fa-shopping-bag"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-cart" style="text-align: center; padding: 60px 20px;">
                                <i class="fa fa-shopping-bag" style="font-size: 80px; color: #ddd;"></i>
                                <h3>No products found</h3>
                                <p>Sorry, no products are available.</p>
                            </div>
                        </div>
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

    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/counter.js"></script>
    <script src="assets/js/custom.js"></script>
    
    <script>
    // Prevent Isotope from initializing and causing layout shifts
    if (typeof Isotope !== 'undefined') {
        // Override Isotope initialization
        Isotope.prototype.option = function() {};
        Isotope.prototype.layout = function() {};
        Isotope.prototype.arrange = function() {};
        Isotope.prototype.reloadItems = function() {};
        
        // Prevent any auto-initialization
        window.Isotope = null;
    }

    // AJAX Filtering - No Page Reload with Footer Fix
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('#filter-buttons a');
        const productsContainer = document.getElementById('products-container');
        const filterInfo = document.getElementById('filter-info');
        const currentCategorySpan = document.getElementById('current-category');
        const productCountSpan = document.getElementById('product-count');
        
        // Function to load products by category
        function loadProducts(category) {
            // Store current scroll position
            const scrollPosition = window.scrollY;
            
            // Show loading state
            productsContainer.innerHTML = '<div class="col-12"><div class="loading-overlay"><div class="spinner"></div><p>Loading products...</p></div></div>';
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'filter');
            formData.append('category', category);
            
            // Fetch products via AJAX
            fetch('shop_page1.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update products container
                productsContainer.innerHTML = data.html;
                
                // Update filter info
                if (category) {
                    currentCategorySpan.textContent = category;
                    const productCount = document.querySelectorAll('#products-container .col-lg-3').length;
                    productCountSpan.textContent = `(${productCount} products found)`;
                    filterInfo.style.display = 'block';
                } else {
                    filterInfo.style.display = 'none';
                }
                
                // Restore scroll position to prevent footer jump
                window.scrollTo(0, scrollPosition);
            })
            .catch(error => {
                console.error('Error:', error);
                productsContainer.innerHTML = '<div class="col-12"><div class="empty-cart" style="text-align: center; padding: 60px 20px;"><i class="fa fa-exclamation-triangle" style="font-size: 80px; color: #ddd;"></i><h3>Error loading products</h3><p>Please try again.</p></div></div>';
            });
        }
        
        // Add click event to filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all buttons
                filterButtons.forEach(btn => {
                    btn.classList.remove('is_active');
                });
                
                // Add active class to clicked button
                this.classList.add('is_active');
                
                // Get category from data attribute
                const category = this.getAttribute('data-category');
                
                // Load products
                loadProducts(category);
            });
        });
    });
    </script>

</body>

</html>

<?php
$conn->close();
?>
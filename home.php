<?php
// Start session
session_start();

// Database connection for session check
$conn_check = new mysqli('localhost', 'root', '', 'dyna_shop');

if ($conn_check->connect_error) {
    die("Connection failed: " . $conn_check->connect_error);
}

// Check if user is logged in with valid session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['session_token']) || !isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Verify session token is still active in database
$check_stmt = $conn_check->prepare("SELECT is_active, logout_time FROM user_sessions WHERE session_token = ? AND user_id = ?");
$check_stmt->bind_param("si", $_SESSION['session_token'], $_SESSION['user_id']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Session not found in database
    session_destroy();
    header("Location: index.php");
    exit();
}

$session_data = $check_result->fetch_assoc();

// Check if session is active (is_active = 1) and logout_time is NULL
if ($session_data['is_active'] != 1 || !is_null($session_data['logout_time'])) {
    // Session is no longer active (user logged out)
    session_destroy();
    header("Location: index.php");
    exit();
}

$check_stmt->close();
$conn_check->close();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dyna_shop';

// Create connection for main content
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch trending products (first 4)
$trending_sql = "SELECT * FROM products LIMIT 4";
$trending_result = $conn->query($trending_sql);

// Fetch most played products (next 6)
$most_played_sql = "SELECT * FROM products LIMIT 6 OFFSET 4";
$most_played_result = $conn->query($most_played_sql);

// Fetch categories (distinct categories from products)
$categories_sql = "SELECT DISTINCT category FROM products LIMIT 5";
$categories_result = $conn->query($categories_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <title>Dyna Shop</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-lugx-gaming.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css"/>
    
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
                        <!-- ***** Logo Start ***** -->
                        <a href="home.php" class="logo">
                            <img src="assets/images/Logo.png" alt="" style="width: 158px;">
                        </a>
                        <!-- ***** Logo End ***** -->
                        <!-- ***** Menu Start ***** -->
                        <ul class="nav">
                            <li><a href="home.php" class="active">Home</a></li>
                            <li><a href="shop_page1.php">Our Shop</a></li>
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

    <div class="main-banner">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 align-self-center">
                    <div class="caption header-text">
                        <h6>DYNA Shop</h6>
                        <h2>BEST CLOTHING EVER!</h2>
                        <p>DYNA Shop is a modern clothing e‑commerce platform designed to bring style, comfort, and affordability together. With a wide range of fashion essentials—from everyday wear to statement pieces—it offers customers a seamless shopping experience through an intuitive interface, secure payment options, and fast delivery services. DYNA Shop emphasizes quality and trend‑forward designs, making it a go‑to destination for individuals who want to express themselves through fashion while enjoying the convenience of online shopping</p>
                        <div class="search-input">
                            <form id="search" action="#">
                                <input type="text" placeholder="Type Something" id='searchText' name="searchKeyword" onkeypress="handle" />
                                <button role="button">Search Now</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-2">
                    <div class="right-image">
                        <img src="assets/images/clothes-13.jpg" alt="">
                        <span class="price">₱1232</span>
                        <span class="offer">-40%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trending Section -->
    <div class="section trending">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="section-heading">
                        <h6>Trending</h6>
                        <h2>Trending Products</h2>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="main-button">
                        <a href="shop_page1.php">View All</a>
                    </div>
                </div>
                
                <?php if ($trending_result && $trending_result->num_rows > 0): ?>
                    <?php while($product = $trending_result->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-6">
                            <div class="item">
                                <div class="thumb">
                                    <a href="product-details.php?id=<?php echo $product['product_id']; ?>">
                                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
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
                                    <span class="category"><?php echo $product['category']; ?></span>
                                    <h4><?php echo $product['name']; ?></h4>
                                    <a href="product-details.php?id=<?php echo $product['product_id']; ?>"><i class="fa fa-shopping-bag"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>No products found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Most Played Section -->
    <div class="section most-played">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="section-heading">
                        <h6>TOP PRODUCTS</h6>
                        <h2>Most Popular</h2>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="main-button">
                        <a href="shop_page1.php">View All</a>
                    </div>
                </div>
                
                <?php if ($most_played_result && $most_played_result->num_rows > 0): ?>
                    <?php while($product = $most_played_result->fetch_assoc()): ?>
                        <div class="col-lg-2 col-md-6 col-sm-6">
                            <div class="item">
                                <div class="thumb">
                                    <a href="product-details.php?id=<?php echo $product['product_id']; ?>">
                                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                                    </a>
                                </div>
                                <div class="down-content">
                                    <span class="category"><?php echo $product['category']; ?></span>
                                    <h4><?php echo $product['name']; ?></h4>
                                    <a href="product-details.php?id=<?php echo $product['product_id']; ?>">Explore</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>No products found.</p>
                    </div>
                <?php endif; ?>
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
// Close database connection
$conn->close();
?>
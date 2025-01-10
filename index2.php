<?php
// Include database connection
include 'db.php';

// Start session to handle cart
session_start();

// Initialize cart if not already done
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to count total items in the cart
function getCartCount() {
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Fetch products for Fruits Shop
$fruits = $conn->query("SELECT * FROM products WHERE category = 'Fruits' LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fruitables - Organic Store</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>
<body>

<!-- Navbar Start -->
<div class="container-fluid fixed-top">
    <div class="container topbar bg-primary d-none d-lg-block">
        <div class="d-flex justify-content-between">
            <div class="top-info ps-2">
                <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i><a href="#" class="text-white">123 Street, New York</a></small>
                <small class="me-3"><i class="fas fa-envelope me-2 text-secondary"></i><a href="#" class="text-white">Email@Example.com</a></small>
            </div>
            <div class="top-link pe-2">
                <a href="#" class="text-white"><small class="mx-2">Privacy Policy</small></a>/
                <a href="#" class="text-white"><small class="mx-2">Terms of Use</small></a>/
                <a href="#" class="text-white"><small class="ms-2">Sales and Refunds</small></a>
            </div>
        </div>
    </div>
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl">
            <a href="index.php" class="navbar-brand"><h1 class="text-primary display-6">Fruitables</h1></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    <a href="index.php" class="nav-item nav-link active">Home</a>
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4"><i class="fas fa-search text-primary"></i></button>
                    <a href="cart.php" class="position-relative me-4 my-auto">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span id="cart-count" class="position-absolute bg-secondary rounded-circle text-dark px-1" style="top: -5px; left: 15px; height: 20px;"><?= getCartCount() ?></span>
                    </a>
                    <a href="login.php" class="my-auto"><i class="fas fa-user fa-2x"></i></a>
                </div>
            </div>
        </nav>
    </div>
</div>
<!-- Navbar End -->

<!-- Fruits Shop Start -->
<div class="container py-5">
    <h1 class="text-center mb-5">Our Fruits</h1>
    <div class="row g-4">
        <?php foreach ($fruits as $fruit): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="rounded position-relative fruite-item">
                    <div class="fruite-img">
                        <img src="img/<?= htmlspecialchars($fruit['image']) ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($fruit['name']) ?>">
                    </div>
                    <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">Fruits</div>
                    <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                        <h4><?= htmlspecialchars($fruit['name']) ?></h4>
                        <p><?= htmlspecialchars($fruit['description']) ?></p>
                        <div class="d-flex justify-content-between">
                            <p class="text-dark fs-5 fw-bold mb-0">$<?= number_format($fruit['price'], 2) ?> / kg</p>
                            <button class="btn border border-secondary rounded-pill px-3 text-primary add-to-cart" data-id="<?= $fruit['id'] ?>"><i class="fa fa-shopping-bag me-2 text-primary"></i> Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- Fruits Shop End -->

<!-- Footer Start -->
<div class="container-fluid bg-dark text-white py-4">
    <div class="container text-center">
        <p>&copy; Fruitables, All Rights Reserved.</p>
    </div>
</div>
<!-- Footer End -->

<script>
    $(document).on('click', '.add-to-cart', function () {
        const productId = $(this).data('id');
        $.post('cart_handler.php', { id: productId }, function (response) {
            if (response.success) {
                $('#cart-count').text(response.cartCount);
            } else {
                alert('Failed to add item to cart.');
            }
        }, 'json');
    });
</script>
</body>
</html>

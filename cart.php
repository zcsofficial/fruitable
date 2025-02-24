<?php
include 'db.php'; // Include the database connection file
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Function to count total items in the cart (for navbar)
function getCartCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM carts WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

// Fetch cart items for the logged-in user
$cartQuery = $conn->prepare("
    SELECT c.id as cart_id, p.id as product_id, p.name, p.price, c.quantity, p.image 
    FROM carts c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?");
$cartQuery->bind_param("i", $userId);
$cartQuery->execute();
$cartItems = $cartQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$cartQuery->close();

// Calculate cart totals
$subtotal = 0;
$totalItems = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalItems += $item['quantity'];
}
$shipping = 250.00; // Flat rate in INR
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cart - Fruitables</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                        <a href="index.php" class="nav-item nav-link">Home</a>
                        <a href="shop.php" class="nav-item nav-link">Shop</a>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                    <div class="d-flex m-3 me-0">
                        <a href="cart.php" class="position-relative me-4 my-auto">
                            <i class="fa fa-shopping-bag fa-2x"></i>
                            <span id="cart-count" class="position-absolute bg-secondary rounded-circle text-dark px-1" style="top: -5px; left: 15px; height: 20px;"><?= getCartCount($conn, $userId) ?></span>
                        </a>
                        <a href="logout.php" class="my-auto"><i class="fas fa-sign-out-alt fa-2x"></i></a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Cart</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active text-white">Cart</li>
        </ol>
    </div>
    <!-- Page Header End -->

    <!-- Cart Page Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <?php if (empty($cartItems)): ?>
                <div class="alert alert-info text-center">Your cart is empty. <a href="shop.php">Start shopping now!</a></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Total</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <th scope="row">
                                        <img src="img/<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded-circle" style="width: 80px; height: 80px;" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </th>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td>₹<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <div class="input-group quantity" style="width: 100px;">
                                            <div class="input-group-btn">
                                                <button class="btn btn-sm btn-minus rounded-circle bg-light border update-quantity" data-cart-id="<?= $item['cart_id'] ?>" data-operation="decrement">
                                                    <i class="fa fa-minus"></i>
                                                </button>
                                            </div>
                                            <input type="text" class="form-control form-control-sm text-center border-0" value="<?= $item['quantity'] ?>" readonly>
                                            <div class="input-group-btn">
                                                <button class="btn btn-sm btn-plus rounded-circle bg-light border update-quantity" data-cart-id="<?= $item['cart_id'] ?>" data-operation="increment">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-md rounded-circle bg-light border remove-item" data-cart-id="<?= $item['cart_id'] ?>">
                                            <i class="fa fa-times text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="row g-4 justify-content-end">
                    <div class="col-8"></div>
                    <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                        <div class="bg-light rounded">
                            <div class="p-4">
                                <h1 class="display-6 mb-4">Cart <span class="fw-normal">Total</span></h1>
                                <div class="d-flex justify-content-between mb-4">
                                    <h5>Subtotal:</h5>
                                    <p>₹<?= number_format($subtotal, 2) ?></p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <h5>Shipping:</h5>
                                    <p>₹<?= number_format($shipping, 2) ?></p>
                                </div>
                            </div>
                            <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                                <h5>Total:</h5>
                                <p>₹<?= number_format($total, 2) ?></p>
                            </div>
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="cart_items" value='<?= htmlentities(json_encode($cartItems)) ?>'>
                                <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                                <input type="hidden" name="total" value="<?= $total ?>">
                                <button type="submit" class="btn border-secondary rounded-pill px-4 py-3 text-primary w-100">Proceed to Checkout</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Cart Page End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
        <div class="container py-5">
            <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
                <div class="row g-4">
                    <div class="col-lg-3">
                        <a href="index.php">
                            <h1 class="text-primary mb-0">Fruitables</h1>
                            <p class="text-secondary mb-0">Fresh Products</p>
                        </a>
                    </div>
                    <div class="col-lg-6">
                        <div class="position-relative mx-auto">
                            <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="email" placeholder="Your Email">
                            <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="d-flex justify-content-end pt-3">
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="#"><i class="fab fa-youtube"></i></a>
                            <a class="btn btn-outline-secondary btn-md-square rounded-circle" href="#"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <div class="footer-item">
                        <h4 class="text-light mb-3">Why People Like Us!</h4>
                        <p class="mb-4">We deliver high-quality organic produce with exceptional service.</p>
                        <a href="about.php" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">Shop Info</h4>
                        <a class="btn-link" href="about.php">About Us</a>
                        <a class="btn-link" href="contact.php">Contact Us</a>
                        <a class="btn-link" href="#">Privacy Policy</a>
                        <a class="btn-link" href="#">Terms & Conditions</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex flex-column text-start footer-item">
                        <h4 class="text-light mb-3">Account</h4>
                        <a class="btn-link" href="profile.php">My Account</a>
                        <a class="btn-link" href="cart.php">Shopping Cart</a>
                        <a class="btn-link" href="orders.php">Order History</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="footer-item">
                        <h4 class="text-light mb-3">Contact</h4>
                        <p>Address: 1429 Netus Rd, NY 48247</p>
                        <p>Email: Example@gmail.com</p>
                        <p>Phone: +0123 4567 8910</p>
                        <p>Payment Accepted</p>
                        <img src="img/payment.png" class="img-fluid" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Update Quantity AJAX
        $('.update-quantity').on('click', function() {
            const cartId = $(this).data('cart-id');
            const operation = $(this).data('operation');
            $.ajax({
                url: 'update_cart.php',
                type: 'POST',
                data: { cart_id: cartId, operation: operation },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Refresh to update the cart
                    } else {
                        alert('Failed to update quantity: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error connecting to server.');
                }
            });
        });

        // Remove Item AJAX
        $('.remove-item').on('click', function() {
            const cartId = $(this).data('cart-id');
            $.ajax({
                url: 'remove_cart.php',
                type: 'POST',
                data: { cart_id: cartId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Refresh to update the cart
                    } else {
                        alert('Failed to remove item: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error connecting to server.');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php $conn->close(); ?>
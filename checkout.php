<?php
// Include database connection
include 'db.php';
session_start();

// Fetch the logged-in user's ID
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if user is not logged in
    exit();
}

$userId = $_SESSION['user_id'];

// Initialize cart if not already done
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to count total items in the cart
function getCartCount() {
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Fetch cart items for the logged-in user
$cartQuery = $conn->prepare("
    SELECT c.product_id, p.name, p.price, c.quantity, p.image 
    FROM carts c 
    INNER JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?");
$cartQuery->bind_param("i", $userId);
$cartQuery->execute();
$cartItems = $cartQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$cartQuery->close();

// Calculate cart totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 15.00; // Example flat rate
$total = $subtotal + $shipping;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure POST fields are available
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['address'], $_POST['city'], $_POST['country'], $_POST['postcode'], $_POST['mobile'], $_POST['email'], $_POST['payment_method'])) {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $company = $_POST['company'] ?? ''; // Use empty string if not provided
        $address = $_POST['address'];
        $city = $_POST['city'];
        $country = $_POST['country'];
        $postcode = $_POST['postcode'];
        $mobile = $_POST['mobile'];
        $email = $_POST['email'];
        $notes = $_POST['notes'] ?? ''; // Use empty string if not provided
        $paymentMethod = $_POST['payment_method'];

        // Insert order into the orders table
        $orderQuery = $conn->prepare("
            INSERT INTO orders (user_id, total_price, status) 
            VALUES (?, ?, 'Pending')");
        $orderQuery->bind_param("id", $userId, $total);
        $orderQuery->execute();
        $orderId = $orderQuery->insert_id; // Get the ID of the inserted order
        $orderQuery->close();

        // Insert each cart item into the order_items table
        foreach ($cartItems as $item) {
            $orderItemQuery = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)");
            $orderItemQuery->bind_param(
                "iiid", 
                $orderId, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price']
            );
            $orderItemQuery->execute();
            $orderItemQuery->close();
        }

        // Insert billing address details into billing_address table
        $billingAddressQuery = $conn->prepare("
            INSERT INTO billing_address (user_id, first_name, last_name, company, address, city, country, postcode, mobile, email, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Adjusting bind_param to match the number of variables
        $billingAddressQuery->bind_param(
            "isssssssssss", 
            $userId, $firstName, $lastName, $company, $address, $city, $country, $postcode, $mobile, $email, $notes
        );
        $billingAddressQuery->execute();
        $billingAddressQuery->close();

        // Clear the user's cart
        $clearCartQuery = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        $clearCartQuery->bind_param("i", $userId);
        $clearCartQuery->execute();
        $clearCartQuery->close();

        // Redirect to a success page
        header("Location: success.php");
        exit();
    } else {
        echo "Some required fields are missing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fruitables - Checkout</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Include SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
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


        <!-- Modal Search Start -->
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Search End -->


        <!-- Single Page Header start -->
        <div class="container-fluid page-header py-5">
            <h1 class="text-center text-white display-6">Checkout</h1>
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pages</a></li>
                <li class="breadcrumb-item active text-white">Checkout</li>
            </ol>
        </div>
        <!-- Single Page Header End -->
<!-- Checkout Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <h1 class="mb-4">Billing details</h1>
        <form action="checkout.php" method="POST" id="checkoutForm">
            <div class="row g-5">
                <div class="col-md-12 col-lg-6 col-xl-7">
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3">First Name<sup>*</sup></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <div class="form-item w-100">
                                <label class="form-label my-3">Last Name<sup>*</sup></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Company Name</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Address<sup>*</sup></label>
                        <input type="text" class="form-control" name="address" placeholder="House Number Street Name" required>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Town/City<sup>*</sup></label>
                        <input type="text" class="form-control" name="city" required>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Country<sup>*</sup></label>
                        <input type="text" class="form-control" name="country" required>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Postcode/Zip<sup>*</sup></label>
                        <input type="text" class="form-control" name="postcode" required>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Mobile<sup>*</sup></label>
                        <input type="tel" class="form-control" name="mobile" required>
                    </div>
                    <div class="form-item">
                        <label class="form-label my-3">Email Address<sup>*</sup></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-item">
                        <textarea name="notes" class="form-control" spellcheck="false" cols="30" rows="5" placeholder="Order Notes (Optional)"></textarea>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6 col-xl-5">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Products</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <th scope="row">
                                        <div class="d-flex align-items-center mt-2">
                                            <img src="img/<?= htmlspecialchars($item['image']) ?>" class="img-fluid rounded-circle" style="width: 90px; height: 90px;" alt="<?= htmlspecialchars($item['name']) ?>">
                                        </div>
                                    </th>
                                    <td class="py-5"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="py-5">$<?= number_format($item['price'], 2) ?></td>
                                    <td class="py-5"><?= $item['quantity'] ?></td>
                                    <td class="py-5">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h4 class="text-primary">Subtotal</h4>
                        <h5>$<?= number_format($subtotal, 2) ?></h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="text-primary">Shipping</h4>
                        <h5>$<?= number_format($shipping, 2) ?></h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="text-primary">Total</h4>
                        <h5>$<?= number_format($total, 2) ?></h5>
                    </div>
                    <div class="form-item mt-4">
                        <label class="form-label my-3">Payment Method<sup>*</sup></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="Credit Card">Credit Card</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>
                    <div class="form-item my-4">
                        <button type="submit" class="btn btn-primary w-100">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Checkout Page End -->
  <!-- Footer Start -->
  <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
            <div class="container py-5">
                <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
                    <div class="row g-4">
                        <div class="col-lg-3">
                            <a href="#">
                                <h1 class="text-primary mb-0">Fruitables</h1>
                                <p class="text-secondary mb-0">Fresh products</p>
                            </a>
                        </div>
                        <div class="col-lg-6">
                            <div class="position-relative mx-auto">
                                <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="number" placeholder="Your Email">
                                <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="d-flex justify-content-end pt-3">
                                <a class="btn  btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                                <a class="btn btn-outline-secondary btn-md-square rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-item">
                            <h4 class="text-light mb-3">Why People Like us!</h4>
                            <p class="mb-4">typesetting, remaining essentially unchanged. It was 
                                popularised in the 1960s with the like Aldus PageMaker including of Lorem Ipsum.</p>
                            <a href="" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Shop Info</h4>
                            <a class="btn-link" href="">About Us</a>
                            <a class="btn-link" href="">Contact Us</a>
                            <a class="btn-link" href="">Privacy Policy</a>
                            <a class="btn-link" href="">Terms & Condition</a>
                            <a class="btn-link" href="">Return Policy</a>
                            <a class="btn-link" href="">FAQs & Help</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Account</h4>
                            <a class="btn-link" href="">My Account</a>
                            <a class="btn-link" href="">Shop details</a>
                            <a class="btn-link" href="">Shopping Cart</a>
                            <a class="btn-link" href="">Wishlist</a>
                            <a class="btn-link" href="">Order History</a>
                            <a class="btn-link" href="">International Orders</a>
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
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.js"></script>
<script>
    // Handle form submission to show success or error alert
    document.getElementById("checkoutForm").onsubmit = function(event) {
        event.preventDefault();
        Swal.fire({
            icon: 'success',
            title: 'Order placed successfully!',
            showConfirmButton: false,
            timer: 1500
        }).then(function() {
            window.location.href = 'order.php'; // Redirect to a success page
        });
    };
</script>
</body>
</html>

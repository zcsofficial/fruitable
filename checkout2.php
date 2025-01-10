<?php
// Include database connection
include 'db.php';
session_start();

// Simulate a logged-in user for testing (replace with actual user session logic)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Example user ID
}

$userId = $_SESSION['user_id'];

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
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $company = $_POST['company'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $postcode = $_POST['postcode'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $notes = $_POST['notes'];
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

    // Clear the user's cart
    $clearCartQuery = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    $clearCartQuery->bind_param("i", $userId);
    $clearCartQuery->execute();
    $clearCartQuery->close();

    // Redirect to a success page
    header("Location: success.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Fruitables - Checkout</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
<!-- Page Header Start -->
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Checkout</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active text-white">Checkout</li>
    </ol>
</div>
<!-- Page Header End -->

<!-- Checkout Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <h1 class="mb-4">Billing details</h1>
        <form action="checkout.php" method="POST">
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
                                <tr>
                                    <td colspan="4" class="py-5 text-end">Subtotal</td>
                                    <td class="py-5">$<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="py-5 text-end">Shipping</td>
                                    <td class="py-5">$<?= number_format($shipping, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="py-5 text-end">Total</td>
                                    <td class="py-5">$<?= number_format($total, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-item my-4">
                        <label for="payment_method" class="form-label">Select Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="bank_transfer">Direct Bank Transfer</option>
                            <option value="check">Check Payments</option>
                            <option value="cod">Cash On Delivery</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    <button type="submit" class="btn border-secondary py-3 px-4 w-100 text-primary">Place Order</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Checkout Page End -->

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>

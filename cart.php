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
$shipping = 3.00; // Flat rate
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Fruitables - Cart</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
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
                    <a href="index.php" class="nav-item nav-link active">Home</a>
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <a href="cart.php" class="position-relative me-4 my-auto">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span id="cart-count" class="position-absolute bg-secondary rounded-circle text-dark px-1" style="top: -5px; left: 15px; height: 20px;"><?= $totalItems ?></span>
                    </a>
                    <a href="login.php" class="my-auto"><i class="fas fa-user fa-2x"></i></a>
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
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active text-white">Cart</li>
    </ol>
</div>
<!-- Page Header End -->

<!-- Cart Page Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
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
                        <td>$<?= number_format($item['price'], 2) ?></td>
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
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
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
                            <p>$<?= number_format($subtotal, 2) ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <h5>Shipping:</h5>
                            <p>$<?= number_format($shipping, 2) ?></p>
                        </div>
                    </div>
                    <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                        <h5>Total:</h5>
                        <p>$<?= number_format($total, 2) ?></p>
                    </div>
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="cart_items" value='<?= json_encode($cartItems) ?>'>
                        <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                        <input type="hidden" name="total" value="<?= $total ?>">
                        <button type="submit" class="btn border-secondary rounded-pill px-4 py-3 text-primary">Proceed to Checkout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Cart Page End -->

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Update Quantity AJAX
    $(document).on('click', '.update-quantity', function () {
        const cartId = $(this).data('cart-id');
        const operation = $(this).data('operation');
        $.post('update_cart.php', { cart_id: cartId, operation: operation }, function () {
            location.reload(); // Refresh to update the cart
        });
    });

    // Remove Item AJAX
    $(document).on('click', '.remove-item', function () {
        const cartId = $(this).data('cart-id');
        $.post('remove_cart.php', { cart_id: cartId }, function () {
            location.reload(); // Refresh to update the cart
        });
    });
</script>
</body>
</html>

<?php
session_start();
header('Content-Type: application/json');
include 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must log in to add items to the cart.']);
    exit;
}

$userId = $_SESSION['user_id']; // Assume user ID is stored in session after login
$productId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($productId > 0) {
    // Check if product already exists in the cart
    $stmt = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update quantity if product already exists in the cart
        $row = $result->fetch_assoc();
        $newQuantity = $row['quantity'] + 1;

        $updateStmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newQuantity, $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new product into the cart
        $insertStmt = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $quantity = 1;
        $insertStmt->bind_param("iii", $userId, $productId, $quantity);
        $insertStmt->execute();
        $insertStmt->close();
    }

    // Get updated cart count
    $countStmt = $conn->prepare("SELECT SUM(quantity) AS cart_count FROM carts WHERE user_id = ?");
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $cartCount = $countResult->fetch_assoc()['cart_count'];
    $countStmt->close();

    echo json_encode(['success' => true, 'cartCount' => $cartCount]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
}
?>

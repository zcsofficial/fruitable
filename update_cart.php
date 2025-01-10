<?php
include 'db.php';
session_start();

$cartId = $_POST['cart_id'];
$operation = $_POST['operation'];

if ($operation === 'increment') {
    $query = $conn->prepare("UPDATE carts SET quantity = quantity + 1 WHERE id = ?");
} else if ($operation === 'decrement') {
    $query = $conn->prepare("UPDATE carts SET quantity = GREATEST(quantity - 1, 1) WHERE id = ?");
}

$query->bind_param("i", $cartId);
$query->execute();
$query->close();
?>

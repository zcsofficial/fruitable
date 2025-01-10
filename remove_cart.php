<?php
include 'db.php';
session_start();

$cartId = $_POST['cart_id'];

$query = $conn->prepare("DELETE FROM carts WHERE id = ?");
$query->bind_param("i", $cartId);
$query->execute();
$query->close();
?>

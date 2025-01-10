<?php
$host = 'localhost';
$db = 'fruitable';
$user = 'root';
$pass = 'Adnan@66202';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

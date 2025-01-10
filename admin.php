<?php
// Include database connection
include 'db.php';

// Initialize variables
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image = $_FILES['image']['name'];

    // Handle image upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $name, $category, $description, $price, $image);

        if ($stmt->execute()) {
            $message = "Product added successfully!";
        } else {
            $message = "Failed to add product: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Failed to upload image.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Panel - Add Product</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Admin Panel - Add Product</h1>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="admin.php" method="POST" enctype="multipart/form-data" class="mt-4">
        <div class="mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select name="category" id="category" class="form-control" required>
                <option value="Fruits">Fruits</option>
                <option value="Vegetables">Vegetables</option>
                <option value="Bread">Bread</option>
                <option value="Meat">Meat</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (in USD)</label>
            <input type="number" step="0.01" name="price" id="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Product Image</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
        </div>

        <button type="submit" class="btn btn-primary">Add Product</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

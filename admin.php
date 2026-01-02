<?php 
include 'config.php'; 
session_start();

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// 1. Handle Delete Action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Optional: Add logic to unlink/delete the image file from the 'images/' folder here
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    header("Location: admin.php");
}

// 2. Handle Add Product Action (Export Requirements)
if (isset($_POST['submit'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $moq = mysqli_real_escape_string($conn, $_POST['moq']);
    $production_capacity = mysqli_real_escape_string($conn, $_POST['production_capacity']); 
    $packaging = mysqli_real_escape_string($conn, $_POST['packaging']); 
    $shipping_method = mysqli_real_escape_string($conn, $_POST['shipping_method']); 
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $specifications = mysqli_real_escape_string($conn, $_POST['specifications']); 
    $advantages = mysqli_real_escape_string($conn, $_POST['advantages']); 
    
    // Image Upload Logic
    $image_name = $_FILES['product_image']['name'];
    $target = "images/" . basename($image_name);

    $sql = "INSERT INTO products (product_name, category, moq, production_capacity, packaging, shipping_method, description, specifications, advantages, image_name) 
            VALUES ('$product_name', '$category', '$moq', '$production_capacity', '$packaging', '$shipping_method', '$description', '$specifications', '$advantages', '$image_name')";
    
    if (mysqli_query($conn, $sql)) {
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
            echo "<script>alert('Product added successfully!'); window.location='admin.php';</script>";
        } else {
            echo "<script>alert('Product added, but image upload failed.');</script>";
        }
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table img { object-fit: cover; border-radius: 4px; }
        .action-btns { white-space: nowrap; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm p-4">
                <h4 class="fw-bold">Add New Product</h4>
                <p class="text-muted small">Fill in all export details as required.</p>
                <hr>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="fw-bold small">Product Name</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Spices">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Product Specifications</label>
                        <textarea name="specifications" class="form-control" rows="2" placeholder="e.g. Moisture <12%, Grade A"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Key Advantages</label>
                        <textarea name="advantages" class="form-control" rows="2" placeholder="e.g. Volcanic soil, Organic"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Capacity</label>
                            <input type="text" name="production_capacity" class="form-control" placeholder="e.g. 50 Tons/Month">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">MOQ</label>
                            <input type="text" name="moq" class="form-control" placeholder="e.g. 1000 kgs">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Packaging</label>
                        <input type="text" name="packaging" class="form-control" placeholder="e.g. 25kg PP Bags">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Shipping Method</label>
                        <input type="text" name="shipping_method" class="form-control" placeholder="e.g. Sea Freight FOB">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">General Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small">Product Image</label>
                        <input type="file" name="product_image" class="form-control" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary w-100 fw-bold">Upload Product</button>
                </form>
            </div>
            <div class="mt-3">
                <a href="index.php" class="btn btn-outline-secondary w-100">View Website</a>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <h4 class="fw-bold text-dark">Existing Products</h4>
                <div class="table-responsive">
                    <table class="table table-hover mt-3 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td><img src='images/".$row['image_name']."' width='60' height='40'></td>";
                                echo "<td class='fw-semibold'>".$row['product_name']."</td>";
                                echo "<td><span class='badge bg-light text-dark border'>".$row['category']."</span></td>";
                                echo "<td class='text-center action-btns'>";
                                echo "<a href='edit-product.php?id=".$row['id']."' class='btn btn-warning btn-sm me-1'>Edit</a>";
                                echo "<a href='admin.php?delete=".$row['id']."' class='btn btn-danger btn-sm' onclick='return confirm(\"Permanently delete this product?\")'>Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
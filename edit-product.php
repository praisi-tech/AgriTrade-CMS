<?php 
include 'config.php'; 
session_start();

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Get the ID of the product to be edited
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    $product = mysqli_fetch_assoc($result);
    
    if (!$product) {
        header("Location: admin.php");
        exit;
    }
}

// Handle Update Action
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $moq = mysqli_real_escape_string($conn, $_POST['moq']);
    $production_capacity = mysqli_real_escape_string($conn, $_POST['production_capacity']);
    $packaging = mysqli_real_escape_string($conn, $_POST['packaging']);
    $shipping_method = mysqli_real_escape_string($conn, $_POST['shipping_method']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $specifications = mysqli_real_escape_string($conn, $_POST['specifications']);
    $advantages = mysqli_real_escape_string($conn, $_POST['advantages']);

    // check if a new image is uploaded
    if (!empty($_FILES['product_image']['name'])) {
        $image_name = $_FILES['product_image']['name'];
        $target = "images/" . basename($image_name);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $target);
        $image_query = ", image_name = '$image_name'";
    } else {
        $image_query = "";
    }

    $sql = "UPDATE products SET 
            product_name = '$product_name', 
            category = '$category', 
            moq = '$moq', 
            production_capacity = '$production_capacity', 
            packaging = '$packaging', 
            shipping_method = '$shipping_method', 
            description = '$description', 
            specifications = '$specifications', 
            advantages = '$advantages' 
            $image_query 
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Product updated successfully!'); window.location='admin.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - <?php echo $product['product_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Edit Product Details</h4>
                    <a href="admin.php" class="btn btn-sm btn-secondary">Back to Panel</a>
                </div>
                <hr>
                <form action="edit-product.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Product Name</label>
                            <input type="text" name="product_name" class="form-control" value="<?php echo $product['product_name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Category</label>
                            <input type="text" name="category" class="form-control" value="<?php echo $product['category']; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small">MOQ</label>
                            <input type="text" name="moq" class="form-control" value="<?php echo $product['moq']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small">Capacity</label>
                            <input type="text" name="production_capacity" class="form-control" value="<?php echo $product['production_capacity']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="fw-bold small">Packaging</label>
                            <input type="text" name="packaging" class="form-control" value="<?php echo $product['packaging']; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Shipping Method</label>
                        <input type="text" name="shipping_method" class="form-control" value="<?php echo $product['shipping_method']; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Short Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo $product['description']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Technical Specifications</label>
                        <textarea name="specifications" class="form-control" rows="3"><?php echo $product['specifications']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Key Advantages</label>
                        <textarea name="advantages" class="form-control" rows="3"><?php echo $product['advantages']; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold small">Product Image (Leave blank to keep current)</label>
                        <input type="file" name="product_image" class="form-control mb-2">
                        <small class="text-muted">Current: <?php echo $product['image_name']; ?></small>
                    </div>

                    <button type="submit" name="update" class="btn btn-success w-100 py-2 fw-bold">UPDATE PRODUCT</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
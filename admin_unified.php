<?php 
require_once 'config.php';
require_once 'auth.php';

// Check authentication
checkAuth();

// ============================================
// SECURITY FUNCTIONS
// ============================================
// Note: CSRF functions (generateCSRFToken, verifyCSRFToken) are in auth.php

// Validate Image Upload
function validateImageUpload($file) {
    $errors = [];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error";
        return $errors;
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds 5MB limit";
    }
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = "Invalid file type. Only JPG, PNG, GIF, WEBP allowed";
    }
    
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        $errors[] = "Invalid file extension";
    }
    
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = "File is not a valid image";
    }
    
    return $errors;
}

function sanitizeFilename($filename, $prefix = 'product_') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $randomName = uniqid($prefix, true);
    return $randomName . '.' . $ext;
}

// ============================================
// HANDLE PRODUCT DELETE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $id = intval($_POST['delete_id']);
    
    $stmt = $conn->prepare("SELECT image_name FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $imagePath = "images/" . $row['image_name'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        setSuccessMessage("Product deleted successfully");
        logSecurityEvent('PRODUCT_DELETED', 'ID: ' . $id);
    }
    $stmt->close();
    
    header("Location: admin.php?tab=products");
    exit;
}

// ============================================
// HANDLE ADD PRODUCT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $errors = [];
    
    if (empty($_POST['product_name'])) {
        $errors[] = "Product name is required";
    }
    
    if (!isset($_FILES['product_image'])) {
        $errors[] = "Product image is required";
    } else {
        $imageErrors = validateImageUpload($_FILES['product_image']);
        $errors = array_merge($errors, $imageErrors);
    }
    
    if (empty($errors)) {
        $product_name = htmlspecialchars(trim($_POST['product_name']), ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
        $moq = htmlspecialchars(trim($_POST['moq']), ENT_QUOTES, 'UTF-8');
        $production_capacity = htmlspecialchars(trim($_POST['production_capacity']), ENT_QUOTES, 'UTF-8');
        $packaging = htmlspecialchars(trim($_POST['packaging']), ENT_QUOTES, 'UTF-8');
        $shipping_method = htmlspecialchars(trim($_POST['shipping_method']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
        $specifications = htmlspecialchars(trim($_POST['specifications']), ENT_QUOTES, 'UTF-8');
        $advantages = htmlspecialchars(trim($_POST['advantages']), ENT_QUOTES, 'UTF-8');
        
        $image_name = sanitizeFilename($_FILES['product_image']['name'], 'product_');
        $target = "images/" . $image_name;
        
        $stmt = $conn->prepare("INSERT INTO products (product_name, category, moq, production_capacity, packaging, shipping_method, description, specifications, advantages, image_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssssssss", 
            $product_name, $category, $moq, $production_capacity, 
            $packaging, $shipping_method, $description, $specifications, 
            $advantages, $image_name
        );
        
        if ($stmt->execute()) {
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
                setSuccessMessage("Product added successfully!");
                logSecurityEvent('PRODUCT_ADDED', 'Name: ' . $product_name);
                header("Location: admin.php?tab=products");
                exit;
            } else {
                $errors[] = "Product added, but image upload failed.";
            }
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE GALLERY DELETE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_gallery'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $id = intval($_POST['delete_id']);
    
    $stmt = $conn->prepare("SELECT image_name FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $imagePath = "images/gallery/" . $row['image_name'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        $deleteStmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        setSuccessMessage("Gallery image deleted successfully");
        logSecurityEvent('GALLERY_DELETED', 'ID: ' . $id);
    }
    $stmt->close();
    
    header("Location: admin.php?tab=gallery");
    exit;
}

// ============================================
// HANDLE ADD GALLERY
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_gallery'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $errors = [];
    
    if (empty($_POST['title'])) {
        $errors[] = "Title is required";
    }
    
    if (!isset($_FILES['gallery_image'])) {
        $errors[] = "Gallery image is required";
    } else {
        $imageErrors = validateImageUpload($_FILES['gallery_image']);
        $errors = array_merge($errors, $imageErrors);
    }
    
    if (empty($errors)) {
        $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
        $gallery_category = htmlspecialchars(trim($_POST['gallery_category']), ENT_QUOTES, 'UTF-8');
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!file_exists('images/gallery')) {
            mkdir('images/gallery', 0755, true);
        }
        
        $image_name = sanitizeFilename($_FILES['gallery_image']['name'], 'gallery_');
        $target = "images/gallery/" . $image_name;
        
        $stmt = $conn->prepare("INSERT INTO gallery (title, description, image_name, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssii", 
            $title, $description, $image_name, 
            $gallery_category, $display_order, $is_active
        );
        
        if ($stmt->execute()) {
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target)) {
                setSuccessMessage("Gallery image added successfully!");
                logSecurityEvent('GALLERY_ADDED', 'Title: ' . $title);
                header("Location: admin.php?tab=gallery");
                exit;
            } else {
                $errors[] = "Gallery added, but image upload failed.";
            }
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE TOGGLE GALLERY ACTIVE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_gallery'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $id = intval($_POST['toggle_id']);
    $is_active = intval($_POST['current_status']) === 1 ? 0 : 1;
    
    $stmt = $conn->prepare("UPDATE gallery SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_active, $id);
    
    if ($stmt->execute()) {
        $status = $is_active ? 'activated' : 'deactivated';
        setSuccessMessage("Gallery image {$status} successfully");
        logSecurityEvent('GALLERY_TOGGLED', "ID: {$id}, Status: {$status}");
    }
    $stmt->close();
    
    header("Location: admin.php?tab=gallery");
    exit;
}

$csrf_token = generateCSRFToken();
$success_message = getSuccessMessage();
$current_tab = $_GET['tab'] ?? 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | AgriTrade CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .nav-tabs .nav-link {
            color: #666;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Admin Panel</h2>
            <p class="text-muted mb-0">Manage Products & Gallery</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary me-2" target="_blank">View Website</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" 
               href="admin.php?tab=products">
                📦 Products
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $current_tab === 'gallery' ? 'active' : ''; ?>" 
               href="admin.php?tab=gallery">
                🖼️ Gallery
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        
        <!-- PRODUCTS TAB -->
        <?php if ($current_tab === 'products'): ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-4">
                    <h4 class="fw-bold">Add New Product</h4>
                    <p class="text-muted small">Fill in all export details</p>
                    <hr>
                    <form action="admin.php?tab=products" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="fw-bold small">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Spices" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Specifications</label>
                            <textarea name="specifications" class="form-control" rows="2" placeholder="e.g. Moisture <12%" maxlength="1000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Key Advantages</label>
                            <textarea name="advantages" class="form-control" rows="2" placeholder="e.g. Volcanic soil" maxlength="1000"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold small">Capacity</label>
                                <input type="text" name="production_capacity" class="form-control" placeholder="50 Tons/Month" maxlength="100">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold small">MOQ</label>
                                <input type="text" name="moq" class="form-control" placeholder="1000 kgs" maxlength="100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Packaging</label>
                            <input type="text" name="packaging" class="form-control" placeholder="25kg PP Bags" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Shipping Method</label>
                            <input type="text" name="shipping_method" class="form-control" placeholder="Sea Freight FOB" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Description</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Product Image *</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <button type="submit" name="submit_product" class="btn btn-primary w-100 fw-bold">Add Product</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h4 class="fw-bold">Products List</h4>
                    <div class="table-responsive">
                        <table class="table table-hover mt-3">
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
                                $stmt = $conn->prepare("SELECT id, product_name, category, image_name FROM products ORDER BY id DESC");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while($row = $result->fetch_assoc()) {
                                    $id = htmlspecialchars($row['id']);
                                    $name = htmlspecialchars($row['product_name']);
                                    $cat = htmlspecialchars($row['category']);
                                    $img = htmlspecialchars($row['image_name']);
                                    
                                    echo "<tr>";
                                    echo "<td><img src='images/{$img}' width='60' height='40' class='object-fit-cover'></td>";
                                    echo "<td class='fw-semibold'>{$name}</td>";
                                    echo "<td><span class='badge bg-light text-dark border'>{$cat}</span></td>";
                                    echo "<td class='text-center'>";
                                    echo "<a href='edit-product.php?id={$id}' class='btn btn-warning btn-sm me-1'>Edit</a>";
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this product?\")'>";
                                    echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
                                    echo "<input type='hidden' name='delete_id' value='{$id}'>";
                                    echo "<button type='submit' name='delete_product' class='btn btn-danger btn-sm'>Delete</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- GALLERY TAB -->
        <?php if ($current_tab === 'gallery'): ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-4">
                    <h4 class="fw-bold">Add Gallery Image</h4>
                    <p class="text-muted small">Upload facility photos</p>
                    <hr>
                    <form action="admin.php?tab=gallery" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="fw-bold small">Title *</label>
                            <input type="text" name="title" class="form-control" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Category</label>
                            <select name="gallery_category" class="form-control">
                                <option value="Facility">Facility</option>
                                <option value="Process">Process</option>
                                <option value="Logistics">Logistics</option>
                                <option value="Quality Control">Quality Control</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0">
                            <small class="text-muted">Lower = appears first</small>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small">Gallery Image *</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label class="form-check-label small" for="is_active">Active (visible)</label>
                        </div>
                        <button type="submit" name="submit_gallery" class="btn btn-primary w-100 fw-bold">Add Image</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h4 class="fw-bold">Gallery Images</h4>
                    <div class="table-responsive">
                        <table class="table table-hover mt-3">
                            <thead class="table-dark">
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("SELECT id, title, image_name, category, display_order, is_active FROM gallery ORDER BY display_order ASC, id DESC");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while($row = $result->fetch_assoc()) {
                                    $id = htmlspecialchars($row['id']);
                                    $title = htmlspecialchars($row['title']);
                                    $img = htmlspecialchars($row['image_name']);
                                    $cat = htmlspecialchars($row['category']);
                                    $order = htmlspecialchars($row['display_order']);
                                    $active = $row['is_active'];
                                    
                                    echo "<tr>";
                                    echo "<td><img src='images/gallery/{$img}' width='60' height='40' class='object-fit-cover rounded'></td>";
                                    echo "<td class='fw-semibold'>{$title}</td>";
                                    echo "<td><span class='badge bg-info'>{$cat}</span></td>";
                                    echo "<td><span class='badge bg-secondary'>{$order}</span></td>";
                                    echo "<td>";
                                    if ($active) {
                                        echo "<span class='badge bg-success'>Active</span>";
                                    } else {
                                        echo "<span class='badge bg-warning text-dark'>Inactive</span>";
                                    }
                                    echo "</td>";
                                    echo "<td class='text-center'>";
                                    
                                    // Toggle button
                                    echo "<form method='POST' style='display:inline;' class='me-1'>";
                                    echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
                                    echo "<input type='hidden' name='toggle_id' value='{$id}'>";
                                    echo "<input type='hidden' name='current_status' value='{$active}'>";
                                    $toggleBtn = $active ? "btn-warning" : "btn-success";
                                    $toggleText = $active ? "Hide" : "Show";
                                    echo "<button type='submit' name='toggle_gallery' class='btn {$toggleBtn} btn-sm'>{$toggleText}</button>";
                                    echo "</form>";
                                    
                                    echo "<a href='edit-gallery.php?id={$id}' class='btn btn-primary btn-sm me-1'>Edit</a>";
                                    
                                    echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this image?\")'>";
                                    echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
                                    echo "<input type='hidden' name='delete_id' value='{$id}'>";
                                    echo "<button type='submit' name='delete_gallery' class='btn btn-danger btn-sm'>Delete</button>";
                                    echo "</form>";
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

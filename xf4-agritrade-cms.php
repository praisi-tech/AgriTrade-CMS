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
    
    $allowedMimes = [
    'image/jpeg', 
    'image/jpg', 
    'image/pjpeg', 
    'image/x-png', 
    'image/png', 
    'image/gif', 
    'image/webp'
];
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
    
    header("Location: xf4-agritrade-cms.php?tab=products");
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
                header("Location: xf4-agritrade-cms.php?tab=products");
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
    
    header("Location: xf4-agritrade-cms.php?tab=gallery");
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
                header("Location: xf4-agritrade-cms.php?tab=gallery");
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
    
    header("Location: xf4-agritrade-cms.php?tab=gallery");
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f0f4f0 0%, #e8f2e8 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .admin-header {
            background: white;
            border-radius: 12px;
            padding: 24px 32px;
            box-shadow: 0 2px 12px rgba(44, 94, 26, 0.08);
            margin-bottom: 24px;
            border-left: 4px solid #2c5e1a;
        }
        
        .admin-header h2 {
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            font-size: 28px;
        }
        
        .admin-header p {
            color: #6b7280;
            margin: 4px 0 0 0;
            font-size: 14px;
        }
        
        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: #22c55e;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        
        .alert .btn-close {
            opacity: 0.4;
        }
        
        /* Tab Styles */
        .nav-tabs {
            border: none;
            background: white;
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(44, 94, 26, 0.06);
            margin-bottom: 24px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .nav-tabs .nav-link:hover {
            color: #2c5e1a;
            background: #f0fdf4;
        }
        
        .nav-tabs .nav-link.active {
            background: #2c5e1a;
            color: white;
            box-shadow: 0 2px 8px rgba(44, 94, 26, 0.3);
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44, 94, 26, 0.06);
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }
        
        .card:hover {
            box-shadow: 0 4px 20px rgba(44, 94, 26, 0.1);
            transform: translateY(-2px);
        }
        
        .card h4 {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 20px;
        }
        
        .card hr {
            margin: 16px 0;
            opacity: 0.1;
            border-color: #2c5e1a;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2c5e1a;
            box-shadow: 0 0 0 4px rgba(44, 94, 26, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
        }
        
        .form-control::placeholder {
            color: #9ca3af;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 5px;
        }
        
        .form-check-input:checked {
            background-color: #2c5e1a;
            border-color: #2c5e1a;
        }
        
        .form-check-label {
            margin-left: 8px;
            font-weight: 500;
            color: #4b5563;
        }
        
        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: #2c5e1a;
            color: white;
            box-shadow: 0 2px 8px rgba(44, 94, 26, 0.3);
        }
        
        .btn-primary:hover {
            background: #1e4012;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 94, 26, 0.4);
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #4b5563;
        }
        
        .btn-outline-danger {
            border: 2px solid #fee2e2;
            color: #dc2626;
            background: white;
        }
        
        .btn-outline-danger:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }
        
        /* Table Styles */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: #2c5e1a;
        }
        
        .table thead th {
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border: none;
        }
        
        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: #f9fafb;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table img {
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        
        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.3px;
        }
        
        .badge.bg-light {
            background: #f3f4f6 !important;
            color: #4b5563 !important;
            border: 1px solid #e5e7eb;
        }
        
        .badge.bg-info {
            background: #06b6d4 !important;
        }
        
        .badge.bg-secondary {
            background: #6b7280 !important;
        }
        
        .badge.bg-success {
            background: #10b981 !important;
        }
        
        .badge.bg-warning {
            background: #f59e0b !important;
        }
        
        /* Action Buttons */
        .action-btns {
            white-space: nowrap;
        }
        
        .action-btns form {
            display: inline-block;
        }
        
        /* Small text */
        small.text-muted {
            color: #9ca3af !important;
            font-size: 12px;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f3f4f6;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #2c5e1a;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #1e4012;
        }
        
        /* Icon styles */
        .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: text-bottom;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                padding: 20px;
            }
            
            .admin-header h2 {
                font-size: 24px;
            }
            
            body {
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>

<div class="container admin-container">
    <!-- Header -->
    <div class="admin-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2>Admin Panel</h2>
                <p class="mb-0">Manage Products & Gallery</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary" target="_blank">
                    View Website
                </a>
                <a href="logout.php" class="btn btn-outline-danger">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0 mt-2" style="padding-left: 20px;">
                <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>" 
               href="xf4-agritrade-cms.php?tab=products">
                Products
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $current_tab === 'gallery' ? 'active' : ''; ?>" 
               href="xf4-agritrade-cms.php?tab=gallery">
                Gallery
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        
        <!-- PRODUCTS TAB -->
        <?php if ($current_tab === 'products'): ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-4">
                    <h4>Add New Product</h4>
                    <p class="text-muted small mb-0">Fill in all export details</p>
                    <hr>
                    <form action="xf4-agritrade-cms.php?tab=products" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="product_name" class="form-control" placeholder="e.g. Whole Nutmeg" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Spices" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specifications</label>
                            <textarea name="specifications" class="form-control" rows="2" placeholder="e.g. Moisture <12%, Grade ABCD" maxlength="1000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Key Advantages</label>
                            <textarea name="advantages" class="form-control" rows="2" placeholder="e.g. Sourced from volcanic soil" maxlength="1000"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Capacity</label>
                                <input type="text" name="production_capacity" class="form-control" placeholder="50 Tons/Month" maxlength="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label">MOQ</label>
                                <input type="text" name="moq" class="form-control" placeholder="1000 kg" maxlength="100">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Packaging</label>
                            <input type="text" name="packaging" class="form-control" placeholder="25kg PP Bags" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shipping Method</label>
                            <input type="text" name="shipping_method" class="form-control" placeholder="Sea Freight FOB" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Detailed product description..." maxlength="2000"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Product Image *</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Max 5MB (JPG, PNG, GIF, WEBP)</small>
                        </div>
                        <button type="submit" name="submit_product" class="btn btn-primary w-100">
                            Add Product
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4">
                    <h4>Products List</h4>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("SELECT id, product_name, category, image_name FROM products ORDER BY id DESC");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows === 0) {
                                    echo "<tr><td colspan='4' class='text-center text-muted py-5'>No products added yet</td></tr>";
                                }
                                
                                while($row = $result->fetch_assoc()) {
                                    $id = htmlspecialchars($row['id']);
                                    $name = htmlspecialchars($row['product_name']);
                                    $cat = htmlspecialchars($row['category']);
                                    $img = htmlspecialchars($row['image_name']);
                                    
                                    echo "<tr>";
                                    echo "<td><img src='images/{$img}' width='70' height='50' alt='{$name}'></td>";
                                    echo "<td class='fw-semibold'>{$name}</td>";
                                    echo "<td><span class='badge bg-light'>{$cat}</span></td>";
                                    echo "<td class='text-center action-btns'>";
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
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-4">
                    <h4>Add Gallery Image</h4>
                    <p class="text-muted small mb-0">Upload facility photos</p>
                    <hr>
                    <form action="xf4-agritrade-cms.php?tab=gallery" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Quality Control Lab" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief description..." maxlength="500"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="gallery_category" class="form-select">
                                <option value="Facility">Facility</option>
                                <option value="Process">Process</option>
                                <option value="Logistics">Logistics</option>
                                <option value="Quality Control">Quality Control</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0" placeholder="0">
                            <small class="text-muted">Lower number appears first</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gallery Image *</label>
                            <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                            <small class="text-muted">Max 5MB (JPG, PNG, GIF, WEBP)</small>
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                                <label class="form-check-label" for="is_active">Active (visible on website)</label>
                            </div>
                        </div>
                        <button type="submit" name="submit_gallery" class="btn btn-primary w-100">
                            Add Image
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4">
                    <h4>Gallery Images</h4>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->prepare("SELECT id, title, image_name, category, display_order, is_active FROM gallery ORDER BY display_order ASC, id DESC");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows === 0) {
                                    echo "<tr><td colspan='6' class='text-center text-muted py-5'>No gallery images yet</td></tr>";
                                }
                                
                                while($row = $result->fetch_assoc()) {
                                    $id = htmlspecialchars($row['id']);
                                    $title = htmlspecialchars($row['title']);
                                    $img = htmlspecialchars($row['image_name']);
                                    $cat = htmlspecialchars($row['category']);
                                    $order = htmlspecialchars($row['display_order']);
                                    $active = $row['is_active'];
                                    
                                    echo "<tr>";
                                    echo "<td><img src='images/gallery/{$img}' width='70' height='50' alt='{$title}'></td>";
                                    echo "<td class='fw-semibold'>{$title}</td>";
                                    echo "<td><span class='badge bg-info'>{$cat}</span></td>";
                                    echo "<td><span class='badge bg-secondary'>{$order}</span></td>";
                                    echo "<td>";
                                    if ($active) {
                                        echo "<span class='badge bg-success'>Active</span>";
                                    } else {
                                        echo "<span class='badge bg-warning'>Inactive</span>";
                                    }
                                    echo "</td>";
                                    echo "<td class='text-center action-btns'>";
                                    
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
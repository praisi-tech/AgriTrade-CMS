<?php 
require_once 'config.php';
require_once 'auth.php';

// Check authentication
checkAuth();

// ============================================
// SECURITY FUNCTIONS
// ============================================

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

function sanitizeFilename($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $randomName = uniqid('gallery_', true);
    return $randomName . '.' . $ext;
}

// ============================================
// HANDLE DELETE ACTION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $id = intval($_POST['delete_id']);
    
    $stmt = $conn->prepare("SELECT image_name FROM gallery WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete image file
        $imagePath = "images/gallery/" . $row['image_name'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        setSuccessMessage("Gallery image deleted successfully");
        logSecurityEvent('GALLERY_DELETED', 'ID: ' . $id);
    }
    $stmt->close();
    
    header("Location: manage-gallery.php");
    exit;
}

// ============================================
// HANDLE ADD GALLERY IMAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
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
        $category = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Create gallery directory if not exists
        if (!file_exists('images/gallery')) {
            mkdir('images/gallery', 0755, true);
        }
        
        $image_name = sanitizeFilename($_FILES['gallery_image']['name']);
        $target = "images/gallery/" . $image_name;
        
        $stmt = $conn->prepare("INSERT INTO gallery (title, description, image_name, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssii", 
            $title, 
            $description, 
            $image_name, 
            $category, 
            $display_order,
            $is_active
        );
        
        if ($stmt->execute()) {
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target)) {
                setSuccessMessage("Gallery image added successfully!");
                logSecurityEvent('GALLERY_ADDED', 'Title: ' . $title);
                header("Location: manage-gallery.php");
                exit;
            } else {
                $errors[] = "Image added, but file upload failed.";
            }
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE TOGGLE ACTIVE STATUS
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
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
    
    header("Location: manage-gallery.php");
    exit;
}

$csrf_token = generateCSRFToken();
$success_message = getSuccessMessage();
$error_message = getErrorMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery | AgriTrade CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
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

    <!-- Navigation -->
    <div class="mb-4">
        <a href="xf4-agritrade-cms.php" class="btn btn-outline-secondary">← Back to Products</a>
        <a href="index.php#gallery" class="btn btn-outline-primary" target="_blank">View Gallery</a>
    </div>

    <div class="row">
        <!-- Add Gallery Image Form -->
        <div class="col-md-4">
            <div class="card shadow-sm p-4">
                <h4 class="fw-bold">Add Gallery Image</h4>
                <p class="text-muted small">Upload facility or process images</p>
                <hr>
                <form action="manage-gallery.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label class="fw-bold small">Image Title *</label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold small">Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold small">Category</label>
                        <select name="category" class="form-control">
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
                        <small class="text-muted">Lower number appears first</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold small">Gallery Image *</label>
                        <input type="file" name="gallery_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <small class="text-muted">Max 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                        <label class="form-check-label small" for="is_active">Active (visible on website)</label>
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary w-100 fw-bold">Upload Image</button>
                </form>
            </div>
            <div class="mt-3">
                <a href="logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>

        <!-- Gallery Images List -->
        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <h4 class="fw-bold text-dark">Gallery Images</h4>
                <div class="table-responsive">
                    <table class="table table-hover mt-3 align-middle">
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
                            $stmt = $conn->prepare("SELECT id, title, description, image_name, category, display_order, is_active FROM gallery ORDER BY display_order ASC, id DESC");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows === 0) {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>No gallery images yet</td></tr>";
                            }
                            
                            while($row = $result->fetch_assoc()) {
                                $id = htmlspecialchars($row['id']);
                                $title = htmlspecialchars($row['title']);
                                $category = htmlspecialchars($row['category']);
                                $image = htmlspecialchars($row['image_name']);
                                $display_order = htmlspecialchars($row['display_order']);
                                $is_active = $row['is_active'];
                                
                                echo "<tr>";
                                echo "<td><img src='images/gallery/{$image}' width='80' height='60' class='object-fit-cover rounded' alt='{$title}'></td>";
                                echo "<td class='fw-semibold'>{$title}</td>";
                                echo "<td><span class='badge bg-info'>{$category}</span></td>";
                                echo "<td><span class='badge bg-secondary'>{$display_order}</span></td>";
                                
                                // Status badge
                                if ($is_active) {
                                    echo "<td><span class='badge bg-success'>Active</span></td>";
                                } else {
                                    echo "<td><span class='badge bg-warning text-dark'>Inactive</span></td>";
                                }
                                
                                echo "<td class='text-center action-btns'>";
                                
                                // Toggle Active Button
                                echo "<form method='POST' style='display:inline;' class='me-1'>";
                                echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
                                echo "<input type='hidden' name='toggle_id' value='{$id}'>";
                                echo "<input type='hidden' name='current_status' value='{$is_active}'>";
                                $toggleBtn = $is_active ? "btn-warning" : "btn-success";
                                $toggleText = $is_active ? "Hide" : "Show";
                                echo "<button type='submit' name='toggle_active' class='btn {$toggleBtn} btn-sm'>{$toggleText}</button>";
                                echo "</form>";
                                
                                // Edit Button
                                echo "<a href='edit-gallery.php?id={$id}' class='btn btn-primary btn-sm me-1'>Edit</a>";
                                
                                // Delete Button
                                echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Permanently delete this image?\")'>";
                                echo "<input type='hidden' name='csrf_token' value='{$csrf_token}'>";
                                echo "<input type='hidden' name='delete_id' value='{$id}'>";
                                echo "<button type='submit' class='btn btn-danger btn-sm'>Delete</button>";
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
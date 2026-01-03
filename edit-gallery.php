<?php 
require_once 'config.php';
require_once 'auth.php';

// Check authentication
checkAuth();

// ============================================
// SECURITY FUNCTIONS
// ============================================

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
// GET GALLERY DATA
// ============================================
$gallery = null;
$errors = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: xf4-agritrade-cms.php?tab=gallery");
    exit;
}

$gallery_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM gallery WHERE id = ?");
$stmt->bind_param("i", $gallery_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setErrorMessage("Gallery image not found");
    header("Location: xf4-agritrade-cms.php?tab=gallery");
    exit;
}

$gallery = $result->fetch_assoc();
$stmt->close();

// ============================================
// HANDLE UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    if (empty($_POST['title'])) {
        $errors[] = "Title is required";
    }
    
    $updateImage = false;
    $new_image_name = $gallery['image_name'];
    
    // Check if new image is uploaded
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
        $imageErrors = validateImageUpload($_FILES['gallery_image']);
        if (empty($imageErrors)) {
            $updateImage = true;
            $new_image_name = sanitizeFilename($_FILES['gallery_image']['name']);
        } else {
            $errors = array_merge($errors, $imageErrors);
        }
    }
    
    if (empty($errors)) {
        $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE gallery SET title=?, description=?, category=?, display_order=?, is_active=?, image_name=? WHERE id=?");
        
        $stmt->bind_param("sssiisi", 
            $title, 
            $description, 
            $category, 
            $display_order,
            $is_active,
            $new_image_name,
            $gallery_id
        );
        
        if ($stmt->execute()) {
            if ($updateImage) {
                // Delete old image
                $oldImagePath = "images/gallery/" . $gallery['image_name'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                
                // Upload new image
                $target = "images/gallery/" . $new_image_name;
                move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target);
            }
            
            setSuccessMessage("Gallery image updated successfully!");
            logSecurityEvent('GALLERY_UPDATED', 'ID: ' . $gallery_id);
            header("Location: xf4-agritrade-cms.php?tab=gallery");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Gallery Image | AgriTrade CMS</title>
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
            max-width: 1200px;
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
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        
        .alert .btn-close {
            opacity: 0.4;
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44, 94, 26, 0.06);
            overflow: hidden;
            background: white;
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
        
        /* Image Preview */
        .image-preview {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        
        .image-preview img {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Badge */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.3px;
        }
        
        .badge.bg-success {
            background: #10b981 !important;
        }
        
        .badge.bg-warning {
            background: #f59e0b !important;
        }
        
        small.text-muted {
            color: #9ca3af !important;
            font-size: 12px;
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
                <h2>Edit Gallery Image</h2>
                <p class="mb-0">Update gallery image details</p>
            </div>
            <div class="d-flex gap-2">
                <a href="xf4-agritrade-cms.php?tab=gallery" class="btn btn-outline-secondary">
                    ← Back to Gallery
                </a>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
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

    <!-- Edit Form -->
    <div class="card p-4">
        <form action="edit-gallery.php?id=<?php echo $gallery_id; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <h4>Gallery Information</h4>
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($gallery['title']); ?>" 
                               placeholder="e.g. Quality Control Lab" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Brief description..." maxlength="500"><?php echo htmlspecialchars($gallery['description']); ?></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="Facility" <?php echo $gallery['category'] == 'Facility' ? 'selected' : ''; ?>>Facility</option>
                                <option value="Process" <?php echo $gallery['category'] == 'Process' ? 'selected' : ''; ?>>Process</option>
                                <option value="Logistics" <?php echo $gallery['category'] == 'Logistics' ? 'selected' : ''; ?>>Logistics</option>
                                <option value="Quality Control" <?php echo $gallery['category'] == 'Quality Control' ? 'selected' : ''; ?>>Quality Control</option>
                                <option value="General" <?php echo $gallery['category'] == 'General' ? 'selected' : ''; ?>>General</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" 
                                   value="<?php echo htmlspecialchars($gallery['display_order']); ?>" min="0">
                            <small class="text-muted">Lower appears first</small>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                   <?php echo $gallery['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active (visible on website)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div>
                            <?php if ($gallery['is_active']): ?>
                                <span class="badge bg-success">Active - Visible on Website</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Inactive - Hidden from Website</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-4">
                    <h4>Gallery Image</h4>
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div class="image-preview">
                            <img src="images/gallery/<?php echo htmlspecialchars($gallery['image_name']); ?>" 
                                 class="img-fluid" 
                                 alt="<?php echo htmlspecialchars($gallery['title']); ?>"
                                 style="max-height: 250px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Upload New Image (Optional)</label>
                        <input type="file" name="gallery_image" class="form-control" accept="image/*">
                        <small class="text-muted">Max 5MB (JPG, PNG, GIF, WEBP). Leave empty to keep current image.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="update" class="btn btn-primary">
                            Update Gallery
                        </button>
                        <a href="xf4-agritrade-cms.php?tab=gallery" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
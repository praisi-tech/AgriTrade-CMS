<?php 
include 'config.php'; 

// ============================================
// SECURITY: Validate and sanitize ID parameter
// ============================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// Sanitize all output data to prevent XSS
$product_name = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');
$category = htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8');
$specifications = htmlspecialchars($product['specifications'], ENT_QUOTES, 'UTF-8');
$advantages = htmlspecialchars($product['advantages'], ENT_QUOTES, 'UTF-8');
$production_capacity = htmlspecialchars($product['production_capacity'], ENT_QUOTES, 'UTF-8');
$moq = htmlspecialchars($product['moq'], ENT_QUOTES, 'UTF-8');
$packaging = htmlspecialchars($product['packaging'], ENT_QUOTES, 'UTF-8');
$image_name = htmlspecialchars($product['image_name'], ENT_QUOTES, 'UTF-8');

// URL encode for WhatsApp link
$whatsapp_text = urlencode("Inquiry for " . $product['product_name']);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $product_name; ?> | AgriTrade CMS Catalog</title>
        <meta name="description" content="<?php echo $description; ?>">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/product-details-style.css">
    </head>
    <body class="bg-light">

        <nav class="navbar navbar-light bg-white shadow-sm mb-5">
            <div class="container">
                <a class="navbar-brand fw-bold fs-3" href="index.php">AgriTrade <span class="text-muted fw-light">CMS</span></a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">← Back to Catalog</a>
            </div>
        </nav>

        <div class="container pb-5">
            <div class="row bg-white rounded-4 shadow-sm overflow-hidden d-flex align-items-stretch product-container">
                
                <div class="col-md-6 p-0 border-end">
                    <img src="images/<?php echo $image_name; ?>" 
                        class="w-100 h-100 detail-img" 
                        alt="<?php echo $product_name; ?>"
                        onerror="this.src='images/placeholder.jpg'">
                </div>
                
                <div class="col-md-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                    <span class="badge bg-success mb-3 text-uppercase p-2 px-3" style="width: fit-content; letter-spacing: 1px;">
                        <?php echo $category; ?>
                    </span>
                    
                    <h1 class="fw-bold display-6 mb-3"><?php echo $product_name; ?></h1>
                    <p class="text-muted mb-4 lead" style="font-size: 1rem;"><?php echo $description; ?></p>
                    
                    <div class="bg-light p-3 p-lg-4 rounded-3 mb-4 border-start border-4 border-success">
                        <h6 class="fw-bold mb-3 text-dark text-uppercase small">Technical Specifications</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless small mb-0">
                                <tr>
                                    <td width="160" class="text-muted py-1">Technical Specs</td>
                                    <td class="py-1">: <?php echo $specifications; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Key Advantages</td>
                                    <td class="py-1">: <?php echo $advantages; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Capacity / Month</td>
                                    <td class="py-1">: <?php echo $production_capacity; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Min. Order (MOQ)</td>
                                    <td class="py-1">: <?php echo $moq; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Packaging</td>
                                    <td class="py-1">: <?php echo $packaging; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-1">Origin</td>
                                    <td class="py-1">: Indonesia</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-lg-6">
                            <a href="https://wa.me/6281200000000?text=<?php echo $whatsapp_text; ?>" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-success btn-lg w-100 fw-bold shadow-sm">
                                WhatsApp Inquiry
                            </a>
                        </div>
                        <div class="col-12 col-lg-6">
                            <a href="mailto:trade@agritrade-cms.com?subject=Inquiry: <?php echo urlencode($product['product_name']); ?>" 
                               class="btn btn-outline-dark btn-lg w-100 fw-bold">
                                Email Inquiry
                            </a>
                        </div>
                    </div>
                    
                    <p class="text-center text-muted x-small mt-3 mb-0" style="font-size: 0.7rem;">
                        * All products meet international export quality standards.
                    </p>
                </div>
            </div>
        </div>

        <footer class="bg-dark text-white pt-5 pb-3">
            <div class="container">
                <div class="row g-4">
                    
                    <div class="col-lg-4 col-md-6">
                        <h3 class="fw-bold mb-3 text-white">AgriTrade <span class="text-success">CMS</span></h3>
                        <p class="text-light opacity-75 small" style="line-height: 1.8;">
                            Premium commodity export management. Connecting international quality standards with local tropical excellence through a transparent digital catalog.
                        </p>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Navigation</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="index.php" class="text-decoration-none text-light opacity-75 footer-link">Home</a></li>
                            <li class="mb-2"><a href="index.php#products" class="text-decoration-none text-light opacity-75 footer-link">Product Catalog</a></li>
                            <li class="mb-2"><a href="index.php#about" class="text-decoration-none text-light opacity-75 footer-link">About Us</a></li>
                            <li class="mb-2"><a href="index.php#faq" class="text-decoration-none text-light opacity-75 footer-link">Trade FAQ</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Resources</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="login.php" class="text-decoration-none text-light opacity-75 footer-link">Admin Panel</a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-light opacity-75 footer-link">Privacy Policy</a></li>
                            <li class="mb-2"><a href="index.php#gallery" class="text-decoration-none text-light opacity-75 footer-link">Gallery</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Market Updates</h6>
                        <p class="text-light opacity-75 small mb-3">Get current commodity prices and shipping schedules.</p>
                        <form method="POST" action="subscribe.php" class="needs-validation" novalidate>
                            <div class="input-group mb-3 shadow-sm">
                                <input type="email" 
                                       name="email" 
                                       class="form-control form-control-sm bg-transparent border-secondary text-white shadow-none" 
                                       placeholder="Email address"
                                       required
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                                <button class="btn btn-success btn-sm px-3" type="submit">Subscribe</button>
                            </div>
                        </form>
                    </div>

                </div>

                <hr class="mt-5 mb-4 border-secondary opacity-25">

                <div class="row">
                    <div class="col-md-6">
                        <p class="small text-light opacity-50 mb-0">&copy; 2025 AgriTrade CMS. Professional Export Gateway.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="small text-light opacity-50 mb-0">Built with PHP & MySQL</p>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>

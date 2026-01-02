<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="AgriTrade CMS - A professional catalog for high-quality global commodities and export-ready products.">
        <title>AgriTrade CMS | Global Export Gateway</title>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">

    </head>

    <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.php">AgriTrade <span class="text-muted fw-light">CMS</span></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link text-dark nav-custom-link" href="#">Home</a></li>                
                    <li class="nav-item"><a class="nav-link text-dark nav-custom-link" href="#products">Product Catalog</a></li>                            
                    <li class="nav-item"><a class="nav-link text-dark nav-custom-link" href="#about">About Us</a></li>    
                    <li class="nav-item"><a class="nav-link text-dark nav-custom-link" href="#gallery">Gallery</a></li>            
                    <li class="nav-item"><a class="nav-link text-dark nav-custom-link" href="#faq">FAQ</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-lg-3 px-4 rounded-pill shadow-sm fw-normal" href="https://wa.me/6281200000000">
                            Contact Sales
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

        <header class="hero-bg text-center">
            <div class="container">
                <h1 class="display-3 fw-bold">Premium Commodities <br> for Global Markets</h1>
                <p class="lead">Delivering the finest natural products with traceable quality and international export standards.</p>
                <a href="#products" class="btn btn-primary btn-lg px-5 shadow fw-normal">Browse Catalog</a>
            </div>
        </header>

        <section id="products" class="container my-5 py-5">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-dark">Global Export Catalog</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">
                    Explore our curated selection of premium commodities, ethically sourced and processed to meet international quality standards for global distribution.
                </p>
            </div>

            <div class="row g-4">
                <?php
                $sql = "SELECT * FROM products ORDER BY id DESC";
                $result = mysqli_query($conn, $sql);
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                ?>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden product-card">
                        <img src="images/<?php echo $row['image_name']; ?>" 
                            class="card-img-top" 
                            style="height: 250px; object-fit: cover;" 
                            alt="<?php echo $row['product_name']; ?>">
                        
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-uppercase text-primary fw-bold"><?php echo $row['category']; ?></small>
                                <span class="badge bg-light text-dark border">Export Grade</span>
                            </div>
                            
                            <h4 class="card-title fw-bold"><?php echo $row['product_name']; ?></h4>
                            <p class="text-muted small mb-0">
                                <?php echo substr($row['description'], 0, 100); ?>...
                            </p>
                            
                            <hr class="my-4 opacity-25">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-0 small">Minimum Order</p>
                                    <span class="fw-bold"><?php echo $row['moq']; ?></span>
                                </div>
                                <a href="product-details.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm px-4">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } } else { ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No products found in the catalog.</p>
                    </div>
                <?php } ?>
            </div>
        </section>

        <section id="about" class="py-5 bg-white">
            <div class="container py-4">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <h6 class="text-primary fw-bold text-uppercase tracking-widest">Global Supply Solutions</h6>
                        <h2 class="display-5 fw-bold mb-4">Premium Commodity Excellence</h2>
                        <p class="lead text-dark">
                            <strong>AgriTrade CMS</strong> is a high-performance export management system designed to connect premium producers with global markets. 
                        </p>
                        <p class="text-muted">
                            Our platform showcases commodities harvested from regions defined by rich soil and ideal tropical climates. We specialize in providing a transparent supply chain for the world’s finest spices, coconut derivatives, and essential oils. Every product managed through our system is traceable, sustainable, and meets international export grades.
                        </p>
                        
                        <div class="row mt-4">
                            <div class="col-sm-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.235.235 0 0 1 .02-.022z"/>
                                        </svg>
                                    </div>
                                    <span class="fw-bold">Verified Sourcing</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.235.235 0 0 1 .02-.022z"/>
                                        </svg>
                                    </div>
                                    <span class="fw-bold">International Grades</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.235.235 0 0 1 .02-.022z"/>
                                        </svg>
                                    </div>
                                    <span class="fw-bold">Secure Logistics</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.235.235 0 0 1 .02-.022z"/>
                                        </svg>
                                    </div>
                                    <span class="fw-bold">Data Transparency</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-light p-4 rounded text-center shadow-sm border">
                                    <h2 class="fw-bold text-primary mb-0">100%</h2>
                                    <p class="small text-muted mb-0">Quality Guaranteed</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-primary text-white p-4 rounded text-center shadow-sm">
                                    <h2 class="fw-bold mb-0">Global</h2>
                                    <p class="small mb-0">Shipping Reach</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <img src="https://images.unsplash.com/photo-1566367576585-051277d52997?auto=format&fit=crop&w=800&q=80" 
                                    class="img-fluid rounded shadow" 
                                    alt="Professional export logistics">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="gallery" class="py-5 bg-white border-top">
            <div class="container py-4">
                <div class="text-center mb-5">
                    <h6 class="text-primary fw-bold text-uppercase">Transparency</h6>
                    <h2 class="display-5 fw-bold text-dark">Our Operations & Facilities</h2>
                    <p class="text-muted">Explore our production process and international standard facilities.</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <img src="https://media.istockphoto.com/id/1500771927/photo/big-warehouse.webp?a=1&b=1&s=612x612&w=0&k=20&c=-J7YJ24e_YFAH-SiikJzf8W93H5xbHxKi7YkmtCfDXE=" class="card-img-top rounded" alt="Warehouse facility" style="height: 250px; object-fit: cover;">
                            <div class="card-body p-2 text-center">
                                <small class="fw-bold">Processing & Storage Warehouse</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&q=80" class="card-img-top rounded" alt="Quality Control" style="height: 250px; object-fit: cover;">
                            <div class="card-body p-2 text-center">
                                <small class="fw-bold">Strict Quality Inspection</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <img src="https://plus.unsplash.com/premium_photo-1661880749508-71d9e7170508?blend=000000&blend-alpha=10&blend-mode=normal&blend-w=1&crop=faces%2Cedges&h=630&mark=https:%2F%2Fimages.unsplash.com%2Fopengraph%2Flogo.png&mark-align=top%2Cleft&mark-pad=50&mark-w=64&w=1200&auto=format&fit=crop&q=60&ixid=M3wxMjA3fDB8MXxhbGx8fHx8fHx8fHwxNzAyNTAzNDY3fA&ixlib=rb-4.0.3" class="card-img-top rounded" alt="Stuffing process" style="height: 250px; object-fit: cover;">
                            <div class="card-body p-2 text-center">
                                <small class="fw-bold">Export Stuffing & Logistics</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="py-5 bg-light">
            <div class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold">Frequently Asked Questions</h2>
                            <p class="text-muted">Essential information regarding our trade terms and conditions.</p>
                        </div>
                        
                        <div class="accordion accordion-flush shadow-sm rounded overflow-hidden" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        What are the available payment methods?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We accept <strong>Telegraphic Transfer (T/T)</strong> with a 30% Down Payment and 70% against Bill of Lading (B/L) scan. We also accept <strong>Irrevocable Letter of Credit (L/C) at Sight</strong> for larger orders.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        What is the Minimum Order Quantity (MOQ)?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Our standard MOQ is <strong>1x20ft Container</strong>. However, for initial trials or high-value essential oils, we may accept smaller quantities via Air Freight. Please contact our desk for specific requests.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq-3">
                                        What documents are included in the shipment?
                                    </button>
                                </h2>
                                <div id="faq-3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        As a standard, each shipment includes:
                                        <ul class="mt-2">
                                            <li>Bill of Lading (B/L)</li>
                                            <li>Commercial Invoice & Packing List</li>
                                            <li>Certificate of Origin (COO)</li>
                                            <li>Phytosanitary Certificate</li>
                                            <li>Fumigation Certificate (if required)</li>
                                        </ul>
                                        <p class="small text-muted mb-0">Note: We can also provide additional documents or third-party inspections (e.g., Sucofindo/SGS) upon request.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <a href="https://wa.me/6285240680868" class="btn btn-success rounded-circle shadow-lg position-fixed" 
            style="bottom: 30px; right: 30px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; z-index: 1000;" 
            target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93a7.898 7.898 0 0 0-2.322-5.607zM7.994 14.52a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
                </svg>
        </a>
        
        <section id="contact" class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Global Export Desk</h2>
                    <p class="text-muted">Inquire about wholesale pricing, technical specifications, or worldwide shipping schedules.</p>
                </div>

                <div class="row g-5">
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <h5 class="fw-bold mb-4">Contact Information</h5>
                            
                            <div class="d-flex mb-3">
                                <div class="text-primary me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold">Primary Origin</p>
                                    <p class="text-muted small">Indonesia (Main Export Hubs)</p>
                                </div>
                            </div>

                            <div class="d-flex mb-3">
                                <div class="text-primary me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-envelope-fill" viewBox="0 0 16 16">
                                        <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold">Email Inquiry</p>
                                    <p class="text-muted small">trade@agritrade-cms.com</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded shadow-sm overflow-hidden border" style="height: 300px;">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d16300000!2d117.888799!3d-2.4833826!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2c4c07d7496404b7%3A0xe37b4dee710ad845!2sIndonesia!5e0!3m2!1sid!2sid!4v1710000000000!5m2!1sid!2sid"
                                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                            </iframe>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm p-4">
                            <h5 class="fw-bold mb-4">Request for Quotation (RFQ)</h5>
                            <form action="#" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Full Name</label>
                                        <input type="text" class="form-control" placeholder="John Doe" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Company Name</label>
                                        <input type="text" class="form-control" placeholder="Global Trade Ltd." required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Email</label>
                                        <input type="email" class="form-control" placeholder="procurement@company.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Destination Port</label>
                                        <input type="text" class="form-control" placeholder="e.g. Rotterdam, Netherlands" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Commodity Interest</label>
                                        <select class="form-select">
                                            <option value="">Select a product...</option>
                                            <option value="Whole Nutmeg">Whole Nutmeg (ABC/SS Grade)</option>
                                            <option value="Coconut Derivatives">Coconut Derivatives</option>
                                            <option value="Essential Oils">Essential Oils / Patchouli</option>
                                            <option value="Spices">Mixed Spices</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Order Details & Technical Specs</label>
                                        <textarea class="form-control" rows="4" placeholder="Mention required quantity (MT), target price (FOB/CIF), or specific quality metrics..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Submit RFQ</button>
                                        <p class="text-center text-muted small mt-2 italic">Note: This is a demo form for portfolio purposes.</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="bg-dark text-white pt-5 pb-3">
            <div class="container">
                <div class="row g-4">
                    
                    <div class="col-lg-4 col-md-6">
                        <h3 class="fw-bold mb-3 text-white">AgriTrade <span class="text-success">CMS</span></h3>
                        <p class="text-light opacity-75 small" style="line-height: 1.8;">
                            A specialized management system for premium commodity exports. Bridging the gap between high-quality tropical producers and global industrial markets with transparency and efficiency.
                        </p>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Navigation</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="#" class="text-decoration-none text-light opacity-75 footer-link">Home</a></li>
                            <li class="mb-2"><a href="#products" class="text-decoration-none text-light opacity-75 footer-link">Product Catalog</a></li>
                            <li class="mb-2"><a href="#about" class="text-decoration-none text-light opacity-75 footer-link">About Us</a></li>
                            <li class="mb-2"><a href="#gallery" class="text-decoration-none text-light opacity-75 footer-link">Gallery</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Resources</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="login.php" class="text-decoration-none text-light opacity-75 footer-link">Admin Panel</a></li>
                            <li class="mb-2"><a href="#faq" class="text-decoration-none text-light opacity-75 footer-link">Trade FAQ</a></li>
                            <li class="mb-2"><a href="#" class="text-decoration-none text-light opacity-75 footer-link">Privacy Policy</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-4 text-uppercase small text-white" style="letter-spacing: 1px;">Newsletter</h6>
                        <p class="text-light opacity-75 small mb-3">Subscribe for latest market updates.</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-sm bg-transparent border-secondary text-white shadow-none" placeholder="Email address">
                            <button class="btn btn-success btn-sm px-3" type="button">Join</button>
                        </div>
                    </div>

                </div>

                <hr class="mt-5 mb-4 border-secondary opacity-25">

                <div class="row">
                    <div class="col-md-6">
                        <p class="small text-light opacity-50 mb-0">&copy; 2025 AgriTrade CMS. Portfolio Project.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="small text-light opacity-50 mb-0">Built with PHP & Bootstrap 5</p>
                    </div>
                </div>
            </div>
        </footer>


    </body>
</html>
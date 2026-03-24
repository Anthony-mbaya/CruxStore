<?php
require_once 'includes/db.php';

// At the top of your file, fetch products from database
$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' LIMIT 6");
$featured_products = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$latest_products = $stmt->fetchAll();


$pageTitle = "Home";

/*
$content = '

<!-- HERO SECTION -->
<section class="container py-4 py-md-5">
    <div class="row align-items-center">

        <div class="col-12 col-md-6 text-center text-md-start">
            <h1 class="fw-bold mb-3" style="font-size: clamp(2rem, 5vw, 3rem);">
                Welcome to <span class="text-primary">CruxStore</span>
            </h1>

            <p class="lead mb-4 px-2 px-md-0">
                Your one-stop shop for premium electronics and durable everyday products.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-2 gap-md-3 justify-content-center justify-content-md-start">
                <a href="register.php" class="btn btn-primary btn-lg w-100 w-sm-auto">Get Started</a>
                <a href="login.php" class="btn btn-outline-success btn-lg w-100 w-sm-auto">Sign In</a>
            </div>
        </div>

        <div class="col-12 col-md-6 text-center mt-4 mt-md-0">
            <img src="assets/images/landing/landing1.avif"
                 class="img-fluid rounded shadow"
                 style="max-height: 350px; object-fit: cover;">
        </div>

    </div>
</section>

<!-- FEATURES -->
<section class="bg-light py-4">
    <div class="container text-center">
        <div class="row g-3">

            <div class="col-6 col-md-3">
                <h6>⚡ Fast Delivery</h6>
                <p class="small mb-0">Quick shipping</p>
            </div>

            <div class="col-6 col-md-3">
                <h6>🔒 Secure Payments</h6>
                <p class="small mb-0">Safe checkout</p>
            </div>

            <div class="col-6 col-md-3">
                <h6>💯 Quality Products</h6>
                <p class="small mb-0">Trusted items</p>
            </div>

            <div class="col-6 col-md-3">
                <h6>📦 Easy Returns</h6>
                <p class="small mb-0">No stress</p>
            </div>

        </div>
    </div>
</section>

<!-- PRODUCTS -->
<section class="container py-4 py-md-5">
    <h2 class="text-center mb-4">Popular Categories</h2>

    <div class="row g-4">

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/earpods.webp"
                     class="card-img-top"
                     style="height:200px; object-fit:cover;">
                <div class="card-body text-center">
                    <h5>Wireless Earpods</h5>
                    <p class="small">Noise cancellation & long battery</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/smartwatches.avif"
                     class="card-img-top"
                     style="height:200px; object-fit:cover;">
                <div class="card-body text-center">
                    <h5>Smart Watches</h5>
                    <p class="small">Track fitness & health</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/accessories.avif"
                     class="card-img-top"
                     style="height:200px; object-fit:cover;">
                <div class="card-body text-center">
                    <h5>Accessories</h5>
                    <p class="small">Chargers & essentials</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- MORE ITEMS -->
<section class="bg-light py-4 py-md-5">
    <div class="container text-center">
        <h2 class="mb-4">More You Can Shop</h2>

        <div class="row g-3">

            <div class="col-6 col-md-3"><h6>🔌 Power Banks</h6></div>
            <div class="col-6 col-md-3"><h6>🎧 Headphones</h6></div>
            <div class="col-6 col-md-3"><h6>💡 LED Lighting</h6></div>
            <div class="col-6 col-md-3"><h6>⌨️ Accessories</h6></div>

            <div class="col-6 col-md-3"><h6>📱 Phone Cases</h6></div>
            <div class="col-6 col-md-3"><h6>🔋 Batteries</h6></div>
            <div class="col-6 col-md-3"><h6>📷 Camera Gear</h6></div>
            <div class="col-6 col-md-3"><h6>🎮 Gaming</h6></div>

        </div>
    </div>
</section>

<!-- WHY US -->
<section class="container py-4 py-md-5">
    <h2 class="text-center mb-4">Why Choose CruxStore</h2>

    <div class="row g-4">

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5>Smart Shopping</h5>
                    <p class="small">Easy browsing and checkout</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5>Curated Products</h5>
                    <p class="small">Only quality items</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5>Accurate Stock</h5>
                    <p class="small">Real-time updates</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- CTA -->
<section class="bg-primary text-white text-center py-4 py-md-5">
    <div class="container">
        <h3>Ready to Start Shopping?</h3>
        <p class="mb-3">Join CruxStore today</p>
        <a href="register.php" class="btn btn-light">Create Account</a>
    </div>
</section>

';
*/

//  $content = '
// <style>

// /* Smooth float animation */
// @keyframes float {
//   0% { transform: translateY(0) rotate(10deg); }
//   50% { transform: translateY(-15px) rotate(-10deg); }
//   100% { transform: translateY(0) rotate(10deg); }
// }

// /* HERO */
// .hero-section {
//     background: linear-gradient(135deg, #7069ff, #2495ff);
//     overflow: hidden;
// }

// /* floating image */
// .floating-image {
//     animation: float 6s ease-in-out infinite;
// }

// /* FEATURE CARDS */
// .feature-card {
//     background: rgba(255,255,255,0.9);
//     border-radius: 18px;
//     padding: 1.8rem;
//     text-align: center;
//     transition: all 0.25s ease;
//     backdrop-filter: blur(8px);
// }

// .feature-card:hover {
//     transform: translateY(-8px);
//     box-shadow: 0 15px 35px rgba(0,0,0,0.08);
// }

// .feature-icon {
//     width: 70px;
//     height: 70px;
//     background: linear-gradient(135deg, #e0e7ff, #ede9fe);
//     border-radius: 16px;
//     display: flex;
//     align-items: center;
//     justify-content: center;
//     margin: 0 auto 1rem;
//     font-size: 1.6rem;
//     transition: 0.3s;
// }

// .feature-card:hover .feature-icon {
//     transform: scale(1.08);
// }

// /* PRODUCT CARD */
// .product-card-modern {
//     background: #fff;
//     border-radius: 18px;
//     overflow: hidden;
//     transition: all 0.3s ease;
// }

// .product-card-modern:hover {
//     transform: translateY(-8px);
//     box-shadow: 0 20px 45px rgba(0,0,0,0.12);
// }

// .product-image-wrapper {
//     overflow: hidden;
//     background: #f9fafb;
// }

// .product-image-wrapper img {
//     width: 100%;
//     height: 240px;
//     object-fit: cover;
//     transition: transform 0.5s ease;
// }

// .product-card-modern:hover img {
//     transform: scale(1.08);
// }

// /* overlay without position relative */
// .product-overlay {
//     display: none;
// }

// .product-card-modern:hover .product-overlay {
//     display: flex;
//     justify-content: center;
//     align-items: center;
//     margin-top: -60px;
// }

// /* price */
// .price-tag {
//     background: linear-gradient(135deg, #2E3192, #58bcff);
//     color: #fff;
//     padding: 5px 12px;
//     border-radius: 20px;
//     font-weight: 600;
// }

// /* CTA */
// .cta-section {
//     background: linear-gradient(135deg, #4f46e5, #7c3aed);
// }

// /* mobile */
// @media (max-width: 768px) {
//     .product-image-wrapper img {
//         height: 180px;
//     }
// }

// </style>

// <!-- HERO -->
// <section class="hero-section text-white py-5">
//     <div class="container py-4">
//         <div class="row align-items-center">
//             <div class="col-lg-6 text-center text-lg-start">
//                 <span class="badge bg-white text-dark px-3 py-2 mb-3 rounded-pill">Welcome to CruxStore</span>
//                 <h1 class="display-4 fw-bold mb-3">
//                     Shop Smarter,<br>
//                     <span class="text-warning">Live Better</span>
//                 </h1>
//                 <p class="lead mb-4 opacity-75">
//                     Discover amazing products with premium quality and fast delivery
//                 </p>
//                 <div class="d-flex gap-2">
//                 <a href="register.php" class="btn btn-light  btn-lg px-2 rounded-pill d-flex gap-2 align-items-center justify-content-center">
//                     Get Started →
//                 </a>
//                 </div>
//             </div>

//             <div class="col-lg-6 text-center mt-4 mt-lg-0">
//                 <img src="assets/images/landing/landing1.avif"
//                      class="img-fluid floating-image"
//                      style="max-height: 380px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
//             </div>
//         </div>
//     </div>
// </section>

// <!-- FEATURES -->
// <section class="py-5 bg-light">
//     <div class="container">
//         <div class="row g-4">

//             <div class="col-6 col-md-3">
//                 <div class="feature-card">
//                     <div class="feature-icon">💨</div>
//                     <h6 class="fw-bold">Lightning Fast</h6>
//                     <p class="small text-muted mb-0">Same day delivery</p>
//                 </div>
//             </div>

//             <div class="col-6 col-md-3">
//                 <div class="feature-card">
//                     <div class="feature-icon">✅</div>
//                     <h6 class="fw-bold">Secure Payments</h6>
//                     <p class="small text-muted mb-0">100% protected</p>
//                 </div>
//             </div>

//             <div class="col-6 col-md-3">
//                 <div class="feature-card">
//                     <div class="feature-icon">💎</div>
//                     <h6 class="fw-bold">Premium Quality</h6>
//                     <p class="small text-muted mb-0">Trusted brands</p>
//                 </div>
//             </div>

//             <div class="col-6 col-md-3">
//                 <div class="feature-card">
//                     <div class="feature-icon">🔄</div>
//                     <h6 class="fw-bold">Easy Returns</h6>
//                     <p class="small text-muted mb-0">30-day guarantee</p>
//                 </div>
//             </div>

//         </div>
//     </div>
// </section>

// <!-- FEATURED PRODUCTS -->
// <section class="py-5">
//     <div class="container">
//         <div class="text-center mb-5">
//             <h2 class="fw-bold">🔥 Featured Products</h2>
//             <p class="text-muted">Hand-picked just for you</p>
//         </div>

//         <div class="row g-4">
//             ' . (count($featured_products) > 0 ? array_reduce($featured_products, function($carry, $product) {
//                 return $carry . '
//                 <div class="col-12 col-sm-6 col-lg-4">
//                     <div class="product-card-modern h-100">

//                         <div class="product-image-wrapper">
//                             <img src="' . htmlspecialchars($product['image_url']) . '">
//                         </div>

//                         <div class="p-3">
//                             <h6 class="fw-bold">' . htmlspecialchars($product['name']) . '</h6>
//                             <p class="small text-muted">' . substr(htmlspecialchars($product['description']), 0, 60) . '...</p>

//                             <div class="d-flex justify-content-between align-items-center">
//                                 <span class="price-tag">KSh ' . number_format($product['price'], 2) . '</span>

//                                 <a href="index_product_details.php?id=' . $product['product_id'] . '" 
//                                    class="btn btn-sm btn-outline-dark rounded-pill">
//                                     View →
//                                 </a>
//                             </div>
//                         </div>

//                     </div>
//                 </div>
//                 ';
//             }, '') : '<p class="text-center text-muted">No products available</p>') . '
//         </div>
//     </div>
// </section>

// <!-- WHY US Section -->
// <section class="py-5">
//     <div class="container">
//         <h2 class="text-center display-6 fw-bold mb-5">Why Choose CruxStore?</h2>
//         <div class="row g-4">
//             <div class="col-md-4">
//                 <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
//                     <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
//                         <p>✨</p>
//                     </div>
//                     <h5 class="fw-bold">Smart Shopping</h5>
//                     <p class="small text-muted">AI-powered recommendations just for you</p>
//                 </div>
//             </div>
//             <div class="col-md-4">
//                 <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
//                     <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
//                         <p>🎯</>
//                     </div>
//                     <h5 class="fw-bold">Curated Products</h5>
//                     <p class="small text-muted">Only the best quality items</p>
//                 </div>
//             </div>
//             <div class="col-md-4">
//                 <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
//                     <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
//                         <p>🔢</p>
//                     </div>
//                     <h5 class="fw-bold">Real-time Stock</h5>
//                     <p class="small text-muted">Always accurate inventory</p>
//                 </div>
//             </div>
//         </div>
//     </div>
// </section>

// <!-- CTA -->
// <section class="cta-section text-white py-5">
//     <div class="container text-center">
//         <h2 class="fw-bold mb-3">Ready to Start Shopping?</h2>
//         <p class="mb-4 opacity-75">Join thousands of happy customers today</p>
//         <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5">
//             Create Account →
//         </a>
//     </div>
// </section>
// ';

$content = '
<style>

/* Smooth float animation */
@keyframes float {
  0% { transform: translateY(0) rotate(10deg); }
  50% { transform: translateY(-15px) rotate(-10deg); }
  100% { transform: translateY(0) rotate(10deg); }
}

/* HERO */
.hero-section {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    overflow: hidden;
}

/* floating image */
.floating-image {
    animation: float 6s ease-in-out infinite;
}

/* FEATURE CARDS */
.feature-card {
    background: rgba(255,255,255,0.7);
    border-radius: 20px;
    padding: 1.8rem;
    text-align: center;
    transition: all 0.25s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 45px rgba(0,0,0,0.08);
}

.feature-icon {
    width: 70px;
    height: 70px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.6rem;
    color: white;
    transition: 0.3s;
}

.feature-card:hover .feature-icon {
    transform: scale(1.08) rotate(5deg);
}

/* PRODUCT CARD */
.product-card-modern {
    background: rgba(255,255,255,0.75);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
}

.product-card-modern:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 55px rgba(0,0,0,0.12);
}

.product-image-wrapper {
    overflow: hidden;
    background: #f9fafb;
}

.product-image-wrapper img {
    width: 100%;
    height: 240px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card-modern:hover img {
    transform: scale(1.08);
}

/* overlay without position relative */
.product-overlay {
    display: none;
}

.product-card-modern:hover .product-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: -60px;
}

/* price */
.price-tag {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    padding: 6px 14px;
    border-radius: 999px;
    font-weight: 600;
}

/* CTA */
.cta-section {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
}

/* Buttons modern style */
.btn-light {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-light:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* mobile */
@media (max-width: 768px) {
    .product-image-wrapper img {
        height: 180px;
    }
}

</style>

<!-- HERO -->
<section class="hero-section text-white py-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center text-lg-start">
                <span class="badge bg-white text-dark px-3 py-2 mb-3 rounded-pill">Welcome to CruxStore</span>
                <h1 class="display-4 fw-bold mb-3">
                    Shop Smarter,<br>
                    <span class="text-warning">Live Better</span>
                </h1>
                <p class="lead mb-4 opacity-75">
                    Discover amazing products with premium quality and fast delivery
                </p>
                <div class="d-flex gap-2">
                <a href="register.php" class="btn btn-light  btn-lg px-2 rounded-pill d-flex gap-2 align-items-center justify-content-center">
                    Get Started →
                </a>
                </div>
            </div>

            <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <img src="assets/images/landing/landing1.avif"
                     class="img-fluid floating-image"
                     style="max-height: 380px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">

            <div class="col-6 col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">💨</div>
                    <h6 class="fw-bold">Lightning Fast</h6>
                    <p class="small text-muted mb-0">Same day delivery</p>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h6 class="fw-bold">Secure Payments</h6>
                    <p class="small text-muted mb-0">100% protected</p>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">💎</div>
                    <h6 class="fw-bold">Premium Quality</h6>
                    <p class="small text-muted mb-0">Trusted brands</p>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h6 class="fw-bold">Easy Returns</h6>
                    <p class="small text-muted mb-0">30-day guarantee</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">🔥 Featured Products</h2>
            <p class="text-muted">Hand-picked just for you</p>
        </div>

        <div class="row g-4">
            ' . (count($featured_products) > 0 ? array_reduce($featured_products, function($carry, $product) {
                return $carry . '
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="product-card-modern h-100">

                        <div class="product-image-wrapper">
                            <img src="' . htmlspecialchars($product['image_url']) . '">
                        </div>

                        <div class="p-3">
                            <h6 class="fw-bold">' . htmlspecialchars($product['name']) . '</h6>
                            <p class="small text-muted">' . substr(htmlspecialchars($product['description']), 0, 60) . '...</p>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price-tag">KSh ' . number_format($product['price'], 2) . '</span>

                                <a href="index_product_details.php?id=' . $product['product_id'] . '" 
                                   class="btn btn-sm btn-outline-dark rounded-pill">
                                    View →
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
                ';
            }, '') : '<p class="text-center text-muted">No products available</p>') . '
        </div>
    </div>
</section>

<!-- WHY US Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center display-6 fw-bold mb-5">Why Choose CruxStore?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <p>✨</p>
                    </div>
                    <h5 class="fw-bold">Smart Shopping</h5>
                    <p class="small text-muted">AI-powered recommendations just for you</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <p>🎯</>
                    </div>
                    <h5 class="fw-bold">Curated Products</h5>
                    <p class="small text-muted">Only the best quality items</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4 rounded-4 shadow-sm bg-white h-100">
                    <div class="feature-icon mx-auto mb-3" style="width: 60px; height: 60px;">
                        <p>🔢</p>
                    </div>
                    <h5 class="fw-bold">Real-time Stock</h5>
                    <p class="small text-muted">Always accurate inventory</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section text-white py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Ready to Start Shopping?</h2>
        <p class="mb-4 opacity-75">Join thousands of happy customers today</p>
        <a href="register.php" class="btn btn-light btn-lg rounded-pill px-5">
            Create Account →
        </a>
    </div>
</section>
';
include 'includes/main_template.php';
?>
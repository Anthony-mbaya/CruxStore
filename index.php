<?php
require_once 'includes/db.php';

$pageTitle = "Home";
$content = '

<!-- HERO SECTION -->
<section class="container py-5">
    <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start">
            <h1 class="display-4 fw-bold mb-3">Welcome to <span class="text-primary">CruxStore</span></h1>
            <p class="lead mb-4">
                Your one-stop shop for premium electronics and durable everyday products.
                From wireless earbuds to smart gadgets — quality meets affordability.
            </p>

            <div class="d-flex gap-3 justify-content-center justify-content-md-start">
                <a href="register.php" class="btn btn-primary btn-lg px-4">Get Started</a>
                <a href="login.php" class="btn btn-outline-success btn-lg px-4">Sign In</a>
            </div>
        </div>

        <div class="col-md-6 text-center mt-4 mt-md-0">
            <img src="assets/images/landing/landing1.avif" alt="CruxStore Products" class="img-fluid rounded shadow">
        </div>
    </div>
</section>

<!-- FEATURE HIGHLIGHT STRIP -->
<section class="bg-light py-4">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-3">
                <h5>⚡ Fast Delivery</h5>
                <p class="small">Quick and reliable shipping</p>
            </div>
            <div class="col-md-3">
                <h5>🔒 Secure Payments</h5>
                <p class="small">M-Pesa & PayPal supported</p>
            </div>
            <div class="col-md-3">
                <h5>💯 Quality Products</h5>
                <p class="small">Tested and trusted items</p>
            </div>
            <div class="col-md-3">
                <h5>📦 Easy Returns</h5>
                <p class="small">Hassle-free return policy</p>
            </div>
        </div>
    </div>
</section>

<!-- PRODUCTS SECTION -->
<section class="container py-5">
    <h2 class="text-center mb-4">Popular Categories</h2>
    <div class="row g-4">

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/earpods.webp" class="card-img-top" alt="Earpods">
                <div class="card-body text-center">
                    <h5 class="card-title">Wireless Earpods</h5>
                    <p class="card-text">High-quality sound with noise cancellation and long battery life.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/smartwatches.avif" class="card-img-top" alt="Smart Watch">
                <div class="card-body text-center">
                    <h5 class="card-title">Smart Watches</h5>
                    <p class="card-text">Track fitness, notifications, and health in style.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <img src="assets/images/landing/accessories.avif" class="card-img-top" alt="Accessories">
                <div class="card-body text-center">
                    <h5 class="card-title">Accessories</h5>
                    <p class="card-text">Chargers, cables, power banks, and more essentials.</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- NON-PERISHABLE SUGGESTIONS -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">More You Can Shop</h2>
        <div class="row text-center">

            <div class="col-md-3">
                <h6>🔌 Power Banks</h6>
            </div>
            <div class="col-md-3">
                <h6>🎧 Headphones</h6>
            </div>
            <div class="col-md-3">
                <h6>💡 LED Lighting</h6>
            </div>
            <div class="col-md-3">
                <h6>⌨️ Computer Accessories</h6>
            </div>
            <div class="col-md-3">
                <h6>📱 Phone Cases</h6>
            </div>
            <div class="col-md-3">
                <h6>🔋 Batteries</h6>
            </div>
            <div class="col-md-3">
                <h6>📷 Camera Accessories</h6>
            </div>
            <div class="col-md-3">
                <h6>🎮 Gaming Accessories</h6>
            </div>

        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="container py-5">
    <h2 class="text-center mb-4">Why Choose CruxStore</h2>
    <div class="row g-4">

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Smart Shopping Experience</h5>
                    <p class="card-text">Browse, compare, and purchase products easily from anywhere.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Curated Product Selection</h5>
                    <p class="card-text">We reduce decision fatigue by offering only high-quality items.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Efficient Inventory System</h5>
                    <p class="card-text">Real-time stock updates ensure product availability accuracy.</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- CTA SECTION -->
<section class="bg-primary text-white text-center py-5">
    <div class="container">
        <h2>Ready to Start Shopping?</h2>
        <p class="mb-4">Join CruxStore today and experience seamless online shopping.</p>
        <a href="register.php" class="btn btn-light btn-lg">Create Account</a>
    </div>
</section>

';

include 'includes/main_template.php';
?>
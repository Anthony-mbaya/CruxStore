<?php
require_once 'includes/db.php';
#require_once 'includes/main_template.php';

$pageTitle = "Home";
$content = '
<section class="hero-section mb-5">
    <div class="row align-items-center">
        <div class="col-md-6 px-3 text-center">
            <a href="login.php" class="btn btn-success btn-lg">Sign In!</a>
            <h1 class="display-4">Purchase Your Products</h1>
            <p class="lead">Discover premium products tailored to your style and budget.</p>
            <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
        </div>
        <div class="col-md-6">
            <img src="assets/images/landing1.jpg" alt="Luxury page" class="img-fluid rounded">
        </div>
    </div>
</section>

<section class="features-section">
    <h2 class="text-center mb-4">Why Choose CruxStore</h2>
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Virtual Product Selection</h5>
                    <p class="card-text">Browse our extensive catalog online and visualize products in your space.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Expert Consultation</h5>
                    <p class="card-text">Our products experts will guide you to create your perfect space.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Secure Payments</h5>
                    <p class="card-text">Safe and convenient payment options including M-Pesa and PayPal.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Reducing Wait Times and Queuing</h5>
                    <p class="card-text">By enabling customers to browse products options and make payments online, the system will reduce the need for physical visits.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Minimizing Decision Fatigue</h5>
                    <p class="card-text">The system will help reduce customer confusion by offering a curated set of products types online.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Efficient Customer Data Retrieval</h5>
                    <p class="card-text">When new products are received, they will be automatically recorded into the inventory, making stock-taking more efficient.</p>
                </div>
            </div>
        </div>
    </div>
</section>
';

include 'includes/main_template.php';
?>
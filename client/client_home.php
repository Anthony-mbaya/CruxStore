<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Only allow logged-in customers
if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Our Products";
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build product query with optional filters
$query = "SELECT * FROM products WHERE status = 'active'";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get distinct categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'active'")->fetchAll();
/*
$content = '
<div class="px-4 py-3 py-md-4">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <h2 class="fw-bold mb-0">Browse Our Collection</h2>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" 
                           name="search" 
                           class="form-control rounded-3" 
                           placeholder="Search products..."
                           value="'.htmlspecialchars($search).'">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select rounded-3">
                        <option value="">All Categories</option>
                        '.array_reduce($categories, function($carry, $cat) use ($category) {
                            $selected = $category === $cat['category'] ? ' selected' : '';
                            return $carry.'<option value="'.htmlspecialchars($cat['category']).'"'.$selected.'>'.htmlspecialchars($cat['category']).'</option>';
                        }, '').'
                    </select>
                </div>

                <div class="col-12 col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary rounded-3">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRODUCT GRID -->
    <div class="row g-4">

        '.array_reduce($products, function($carry, $product) {
            $isInStock = $product['stock_quantity'] > 0;
            return $carry.'

            <!--<div class="col-12 col-sm-6 col-lg-2">-->
            <div class="col-6 col-sm-3 col-lg-2">

                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden product-card">

                    <!-- IMAGE -->
                    <div class="position-relative">
                        <img src="../'.htmlspecialchars($product['image_url']).'" 
                             class="w-100"
                             style="height: 250px; object-fit: cover;">

                        <span class="badge position-absolute top-0 end-0 m-2 '.($isInStock ? 'bg-success' : 'bg-danger').'">
                            '.($isInStock ? 'In Stock' : 'Out of Stock').'
                        </span>
                    </div>

                    <!-- BODY -->
                    <div class="card-body d-flex flex-column p-3">
                        <h6 class="fw-bold mb-1">'.htmlspecialchars($product['name']).'</h6>
                        <small class="text-muted mb-2">'.htmlspecialchars($product['category']).'</small>
                        <p class="text-muted small mb-3 d-none d-md-block" >
                        '.substr(htmlspecialchars($product['description']), 0, 60).'...
                        </p>

                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary fs-5">
                                KSh '.number_format($product['price'], 2).'
                            </span>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="card-footer bg-white border-0 p-3 pt-0">
                        <a href="product_details.php?id='.$product['product_id'].'" 
                           class="btn btn-outline-primary w-100 rounded-3">
                            View Details
                        </a>
                    </div>
                </div>
            </div>

            ';
        }, '').'

    </div>

</div> '; 
*/
$content = '
<style>

/* Page feel */
body {
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
}

/* Header */
h2 {
    letter-spacing: -0.4px;
}

/* Search Card (glass style like login/register) */
.card {
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.7);
    border-radius: 16px !important;
}

/* Inputs */
.form-control,
.form-select {
    border-radius: 12px !important;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    background: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

/* Button */
.btn-primary {
    border-radius: 12px !important;
    font-weight: 600;
    font-size: 16px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    border: none;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px rgba(99,102,241,0.25);
}

/* Product Card */
.product-card {
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(10px);
    border-radius: 18px !important;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-6px) scale(1.01);
    box-shadow: 0 20px 45px rgba(0,0,0,0.1);
}

/* Image polish */
.product-card img {
    transition: transform 0.4s ease;
}

.product-card:hover img {
    transform: scale(1.05);
}

/* Price highlight */
.text-primary {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Footer button */
.btn-outline-primary {
    border-radius: 12px !important;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline-primary:hover {
    background: #6366f1;
    border-color: #6366f1;
    color: #fff;
}

/* Badge polish */
.badge {
    font-size: 0.7rem;
    padding: 6px 10px;
    border-radius: 999px;
    backdrop-filter: blur(6px);
}

/* Card footer spacing fix */
.card-footer {
    background: transparent !important;
}

/* Smooth spacing rhythm */
.card-body h6 {
    font-size: 0.95rem;
}

/* Mobile tightening */
@media (max-width: 768px) {
    .product-card img {
        height: 200px !important;
    }
}

</style>

<div class="px-4 py-3 py-md-4">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <h2 class="fw-bold mb-0">Browse Our Collection</h2>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" 
                           name="search" 
                           class="form-control rounded-3" 
                           placeholder="Search products..."
                           value="'.htmlspecialchars($search).'">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select rounded-3">
                        <option value="">All Categories</option>
                        '.array_reduce($categories, function($carry, $cat) use ($category) {
                            $selected = $category === $cat['category'] ? ' selected' : '';
                            return $carry.'<option value="'.htmlspecialchars($cat['category']).'"'.$selected.'>'.htmlspecialchars($cat['category']).'</option>';
                        }, '').'
                    </select>
                </div>

                <div class="col-12 col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary rounded-3 py-2 border border-2">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRODUCT GRID -->
    <div class="row g-4">

        '.array_reduce($products, function($carry, $product) {
            $isInStock = $product['stock_quantity'] > 0;
            return $carry.'

            <!--<div class="col-12 col-sm-6 col-lg-2">-->
            <div class="col-6 col-sm-3 col-lg-2">

                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden product-card">

                    <!-- IMAGE -->
                    <div class="position-relative">
                        <img src="../'.htmlspecialchars($product['image_url']).'" 
                             class="w-100"
                             style="height: 250px; object-fit: cover;">

                        <span class="badge position-absolute top-0 end-0 m-2 '.($isInStock ? 'bg-success' : 'bg-danger').'">
                            '.($isInStock ? 'In Stock' : 'Out of Stock').'
                        </span>
                    </div>

                    <!-- BODY -->
                    <div class="card-body d-flex flex-column p-3">
                        <h6 class="fw-bold mb-1">'.htmlspecialchars($product['name']).'</h6>
                        <small class="text-muted mb-2">'.htmlspecialchars($product['category']).'</small>
                        <p class="text-muted small mb-3 d-none d-md-block" >
                        '.substr(htmlspecialchars($product['description']), 0, 60).'...
                        </p>

                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary fs-5">
                                KSh '.number_format($product['price'], 2).'
                            </span>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="card-footer bg-white border-0 p-3 pt-0">
                        <a href="product_details.php?id='.$product['product_id'].'" 
                           class="btn btn-outline-primary w-100 rounded-3">
                            View Details
                        </a>
                    </div>
                </div>
            </div>

            ';
        }, '').'

    </div>

</div> ';
include '../includes/main_template.php';
?>
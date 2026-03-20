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
$content = '
<div class="container py-3 py-md-4">

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
            <div class="col-12 col-sm-4 col-lg-3">

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

</div>
';
/*
$content = '
<div class="container">
    <h2 class="my-4">Browse Our Collection</h2>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="'.htmlspecialchars($search).'">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        '.array_reduce($categories, function($carry, $cat) use ($category) {
                            $selected = $category === $cat['category'] ? ' selected' : '';
                            return $carry.'<option value="'.htmlspecialchars($cat['category']).'"'.$selected.'>'.htmlspecialchars($cat['category']).'</option>';
                        }, '').'
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        '.array_reduce($products, function($carry, $product) {
            return $carry.'
            <div class="col">
                <div class="card h-100">
                    <img src="../'.htmlspecialchars($product['image_url']).'" class="card-img-top" alt="'.htmlspecialchars($product['name']).'" style="height: 350px; object-fit: cover; ">
                    <div class="card-body">
                        <h5 class="card-title">'.htmlspecialchars($product['name']).'</h5>
                        <p class="card-text text-muted">'.htmlspecialchars($product['category']).'</p>
                        <p class="card-text">'.substr(htmlspecialchars($product['description']), 0, 100).'...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5">KSh '.number_format($product['price'], 2).'</span>
                            <span class="badge '.($product['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger').'">
                                '.($product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock').'
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="product_details.php?id='.$product['product_id'].'" class="btn btn-outline-primary w-100">View Details</a>
                    </div>
                </div>
            </div>';
        }, '').'
    </div>
</div>';
*/


include '../includes/main_template.php';
?>
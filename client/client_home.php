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



include '../includes/main_template.php';
?>
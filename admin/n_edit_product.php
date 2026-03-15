<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Edit Product";
$error = '';
$success = '';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['msg_type'] = "danger";
    header("Location: products.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name']); 
    $description = trim($_POST['description']);
    $category = trim($_POST['category']); 
    $price = trim($_POST['price']);
    $stock_quantity = trim($_POST['stock_quantity']); 
    
    // Handle image upload if new image is provided
    $image_url = $product['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            // Delete old image if it exists
            if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                unlink('../' . $product['image_url']);
            }
            $image_url = 'assets/images/products/' . $file_name;
        }
    }
    
    $stmt = $pdo->prepare("UPDATE products SET 
        name = ?, description = ?, category = ?, 
        price = ?, stock_quantity = ?, image_url = ?
        WHERE product_id = ?");
    
    if ($stmt->execute([$name, $description, $category, $price, $stock_quantity, $image_url, $product_id])) {
        $_SESSION['message'] = "Product updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: products.php");
        exit();
    } else {
        $error = "Failed to update product. Please try again.";
    }
}

$content = '
<div class="container">
    <h2 class="my-4">Edit Product</h2>
    
    <div class="card">
        <div class="card-header">
            <h5>Edit Product Details</h5>
        </div>
        <div class="card-body">
            ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="' . htmlspecialchars($product['name']) . '" required>
                    </div> 
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required>' . htmlspecialchars($product['description']) . '</textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" value="' . htmlspecialchars($product['category']) . '" required>
                    </div> 
                    <div class="col-md-4">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="' . htmlspecialchars($product['price']) . '" required>
                    </div>
                    <div class="col-md-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="stock_quantity" value="' . htmlspecialchars($product['stock_quantity']) . '" required>
                    </div> 
                    <div class="col-md-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        ' . (!empty($product['image_url']) ? '<div class="mt-2"><img src="../' . htmlspecialchars($product['image_url']) . '" style="max-height: 100px;"></div>' : '') . '
                    </div>
                    <div class="col-12">
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>';

include '../includes/main_template.php';
?>
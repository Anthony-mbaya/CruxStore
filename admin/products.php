<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Only allow admins
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Product Management";
$error = '';

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $stock_quantity = trim($_POST['stock_quantity']);
    $price = trim($_POST['price']);
    //image handled below
    //$image_url = trim($_POST['image_url']);

    //shouuld created image in assests/oimages/products
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_url = 'assets/images/products/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products
        (name, description, category, stock_quantity, price, image_url)
        VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$name, $description, $category, $stock_quantity, $price, $image_url])) {
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: products.php");
        exit();
    } else {
        $error = "Failed to add product. Please try again.";
    }
}

// Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Product Management</h2>

    <!-- Products List -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Product Catalog</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . array_reduce($products, function($carry, $product) {
                            return $carry . '
                            <tr>
                                <td><img src="../' . htmlspecialchars($product['image_url']) . '" alt="Product Image" style="width:50px;"></td>
                                <td>' . htmlspecialchars($product['name']) . '</td>
                                <td>' . htmlspecialchars($product['category']) . '</td>
                                <td>' . number_format($product['price'], 2) . '</td>
                                <td>' . htmlspecialchars($product['stock_quantity']) . '</td>
                                <td>
                                    <a href="n_edit_product.php?id=' . $product['product_id'] . '" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="n_delete_product.php?id=' . $product['product_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>
                                </td>
                            </tr>
                            ';
                        }, '') . '
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Product Form -->
    <div class="card ">
        <div class="card-header">
            <h5>Add New Product</h5>
        </div>
        <div class="card-body">
            ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" required>
                    </div>
                    <div class="col-md-4">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="col-md-3">
                        <label for="stock_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                    </div>
                    <div class="col-md-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


</div>
';

include '../includes/main_template.php';
?>
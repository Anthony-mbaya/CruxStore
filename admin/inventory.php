<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Inventory Management";
$error = '';

// Handle inventory update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, status = ? WHERE product_id = ?");
    if ($stmt->execute([$quantity, $status, $product_id])) {
        $_SESSION['message'] = "Inventory updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: inventory.php");
        exit();
    } else {
        $error = "Failed to update inventory.";
    }
}

// Fetch all products with inventory data
$products = $pdo->query("
    SELECT product_id, name, stock_quantity, status
    FROM products
    ORDER BY stock_quantity ASC
")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Inventory Management</h2>

    <!-- Inventory List -->
    <div class="card">
        <div class="card-header">
            <h5>Current Inventory</h5>
        </div>
        <div class="card-body">
            ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . array_reduce($products, function($carry, $product) {
                            $statusClass = '';
                            switch ($product['status']) {
                                case 'active': $statusClass = 'success'; break;
                                case 'inactive': $statusClass = 'secondary'; break;
                                case 'out_of_stock': $statusClass = 'danger'; break;
                            }

                            $stockClass = $product['stock_quantity'] > 10 ? 'success' :
                                         ($product['stock_quantity'] > 0 ? 'warning' : 'danger');

                            return $carry . '
                            <tr>
                                <td>' . htmlspecialchars($product['name']) . '</td>
                                <td><span class="badge bg-' . $stockClass . '">' . $product['stock_quantity'] . '</span></td>
                                <td><span class="badge bg-' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $product['status'])) . '</span></td>
                                <td>
                                    <form method="POST" class="row g-2">
                                        <input type="hidden" name="product_id" value="' . $product['product_id'] . '">
                                        <div class="col">
                                            <input type="number" name="quantity" value="' . $product['stock_quantity'] . '" class="form-control form-control-sm">
                                        </div>
                                        <div class="col">
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="active"' . ($product['status'] === 'active' ? ' selected' : '') . '>Active</option>
                                                <option value="inactive"' . ($product['status'] === 'inactive' ? ' selected' : '') . '>Inactive</option>
                                                <option value="out_of_stock"' . ($product['status'] === 'out_of_stock' ? ' selected' : '') . '>Out of Stock</option>
                                            </select>
                                        </div>
                                        <div class="col">
                                            <button type="submit" name="update_inventory" class="btn btn-sm btn-primary">Update</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            ';
                        }, '') . '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
';

include '../includes/main_template.php';
?>
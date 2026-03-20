<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Your Shopping Cart";

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_cart'])) {

        foreach ($_POST['quantities'] as $product_id => $quantity) {

            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);

                // DELETE from DB
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);

            } else {
                $_SESSION['cart'][$product_id] = $quantity;

                // INSERT or UPDATE in DB
                $stmt = $pdo->prepare("
                    INSERT INTO cart (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
                ");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            }
        }

        $_SESSION['message'] = "Cart updated!";
        $_SESSION['msg_type'] = "success";

    } elseif (isset($_POST['checkout'])) {
        header("Location: checkout.php");
        exit();
    }
}

// Get cart items with product details
$cartItems = [];
$total = 0;
if (empty($_SESSION['cart'])) {
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    foreach ($stmt->fetchAll() as $item) {
        $_SESSION['cart'][$item['product_id']] = $item['quantity'];
    }
}
if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id IN ($placeholders)");
    //$stmt->execute([$_SESSION['user_id']]);
    $stmt->execute(array_keys($_SESSION['cart']));
    $products = $stmt->fetchAll();


    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['product_id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;

        $cartItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

$content = '
<div class="container py-5">
    <h2 class="mb-4 ">Your Shopping Cart</h2>

    '.((empty($cartItems)) ? '
    <div class="alert alert-info">
        Your cart is empty. <a href="client_home.php" class="alert-link">Browse products</a> to get started.
    </div>' : '
    <form method="POST">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    '.array_reduce($cartItems, function($carry, $item) {
                        return $carry.'
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../'.htmlspecialchars($item['product']['image_url']).'" alt="Product image" width="60" class="me-3">
                                    <div>
                                        <h6 class="mb-0">'.htmlspecialchars($item['product']['name']).'</h6>
                                        <small class="text-muted">'.htmlspecialchars($item['product']['category']).'</small>
                                    </div>
                                </div>
                            </td>
                            <td>KSh '.number_format($item['product']['price'], 2).'</td>
                            <td>
                                <input type="number" name="quantities['.$item['product']['product_id'].']"
                                       min="1" max="10" value="'.$item['quantity'].'" class="form-control" style="width: 70px;">
                            </td>
                            <td>KSh '.number_format($item['subtotal'], 2).'</td>
                            <td>
                                <a href="remove_from_cart.php?id='.$item['product']['product_id'].'" class="btn btn-sm btn-danger">Remove</a>
                            </td>
                        </tr>';
                    }, '').'
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td colspan="2"><strong>KSh '.number_format($total, 2).'</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

         <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mt-4">
    
    <a href="client_home.php" class="btn btn-outline-secondary w-100 w-md-auto">
        Continue Shopping
    </a>

    <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
        <button type="submit" name="update_cart" class="btn btn-outline-primary w-100 w-md-auto">
            Update Cart
        </button>
        <button type="submit" name="checkout" class="btn btn-primary w-100 w-md-auto">
            Proceed to Checkout
        </button>
    </div>

</div>
    </form>').'
</div>';

include '../includes/main_template.php';

<?php
require_once 'db.php';
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CruxStore - <?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container p-3">
    <div>
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark py-3 rounded-3">
        <div class="container">
            <a class="navbar-brand fw-bolder">CruxStore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isCustomer()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="client_home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="#">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="#">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link px-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger text-white px-3" href="../logout.php">Logout</a>
                    </li>

                    <?php elseif (isDeliverer()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliverer_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliverer_assignments.php">My Deliveries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliverer_location.php">Update Location</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliverer_profile.php">My Profile</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link px-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger text-white px-3" href="../logout.php">Logout</a>
                    </li>

                    <?php elseif (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="admin_home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="orders_page.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="inventory.php">Inventory</a>
                    </li> 
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliveries.php">Deliveries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="deliverers.php">Deliverers</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link px-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger text-white px-3" href="../logout.php">Logout</a>
                    </li>

                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?>">
                <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Main content will be inserted here -->
        <?php echo $content; ?>
    </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-4 rounded-3">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> CruxStore. All rights reserved.</p>
            <p>&copy; Dev_Tonny: +254797725284 </p>
        </div>
    </footer>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
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

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- NAVBAR OUTSIDE CONTAINER -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-3 px-lg-4">

        <a class="navbar-brand fw-bold fs-4 text-primary">CruxStore</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">

                <?php if (isCustomer()): ?>
                    <li class="nav-item"><a class="nav-link" href="client_home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer_profile.php">Profile</a></li>
                    <li class="nav-item">
                        <span class="nav-link fw-semibold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm px-3" href="../logout.php">Logout</a>
                    </li>

                <?php elseif (isDeliverer()): ?>
                    <li class="nav-item"><a class="nav-link" href="deliverer_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="deliverer_assignments.php">Deliveries</a></li>
                    <li class="nav-item"><a class="nav-link" href="deliverer_location.php">Location</a></li>
                    <li class="nav-item"><a class="nav-link" href="deliverer_profile.php">Profile</a></li>
                    <li class="nav-item">
                        <span class="nav-link fw-semibold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm px-3" href="../logout.php">Logout</a>
                    </li>

                <?php elseif (isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_home.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders_page.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="deliveries.php">Deliveries</a></li>
                    <li class="nav-item"><a class="nav-link" href="deliverers.php">Deliverers</a></li>
                    <li class="nav-item">
                        <span class="nav-link fw-semibold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm px-3" href="../logout.php">Logout</a>
                    </li>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="register.php">Register</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container" style="margin-top: 90px;">
    <div class="bg-white p-3 p-md-4 rounded-4 shadow-sm">

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> rounded-3">
                <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php echo $content; ?>

    </div>

    <!-- FOOTER -->
    <footer class="bg-light text-dark text-center py-4 rounded-3 shadow-sm mt-5 small">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> CruxStore. All rights reserved.</p>
        <p class="mb-0">Dev_Tonny: +254797725284</p>
    </footer>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>

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

<div class="container py-3">

    <!-- MODERN NAVBAR -->
    <!--<nav class="navbar navbar-expand-lg navbar-dark bg-light rounded-2 shadow mb-4">-->
        <nav class="navbar navbar-expand-lg navbar-dark bg-light rounded-2 shadow mb-4 fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-4 text-primary">CruxStore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">

                    <?php if (isCustomer()): ?>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="client_home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="orders.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="cart.php">Cart</a></li>
                        <li class="nav-item"><span class="nav-link px-3 text-dark fw-bold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="customer_profile.php">Profile</a></li>
                        <li class="nav-item"><a class="btn btn-danger px-3 py-2" href="../logout.php">Logout</a></li>

                    <?php elseif (isDeliverer()): ?>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="deliverer_dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="deliverer_assignments.php">Deliveries</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="deliverer_location.php">Location</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="deliverer_profile.php">Profile</a></li>
                        <li class="nav-item"><span class="nav-link px-3 text-dark fw-bold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                        <li class="nav-item"><a class="btn btn-danger px-3 py-2" href="../logout.php">Logout</a></li>

                    <?php elseif (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="admin_home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="products.php">Products</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="orders_page.php">Orders</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="inventory.php">Inventory</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="deliveries.php">Deliveries</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark text-dark" href="deliverers.php">Deliverers</a></li>
                        <li class="nav-item"><span class="nav-link px-3 text-dark fw-bold">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                        <li class="nav-item"><a class="btn btn-danger px-3 py-2" href="../logout.php">Logout</a></li>

                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link px-3 text-dark" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-primary px-3 py-2" href="register.php">Register</a></li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENTnbvj -->
    <div class="bg-white p-4 rounded-4 shadow-sm mt-5">

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
    <footer class="bg-light fst-italic text-dark text-center py-4 rounded-2 shadow mt-5">
        <p class="mb-1">&copy; <?php echo date('Y'); ?> CruxStore. All rights reserved.</p>
        <p class="mb-0">Dev_Tonny: +254797725284</p>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
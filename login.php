<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$pageTitle = "Login";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'customer') {
            header("Location: client/client_home.php");
        } elseif ($user['role'] === 'staff') {
            header("Location: admin/admin_home.php");
        } elseif($user['role'] === 'deliverer'){
            header("Location: deliverer/deliverer_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
/*
$content = '
<div class="row justify-content-center py-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Login</h4>
            </div>
            <div class="card-body">
                ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your Username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Entrt your Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <div class="mt-3">
                    Don\'t have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
</div>
';
*/
$content = '
<div class="row justify-content-center align-items-center" style="min-height: screen;">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0" style="
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        ">

            <div class="card-body p-4 p-md-5">

                <div class="mb-4">
                    <h3 class="fw-bold mb-1" style="letter-spacing:-0.5px;">Welcome back</h3>
                    <p class="text-muted small mb-0">Login to continue</p>
                </div>

                ' . ($error ? '<div class="alert alert-danger small py-2 border-0" style="border-radius:10px;">' . $error . '</div>' : '') . '

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label small text-muted">Username</label>
                        <input type="text" class="form-control modern-input" id="username" name="username" placeholder="anthony_mbaya" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-muted">Password</label>
                        <input type="password" class="form-control modern-input" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 small">
                        <div>
                            <input type="checkbox" id="remember" class="form-check-input me-1">
                            <label for="remember" class="text-muted">Remember me</label>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn modern-btn">
                            Login →
                        </button>
                    </div>

                </form>

                <div class="text-center mt-4 small">
                    <span class="text-muted">Don\'t have an account?</span>
                    <a href="register.php" class="fw-semibold text-decoration-none">Register</a>
                </div>

            </div>
        </div>

    </div>
</div>

<style>
.modern-input {
    border-radius: 12px;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    transition: all 0.2s ease;
}

.modern-input:focus {
    background: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.modern-btn {
    border-radius: 12px;
    padding: 12px;
    font-weight: 600;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    border: none;
    color: white;
    transition: all 0.2s ease;
}

.modern-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px rgba(99,102,241,0.25);
}

.card:hover {
    transition: 0.3s ease;
}
</style>
';
include 'includes/main_template.php';
?>
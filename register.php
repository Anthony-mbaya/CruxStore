<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$pageTitle = "Register";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
    $role = 'customer'; // Default role

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists!";
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$username, $hashed_password, $email, $phone, $role])) {
                $user_id = $pdo->lastInsertId();

                $_SESSION['message'] = "Registration successful! Please login.";
                $_SESSION['msg_type'] = "success";
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
/*
$content = '
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Create Account</h4>
            </div>
            <div class="card-body">
                ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your Username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your Email Address" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-bold">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your Phone Number" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your Password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                <div class="mt-3">
                    Already have an account? <a href="login.php">Login here</a>
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
                    <h3 class="fw-bold mb-1" style="letter-spacing:-0.5px;">Create account</h3>
                    <p class="text-muted small mb-0">Get started in seconds</p>
                </div>

                ' . ($error ? '<div class="alert alert-danger small py-2 border-0" style="border-radius:10px;">' . $error . '</div>' : '') . '

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label small text-muted">Username</label>
                        <input type="text" class="form-control modern-input" id="username" name="username" placeholder="anthony_mbaya" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Email</label>
                        <input type="email" class="form-control modern-input" id="email" name="email" placeholder="you@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Phone Number</label>
                        <input type="tel" class="form-control modern-input" id="phone" name="phone" placeholder="+254..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Password</label>
                        <input type="password" class="form-control modern-input" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-muted">Confirm Password</label>
                        <input type="password" class="form-control modern-input" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn modern-btn">
                            Create Account →
                        </button>
                    </div>

                </form>

                <div class="text-center mt-4 small">
                    <span class="text-muted">Already have an account?</span>
                    <a href="login.php" class="fw-semibold text-decoration-none">Login</a>
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
    transform: translateY(-3px);
    transition: 0.3s ease;
}
</style>
';
include 'includes/main_template.php';
?>
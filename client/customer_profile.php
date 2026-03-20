<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isCustomer()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "My Profile";

// ✅ GET USER DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ✅ HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    try {
        $pdo->beginTransaction();

        // Update basic info
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, phone = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$username, $phone, $_SESSION['user_id']]);

        // Update password ONLY if provided
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        }

        $pdo->commit();

        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['msg_type'] = "success";

        header("Location: customer_profile.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// ✅ HANDLE DELETE ACCOUNT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {

    try {
        $pdo->beginTransaction();

        // Delete cart
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")
            ->execute([$_SESSION['user_id']]);

        // Delete user (cascades if FK set)
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")
            ->execute([$_SESSION['user_id']]);

        $pdo->commit();

        session_destroy();

        header("Location: ../login.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting account: " . $e->getMessage();
    }
}

$content = '
<div class="container py-4">

    <h2 class="mb-4">My Profile</h2>

    '.(isset($error) ? '<div class="alert alert-danger">'.$error.'</div>' : '').'

    <div class="row">

        <!-- PROFILE INFO -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Account Information</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="'.htmlspecialchars($user['email']).'" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Joined</label>
                        <input type="text" class="form-control" value="'.date("d M Y", strtotime($user['created_at'])).'" readonly>
                    </div>

                </div>
            </div>
        </div>

        <!-- UPDATE FORM -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Edit Profile</h5>
                </div>
                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control"
                                   value="'.htmlspecialchars($user['username']).'" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="'.htmlspecialchars($user['phone']).'" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password (optional)</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-success w-100">
                            Update Profile
                        </button>

                    </form>

                </div>
            </div>
        </div>

    </div>

    <!-- DELETE ACCOUNT -->
    <div class="card mt-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5>Danger Zone</h5>
        </div>
        <div class="card-body">

            <p>This action cannot be undone. Your account and data will be permanently deleted.</p>

            <form method="POST" onsubmit="return confirm(\'Are you sure you want to delete your account?\')">
                <button type="submit" name="delete_account" class="btn btn-danger">
                    Delete My Account
                </button>
            </form>

        </div>
    </div>

</div>
';

include '../includes/main_template.php';
?>
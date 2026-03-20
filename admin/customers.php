<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Customers";

// ✅ HANDLE PROMOTION
if (isset($_GET['promote'])) {

    $user_id = (int) $_GET['promote'];

    try {
        $pdo->beginTransaction();

        // 1. Update role
        $stmt = $pdo->prepare("UPDATE users SET role = 'deliverer' WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // 2. Insert into deliverers table (avoid duplicate)
        $stmt = $pdo->prepare("
            INSERT INTO deliverers (user_id, vehicle_type, license_plate, is_active)
            VALUES (?, '', '', 1)
            ON DUPLICATE KEY UPDATE user_id = user_id
        ");
        $stmt->execute([$user_id]);

        $pdo->commit();

        $_SESSION['message'] = "Customer promoted to deliverer successfully!";
        $_SESSION['msg_type'] = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: customers.php");
    exit();
}

// ✅ FETCH CUSTOMERS ONLY
$customers = $pdo->query("
    SELECT user_id, username, email, phone, created_at
    FROM users
    WHERE role = 'customer'
    ORDER BY created_at DESC
")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Customers</h2>

    <div class="card">
        <div class="card-header">
            <h5>Customer List</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        ' . array_reduce($customers, function($carry, $c) {

                            return $carry . '

                            <tr>

                                <td>#'.$c['user_id'].'</td>

                                <td>'.htmlspecialchars($c['username']).'</td>

                                <td>'.htmlspecialchars($c['email']).'</td>

                                <td>'.htmlspecialchars($c['phone']).'</td>

                                <td>'.date("d M Y", strtotime($c['created_at'])).'</td>

                                <td>

                                    <a href="customers.php?promote='.$c['user_id'].'"
                                       class="btn btn-sm btn-success"
                                       onclick="return confirm(\'Promote this customer to deliverer?\')">
                                       Promote to Deliverer
                                    </a>

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
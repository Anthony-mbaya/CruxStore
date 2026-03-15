<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Deliverers";

// Fetch deliverers and their delivery stats
$deliverers = $pdo->query("
    SELECT 
        d.deliverer_id,
        u.username,
        u.phone,
        d.vehicle_type,
        d.license_plate,
        d.is_active,
        COUNT(del.delivery_id) as total_deliveries,
        SUM(CASE WHEN del.status = 'delivered' THEN 1 ELSE 0 END) as delivered
    FROM deliverers d
    JOIN users u ON d.user_id = u.user_id
    LEFT JOIN deliveries del ON d.deliverer_id = del.deliverer_id
    GROUP BY d.deliverer_id
    ORDER BY u.username
")->fetchAll();

$content = '
<div class="container">
    <h2 class="my-4">Deliverers</h2>

    <div class="card">
        <div class="card-header">
            <h5>Deliverer List</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>Plate</th>
                            <th>Total Deliveries</th>
                            <th>Delivered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        ' . array_reduce($deliverers, function($carry, $d) {

                            $status = $d['is_active'] ? 
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-danger">Inactive</span>';

                            return $carry . '

                            <tr>

                                <td>#'.$d['deliverer_id'].'</td>

                                <td>'.htmlspecialchars($d['username']).'</td>

                                <td>'.htmlspecialchars($d['phone']).'</td>

                                <td>'.htmlspecialchars($d['vehicle_type']).'</td>

                                <td>'.htmlspecialchars($d['license_plate']).'</td>

                                <td>'.$d['total_deliveries'].'</td>

                                <td>'.$d['delivered'].'</td>

                                <td>'.$status.'</td>

                                <td>

                                    <a href="deliveries.php?deliverer='.$d['deliverer_id'].'" 
                                       class="btn btn-sm btn-primary">
                                       View Deliveries
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
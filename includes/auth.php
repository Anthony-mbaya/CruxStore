<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

function isDeliverer() {
    return isLoggedIn() && $_SESSION['role'] === 'deliverer';
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'staff';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "Please login to access this page";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAuthorized($requiredRole) {
    if (!isLoggedIn()) {
        redirectIfNotLoggedIn();
    }
    
    $authorized = false;
    switch ($requiredRole) {
        case 'customer':
            $authorized = isCustomer();
            break;
        case 'deliverer':
            $authorized = isDeliverer();
            break;
        case 'admin':
            $authorized = isAdmin();
            break;
        case 'staff': // Alias for admin
            $authorized = isAdmin();
            break;
        default:
            $authorized = false;
    }
    
    if (!$authorized) {
        $_SESSION['message'] = "You are not authorized to access this page";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../index.php");
        exit();
    }
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        if (isCustomer()) {
            header("Location: client/client_home.php");
        } elseif (isAdmin()) {
            header("Location: admin/admin_home.php");
        } elseif (isDeliverer()) {
            header("Location: deliverer/deliverer_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
}

// New function to get current user's ID safely
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

// New function to check if user can access a specific delivery
function canAccessDelivery($deliveryId, $pdo) {
    if (!isLoggedIn()) return false;
    
    if (isAdmin()) {
        return true; // Admins can access all deliveries
    }
    
    $stmt = $pdo->prepare("
        SELECT 1 FROM deliveries d
        LEFT JOIN orders o ON d.order_id = o.order_id
        WHERE d.delivery_id = ?
        AND (o.customer_id = ? OR d.deliverer_id IN (
            SELECT deliverer_id FROM deliverers WHERE user_id = ?
        ))
    ");
    
    $stmt->execute([$deliveryId, $_SESSION['user_id'], $_SESSION['user_id']]);
    return (bool)$stmt->fetchColumn();
}
?>
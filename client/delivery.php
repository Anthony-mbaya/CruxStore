<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$deliveryId = $_GET['id'];

// Determine role of user
$isDeliverer = isDeliverer();
$isCustomer = isCustomer();
$isStaff = isAdmin();
$loggedInUserId = $_SESSION['user_id'] ?? null;

// Fetch delivery info
$accessQuery = "
    SELECT d.*, 
           o.customer_id AS order_customer_id, 
           o.order_id, o.delivery_address,
           u.username AS customer_name, 
           u.phone AS customer_phone,
           dl.vehicle_type, 
           dl.license_plate, 
           du.username AS deliverer_name,
           du.user_id AS deliverer_user_id
    FROM deliveries d
    JOIN orders o ON d.order_id = o.order_id
    JOIN users u ON o.customer_id = u.user_id
    LEFT JOIN deliverers dl ON d.deliverer_id = dl.deliverer_id
    LEFT JOIN users du ON dl.user_id = du.user_id
    WHERE d.delivery_id = ?
";
$stmt = $pdo->prepare($accessQuery);
$stmt->execute([$deliveryId]);
$delivery = $stmt->fetch();

if (!$delivery) {
    header("Location: ../index.php");
    exit();
}

// Access control
$allowed = false;

if ($isStaff) {
    $allowed = true;
} elseif ($isDeliverer && $delivery['deliverer_user_id'] == $loggedInUserId) {
    $allowed = true;
} elseif ($isCustomer && $delivery['order_customer_id'] == $loggedInUserId) {
    $allowed = true;
}

if (!$allowed) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Track Delivery #" . htmlspecialchars($delivery['delivery_id']);

$statusColors = [
    'pending' => 'secondary',
    'assigned' => 'warning',
    'picked_up' => 'primary',
    'in_transit' => 'info',
    'delivered' => 'success',
    'failed' => 'danger'
];

$statusBadge = $statusColors[$delivery['status']] ?? 'dark';
$statusText = ucfirst(str_replace('_', ' ', $delivery['status']));

ob_start();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<div class="container py-4">
    <div class="mb-3">
        <a class="btn btn-secondary px-3" href="javascript:history.back()">&larr; Back</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Live Tracking</h5>
                </div>
                <div class="card-body">
                    <div id="map" style="height: 400px;"></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Delivery Info</h5>
                </div>
                <div class="card-body">
                    <p>Status: <span class="badge bg-<?= $statusBadge ?>"><?= $statusText ?></span></p>
                    <p>Order #: <?= htmlspecialchars($delivery['order_id']) ?></p>
                    <p>Customer: <?= htmlspecialchars($delivery['customer_name']) ?><br>
                       <a href="tel:<?= htmlspecialchars($delivery['customer_phone']) ?>">
                       <?= htmlspecialchars($delivery['customer_phone']) ?></a></p>

                    <?php if ($delivery['deliverer_name']): ?>
                        <p>Deliverer: <?= htmlspecialchars($delivery['deliverer_name']) ?><br>
                        <?= htmlspecialchars($delivery['vehicle_type']) ?> (<?= htmlspecialchars($delivery['license_plate']) ?>)</p>
                    <?php endif; ?>

                    <p>Address:<br><?= nl2br(htmlspecialchars($delivery['delivery_address'])) ?></p>

                    <div class="card mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Map Legend</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge bg-success">●</span> Pickup Point
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-danger">●</span> Destination
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-primary">●</span> Deliverer Location
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([0, 0], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

function coloredIcon(color) {
    return new L.Icon({
        iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-${color}.png`,
        shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
}

const pickup = [<?= $delivery['pickup_latitude'] ?>, <?= $delivery['pickup_longitude'] ?>];
const destination = [<?= $delivery['destination_latitude'] ?>, <?= $delivery['destination_longitude'] ?>];
const current = [<?= $delivery['current_latitude'] ?: 'null' ?>, <?= $delivery['current_longitude'] ?: 'null' ?>];

L.marker(pickup, {icon: coloredIcon("green")}).addTo(map).bindPopup("Pickup Location");
L.marker(destination, {icon: coloredIcon("red")}).addTo(map).bindPopup("Destination");

<?php if ($isDeliverer): ?>
function updateLocation(lat, lng) {
    fetch('update_location.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ delivery_id: <?= $deliveryId ?>, lat, lng })
    });
}

function trackDeliverer() {
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            const marker = L.marker([lat, lng], {icon: coloredIcon("blue")})
                .addTo(map)
                .bindPopup("You (Deliverer)")
                .openPopup();

            map.setView([lat, lng], 14);
            updateLocation(lat, lng);
        });
    } else {
        alert("Geolocation is not supported.");
    }
}
trackDeliverer();
<?php else: ?>
if (current[0] && current[1]) {
    L.marker(current, {icon: coloredIcon("blue")}).addTo(map).bindPopup("Current Delivery Location");
    map.setView(current, 14);
} else {
    map.fitBounds([pickup, destination]);
}
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
include '../includes/main_template.php';
?>

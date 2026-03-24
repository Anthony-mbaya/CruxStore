<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

$pageTitle = "Update My Location";

// Get active delivery information for the current deliverer
$activeDelivery = null;
try {
    $stmt = $pdo->prepare("
        SELECT d.*, o.delivery_address, u.username as customer_name,u.phone
        FROM deliveries d
        JOIN orders o ON d.order_id = o.order_id
        JOIN users u ON o.customer_id = u.user_id
        WHERE d.deliverer_id = (
            SELECT deliverer_id FROM deliverers WHERE user_id = ?
        )
        AND d.status IN ('assigned', 'picked_up', 'in_transit')
        ORDER BY d.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activeDelivery = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching active delivery: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $pathData = isset($_POST['path_data']) ? $_POST['path_data'] : '';
    
    try {
        // Update all active deliveries with this location and path
        $stmt = $pdo->prepare("
            UPDATE deliveries d
            JOIN deliverers dv ON d.deliverer_id = dv.deliverer_id
            SET d.current_latitude = ?, 
                d.current_longitude = ?, 
                 d.path_data = COALESCE(NULLIF(?, ''), d.path_data),
                d.updated_at = NOW()
            WHERE dv.user_id = ?
        ");
        $stmt->execute([$latitude, $longitude, $pathData, $_SESSION['user_id']]);
        // Return JSON response for AJAX calls
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Location updated']);
            exit();
        }
        
        $_SESSION['message'] = "Your location has been updated";
        $_SESSION['msg_type'] = "success";
        header("Location: deliverer_dashboard.php");
        exit();
    } catch (PDOException $e) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
        $_SESSION['message'] = "Error updating location: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

// Add path_data column to deliveries table if it doesn't exist
try {
    $pdo->exec("ALTER TABLE deliveries ADD COLUMN IF NOT EXISTS path_data TEXT");
} catch (PDOException $e) {
    // Column might already exist, ignore error
}

$content = '
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mb-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Real-time Delivery Tracking</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="locationForm">
                        <div class="mb-3">
                            <div id="locationStatus" class="alert alert-info">
                                Getting your location...
                            </div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                            <input type="hidden" id="pathData" name="path_data">
                            <input type="hidden" name="ajax" value="1">
                        </div>
                        
                        <div id="map" style="height: 400px;" class="mb-3"></div>
                        
                        <div class="d-flex justify-content-between gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                Update Location
                            </button>
                            <button type="button" class="btn btn-secondary" id="toggleTracking">
                                Start Auto-Tracking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Delivery Information</h5>
                </div>
                <div class="card-body">
                    ' . ($activeDelivery ? '
                    <div class="mb-3">
                        <strong>Customer:</strong> ' . htmlspecialchars($activeDelivery['customer_name']) . '
                    </div>
                    <div class="mb-3">
                        <strong>Customer Number:</strong> ' . htmlspecialchars($activeDelivery['phone']) . '
                    </div>
                    <div class="mb-2"> <strong>Distance Traveled:</strong> <span id="distanceTraveled">0 km</span> </div> <div class="mb-2"> <strong>Estimated Distance to Destination:</strong> <span id="distanceToDestination">Calculating...</span> </div>
                    <div class="mb-3">
                        <strong>Status:</strong> 
                        <span class="badge bg-' . 
                        ($activeDelivery['status'] === 'assigned' ? 'warning' : 
                         ($activeDelivery['status'] === 'picked_up' ? 'info' : 'primary')) . '">
                            ' . ucfirst(str_replace('_', ' ', $activeDelivery['status'])) . '
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Delivery Address:</strong><br>
                        ' . nl2br(htmlspecialchars($activeDelivery['delivery_address'])) . '
                    </div>
                    
                    ' : '
                    <div class="alert alert-warning">
                        No active delivery found. Please check your dashboard for available deliveries.
                    </div>
                    ') . '
                </div>
            </div>
            
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
                        <span class="badge bg-primary">●</span> Your Current Location
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const activeDelivery = ' . json_encode($activeDelivery) . ';
</script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
let map, marker;
let isTracking = false;
let pathLine = null;
let lastLat = null;
let lastLong = null;


function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;

    const a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI/180) *
        Math.cos(lat2 * Math.PI/180) *
        Math.sin(dLng/2) * Math.sin(dLng/2);

    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
}

function initMap() {
    map = L.map("map").setView([0, 0], 2);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap"
    }).addTo(map);

    getLocation();
}

function getLocation() {
    if (!navigator.geolocation) {
        alert("Geolocation not supported");
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        updateMap(lat, lng);
        updateForm(lat, lng);

        document.getElementById("locationStatus").innerText = "Location ready";
        document.getElementById("locationStatus").className = "alert alert-success";

    }, err => {
        document.getElementById("locationStatus").innerText = err.message;
        document.getElementById("locationStatus").className = "alert alert-danger";
    });
}
/*
function updateMap(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map)
            .bindPopup("You are here").openPopup();
    }

    map.setView([lat, lng], 15);
}
*/
let pickupMarker, destinationMarker;


function updateMap(lat, lng) {
    // Current location marker
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map)
            .bindPopup("You are here").openPopup();
    }
    //colored icons
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
    // Pickup & Destination markers (only if delivery exists)
    if (activeDelivery) {

        // Pickup
        if (!pickupMarker && activeDelivery.pickup_latitude && activeDelivery.pickup_longitude) {
            pickupMarker = L.marker([
                activeDelivery.pickup_latitude,
                activeDelivery.pickup_longitude
            ], { title: "Pickup Point", icon: coloredIcon("green")})
            .addTo(map)
            .bindPopup("Pickup Location");
        }

        // Destination
        if (!destinationMarker && activeDelivery.destination_latitude && activeDelivery.destination_longitude) {
            destinationMarker = L.marker([
                activeDelivery.destination_latitude,
                activeDelivery.destination_longitude
            ], { 
                title: "Destination",
                icon: coloredIcon("red")
            })
            .addTo(map)
            .bindPopup("Destination");
        }
    }

    

    // Draw path
    if (activeDelivery) {

        const destLat = activeDelivery.destination_latitude;
        const destLng = activeDelivery.destination_longitude;
        const pickLat =  activeDelivery.pickup_latitude;
        const pickLong = activeDelivery.pickup_longitude;

        if (destLat && destLng) {

            const route = [
                [pickLat, pickLong],
                [destLat, destLng]
            ];

            if (pathLine) {
                pathLine.setLatLngs(route);
            } else {
                pathLine = L.polyline(route, {
                    color: "green",
                    weight: 4
                }).addTo(map);
            }
        }
    }
        //map.fitBounds(L.latLngBounds(path));
        map.setView([lat, lng], 13);
}
    
function updateForm(lat, lng) {
    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
    document.getElementById("submitBtn").disabled = false;
}


function sendLocation() {
    const lat = document.getElementById("latitude").value;
    const lng = document.getElementById("longitude").value;

    const form = document.getElementById("locationForm");
    const data = new FormData(form);

    fetch("", {
        method: "POST",
        body: data
    });
}

// Auto tracking toggle
document.getElementById("toggleTracking").addEventListener("click", function () {
    isTracking = !isTracking;

    this.textContent = isTracking ? "Stop Tracking" : "Start Tracking";
    this.className = isTracking ? "btn btn-warning" : "btn btn-secondary";

    if (isTracking) {
        navigator.geolocation.watchPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            updateMap(lat, lng);
            updateForm(lat, lng);

            if (lastLat !== null && lastLng !== null) {
                const dist = calculateDistance(lastLat, lastLng, lat, lng);
                totalDistance += dist;

                document.getElementById("distanceTraveled").innerText =
                    totalDistance.toFixed(2) + " km";
            }

            lastLat = lat;
            lastLng = lng;

            const destLat = activeDelivery.destination_latitude;
            const destLng = activeDelivery.destination_longitude;
            if (activeDelivery) {
                const remaining = calculateDistance(lat, lng, destLat, destLng);

                document.getElementById("distanceToDestination").innerText =
                    remaining.toFixed(2) + " km";
            }

            sendLocation();

        }, err => console.log(err), { enableHighAccuracy: true });
    }
});


// Manual submit
document.getElementById("locationForm").addEventListener("submit", function(e) {
    e.preventDefault();
    sendLocation();
    alert("Location updated");
});

document.addEventListener("DOMContentLoaded", initMap);
</script>';

include '../includes/main_template.php';
?>
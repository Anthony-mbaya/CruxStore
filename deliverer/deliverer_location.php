<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
redirectIfNotAuthorized('deliverer');

$pageTitle = "Update My Location";

// Get active delivery information for the current deliverer
$activeDelivery = null;
try {
    $stmt = $pdo->prepare("
        SELECT d.*, o.delivery_address, u.username as customer_name
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
            UPDATE deliveries 
            SET current_latitude = ?, current_longitude = ?, 
                path_data = COALESCE(NULLIF(?, ''), path_data),
                updated_at = NOW()
            WHERE deliverer_id = (
                SELECT deliverer_id FROM deliverers WHERE user_id = ?
            )
            AND status IN ('assigned', 'picked_up', 'in_transit')
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
        <div class="col-lg-8">
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
                        
                        <div class="d-flex justify-content-between">
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
                    <div id="deliveryStats" class="mt-3">
                        <div class="mb-2">
                            <strong>Distance Traveled:</strong> <span id="distanceTraveled">0 km</span>
                        </div>
                        <div class="mb-2">
                            <strong>Estimated Distance to Destination:</strong> <span id="distanceToDestination">Calculating...</span>
                        </div>
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
                    <div class="mb-2">
                        <span style="color: #ff6b6b;">━━━</span> Path Traveled
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
// Global variables
let map;
let currentLocationMarker;
let pickupMarker;
let destinationMarker;
let pathPolyline;
let isTracking = false;
let pathPoints = [];
let totalDistance = 0;
let lastPosition = null;

// Delivery data from PHP
const activeDelivery = ' . ($activeDelivery ? json_encode($activeDelivery) : 'null') . ';

// Custom icons
const pickupIcon = L.divIcon({
    html: `<div style="background-color: #28a745; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
    className: "custom-div-icon",
    iconSize: [20, 20],
    iconAnchor: [10, 10]
});

const destinationIcon = L.divIcon({
    html: `<div style="background-color: #dc3545; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
    className: "custom-div-icon",
    iconSize: [20, 20],
    iconAnchor: [10, 10]
});

const currentLocationIcon = L.divIcon({
    html: `<div style="background-color: #007bff; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); animation: pulse 2s infinite;"></div>
    <style>
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }
    </style>`,
    className: "custom-div-icon",
    iconSize: [15, 15],
    iconAnchor: [7, 7]
});

function initMap() {
    map = L.map("map").setView([0, 0], 2);
    
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors"
    }).addTo(map);
    
    // Initialize path polyline
    pathPolyline = L.polyline([], {
        color: "#ff6b6b",
        weight: 4,
        opacity: 0.8,
        smoothFactor: 1
    }).addTo(map);
    
    // Add pickup and destination markers if we have active delivery
    if (activeDelivery) {
        // Pickup marker
        pickupMarker = L.marker([activeDelivery.pickup_latitude, activeDelivery.pickup_longitude], {
            icon: pickupIcon
        }).addTo(map).bindPopup("Pickup Point");
        
        // Destination marker
        destinationMarker = L.marker([activeDelivery.destination_latitude, activeDelivery.destination_longitude], {
            icon: destinationIcon
        }).addTo(map).bindPopup("Destination: " + activeDelivery.delivery_address);
        
        // Load existing path if available
        if (activeDelivery.path_data) {
            try {
                pathPoints = JSON.parse(activeDelivery.path_data);
                pathPolyline.setLatLngs(pathPoints);
                calculateTotalDistance();
            } catch (e) {
                console.log("Could not parse existing path data");
            }
        }
        
        // Set initial map bounds to show all markers
        const group = new L.featureGroup([pickupMarker, destinationMarker]);
        if (pathPoints.length > 0) {
            group.addLayer(pathPolyline);
        }
        map.fitBounds(group.getBounds().pad(0.1));
    }
    
    // Try to get current location
    getCurrentLocation();
}

function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                updateCurrentLocation(lat, lng);
                
                document.getElementById("locationStatus").textContent = "Location found! Auto-tracking available.";
                document.getElementById("locationStatus").className = "alert alert-success";
            },
            error => {
                document.getElementById("locationStatus").textContent = "Error getting location: " + error.message;
                document.getElementById("locationStatus").className = "alert alert-danger";
            },
            { enableHighAccuracy: true }
        );
    } else {
        document.getElementById("locationStatus").textContent = "Geolocation is not supported by your browser";
        document.getElementById("locationStatus").className = "alert alert-danger";
    }
}

function updateCurrentLocation(lat, lng) {
    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
    document.getElementById("submitBtn").disabled = false;
    
    // Update or create current location marker
    if (currentLocationMarker) {
        currentLocationMarker.setLatLng([lat, lng]);
    } else {
        currentLocationMarker = L.marker([lat, lng], {
            icon: currentLocationIcon
        }).addTo(map).bindPopup("Your Current Location");
    }
    
    // Add point to path if tracking is enabled and we have moved significantly
    if (isTracking && shouldAddPoint(lat, lng)) {
        pathPoints.push([lat, lng]);
        pathPolyline.addLatLng([lat, lng]);
        calculateDistanceFromLastPoint(lat, lng);
        updatePathData();
        
        // Auto-submit location update
        submitLocationUpdate();
    }
    
    // Update distance to destination
    updateDistanceToDestination(lat, lng);
    
    lastPosition = { lat, lng };
}

function shouldAddPoint(lat, lng) {
    if (!lastPosition) return true;
    
    const distance = calculateDistance(lastPosition.lat, lastPosition.lng, lat, lng);
    return distance > 0.01; // Only add point if moved more than 10 meters
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371; // Radius of the Earth in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function calculateDistanceFromLastPoint(lat, lng) {
    if (pathPoints.length > 1) {
        const prevPoint = pathPoints[pathPoints.length - 2];
        const distance = calculateDistance(prevPoint[0], prevPoint[1], lat, lng);
        totalDistance += distance;
        document.getElementById("distanceTraveled").textContent = totalDistance.toFixed(2) + " km";
    }
}

function calculateTotalDistance() {
    totalDistance = 0;
    for (let i = 1; i < pathPoints.length; i++) {
        const distance = calculateDistance(
            pathPoints[i-1][0], pathPoints[i-1][1],
            pathPoints[i][0], pathPoints[i][1]
        );
        totalDistance += distance;
    }
    document.getElementById("distanceTraveled").textContent = totalDistance.toFixed(2) + " km";
}

function updateDistanceToDestination(lat, lng) {
    if (activeDelivery) {
        const distance = calculateDistance(
            lat, lng,
            activeDelivery.destination_latitude,
            activeDelivery.destination_longitude
        );
        document.getElementById("distanceToDestination").textContent = distance.toFixed(2) + " km";
    }
}

function updatePathData() {
    document.getElementById("pathData").value = JSON.stringify(pathPoints);
}

function submitLocationUpdate() {
    const formData = new FormData(document.getElementById("locationForm"));
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error("Location update failed:", data.message);
        }
    })
    .catch(error => {
        console.error("Error updating location:", error);
    });
}

function toggleTracking() {
    const button = document.getElementById("toggleTracking");
    
    if (isTracking) {
        isTracking = false;
        button.textContent = "Start Auto-Tracking";
        button.className = "btn btn-secondary";
        document.getElementById("locationStatus").textContent = "Auto-tracking stopped";
        document.getElementById("locationStatus").className = "alert alert-warning";
    } else {
        isTracking = true;
        button.textContent = "Stop Auto-Tracking";
        button.className = "btn btn-warning";
        document.getElementById("locationStatus").textContent = "Auto-tracking enabled - your path is being recorded";
        document.getElementById("locationStatus").className = "alert alert-success";
        
        // Start watching position for real-time updates
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                position => {
                    if (isTracking) {
                        updateCurrentLocation(position.coords.latitude, position.coords.longitude);
                    }
                },
                error => console.error("Geolocation error:", error),
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 5000
                }
            );
        }
    }
}

// Manual location update
document.getElementById("locationForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const lat = document.getElementById("latitude").value;
    const lng = document.getElementById("longitude").value;
    
    if (lat && lng) {
        // Add current point to path
        pathPoints.push([parseFloat(lat), parseFloat(lng)]);
        pathPolyline.addLatLng([parseFloat(lat), parseFloat(lng)]);
        updatePathData();
        
        // Submit the form
        submitLocationUpdate();
        
        alert("Location updated successfully!");
    }
});

// Initialize on page load
document.addEventListener("DOMContentLoaded", initMap);

// Periodic location updates when tracking is enabled
setInterval(() => {
    if (isTracking) {
        getCurrentLocation();
    }
}, 30000); // Update every 30 seconds
</script>';

include '../includes/main_template.php';
?>
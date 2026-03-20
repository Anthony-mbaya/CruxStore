<?php
$host = 'localhost';
$dbname = 'urban_interior_designers';
$username = 'root';
$password = '';

try {
    // Step 1: Connect to MySQL server without selecting a DB
    $pdoTemp = new PDO("mysql:host=$host", $username, $password);
    $pdoTemp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 2: Create the database if it doesn't exist
    $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdoTemp = null; // Close temp connection

    // Step 3: Connect to the newly created database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Step 4: Create tables if they don't exist

    // Users (now with 'deliverer' role)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20),
            role ENUM('customer', 'staff', 'deliverer') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Products
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            category VARCHAR(50),
            stock_quantity INT DEFAULT 0,
            price DECIMAL(10,2) NOT NULL,
            status ENUM('active', 'out_of_stock') DEFAULT 'active',
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    //cart items
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_cart (user_id, product_id)
)");

    // Orders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
            delivery_address TEXT,
            notes TEXT,
            FOREIGN KEY (customer_id) REFERENCES users(user_id)
        )
    ");

    // Order Items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        )
    ");

    // Payments
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            payment_method ENUM('mpesa', 'paypal') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            transaction_id VARCHAR(100) NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id)
        )
    ");

    // Deliverers (streamlined - only static profile data)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deliverers (
            deliverer_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            vehicle_type VARCHAR(50),
            license_plate VARCHAR(20),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    // Deliveries (now contains all delivery-specific location and status data)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deliveries (
            delivery_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            deliverer_id INT,
            pickup_latitude DECIMAL(10,8) NOT NULL,
            pickup_longitude DECIMAL(11,8) NOT NULL,
            destination_latitude DECIMAL(10,8) NOT NULL,
            destination_longitude DECIMAL(11,8) NOT NULL,
            current_latitude DECIMAL(10,8),
            current_longitude DECIMAL(11,8),
            status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed') DEFAULT 'pending',
            estimated_delivery_time DATETIME,
            actual_delivery_time DATETIME,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (deliverer_id) REFERENCES deliverers(deliverer_id),
            INDEX idx_deliverer_status (deliverer_id, status),
            INDEX idx_status (status)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_stations (
            station_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8)
)");

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
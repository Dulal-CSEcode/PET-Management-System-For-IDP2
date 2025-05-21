<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to check if tables exist and create them if they don't
function ensureTablesExist($conn) {
    // Check if pricing_plans table exists
    $result = $conn->query("SHOW TABLES LIKE 'pricing_plans'");
    if ($result->num_rows == 0) {
        // Create pricing_plans table
        $sql = "CREATE TABLE IF NOT EXISTS pricing_plans (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            features TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($sql)) {
            return ['success' => false, 'error' => "Error creating pricing_plans table: " . $conn->error];
        }
        
        // Insert default plans
        $sql = "INSERT INTO pricing_plans (name, price, description, features) VALUES
            ('Basic', 4990, 'Basic plan for pet services', 'Pet Feeding,Pet Grooming,Pet Training,Pet Exercise,Pet Treatment'),
            ('Standard', 9990, 'Standard plan with more features', 'All features in Basic,Enhanced Pet Services,Priority Support,Extended Medical Treatments'),
            ('Extended', 14900, 'Extended plan with all features', 'All features in Standard,Advanced Pet Services,Personalized Health Plans,24/7 Support,Customizable Pet Training,Exclusive Discounts on Products')";
        
        if (!$conn->query($sql)) {
            return ['success' => false, 'error' => "Error inserting default pricing plans: " . $conn->error];
        }
    }
    
    // Check if pricing_plan_orders table exists
    $result = $conn->query("SHOW TABLES LIKE 'pricing_plan_orders'");
    if ($result->num_rows == 0) {
        // Create pricing_plan_orders table
        $sql = "CREATE TABLE IF NOT EXISTS pricing_plan_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'active', 'cancelled') DEFAULT 'pending'
        )";
        
        if (!$conn->query($sql)) {
            return ['success' => false, 'error' => "Error creating pricing_plan_orders table: " . $conn->error];
        }
    }
    
    return ['success' => true];
}

// Check if users table exists (we need at least one user)
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => "Users table does not exist"]);
    exit;
}

// Ensure tables exist
$tablesCheck = ensureTablesExist($conn);
if (!$tablesCheck['success']) {
    echo json_encode($tablesCheck);
    exit;
}

// Get a user ID from the users table
$userResult = $conn->query("SELECT id FROM users LIMIT 1");
if ($userResult->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => "No users found in the database"]);
    exit;
}

$userId = $userResult->fetch_assoc()['id'];

// Try to insert a test order
$sql = "INSERT INTO pricing_plan_orders (user_id, plan_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$planId = 1; // Use the first plan
$stmt->bind_param("ii", $userId, $planId);

if ($stmt->execute()) {
    $orderId = $conn->insert_id;
    
    // Verify the order was inserted
    $verifyResult = $conn->query("SELECT * FROM pricing_plan_orders WHERE id = $orderId");
    if ($verifyResult->num_rows > 0) {
        $order = $verifyResult->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'message' => 'Test order inserted successfully',
            'order' => $order
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => "Order was inserted but could not be retrieved"]);
    }
} else {
    echo json_encode(['success' => false, 'error' => "Failed to insert test order: " . $stmt->error]);
}
?> 
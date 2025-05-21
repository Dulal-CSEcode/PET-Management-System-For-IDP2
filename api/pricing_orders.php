<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file for debugging
$logFile = __DIR__ . '/pricing_orders_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

logMessage("API request received: " . $_SERVER['REQUEST_METHOD']);

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
            logMessage("Error creating pricing_plans table: " . $conn->error);
            return false;
        }
        
        // Insert default plans
        $sql = "INSERT INTO pricing_plans (name, price, description, features) VALUES
            ('Basic', 4990, 'Basic plan for pet services', 'Pet Feeding,Pet Grooming,Pet Training,Pet Exercise,Pet Treatment'),
            ('Standard', 9990, 'Standard plan with more features', 'All features in Basic,Enhanced Pet Services,Priority Support,Extended Medical Treatments'),
            ('Extended', 14900, 'Extended plan with all features', 'All features in Standard,Advanced Pet Services,Personalized Health Plans,24/7 Support,Customizable Pet Training,Exclusive Discounts on Products')";
        
        if (!$conn->query($sql)) {
            logMessage("Error inserting default pricing plans: " . $conn->error);
            return false;
        }
    }
    
    // Check if pricing_plan_orders table exists
    $result = $conn->query("SHOW TABLES LIKE 'pricing_plan_orders'");
    if ($result->num_rows == 0) {
        // Create pricing_plan_orders table without foreign keys for simplicity
        $sql = "CREATE TABLE IF NOT EXISTS pricing_plan_orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'pending'
        )";
        
        if (!$conn->query($sql)) {
            logMessage("Error creating pricing_plan_orders table: " . $conn->error);
            return false;
        }
    }
    
    return true;
}

// Ensure tables exist
if (!ensureTablesExist($conn)) {
    echo json_encode(['success' => false, 'error' => 'Failed to ensure required tables exist']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
logMessage("Request method: " . $method);

switch($method) {
    case 'GET':
        // Admin: get all orders
        $sql = "SELECT o.id, u.name as user_name, p.name as plan_name, p.price, o.order_date, o.status
                FROM pricing_plan_orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN pricing_plans p ON o.plan_id = p.id
                ORDER BY o.order_date DESC";
        $result = $conn->query($sql);
        if ($result) {
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            logMessage("GET request successful. Found " . count($orders) . " orders.");
            echo json_encode(['success' => true, 'data' => $orders]);
        } else {
            logMessage("GET request failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'POST':
        // Get the raw POST data
        $rawData = file_get_contents('php://input');
        logMessage("Raw POST data: " . $rawData);
        
        $data = json_decode($rawData, true);
        if ($data === null) {
            logMessage("Failed to decode JSON data");
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            exit;
        }
        
        logMessage("Decoded data: " . print_r($data, true));
        
        if (!isset($data['user_id']) || !isset($data['plan_id'])) {
            logMessage("Missing user_id or plan_id");
            echo json_encode(['success' => false, 'error' => 'Missing user or plan']);
            exit;
        }
        
        // Validate user_id and plan_id are integers
        $userId = (int)$data['user_id'];
        $planId = (int)$data['plan_id'];
        
        logMessage("Processed user_id: " . $userId);
        logMessage("Processed plan_id: " . $planId);
        
        // Simple direct insert without checks for simplicity
        $sql = "INSERT INTO pricing_plan_orders (user_id, plan_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $planId);
        
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            logMessage("Order inserted successfully with ID: " . $orderId);
            echo json_encode([
                'success' => true, 
                'message' => 'Order placed successfully',
                'order_id' => $orderId
            ]);
        } else {
            logMessage("Failed to insert order: " . $stmt->error);
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    case 'PUT':
        $rawData = file_get_contents('php://input');
        logMessage("Raw PUT data: " . $rawData);
        
        $data = json_decode($rawData, true);
        if ($data === null) {
            logMessage("Failed to decode JSON data");
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            exit;
        }
        
        logMessage("Decoded PUT data: " . print_r($data, true));
        
        if (!isset($data['id']) || !isset($data['status'])) {
            logMessage("Missing id or status");
            echo json_encode(['success' => false, 'error' => 'Missing id or status']);
            exit;
        }
        
        // Validate id is an integer
        $id = (int)$data['id'];
        $status = $data['status'];
        
        $sql = "UPDATE pricing_plan_orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            logMessage("Order status updated successfully for ID: " . $id);
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            logMessage("Failed to update order status: " . $stmt->error);
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        break;
        
    default:
        logMessage("Unsupported method: " . $method);
        echo json_encode(['success' => false, 'error' => 'Unsupported method']);
        break;
}
?> 
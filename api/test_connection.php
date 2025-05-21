<?php
header('Content-Type: application/json');
require_once 'config.php';

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Check if pricing_plan_orders table exists
$result = $conn->query("SHOW TABLES LIKE 'pricing_plan_orders'");
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => "Table pricing_plan_orders does not exist"]);
    exit;
}

// Check pricing_plans table
$result = $conn->query("SELECT * FROM pricing_plans LIMIT 3");
if ($result) {
    $plans = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'message' => 'Connection successful', 'plans' => $plans]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?> 
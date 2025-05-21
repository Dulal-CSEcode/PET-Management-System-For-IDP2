<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getAdoptionRequest($conn, $_GET['id']);
        } else if (isset($_GET['user_id'])) {
            getUserAdoptionRequests($conn, $_GET['user_id']);
        } else if (isset($_GET['pet_id'])) {
            getPetAdoptionRequests($conn, $_GET['pet_id']);
        } else {
            getAllAdoptionRequests($conn);
        }
        break;
    case 'POST':
        createAdoptionRequest($conn);
        break;
    case 'PUT':
        updateAdoptionRequest($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteAdoptionRequest($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Adoption request ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all adoption requests
function getAllAdoptionRequests($conn) {
    $sql = "SELECT ar.*, p.name as pet_name, p.breed, p.gender, u.name as user_name, u.email as user_email
            FROM adoption_requests ar
            JOIN pets p ON ar.pet_id = p.id
            JOIN users u ON ar.user_id = u.id
            ORDER BY ar.created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $requests]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific adoption request
function getAdoptionRequest($conn, $id) {
    $sql = "SELECT ar.*, p.name as pet_name, p.breed, p.gender, u.name as user_name, u.email as user_email
            FROM adoption_requests ar
            JOIN pets p ON ar.pet_id = p.id
            JOIN users u ON ar.user_id = u.id
            WHERE ar.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $request = $result->fetch_assoc();
        if ($request) {
            echo json_encode(['success' => true, 'data' => $request]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Adoption request not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get adoption requests for a specific user
function getUserAdoptionRequests($conn, $user_id) {
    $sql = "SELECT ar.*, p.name as pet_name, p.breed, p.gender, p.image_url
            FROM adoption_requests ar
            JOIN pets p ON ar.pet_id = p.id
            WHERE ar.user_id = ?
            ORDER BY ar.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $requests]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get adoption requests for a specific pet
function getPetAdoptionRequests($conn, $pet_id) {
    $sql = "SELECT ar.*, u.name as user_name, u.email as user_email, u.phone
            FROM adoption_requests ar
            JOIN users u ON ar.user_id = u.id
            WHERE ar.pet_id = ?
            ORDER BY ar.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $requests]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Create a new adoption request
function createAdoptionRequest($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pet_id']) || !isset($data['user_id']) || !isset($data['reason'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $pet_id = $data['pet_id'];
    $user_id = $data['user_id'];
    $reason = $data['reason'];
    $status = 'pending'; // Default status is 'pending'
    
    // Check if pet exists and is available
    $sql = "SELECT id, is_available FROM pets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
        return;
    }
    
    $pet = $result->fetch_assoc();
    if (!$pet['is_available']) {
        http_response_code(400);
        echo json_encode(['error' => 'Pet is not available for adoption']);
        return;
    }
    
    // Check if user exists
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    // Check if user already has a pending request for this pet
    $sql = "SELECT id FROM adoption_requests WHERE pet_id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'You already have a pending adoption request for this pet']);
        return;
    }
    
    // Insert adoption request
    $sql = "INSERT INTO adoption_requests (pet_id, user_id, reason, status) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $pet_id, $user_id, $reason, $status);
    
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Adoption request submitted successfully', 'id' => $request_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Update an adoption request (e.g., change status)
function updateAdoptionRequest($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Request ID and status are required']);
        return;
    }
    
    $id = $data['id'];
    $status = $data['status'];
    
    // Validate status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Must be one of: pending, approved, rejected']);
        return;
    }
    
    // Check if request exists
    $sql = "SELECT ar.id, ar.pet_id, ar.status FROM adoption_requests ar WHERE ar.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Adoption request not found']);
        return;
    }
    
    $request = $result->fetch_assoc();
    
    // If approving the request, update pet availability
    if ($status === 'approved' && $request['status'] !== 'approved') {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update request status
            $sql = "UPDATE adoption_requests SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            
            // Update pet availability
            $sql = "UPDATE pets SET is_available = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request['pet_id']);
            $stmt->execute();
            
            // Reject other pending requests for this pet
            $sql = "UPDATE adoption_requests SET status = 'rejected' WHERE pet_id = ? AND id != ? AND status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $request['pet_id'], $id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => 'Adoption request approved successfully']);
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        // Just update the request status
        $sql = "UPDATE adoption_requests SET status = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Adoption request updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
        }
    }
}

// Delete an adoption request
function deleteAdoptionRequest($conn, $id) {
    $sql = "DELETE FROM adoption_requests WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Adoption request deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}
?> 
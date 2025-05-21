<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getService($conn, $_GET['id']);
        } else if (isset($_GET['clinic_id'])) {
            getClinicServices($conn, $_GET['clinic_id']);
        } else {
            getAllServices($conn);
        }
        break;
    case 'POST':
        createService($conn);
        break;
    case 'PUT':
        updateService($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteService($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Service ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all services with clinic names
function getAllServices($conn) {
    $sql = "SELECT cs.*, vc.name as clinic_name
            FROM clinic_services cs
            JOIN vet_clinics vc ON cs.clinic_id = vc.id
            ORDER BY cs.name";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $services = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $services]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get services for a specific clinic
function getClinicServices($conn, $clinic_id) {
    // Validate clinic exists
    $sql = "SELECT id FROM vet_clinics WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        return;
    }
    
    $sql = "SELECT cs.*
            FROM clinic_services cs
            WHERE cs.clinic_id = ?
            ORDER BY cs.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $services = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $services]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific service
function getService($conn, $id) {
    $sql = "SELECT cs.*, vc.name as clinic_name
            FROM clinic_services cs
            JOIN vet_clinics vc ON cs.clinic_id = vc.id
            WHERE cs.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $service = $result->fetch_assoc();
        
        if ($service) {
            echo json_encode(['success' => true, 'data' => $service]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Create a new service
function createService($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['clinic_id']) || !isset($data['name']) || !isset($data['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Clinic ID, name, and price are required']);
        return;
    }
    
    $clinic_id = $data['clinic_id'];
    $name = $data['name'];
    $description = isset($data['description']) ? $data['description'] : '';
    $price = $data['price'];
    
    // Validate clinic exists
    $sql = "SELECT id FROM vet_clinics WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        return;
    }
    
    // Check if service already exists for this clinic
    $sql = "SELECT id FROM clinic_services WHERE clinic_id = ? AND name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $clinic_id, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Service with this name already exists for this clinic']);
        return;
    }
    
    // Insert new service
    $sql = "INSERT INTO clinic_services (clinic_id, name, description, price) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issd", $clinic_id, $name, $description, $price);
    
    if ($stmt->execute()) {
        $service_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Service created successfully', 
            'id' => $service_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Update a service
function updateService($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Service ID is required']);
        return;
    }
    
    $id = $data['id'];
    $clinic_id = isset($data['clinic_id']) ? $data['clinic_id'] : null;
    $name = isset($data['name']) ? $data['name'] : null;
    $description = isset($data['description']) ? $data['description'] : null;
    $price = isset($data['price']) ? $data['price'] : null;
    
    // Check if service exists
    $sql = "SELECT id, clinic_id FROM clinic_services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
        return;
    }
    
    $service = $result->fetch_assoc();
    
    // If updating clinic_id, validate clinic exists
    if ($clinic_id !== null && $clinic_id != $service['clinic_id']) {
        $sql = "SELECT id FROM vet_clinics WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $clinic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Clinic not found']);
            return;
        }
    } else {
        $clinic_id = $service['clinic_id']; // Use existing clinic_id
    }
    
    // Check for duplicate name if name is being updated
    if ($name !== null) {
        $sql = "SELECT id FROM clinic_services WHERE clinic_id = ? AND name = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $clinic_id, $name, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Service with this name already exists for this clinic']);
            return;
        }
    }
    
    // Build update query based on provided fields
    $updates = [];
    $types = "";
    $values = [];
    
    if ($clinic_id !== null) {
        $updates[] = "clinic_id = ?";
        $types .= "i";
        $values[] = $clinic_id;
    }
    
    if ($name !== null) {
        $updates[] = "name = ?";
        $types .= "s";
        $values[] = $name;
    }
    
    if ($description !== null) {
        $updates[] = "description = ?";
        $types .= "s";
        $values[] = $description;
    }
    
    if ($price !== null) {
        $updates[] = "price = ?";
        $types .= "d";
        $values[] = $price;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    // Add id to values array and types
    $values[] = $id;
    $types .= "i";
    
    $sql = "UPDATE clinic_services SET " . implode(", ", $updates) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Delete a service
function deleteService($conn, $id) {
    // Check if service exists
    $sql = "SELECT id FROM clinic_services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
        return;
    }
    
    // Check if service is used in any appointments
    $sql = "SELECT id FROM vet_appointment_services WHERE service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Cannot delete service because it is used in one or more appointments']);
        return;
    }
    
    // Delete service
    $sql = "DELETE FROM clinic_services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}
?> 
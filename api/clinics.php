<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getClinic($conn, $_GET['id']);
        } else {
            getAllClinics($conn);
        }
        break;
    case 'POST':
        createClinic($conn);
        break;
    case 'PUT':
        updateClinic($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteClinic($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Clinic ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all clinics
function getAllClinics($conn) {
    $sql = "SELECT * FROM vet_clinics ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if ($result) {
        $clinics = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $clinics]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific clinic
function getClinic($conn, $id) {
    $sql = "SELECT * FROM vet_clinics WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $clinic = $result->fetch_assoc();
        if ($clinic) {
            // Get clinic working hours
            $sql = "SELECT day, open_time, close_time FROM clinic_hours WHERE clinic_id = ? ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $hours_result = $stmt->get_result();
            
            if ($hours_result) {
                $hours = $hours_result->fetch_all(MYSQLI_ASSOC);
                $clinic['hours'] = $hours;
            }
            
            // Get available services
            $sql = "SELECT id, name, description, price FROM clinic_services WHERE clinic_id = ? ORDER BY name ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $services_result = $stmt->get_result();
            
            if ($services_result) {
                $services = $services_result->fetch_all(MYSQLI_ASSOC);
                $clinic['services'] = $services;
            }
            
            echo json_encode(['success' => true, 'data' => $clinic]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Clinic not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Create a new clinic
function createClinic($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['address']) || !isset($data['phone']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $name = $data['name'];
    $address = $data['address'];
    $phone = $data['phone'];
    $email = $data['email'];
    $description = $data['description'] ?? null;
    $image = $data['image'] ?? null;
    
    // Check if clinic with the same name exists
    $sql = "SELECT id FROM vet_clinics WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'A clinic with this name already exists']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert clinic
        $sql = "INSERT INTO vet_clinics (name, address, phone, email, description, image) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $address, $phone, $email, $description, $image);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert clinic: ' . $stmt->error);
        }
        
        $clinic_id = $conn->insert_id;
        
        // Insert working hours if provided
        if (isset($data['hours']) && is_array($data['hours'])) {
            foreach ($data['hours'] as $hour) {
                if (!isset($hour['day']) || !isset($hour['open_time']) || !isset($hour['close_time'])) {
                    continue;
                }
                
                $sql = "INSERT INTO clinic_hours (clinic_id, day, open_time, close_time) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $clinic_id, $hour['day'], $hour['open_time'], $hour['close_time']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert clinic hours: ' . $stmt->error);
                }
            }
        }
        
        // Insert services if provided
        if (isset($data['services']) && is_array($data['services'])) {
            foreach ($data['services'] as $service) {
                if (!isset($service['name']) || !isset($service['price'])) {
                    continue;
                }
                
                $serviceName = $service['name'];
                $serviceDescription = $service['description'] ?? null;
                $servicePrice = floatval($service['price']);
                
                $sql = "INSERT INTO clinic_services (clinic_id, name, description, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issd", $clinic_id, $serviceName, $serviceDescription, $servicePrice);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert clinic service: ' . $stmt->error);
                }
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clinic created successfully', 
            'id' => $clinic_id
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Update an existing clinic
function updateClinic($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Clinic ID is required']);
        return;
    }
    
    $id = $data['id'];
    
    // Check if clinic exists
    $sql = "SELECT id FROM vet_clinics WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        return;
    }
    
    // Build update SQL dynamically based on provided fields
    $updates = [];
    $params = [];
    $types = "";
    
    $fields = [
        'name' => 's',
        'address' => 's',
        'phone' => 's',
        'email' => 's',
        'description' => 's',
        'image' => 's'
    ];
    
    foreach ($fields as $field => $type) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
            $types .= $type;
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update clinic
        $sql = "UPDATE vet_clinics SET " . implode(", ", $updates) . " WHERE id = ?";
        $types .= "i";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update clinic: ' . $stmt->error);
        }
        
        // Update working hours if provided
        if (isset($data['hours']) && is_array($data['hours'])) {
            // First delete existing hours
            $sql = "DELETE FROM clinic_hours WHERE clinic_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete existing clinic hours: ' . $stmt->error);
            }
            
            // Then insert new hours
            foreach ($data['hours'] as $hour) {
                if (!isset($hour['day']) || !isset($hour['open_time']) || !isset($hour['close_time'])) {
                    continue;
                }
                
                $sql = "INSERT INTO clinic_hours (clinic_id, day, open_time, close_time) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id, $hour['day'], $hour['open_time'], $hour['close_time']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert clinic hours: ' . $stmt->error);
                }
            }
        }
        
        // Update services if provided
        if (isset($data['services']) && is_array($data['services'])) {
            // First delete existing services
            $sql = "DELETE FROM clinic_services WHERE clinic_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete existing clinic services: ' . $stmt->error);
            }
            
            // Then insert new services
            foreach ($data['services'] as $service) {
                if (!isset($service['name']) || !isset($service['price'])) {
                    continue;
                }
                
                $serviceName = $service['name'];
                $serviceDescription = $service['description'] ?? null;
                $servicePrice = floatval($service['price']);
                
                $sql = "INSERT INTO clinic_services (clinic_id, name, description, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issd", $id, $serviceName, $serviceDescription, $servicePrice);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert clinic service: ' . $stmt->error);
                }
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Clinic updated successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Delete a clinic
function deleteClinic($conn, $id) {
    // Check if clinic exists
    $sql = "SELECT id FROM vet_clinics WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        return;
    }
    
    // Check if clinic has appointments
    $sql = "SELECT id FROM vet_appointments WHERE clinic_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete clinic with existing appointments']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete clinic hours
        $sql = "DELETE FROM clinic_hours WHERE clinic_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete clinic hours: ' . $stmt->error);
        }
        
        // Delete clinic services
        $sql = "DELETE FROM clinic_services WHERE clinic_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete clinic services: ' . $stmt->error);
        }
        
        // Delete clinic
        $sql = "DELETE FROM vet_clinics WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete clinic: ' . $stmt->error);
        }
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Clinic deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 
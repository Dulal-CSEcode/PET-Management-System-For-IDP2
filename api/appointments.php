<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getAppointment($conn, $_GET['id']);
        } elseif (isset($_GET['pet_id'])) {
            getPetAppointments($conn, $_GET['pet_id']);
        } elseif (isset($_GET['clinic_id'])) {
            getClinicAppointments($conn, $_GET['clinic_id']);
        } elseif (isset($_GET['user_id'])) {
            getUserAppointments($conn, $_GET['user_id']);
        } else {
            getAllAppointments($conn);
        }
        break;
    case 'POST':
        createAppointment($conn);
        break;
    case 'PUT':
        updateAppointment($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteAppointment($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Appointment ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all appointments
function getAllAppointments($conn) {
    // Only administrators should be able to see all appointments
    // In a real application, add authentication and authorization checks here
    
    error_log("Fetching all appointments");
    
    $sql = "SELECT va.*, p.name as pet_name, vc.name as clinic_name, u.first_name, u.last_name
            FROM vet_appointments va
            JOIN pets p ON va.pet_id = p.id
            JOIN vet_clinics vc ON va.clinic_id = vc.id
            JOIN users u ON p.owner_id = u.id
            ORDER BY va.appointment_date DESC, va.appointment_time ASC";
    
    error_log("SQL query: " . $sql);
    
    $result = $conn->query($sql);
    
    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        $count = count($appointments);
        error_log("Found $count appointments");
        
        // Get services for each appointment
        foreach ($appointments as &$appointment) {
            $appointment['services'] = getAppointmentServices($conn, $appointment['id']);
            error_log("Appointment ID: {$appointment['id']} has " . count($appointment['services']) . " services");
        }
        
        echo json_encode(['success' => true, 'data' => $appointments]);
    } else {
        $error = $conn->error;
        error_log("Database error in getAllAppointments: $error");
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $error]);
    }
}

// Get appointments for a specific user (pet owner)
function getUserAppointments($conn, $user_id) {
    $sql = "SELECT va.*, p.name as pet_name, p.type as pet_type, vc.name as clinic_name
            FROM vet_appointments va
            JOIN pets p ON va.pet_id = p.id
            JOIN vet_clinics vc ON va.clinic_id = vc.id
            WHERE p.owner_id = ?
            ORDER BY va.appointment_date DESC, va.appointment_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get services for each appointment
        foreach ($appointments as &$appointment) {
            $appointment['services'] = getAppointmentServices($conn, $appointment['id']);
        }
        
        echo json_encode(['success' => true, 'data' => $appointments]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get appointments for a specific clinic
function getClinicAppointments($conn, $clinic_id) {
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
    
    $sql = "SELECT va.*, p.name as pet_name, p.type as pet_type, u.first_name, u.last_name
            FROM vet_appointments va
            JOIN pets p ON va.pet_id = p.id
            JOIN users u ON p.owner_id = u.id
            WHERE va.clinic_id = ?
            ORDER BY va.appointment_date DESC, va.appointment_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clinic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get services for each appointment
        foreach ($appointments as &$appointment) {
            $appointment['services'] = getAppointmentServices($conn, $appointment['id']);
        }
        
        echo json_encode(['success' => true, 'data' => $appointments]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get appointments for a specific pet
function getPetAppointments($conn, $pet_id) {
    // Validate pet exists
    $sql = "SELECT id FROM pets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
        return;
    }
    
    $sql = "SELECT va.*, vc.name as clinic_name, vc.address
            FROM vet_appointments va
            JOIN vet_clinics vc ON va.clinic_id = vc.id
            WHERE va.pet_id = ?
            ORDER BY va.appointment_date DESC, va.appointment_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get services for each appointment
        foreach ($appointments as &$appointment) {
            $appointment['services'] = getAppointmentServices($conn, $appointment['id']);
        }
        
        echo json_encode(['success' => true, 'data' => $appointments]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific appointment with details
function getAppointment($conn, $id) {
    $sql = "SELECT va.*, p.name as pet_name, p.type as pet_type,
            u.first_name, u.last_name, u.email, u.phone,
            vc.name as clinic_name, vc.address, vc.phone as clinic_phone
            FROM vet_appointments va
            JOIN pets p ON va.pet_id = p.id
            JOIN users u ON p.owner_id = u.id
            JOIN vet_clinics vc ON va.clinic_id = vc.id
            WHERE va.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $appointment = $result->fetch_assoc();
        
        if ($appointment) {
            // Get services for this appointment
            $appointment['services'] = getAppointmentServices($conn, $id);
            
            echo json_encode(['success' => true, 'data' => $appointment]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Appointment not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Helper function to get services for an appointment
function getAppointmentServices($conn, $appointment_id) {
    $sql = "SELECT vas.service_id, cs.name, cs.price
            FROM vet_appointment_services vas
            JOIN clinic_services cs ON vas.service_id = cs.id
            WHERE vas.appointment_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return [];
}

// Create a new appointment
function createAppointment($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['pet_id']) || !isset($data['clinic_id']) || 
        !isset($data['appointment_date']) || !isset($data['appointment_time']) ||
        !isset($data['services']) || empty($data['services'])) {
        
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $pet_id = $data['pet_id'];
    $clinic_id = $data['clinic_id'];
    $appointment_date = $data['appointment_date'];
    $appointment_time = $data['appointment_time'];
    $reason = $data['reason'] ?? null;
    $services = $data['services'];
    
    // Validate pet exists
    $sql = "SELECT id FROM pets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pet not found']);
        return;
    }
    
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
    
    // Validate services exist and belong to the clinic
    foreach ($services as $service_id) {
        $sql = "SELECT id FROM clinic_services WHERE id = ? AND clinic_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $service_id, $clinic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid service ID: ' . $service_id]);
            return;
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert appointment
        $sql = "INSERT INTO vet_appointments (pet_id, clinic_id, appointment_date, appointment_time, reason, status)
                VALUES (?, ?, ?, ?, ?, 'scheduled')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $pet_id, $clinic_id, $appointment_date, $appointment_time, $reason);
        $stmt->execute();
        
        $appointment_id = $conn->insert_id;
        
        // Insert appointment services
        $sql = "INSERT INTO vet_appointment_services (appointment_id, service_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($services as $service_id) {
            $stmt->bind_param("ii", $appointment_id, $service_id);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment created successfully',
            'id' => $appointment_id
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create appointment: ' . $e->getMessage()]);
    }
}

// Update an existing appointment
function updateAppointment($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        return;
    }
    
    $id = $data['id'];
    
    // Check if appointment exists
    $sql = "SELECT * FROM vet_appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
        return;
    }
    
    $appointment = $result->fetch_assoc();
    
    // Prepare fields for update
    $pet_id = isset($data['pet_id']) ? $data['pet_id'] : $appointment['pet_id'];
    $clinic_id = isset($data['clinic_id']) ? $data['clinic_id'] : $appointment['clinic_id'];
    $appointment_date = isset($data['appointment_date']) ? $data['appointment_date'] : $appointment['appointment_date'];
    $appointment_time = isset($data['appointment_time']) ? $data['appointment_time'] : $appointment['appointment_time'];
    $reason = isset($data['reason']) ? $data['reason'] : $appointment['reason'];
    $status = isset($data['status']) ? $data['status'] : $appointment['status'];
    $notes = isset($data['notes']) ? $data['notes'] : $appointment['notes'];
    
    // Additional validations
    if (isset($data['pet_id'])) {
        $sql = "SELECT id FROM pets WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pet_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Pet not found']);
            return;
        }
    }
    
    if (isset($data['clinic_id'])) {
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
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update appointment
        $sql = "UPDATE vet_appointments SET 
                pet_id = ?, clinic_id = ?, appointment_date = ?, 
                appointment_time = ?, reason = ?, status = ?, notes = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssi", $pet_id, $clinic_id, $appointment_date,
                          $appointment_time, $reason, $status, $notes, $id);
        $stmt->execute();
        
        // Update services if provided
        if (isset($data['services'])) {
            $services = $data['services'];
            
            // Validate new services
            foreach ($services as $service_id) {
                $sql = "SELECT id FROM clinic_services WHERE id = ? AND clinic_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $service_id, $clinic_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Invalid service ID: ' . $service_id);
                }
            }
            
            // Delete existing services
            $sql = "DELETE FROM vet_appointment_services WHERE appointment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Insert new services
            $sql = "INSERT INTO vet_appointment_services (appointment_id, service_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($services as $service_id) {
                $stmt->bind_param("ii", $id, $service_id);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment updated successfully'
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update appointment: ' . $e->getMessage()]);
    }
}

// Delete an appointment
function deleteAppointment($conn, $id) {
    // Check if appointment exists
    $sql = "SELECT id FROM vet_appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Appointment not found']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete appointment services
        $sql = "DELETE FROM vet_appointment_services WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete appointment
        $sql = "DELETE FROM vet_appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete appointment: ' . $e->getMessage()]);
    }
}
?> 
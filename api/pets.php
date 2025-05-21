<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getPet($conn, $_GET['id']);
        } else if (isset($_GET['category'])) {
            getPetsByCategory($conn, $_GET['category']);
        } else {
            getAllPets($conn);
        }
        break;
    case 'POST':
        addPet($conn);
        break;
    case 'PUT':
        updatePet($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deletePet($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Pet ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all pets
function getAllPets($conn) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM pets p 
            LEFT JOIN pet_categories c ON p.category_id = c.id
            ORDER BY p.id";
    $result = $conn->query($sql);
    
    if ($result) {
        $pets = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $pets]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get pets by category
function getPetsByCategory($conn, $category) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM pets p 
            LEFT JOIN pet_categories c ON p.category_id = c.id
            WHERE c.name = ?
            ORDER BY p.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $pets = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $pets]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific pet
function getPet($conn, $id) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM pets p 
            LEFT JOIN pet_categories c ON p.category_id = c.id
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $pet = $result->fetch_assoc();
        if ($pet) {
            echo json_encode(['success' => true, 'data' => $pet]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Pet not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Add a new pet
function addPet($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['category_id']) || !isset($data['gender'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $name = $data['name'];
    $category_id = $data['category_id'];
    $breed = $data['breed'] ?? null;
    $age = $data['age'] ?? null;
    $gender = $data['gender'];
    $size = $data['size'] ?? null;
    $color = $data['color'] ?? null;
    $description = $data['description'] ?? null;
    $medical_history = $data['medical_history'] ?? null;
    $is_available = $data['is_available'] ?? true;
    $image_url = $data['image_url'] ?? null;
    
    $sql = "INSERT INTO pets (name, category_id, breed, age, gender, size, color, description, medical_history, is_available, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisssssssis", $name, $category_id, $breed, $age, $gender, $size, $color, $description, $medical_history, $is_available, $image_url);
    
    if ($stmt->execute()) {
        $pet_id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Pet added successfully', 'id' => $pet_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Update a pet
function updatePet($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Pet ID is required']);
        return;
    }
    
    $id = $data['id'];
    $name = $data['name'] ?? null;
    $category_id = $data['category_id'] ?? null;
    $breed = $data['breed'] ?? null;
    $age = $data['age'] ?? null;
    $gender = $data['gender'] ?? null;
    $size = $data['size'] ?? null;
    $color = $data['color'] ?? null;
    $description = $data['description'] ?? null;
    $medical_history = $data['medical_history'] ?? null;
    $is_available = $data['is_available'] ?? null;
    $image_url = $data['image_url'] ?? null;
    
    // Build update SQL dynamically based on provided fields
    $updates = [];
    $params = [];
    $types = "";
    
    if ($name !== null) {
        $updates[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }
    
    if ($category_id !== null) {
        $updates[] = "category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($breed !== null) {
        $updates[] = "breed = ?";
        $params[] = $breed;
        $types .= "s";
    }
    
    if ($age !== null) {
        $updates[] = "age = ?";
        $params[] = $age;
        $types .= "i";
    }
    
    if ($gender !== null) {
        $updates[] = "gender = ?";
        $params[] = $gender;
        $types .= "s";
    }
    
    if ($size !== null) {
        $updates[] = "size = ?";
        $params[] = $size;
        $types .= "s";
    }
    
    if ($color !== null) {
        $updates[] = "color = ?";
        $params[] = $color;
        $types .= "s";
    }
    
    if ($description !== null) {
        $updates[] = "description = ?";
        $params[] = $description;
        $types .= "s";
    }
    
    if ($medical_history !== null) {
        $updates[] = "medical_history = ?";
        $params[] = $medical_history;
        $types .= "s";
    }
    
    if ($is_available !== null) {
        $updates[] = "is_available = ?";
        $params[] = $is_available;
        $types .= "i";
    }
    
    if ($image_url !== null) {
        $updates[] = "image_url = ?";
        $params[] = $image_url;
        $types .= "s";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $sql = "UPDATE pets SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pet updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Delete a pet
function deletePet($conn, $id) {
    $sql = "DELETE FROM pets WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pet deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}
?> 
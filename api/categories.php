<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getCategory($conn, $_GET['id']);
        } else {
            getAllCategories($conn);
        }
        break;
    case 'POST':
        addCategory($conn);
        break;
    case 'PUT':
        updateCategory($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteCategory($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all categories
function getAllCategories($conn) {
    $sql = "SELECT * FROM pet_categories ORDER BY id";
    $result = $conn->query($sql);
    
    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $categories]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific category
function getCategory($conn, $id) {
    $sql = "SELECT * FROM pet_categories WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $category = $result->fetch_assoc();
        if ($category) {
            echo json_encode(['success' => true, 'data' => $category]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Add a new category
function addCategory($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required']);
        return;
    }
    
    $name = $data['name'];
    $description = $data['description'] ?? null;
    
    // Check if category already exists
    $sql = "SELECT id FROM pet_categories WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Category already exists']);
        return;
    }
    
    $sql = "INSERT INTO pet_categories (name, description) VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $description);
    
    if ($stmt->execute()) {
        $category_id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Category added successfully', 'id' => $category_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Update a category
function updateCategory($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        return;
    }
    
    $id = $data['id'];
    $name = $data['name'] ?? null;
    $description = $data['description'] ?? null;
    
    // Build update SQL dynamically based on provided fields
    $updates = [];
    $params = [];
    $types = "";
    
    if ($name !== null) {
        // Check if another category with the same name exists
        $sql = "SELECT id FROM pet_categories WHERE name = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Another category with this name already exists']);
            return;
        }
        
        $updates[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }
    
    if ($description !== null) {
        $updates[] = "description = ?";
        $params[] = $description;
        $types .= "s";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $sql = "UPDATE pet_categories SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Delete a category
function deleteCategory($conn, $id) {
    // Check if category has pets
    $sql = "SELECT COUNT(*) as count FROM pets WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete category with pets. Remove or reassign pets first.']);
        return;
    }
    
    $sql = "DELETE FROM pet_categories WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}
?> 
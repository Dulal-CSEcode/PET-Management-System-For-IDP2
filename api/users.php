<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request to the appropriate handler
switch($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getUser($conn, $_GET['id']);
        } else {
            getAllUsers($conn);
        }
        break;
    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] === 'login') {
            login($conn);
        } else if (isset($_GET['action']) && $_GET['action'] === 'register') {
            register($conn);
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            // Validate and insert order
            $stmt = $conn->prepare("INSERT INTO product_orders (user_id, pet_id, product_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $data['user_id'], $data['pet_id'], $data['product_id'], $data['quantity']);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
        break;
    case 'PUT':
        updateUser($conn);
        break;
    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteUser($conn, $_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Get all users
function getAllUsers($conn) {
    $sql = "SELECT id, name, email, phone, role, created_at FROM users ORDER BY id";
    $result = $conn->query($sql);
    
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Get a specific user
function getUser($conn, $id) {
    $sql = "SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $user = $result->fetch_assoc();
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// User login
function login($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }
    
    $email = $data['email'];
    $password = $data['password'];
    
    // Special case for admin login
    if ($email === 'admin@gmail.com' && $password === 'admin') {
        echo json_encode([
            'success' => true,
            'message' => 'Admin login successful',
            'user' => [
                'id' => 0,
                'name' => 'Administrator',
                'email' => 'admin@gmail.com',
                'role' => 'admin'
            ]
        ]);
        return;
    }
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $user = $result->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from response
            unset($user['password']);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// User registration
function register($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name, email and password are required']);
        return;
    }
    
    $name = $data['name'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $phone = $data['phone'] ?? null;
    $role = 'user'; // Default role is 'user'
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        return;
    }
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $password, $phone, $role);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user_id,
                'name' => $name,
                'email' => $email,
                'role' => $role
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Update user
function updateUser($conn) {
    // Get request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    $id = $data['id'];
    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    $password = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
    $phone = $data['phone'] ?? null;
    $role = $data['role'] ?? null;
    
    // Build update SQL dynamically based on provided fields
    $updates = [];
    $params = [];
    $types = "";
    
    if ($name !== null) {
        $updates[] = "name = ?";
        $params[] = $name;
        $types .= "s";
    }
    
    if ($email !== null) {
        // Check if email already exists for another user
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
        
        $updates[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }
    
    if ($password !== null) {
        $updates[] = "password = ?";
        $params[] = $password;
        $types .= "s";
    }
    
    if ($phone !== null) {
        $updates[] = "phone = ?";
        $params[] = $phone;
        $types .= "s";
    }
    
    if ($role !== null) {
        $updates[] = "role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

// Delete user
function deleteUser($conn, $id) {
    $sql = "DELETE FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}
?> 
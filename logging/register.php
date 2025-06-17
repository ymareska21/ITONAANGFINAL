<?php
session_start();
header('Content-Type: application/json');
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['registerName']) ? trim($_POST['registerName']) : '';
    $email = isset($_POST['registerEmail']) ? trim($_POST['registerEmail']) : '';
    $password = isset($_POST['registerPassword']) ? $_POST['registerPassword'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
 
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }
 
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }
 
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
 
    $conn = new mysqli('localhost', 'root', '', 'ordering');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }
 
    // Validation for full name
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_FN = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Fullname already registered.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
 
    // Validation for email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
 
    // Insert new user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (user_FN, user_email, user_password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $passwordHash);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed.']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
 
 
<?php
require_once '../database_connections/db_connect.php'; // <-- fixed path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $admin_password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($admin_username === '' || $admin_password === '') {
        echo "Username and password are required.";
        exit;
    }

    // Hash the password for security
    $passwordHash = password_hash($admin_password, PASSWORD_BCRYPT);

    // Insert into admin_users table
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin_username, $passwordHash);

    if ($stmt->execute()) {
        echo "Admin added successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    exit;
}

echo "Invalid request.";
?>
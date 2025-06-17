<?php
session_start();
header('Content-Type: application/json');
 
// Database connection
$servername = "localhost";
$username = "root";
$password = "";    
$dbname = "ordering";
 
$conn = new mysqli($servername, $username, $password, $dbname);
 
// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (!isset($_POST['fullname']) || !isset($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        $conn->close();
        exit;
    }
 
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];
 
    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admin_users WHERE username = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        $conn->close();
        exit;
    }
 
    $stmt->bind_param("s", $fullname);
    $stmt->execute();
    $stmt->store_result();
 
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $user_FN, $user_password);
        $stmt->fetch();
 
        if (password_verify($password, $user_password)) {
            $_SESSION['user'] = [
                'user_id' => $user_id,
                'user_FN' => $user_FN,
                'is_admin' => true
            ];
            echo json_encode([
                'success' => true,
                'redirect' => 'admin/admin.php',  
                'fullname' => $user_FN,
                'firstName' => explode(' ', $user_FN)[0],
                'initials' => strtoupper(substr($user_FN, 0, 1)),
                'user_id' => $user_id,
                'is_admin' => true
            ]);
 
            $stmt->close();
            $conn->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password for admin user.']);
            $stmt->close();
            $conn->close();
            exit;
        }
    } else {
        $stmt->close();  
 
        $stmt = $conn->prepare("SELECT user_id, user_FN, user_password FROM users WHERE user_FN = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            $conn->close();
            exit;
        }
 
        $stmt->bind_param("s", $fullname);
        $stmt->execute();
        $stmt->store_result();
 
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $user_FN, $user_password);
            $stmt->fetch();
 
            if (password_verify($password, $user_password)) {
       
                $_SESSION['user'] = [
                    'user_id' => $user_id,
                    'user_FN' => $user_FN,
                    'is_admin' => false
                ];
 
                echo json_encode([
                    'success' => true,
                    'fullname' => $user_FN,
                    'firstName' => explode(' ', $user_FN)[0],
                    'initials' => strtoupper(substr($user_FN, 0, 1)),
                    'user_id' => $user_id,
                    'is_admin' => false
                ]);
                $stmt->close();
                $conn->close();
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect password for regular user.']);
                $stmt->close();
                $conn->close();
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found in both admin and regular user tables.']);
            $conn->close();
            exit;
        }
    }
} else {
    // Invalid method
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    $conn->close();
    exit;
}
 
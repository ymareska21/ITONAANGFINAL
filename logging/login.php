<?php
session_start();
header('Content-Type: application/json');

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'ordering');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Full name and password required.']);
        exit;
    }

    // 1. Try admin login (admin_users table)
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
        $stmt->bind_result($admin_id, $admin_username, $admin_password);
        $stmt->fetch();

        if (password_verify($password, $admin_password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin'] = [
                'admin_id' => $admin_id,
                'username' => $admin_username
            ];
            echo json_encode([
                'success' => true,
                'is_admin' => true,
                'fullname' => $admin_username,
                'firstName' => explode(' ', $admin_username)[0],
                'initials' => strtoupper(substr($admin_username, 0, 1)),
                'admin_id' => $admin_id,
                'redirect' => 'admin/admin.php' 
            ]);
            $stmt->close();
            $conn->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            $stmt->close();
            $conn->close();
            exit;
        }
    }
    $stmt->close();

    // 2. Try user login (users table)
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
                'user_FN' => $user_FN
            ];
            echo json_encode([
                'success' => true,
                'fullname' => $user_FN,
                'firstName' => explode(' ', $user_FN)[0],
                'initials' => strtoupper(substr($user_FN, 0, 1)),
                'user_id' => $user_id
            ]);
            $stmt->close();
            $conn->close();
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            $stmt->close();
            $conn->close();
            exit;
        }
    }
    $stmt->close();

    // Not found in either table
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    $conn->close();
    exit;
}

// Invalid method
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
$conn->close();
exit;
exit;

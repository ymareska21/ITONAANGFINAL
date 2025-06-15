<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($name) && empty($email)) {
        echo json_encode(['exists' => false, 'message' => 'No data provided.']);
        exit;
    }

    $conn = new mysqli('localhost', 'root', '', 'ordering');
    if ($conn->connect_error) {
        echo json_encode(['exists' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    $exists = false;
    $field = '';
    $user_id = null;
    // Check fullname
    if (!empty($name)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_FN = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $exists = true;
            $field = 'fullname';
            $stmt->bind_result($user_id);
            $stmt->fetch();
        }
        $stmt->close();
    }
    // Check email
    if (!$exists && !empty($email)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $exists = true;
            $field = 'email';
            $stmt->bind_result($user_id);
            $stmt->fetch();
        }
        $stmt->close();
    }

    $conn->close();

    if ($exists) {
        echo json_encode([
            'exists' => true,
            'field' => $field,
            'user_id' => $user_id,
            'message' => ucfirst($field) . ' already exists.'
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit;
}

echo json_encode(['exists' => false, 'message' => 'Invalid request.']);
exit;

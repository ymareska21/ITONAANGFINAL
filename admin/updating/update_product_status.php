<?php
require_once '../database_connections/db_connect.php'; // <-- fixed path
if (isset($_POST['id'], $_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $stmt = $conn->prepare("UPDATE products SET status=? WHERE id=?");
    $stmt->bind_param("ss", $status, $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}
echo json_encode(['success' => false]);
exit();

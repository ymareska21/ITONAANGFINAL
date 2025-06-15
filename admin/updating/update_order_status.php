<?php
require_once '../database_connections/db_connect.php'; // <-- fixed path
if (isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE transaction SET status=? WHERE transac_id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}
header("Location: ../admin.php");
exit();
?>

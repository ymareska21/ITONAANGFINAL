<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Location
    if (isset($_POST['name']) && !isset($_POST['action'])) {
        $name = $_POST['name'];
        $status = isset($_POST['status']) ? $_POST['status'] : 'open';
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../img/';
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'img/' . $filename;
            }
        }
        $stmt = $conn->prepare("INSERT INTO locations (name, status, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $status, $imagePath);
        if ($stmt->execute()) {
            echo "Location added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        exit;
    }

    // Edit Location
    if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'], $_POST['name'], $_POST['status'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $status = $_POST['status'];
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../img/';
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'img/' . $filename;
            }
        }
        if ($imagePath) {
            $stmt = $conn->prepare("UPDATE locations SET name=?, status=?, image=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $status, $imagePath, $id);
        } else {
            $stmt = $conn->prepare("UPDATE locations SET name=?, status=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $status, $id);
        }
        if ($stmt->execute()) {
            echo "Location updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        exit;
    }

    // Delete Location
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM locations WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "Location deleted successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        exit;
    }

    // Toggle Status
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'], $_POST['status'])) {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE locations SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo "Location status updated.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        exit;
    }
}

echo "Invalid request.";
?>

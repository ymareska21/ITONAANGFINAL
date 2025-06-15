<?php
header('Content-Type: application/json');
session_start();

// Basic validation
$pickup_name = isset($_POST['pickup_name']) ? trim($_POST['pickup_name']) : '';
$pickup_location = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : '';
$pickup_time = isset($_POST['pickup_time']) ? trim($_POST['pickup_time']) : '';
$special_instructions = isset($_POST['special_instructions']) ? trim($_POST['special_instructions']) : '';
$cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];

if ($pickup_name === '' || $pickup_location === '' || $pickup_time === '' || empty($cart_items)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill out all required pickup details and have at least one item in your cart.'
    ]);
    exit;
}

// Convert pickup_time (HTML input type="time" gives "HH:MM") to full datetime for today
$date = date('Y-m-d');
$pickup_datetime = $date . ' ' . $pickup_time . ':00';

// Connect to database
$conn = new mysqli("localhost", "root", "", "ordering");
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit;
}

// Use session user ID if available, else 0
$user_id = isset($_SESSION['user']['user_id']) ? intval($_SESSION['user']['user_id']) : 0;
$total_amount = 0; // You can update this if you want to track order total
$status = 'pending';

// Insert into transaction (note: primary key is transac_id)
$stmt = $conn->prepare("INSERT INTO transaction (user_id, total_amount, status, created_at) VALUES (?, ?, ?, NOW())");
foreach ($cart_items as $item) {
    $total_amount += floatval($item['price']) * intval($item['quantity']);
}
$stmt->bind_param("ids", $user_id, $total_amount, $status);
if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create transaction.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$transaction_id = $conn->insert_id; // This is transac_id
$stmt->close();

// Generate reference number: CNC-YYYYMMDD-<transaction_id>
$reference_number = 'CNC-' . date('Ymd') . '-' . str_pad($transaction_id, 4, '0', STR_PAD_LEFT);

// Optionally, save it to the transaction table if you have a reference_number column
$conn->query("UPDATE transaction SET reference_number = '$reference_number' WHERE transac_id = $transaction_id");

// Insert into transaction_items
foreach ($cart_items as $item) {
    $product_id = $item['product_id']; // string
    $quantity = intval($item['quantity']);
    $size = isset($item['size']) ? $item['size'] : (preg_match('/\((.*?)\)$/', $item['name'], $m) ? $m[1] : '');
    $price = floatval($item['price']);
    $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, size, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isisd", $transaction_id, $product_id, $quantity, $size, $price);
    $stmt->execute();
    $stmt->close();
}

// Insert into pickup_detail (transaction_id, pickup_name, pickup_location, pickup_time, special_instructions)
$stmt = $conn->prepare("INSERT INTO pickup_detail (transaction_id, pickup_name, pickup_location, pickup_time, special_instructions) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $transaction_id, $pickup_name, $pickup_location, $pickup_datetime, $special_instructions);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Pickup order placed successfully.',
        'reference_number' => $reference_number // send formatted reference
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save pickup details. Please try again.'
    ]);
}

$stmt->close();
$conn->close();

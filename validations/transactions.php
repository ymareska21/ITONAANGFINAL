<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (
    !$data ||
    !isset($data['user_id']) ||
    !isset($data['items']) ||
    !isset($data['total']) ||
    !isset($data['method'])
) {
    echo json_encode(['success' => false, 'message' => 'Invalid data', 'debug' => $data]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "ordering");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$conn->begin_transaction();

try {
    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transaction (user_id, total_amount, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->bind_param("id", $data['user_id'], $data['total']);
    if (!$stmt->execute()) throw new Exception("Failed to insert transaction: " . $stmt->error);
    $transaction_id = $stmt->insert_id;
    $stmt->close();

    // Insert transaction items
    foreach ($data['items'] as $item) {
        $size = '';
        if (isset($item['size']) && $item['size']) {
            $size = $item['size'];
        } elseif (isset($item['name'])) {
            if (preg_match('/\((.*?)\)$/', $item['name'], $matches)) {
                $size = $matches[1];
            }
        }
        $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, size, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisd", $transaction_id, $item['id'], $item['quantity'], $size, $item['price']);
        if (!$stmt->execute()) throw new Exception("Failed to insert transaction item: " . $stmt->error);
        $stmt->close();
    }

    // Insert pickup or delivery details
    if ($data['method'] === 'pickup' && isset($data['pickupInfo'])) {
        $pickup = $data['pickupInfo'];
        $special = isset($pickup['special']) ? $pickup['special'] : '';
        $stmt = $conn->prepare("INSERT INTO pickup_detail (transaction_id, pickup_location, pickup_time, special_instructions) VALUES (?, ?, ?, ?)");
        $pickup_location = $pickup['name'] . " (" . $pickup['phone'] . ")";
        $stmt->bind_param("isss", $transaction_id, $pickup_location, $pickup['time'], $special);
        if (!$stmt->execute()) throw new Exception("Failed to insert pickup details: " . $stmt->error);
        $stmt->close();
    } elseif ($data['method'] === 'delivery' && isset($data['deliveryInfo'])) {
        $delivery = $data['deliveryInfo'];
        $stmt = $conn->prepare("INSERT INTO delivery_detail (transaction_id, recipient_name, total, phone, street, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isdsssss",
            $transaction_id,
            $delivery['name'],
            $data['total'],
            $delivery['phone'],
            $delivery['street'],
            $delivery['city'],
            $delivery['state'],
            $delivery['zip']
        );
        if (!$stmt->execute()) throw new Exception("Failed to insert delivery details: " . $stmt->error);
        $stmt->close();
    } else {
        throw new Exception("No valid pickup or delivery info provided.");
    }

    // Remove items from cart for this user
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $data['user_id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'transaction_id' => $transaction_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>
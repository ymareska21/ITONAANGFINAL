<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
$user_id = $_SESSION['user']['user_id'];
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid transaction ID.";
    exit;
}
$transac_id = intval($_GET['id']);

$conn = new mysqli("localhost", "root", "", "ordering");
if ($conn->connect_error) {
    die("Database connection failed");
}

// Fetch transaction details
$stmt = $conn->prepare("SELECT t.transac_id, t.created_at, t.total_amount, t.status
    FROM transaction t
    WHERE t.transac_id = ? AND t.user_id = ?");
$stmt->bind_param("ii", $transac_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Order not found or you do not have permission to view this order.";
    exit;
}
$order = $result->fetch_assoc();
$stmt->close();

// Fetch items for this order
$stmt2 = $conn->prepare("SELECT ti.quantity, ti.size, ti.price, p.name 
    FROM transaction_items ti 
    JOIN products p ON ti.product_id = p.id 
    WHERE ti.transaction_id = ?");
$stmt2->bind_param("i", $transac_id);
$stmt2->execute();
$items = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details - Cups & Cuddles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container my-5">
    <h2 class="mb-4">Order Details</h2>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Reference Number: <?php echo htmlspecialchars($order['transac_id']); ?></h5>
            <p class="card-text"><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
            <p class="card-text"><strong>Status:</strong>
                <?php
                $status = strtolower($order['status']);
                if ($status === 'ready') {
                    echo '<span class="badge bg-success">Ready</span>';
                } elseif ($status === 'pending') {
                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($status === 'preparing') {
                    echo '<span class="badge bg-info text-dark">Preparing</span>';
                } elseif ($status === 'picked up') {
                    echo '<span class="badge bg-secondary">Picked Up</span>';
                } elseif ($status === 'cancelled') {
                    echo '<span class="badge bg-danger">Cancelled</span>';
                } else {
                    echo htmlspecialchars(ucfirst($order['status']));
                }
                ?>
            </p>
            <p class="card-text"><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
        </div>
    </div>
    <h5>Items</h5>
    <ul class="list-group mb-4">
        <?php while ($item = $items->fetch_assoc()): ?>
            <li class="list-group-item">
                <?php echo htmlspecialchars($item['name']); ?>
                (<?php echo htmlspecialchars($item['size']); ?>) &times; <?php echo (int)$item['quantity']; ?>
                - ₱<?php echo number_format($item['price'], 2); ?>
            </li>
        <?php endwhile; ?>
    </ul>
    <a href="order_history.php" class="btn btn-secondary">Back to Order History</a>
</div>
</body>
</html>
<?php $stmt2->close(); $conn->close(); ?>

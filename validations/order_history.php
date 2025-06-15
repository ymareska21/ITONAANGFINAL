<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
$user_id = $_SESSION['user']['user_id'];

$conn = new mysqli("localhost", "root", "", "ordering");
if ($conn->connect_error) {
    die("Database connection failed");
}

// Fetch all orders for this user, most recent first
$sql = "SELECT t.transac_id, t.created_at, t.total_amount, t.status
        FROM transaction t
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

// Check if the latest order is 'ready'
$latest_ready = false;
if ($orders->num_rows > 0) {
    $latest_order = $orders->fetch_assoc();
    if ($latest_order['status'] === 'ready') {
        $latest_ready = true;
    }
    // Move pointer back for table loop
    $orders->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - Cups & Cuddles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container my-5">
    <h2 class="mb-4">Order History</h2>
    <?php if ($latest_ready): ?>
        <div class="alert alert-success" style="font-weight:bold;">
            Your latest order is <span style="color:#388e3c;">READY</span> for pickup!
        </div>
    <?php endif; ?>
    <?php if ($orders->num_rows === 0): ?>
        <div class="alert alert-info">No orders found.</div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Reference Number</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr class="order-row" style="cursor:pointer;" onclick="window.location.href='order_detail.php?id=<?php echo urlencode($order['transac_id']); ?>'">
                    <td><?php echo htmlspecialchars($order['transac_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                    <td>
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
                    </td>
                    <td>
                        <ul class="mb-0">
                        <?php
                        // Fetch items for this order (prepared statement)
                        $stmt2 = $conn->prepare("SELECT ti.quantity, ti.size, ti.price, p.name 
                            FROM transaction_items ti 
                            JOIN products p ON ti.product_id = p.id 
                            WHERE ti.transaction_id = ?");
                        $stmt2->bind_param("i", $order['transac_id']);
                        $stmt2->execute();
                        $items = $stmt2->get_result();
                        while ($item = $items->fetch_assoc()):
                        ?>
                            <li>
                                <?php echo htmlspecialchars($item['name']); ?>
                                (<?php echo htmlspecialchars($item['size']); ?>) &times; <?php echo (int)$item['quantity']; ?>
                                - ₱<?php echo number_format($item['price'], 2); ?>
                            </li>
                        <?php endwhile; $stmt2->close(); ?>
                        </ul>
                    </td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    <a href="../index.php" class="btn btn-secondary mt-3">Back to Home</a>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
</body>
</html>


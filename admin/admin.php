<?php
// Instead, just start the session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../admin/database_connections/db_connect.php';


// Fetch only 'picked up' orders for order history
function fetch_pickedup_orders() {
    global $conn;
    $orders = [];
    $sql = "SELECT t.transac_id, t.user_id, t.total_amount, t.status, t.created_at, u.user_FN AS customer_name
            FROM transaction t
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE t.status = 'picked up'
            ORDER BY t.created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("SQL Error: " . mysqli_error($conn));
    }
    while ($row = mysqli_fetch_assoc($result)) {
        // Fetch items for this transaction
        $stmt = $conn->prepare("SELECT ti.quantity, ti.size, ti.price, p.name 
            FROM transaction_items ti 
            JOIN products p ON ti.product_id = p.id 
            WHERE ti.transaction_id = ?");
        $stmt->bind_param("i", $row['transac_id']);
        $stmt->execute();
        $items = $stmt->get_result();
        $row['items'] = [];
        while ($item = $items->fetch_assoc()) {
            $row['items'][] = $item;
        }
        $stmt->close();
        $orders[] = $row;
    }
    return $orders;
}

// Fetch live orders for display (pending, preparing, ready)
function fetch_live_orders($status = '') {
    global $conn;
    $allowed_statuses = ['pending', 'preparing', 'ready'];
    $where = '';
    if ($status !== '' && in_array($status, $allowed_statuses)) {
        $where = "WHERE t.status = '" . mysqli_real_escape_string($conn, $status) . "'";
    } else {
        $where = "WHERE t.status IN ('pending','preparing','ready')";
    }
    $sql = "SELECT t.transac_id, t.user_id, t.total_amount, t.status, t.created_at, u.user_FN AS customer_name
            FROM transaction t
            LEFT JOIN users u ON t.user_id = u.user_id
            $where
            ORDER BY t.created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        die("SQL Error: " . mysqli_error($conn));
    }
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Fetch items for this transaction
        $stmt = $conn->prepare("SELECT ti.quantity, ti.size, ti.price, p.name 
            FROM transaction_items ti 
            JOIN products p ON ti.product_id = p.id 
            WHERE ti.transaction_id = ?");
        $stmt->bind_param("i", $row['transac_id']);
        $stmt->execute();
        $items = $stmt->get_result();
        $row['items'] = [];
        while ($item = $items->fetch_assoc()) {
            $row['items'][] = $item;
        }
        $stmt->close();
        $orders[] = $row;
    }
    return $orders;
}

// Get status filter from GET parameter for live orders
$live_status = isset($_GET['status']) ? $_GET['status'] : '';
$allowed_statuses = ['pending', 'preparing', 'ready'];

// Add this function to fetch all products with sales count
function fetch_products_with_sales() {
    global $conn;
    $products = [];
    $sql = "SELECT p.id, p.name, p.category, p.price, p.status, p.created_at,
                   COALESCE(SUM(ti.quantity), 0) AS sales
            FROM products p
            LEFT JOIN transaction_items ti ON p.id = ti.product_id
            GROUP BY p.id";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    return $products;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop Admin Dashboard</title>
    <link rel="stylesheet" href="./css/main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">

</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <span>Cups&Cuddles</span>
            </div>
            
            <nav class="nav-menu">
                <a href="#" class="nav-item" data-section="live-orders">
                    <span class="nav-icon">📊</span>
                    <span>Live Orders</span>
                </a>
                <a href="#" class="nav-item" data-section="order-history">
                    <span class="nav-icon">📜</span>
                    <span>Order History</span>
                </a>
                <a href="#" class="nav-item active" data-section="products">
                    <span class="nav-icon">☕</span>
                    <span>Products</span>
                </a>
                <a href="#" class="nav-item" data-section="active-location">
                    <span class="nav-icon">📦</span>
                    <span>Active Location</span>
                </a>
                <a href="#" class="nav-item" data-section="add-admin">
                    <span class="nav-icon">⚙️</span>
                    <span>Add admin</span>
                </a>
            </nav>
            
            <!-- Replace Busy Mode with Logout button -->
            <div class="sidebar-logout" style="padding: 15px 20px; border-top: 1px solid #eaedf0; margin-top: auto;">
                <form action="logout/admin_logout.php" method="post" style="display:block;">
                    <button type="submit" class="btn-secondary" style="width:100%;">Logout</button>
                </form>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header"></header>
            <!-- Page Content -->
            <div class="page-content">
                <!-- Order History Section -->
                <div id="order-history-section" class="content-section active">
                    <h1>Order History</h1>
                    
                    <!-- Tab Navigation -->
                    <div class="tabs">
                        <a href="#" class="tab active">Picked Up Orders</a>
                    </div>
                    
                    <!-- Orders Table (Dynamic from transactions) -->
                    <div class="table-container">
                    <table class="orders-table" border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; font-family: Arial, sans-serif; font-size: 14px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
    <thead style="background-color: #f9fafb; text-align: left;">
        <tr style="border-bottom: 2px solid #e5e7eb;">
            <th style="padding: 12px 16px; color: #111827;">Reference Number</th>
            <th style="padding: 12px 16px; color: #111827; text-align:center;">Item</th>
            <th style="padding: 12px 16px; color: #111827; text-align:center;">Quantity</th>
            <th style="padding: 12px 16px; color: #111827;">Customer</th>
            <th style="padding: 12px 16px; color: #111827;">Total</th>
            <th style="padding: 12px 16px; color: #111827;">Status</th>
        </tr>
    </thead>
    <tbody>

                                <?php
                                $orders = fetch_pickedup_orders();
                                if (empty($orders)) {
                                    echo '<tr><td colspan="6" style="text-align:center;">No picked up orders found.</td></tr>';
                                } else {
                                    foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['transac_id']) ?></td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <?php
                                            $itemLines = [];
                                            foreach ($order['items'] as $item) {
                                                $itemLines[] = htmlspecialchars($item['name']) . ' (' . htmlspecialchars($item['size']) . ') - ₱' . number_format($item['price'], 2);
                                            }
                                            echo implode('<br>', $itemLines);
                                            ?>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <?php
                                            $qtyLines = [];
                                            foreach ($order['items'] as $item) {
                                                $qtyLines[] = (int)$item['quantity'];
                                            }
                                            echo implode('<br>', $qtyLines);
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($order['status']) ?></td>
                                    </tr>
                                <?php endforeach;
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Live Orders Section -->
                <div id="live-orders-section" class="content-section">
                    <h1>Live Orders</h1>
                    
                    <div class="tabs" id="live-orders-tabs">
                        <a href="?status=" class="tab<?= $live_status === '' ? ' active' : '' ?>" data-status="">All Orders</a>
                        <a href="?status=preparing" class="tab<?= $live_status === 'preparing' ? ' active' : '' ?>" data-status="preparing">Preparing</a>
                        <a href="?status=ready" class="tab<?= $live_status === 'ready' ? ' active' : '' ?>" data-status="ready">Ready</a>
                        <a href="?status=pending" class="tab<?= $live_status === 'pending' ? ' active' : '' ?>" data-status="pending">Pending</a>
                    </div>

                    <div class="live-orders-grid">
                        <?php
                        $liveOrders = fetch_live_orders($live_status);
                        foreach ($liveOrders as $order): ?>
                        <div class="order-card <?= htmlspecialchars($order['status']) ?>">
                            <div class="order-header">
                                <span class="order-id">
                                    <strong>Reference Number:</strong> <?= htmlspecialchars($order['transac_id']) ?>
                                </span>
                                <span class="order-time"><?= htmlspecialchars($order['created_at']) ?></span>
                            </div>
                            <div class="customer-info">
                                <div>
                                    <h4><?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></h4>
                                    <p>₱<?= htmlspecialchars($order['total_amount']) ?></p>
                                </div>
                            </div>
                            <div class="order-items">
                                <ul class="mb-0">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <?= htmlspecialchars($item['name']) ?>
                                        (<?= htmlspecialchars($item['size']) ?>) &times; <?= (int)$item['quantity'] ?>
                                        - ₱<?= number_format($item['price'], 2) ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="order-actions">
                                <?php if ($order['status'] == 'pending'): ?>
                                    <form method="post" action="updating/update_order_status.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $order['transac_id'] ?>">
                                        <input type="hidden" name="status" value="preparing">
                                        <button type="submit" class="btn-accept">Accept</button>
                                    </form>
                                    <form method="post" action="updating/update_order_status.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $order['transac_id'] ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn-reject">Reject</button>
                                    </form>
                                <?php elseif ($order['status'] == 'preparing'): ?>
                                    <form method="post" action="updating/update_order_status.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $order['transac_id'] ?>">
                                        <input type="hidden" name="status" value="ready">
                                        <button type="submit" class="btn-ready">Mark as Ready</button>
                                    </form>
                                <?php elseif ($order['status'] == 'ready'): ?>
                                    <form method="post" action="updating/update_order_status.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $order['transac_id'] ?>">
                                        <input type="hidden" name="status" value="picked up">
                                        <button type="submit" class="btn-complete" style="background:#4caf50; color:#fff; padding:8px 12px; border-radius:4px;">Mark as Picked Up</button>
                                    </form>
                                <?php elseif ($order['status'] == 'picked up'): ?>
                                    <span class="btn-complete" style="background:#4caf50; color:#fff; padding:8px 12px; border-radius:4px;">Picked Up</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($liveOrders)): ?>
                            <div style="padding:30px;text-align:center;color:#888;">No live orders.</div>
                        <?php endif; ?>
                    </div>
                </div>

              
                <!-- Products Section -->
                <div id="products-section" class="content-section active">
                    <h1>Products Management</h1>
                    
                    <!-- Add Product Button (outside modal) -->
                    <button id="showAddProductModalBtn" class="btn-primary" style="margin: 20px;">+ Add Product</button>
                    <div class="tabs">
                        <a href="#" class="tab active">All Products</a>
                    </div>
                    
                    <div class="table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Sales</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products = fetch_products_with_sales();
                                foreach ($products as $product): ?>
                                <tr data-product-id="<?= htmlspecialchars($product['id']) ?>"
                                    data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-product-category="<?= htmlspecialchars($product['category']) ?>"
                                    data-product-price="<?= htmlspecialchars($product['price']) ?>"
                                    data-product-status="<?= htmlspecialchars($product['status']) ?>">
                                    <td><?= htmlspecialchars($product['id']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td class="<?= $product['status'] === 'active' ? 'stock-good' : 'stock-out' ?>">
                                        <?= $product['status'] === 'active' ? 'In Stock' : 'Out of Stock' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $product['status'] === 'active' ? 'active' : 'inactive' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= (int)$product['sales'] ?></td>
                                    <td>
  <div class="action-menu" style="position: relative;">
    <button class="action-btn" type="button"
      style="background-color: #f3f4f6; border: none; border-radius: 50%; width: 36px; height: 36px; font-size: 20px; cursor: pointer;">
      ⋮
    </button>

    <div class="dropdown-menu"
      style="display: none; position: absolute; z-index: 10; left: -160px; top: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 10px 16px; display: flex; flex-direction: row; gap: 12px; min-width: 150px;">
      
      <button type="button" class="menu-item edit-product-btn"
        style="flex: 1; padding: 10px 16px; background: none; border: none; font-size: 14px; color: #374151; cursor: pointer; white-space: nowrap;">
        Edit
      </button>

      <button type="button" class="menu-item toggle-status-btn"
        style="flex: 1; padding: 10px 16px; background: none; border: none; font-size: 14px; color: #2563eb; cursor: pointer; white-space: nowrap;">
        Set <?= $product['status'] === 'active' ? 'Inactive' : 'Active' ?>
      </button>

    </div>
  </div>
</td>




                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                   
                   <!-- Add Product Modal -->
<div id="addProductModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,0.15);">
    <div class="modal-content" style="background:#ffffff;padding:32px 28px;border-radius:20px;max-width:440px;width:100%;position:relative;box-shadow:0 8px 24px rgba(0,0,0,0.1);">
        <button id="closeAddProductModal" type="button" style="position:absolute;top:18px;right:18px;font-size:1.5rem;background:none;border:none;color:#555;cursor:pointer;">&times;</button>
        <h2 style="margin-bottom:24px;font-size:1.5rem;font-weight:600;color:#222;">Add New Product</h2>
        <form id="addProductForm" enctype="multipart/form-data" method="post" action="adding/add_products.php">
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Product ID</label>
                <input type="text" name="id" required class="form-control" placeholder="Unique ID" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Name</label>
                <input type="text" name="name" required class="form-control" placeholder="Product Name" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Description</label>
                <textarea name="description" required class="form-control" placeholder="Description" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;resize:vertical;"></textarea>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Price</label>
                <input type="number" name="price" step="0.01" required class="form-control" placeholder="Price" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Category</label>
                <input type="text" name="category" required class="form-control" placeholder="Category" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Image</label>
                <input type="file" name="image" accept="image/*" required class="form-control" style="width:100%;padding:8px 0;border:none;">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#333;">Status</label>
                <select name="status" class="form-control" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:12px 0;border:none;border-radius:10px;background:#059669;color:white;font-weight:600;font-size:1rem;cursor:pointer;">Add Product</button>
        </form>
        <div id="addProductResult" style="margin-top:14px;color:#059669;font-weight:600;font-size:0.95rem;"></div>
    </div>
</div>


                 <!-- Edit Product Modal -->
<div id="editProductModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,0.15);">
    <div class="modal-content" style="background:#fff;padding:28px 24px;border-radius:20px;max-width:420px;width:100%;position:relative;box-shadow:0 4px 18px rgba(0,0,0,0.1);">
        <button id="closeEditProductModal" type="button" style="position:absolute;top:16px;right:16px;font-size:1.5rem;background:none;border:none;cursor:pointer;color:#555;">&times;</button>
        <h2 style="margin-bottom:20px;font-size:1.25rem;font-weight:600;color:#333;">Edit Product</h2>
        <form id="editProductForm" method="post">
            <input type="hidden" name="product_id" id="editProductId">
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block;margin-bottom:6px;font-size:0.95rem;color:#555;">Name</label>
                <input type="text" name="new_name" id="editProductName" required class="form-control" placeholder="Product Name" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block;margin-bottom:6px;font-size:0.95rem;color:#555;">Price</label>
                <input type="number" name="new_price" id="editProductPrice" step="0.01" required class="form-control" placeholder="Price" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label style="display:block;margin-bottom:6px;font-size:0.95rem;color:#555;">Category</label>
                <input type="text" name="new_category" id="editProductCategory" required class="form-control" placeholder="Category" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:10px;font-size:0.95rem;">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;background:#059669;color:#fff;padding:12px 0;border:none;border-radius:10px;font-size:1rem;cursor:pointer;font-weight:600;">Save Changes</button>
        </form>
        <div id="editProductResult" style="margin-top:14px;color:#059669;font-weight:600;font-size:0.95rem;"></div>
    </div>
</div>
                                </div>

                <!-- Active Location Section -->
                <div id="active-location-section" class="content-section">
                    <h1>Active Locations</h1>
                    <div class="page-header">
                        <button id="showAddLocationModalBtn" class="btn-primary" style="margin: 20px;">+ Add Location</button>
                    </div>
                    <div class="tabs">
                        <a href="#" class="tab active">All Locations</a>
                    </div>
                    <div class="table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Location Name</th>
                                    <th>Status</th>
                                    <th>Image</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="locationsTableBody">
                                <?php
                                // Fetch locations from DB (with image)
                                $locations = [];
                                $res = $conn->query("SELECT * FROM locations ORDER BY id DESC");
                                while ($row = $res->fetch_assoc()) $locations[] = $row;
                                foreach ($locations as $loc): ?>
                                <tr data-location-id="<?= $loc['id'] ?>"
                                    data-location-name="<?= htmlspecialchars($loc['name']) ?>"
                                    data-location-status="<?= htmlspecialchars($loc['status']) ?>">
                                    <td><?= htmlspecialchars($loc['name']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $loc['status'] === 'open' ? 'active' : 'inactive' ?>">
                                            <?= ucfirst($loc['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($loc['image'])): ?>
                                            <img src="<?= htmlspecialchars($loc['image']) ?>" alt="Location Image" style="width:60px;height:40px;object-fit:cover;border-radius:6px;">
                                        <?php else: ?>
                                            <span style="color:#aaa;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                <div class="action-menu" style="position: relative; display: inline-block;">
                                    <button class="action-btn" type="button"
                                        style="background-color: #f3f4f6; border: none; border-radius: 50%; width: 36px; height: 36px; font-size: 20px; cursor: pointer; transition: background 0.3s;">
                                        ⋮
                                    </button>
                                    <div class="dropdown-menu"
                                        style="display: none; position: absolute; z-index: 10; top: 0; right: 100%; background: white; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; padding: 8px; display: flex; flex-direction: row; gap: 8px;">
                                        
                                        <button type="button" class="menu-item edit-location-btn"
                                            style="padding: 10px 16px; background: none; border: none; font-size: 14px; color: #374151; cursor: pointer;">
                                            Edit
                                        </button>
                                        
                                        <button type="button" class="menu-item delete-location-btn"
                                            style="padding: 10px 16px; background: none; border: none; font-size: 14px; color: #ef4444; cursor: pointer;">
                                            Delete
                                        </button>
                                        
                                        <button type="button" class="menu-item toggle-location-status-btn"
                                            style="padding: 10px 16px; background: none; border: none; font-size: 14px; color: #2563eb; cursor: pointer;">
                                            Set <?= $loc['status'] === 'open' ? 'Closed' : 'Open' ?>
                                        </button>
                                    </div>
                                </div>
                            </td>


                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                   <!-- Add Location Modal -->
<div id="addLocationModal" class="modal" style="display:none;">
    <div class="modal-content" style="background:#f9fafb;padding:36px 32px;border-radius:20px;max-width:460px;width:100%;position:relative;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <button id="closeAddLocationModal" type="button" style="position:absolute;top:16px;right:16px;font-size:1.5rem;background:none;border:none;cursor:pointer;color:#6b7280;">&times;</button>
        <h2 style="margin-bottom:24px;font-size:1.4rem;color:#111827;">Add New Location</h2>
        <form id="addLocationForm" method="post" action="database_connections/locations.php" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Location Name</label>
                <input type="text" name="name" required class="form-control" placeholder="Location Name" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Image</label>
                <input type="file" name="image" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Status</label>
                <select name="status" class="form-control" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="open" selected>Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:12px;background-color:#059669;color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;">Add Location</button>
        </form>
        <div id="addLocationResult" style="margin-top:14px;color:#10b981;font-weight:600;"></div>
    </div>
</div>

<!-- Edit Location Modal -->
<div id="editLocationModal" class="modal" style="display:none;">
    <div class="modal-content" style="background:#f9fafb;padding:36px 32px;border-radius:20px;max-width:460px;width:100%;position:relative;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <button id="closeEditLocationModal" type="button" style="position:absolute;top:16px;right:16px;font-size:1.5rem;background:none;border:none;cursor:pointer;color:#6b7280;">&times;</button>
        <h2 style="margin-bottom:24px;font-size:1.4rem;color:#111827;">Edit Location</h2>
        <form id="editLocationForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editLocationId">
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Location Name</label>
                <input type="text" name="name" id="editLocationName" required class="form-control" placeholder="Location Name" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Image (leave blank to keep current)</label>
                <input type="file" name="image" accept="image/*" class="form-control" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Status</label>
                <select name="status" id="editLocationStatus" class="form-control" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:12px;background-color:#059669;color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;">Save Changes</button>
        </form>
        <div id="editLocationResult" style="margin-top:14px;color:#10b981;font-weight:600;"></div>
    </div>
</div>
</div>

               <!-- Add Admin Section -->
<div id="add-admin-section" class="content-section">
    <h1 style="text-align:center;margin-bottom:24px;">Add Admin</h1>
    <div class="table-container" style="max-width:420px;margin:auto;">
        <form id="addAdminForm" method="post" action="adding/add_admin.php" 
              style="background:#fff;padding:32px 28px;border-radius:18px;box-shadow:0 4px 12px rgba(0,0,0,0.06);">
            <div class="form-group" style="margin-bottom:16px;">
                <label for="adminUsername" style="display:block;font-weight:600;margin-bottom:6px;">Username</label>
                <input type="text" name="username" id="adminUsername" required 
                       class="form-control" placeholder="Enter username" 
                       style="width:100%;padding:10px 14px;border:1px solid #ccc;border-radius:8px;">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="adminPassword" style="display:block;font-weight:600;margin-bottom:6px;">Password</label>
                <input type="password" name="password" id="adminPassword" required 
                       class="form-control" placeholder="Enter password" 
                       style="width:100%;padding:10px 14px;border:1px solid #ccc;border-radius:8px;">
            </div>
            <button type="submit" class="btn-primary" 
                    style="width:100%;background-color:#059669;color:#fff;padding:12px;border:none;border-radius:10px;font-weight:600;cursor:pointer;">
                Add Admin
            </button>
            <div id="addAdminResult" style="margin-top:12px;color:#059669;font-weight:600;"></div>
        </form>
    </div>
</div>

    <script>
  
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            menu.style.display = "none";
        });
    });

    // Toggle status via AJAX
    document.querySelectorAll('.toggle-status-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var row = btn.closest('tr');
            var productId = row.getAttribute('data-product-id');
            var currentStatus = row.getAttribute('data-product-status');
            var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            fetch('updating/update_product_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(productId) + '&status=' + encodeURIComponent(newStatus)
            }).then(() => {
                location.reload();
            });
        });
    });

    // Edit product modal logic
    const editModal = document.getElementById('editProductModal');
    const editForm = document.getElementById('editProductForm');
    const closeEditModalBtn = document.getElementById('closeEditProductModal');
    if (editModal && editForm && closeEditModalBtn) {
        document.querySelectorAll('.edit-product-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr');
                document.getElementById('editProductId').value = row.getAttribute('data-product-id');
                document.getElementById('editProductName').value = row.getAttribute('data-product-name');
                document.getElementById('editProductPrice').value = row.getAttribute('data-product-price');
                document.getElementById('editProductCategory').value = row.getAttribute('data-product-category');
                editModal.style.display = 'flex';
            });
        });
        closeEditModalBtn.onclick = function() {
            editModal.style.display = 'none';
        };
        editForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            fetch('updating/update_product.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            }).then(() => {
                editModal.style.display = 'none';
                location.reload();
            });
        };
    }

    // Add Product Modal logic
    document.addEventListener("DOMContentLoaded", function() {
        // Only run this once!
        var addProductModal = document.getElementById('addProductModal');
        var showAddProductModalBtn = document.getElementById('showAddProductModalBtn');
        var closeAddProductModal = document.getElementById('closeAddProductModal');
        var addProductForm = document.getElementById('addProductForm');
        var addProductResult = document.getElementById('addProductResult');

        // Defensive: check if elements exist
        if (showAddProductModalBtn && addProductModal) {
            showAddProductModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addProductModal.style.display = 'flex';
            });
        }
        if (closeAddProductModal && addProductModal) {
            closeAddProductModal.addEventListener('click', function(e) {
                e.preventDefault();
                addProductModal.style.display = 'none';
                if (addProductResult) addProductResult.textContent = '';
                if (addProductForm) addProductForm.reset();
            });
        }
        if (addProductModal) {
            addProductModal.addEventListener('click', function(e) {
                if (e.target === addProductModal) {
                    addProductModal.style.display = 'none';
                    if (addProductResult) addProductResult.textContent = '';
                    if (addProductForm) addProductForm.reset();
                }
            });
        }
        if (addProductForm) {
            addProductForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (addProductResult) addProductResult.textContent = '';
                const formData = new FormData(addProductForm);
                fetch('adding/add_products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (addProductResult) addProductResult.textContent = text;
                    if (text.toLowerCase().includes('success')) {
                        setTimeout(() => {
                            addProductModal.style.display = 'none';
                            addProductForm.reset();
                            location.reload();
                        }, 1200);
                    }
                })
                .catch(() => {
                    if (addProductResult) addProductResult.textContent = 'Failed to add product.';
                });
            });
        }
    });

    // Location Modal Logic
    document.addEventListener("DOMContentLoaded", function() {
        // Add Location Modal
        var addLocationModal = document.getElementById('addLocationModal');
        var showAddLocationModalBtn = document.getElementById('showAddLocationModalBtn');
        var closeAddLocationModal = document.getElementById('closeAddLocationModal');
        var addLocationForm = document.getElementById('addLocationForm');
        var addLocationResult = document.getElementById('addLocationResult');

        if (showAddLocationModalBtn && addLocationModal) {
            showAddLocationModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addLocationModal.style.display = 'flex';
            });
        }
        if (closeAddLocationModal && addLocationModal) {
            closeAddLocationModal.addEventListener('click', function(e) {
                e.preventDefault();
                addLocationModal.style.display = 'none';
                if (addLocationResult) addLocationResult.textContent = '';
                if (addLocationForm) addLocationForm.reset();
            });
        }
        if (addLocationModal) {
            addLocationModal.addEventListener('click', function(e) {
                if (e.target === addLocationModal) {
                    addLocationModal.style.display = 'none';
                    if (addLocationResult) addLocationResult.textContent = '';
                    if (addLocationForm) addLocationForm.reset();
                }
            });
        }
        if (addLocationForm) {
            addLocationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (addLocationResult) addLocationResult.textContent = '';
                const formData = new FormData(addLocationForm);
                fetch('database_connections/locations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (addLocationResult) addLocationResult.textContent = text;
                    if (text.toLowerCase().includes('success')) {
                        setTimeout(() => {
                            addLocationModal.style.display = 'none';
                            addLocationForm.reset();
                            location.reload();
                        }, 1200);
                    }
                })
                .catch(() => {
                    if (addLocationResult) addLocationResult.textContent = 'Failed to add location.';
                });
            });
        }

        // Edit Location Modal
        var editLocationModal = document.getElementById('editLocationModal');
        var editLocationForm = document.getElementById('editLocationForm');
        var closeEditLocationModal = document.getElementById('closeEditLocationModal');
        var editLocationResult = document.getElementById('editLocationResult');

        document.querySelectorAll('.edit-location-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr');
                document.getElementById('editLocationId').value = row.getAttribute('data-location-id');
                document.getElementById('editLocationName').value = row.getAttribute('data-location-name');
                document.getElementById('editLocationStatus').value = row.getAttribute('data-location-status');
                document.getElementById('editLocationModal').style.display = 'flex';
            });
        });
        if (closeEditLocationModal && editLocationModal) {
            closeEditLocationModal.addEventListener('click', function(e) {
                e.preventDefault();
                editLocationModal.style.display = 'none';
                if (editLocationResult) editLocationResult.textContent = '';
                if (editLocationForm) editLocationForm.reset();
            });
        }
        if (editLocationModal) {
            editLocationModal.addEventListener('click', function(e) {
                if (e.target === editLocationModal) {
                    editLocationModal.style.display = 'none';
                    if (editLocationResult) editLocationResult.textContent = '';
                    if (editLocationForm) editLocationForm.reset();
                }
            });
        }
        if (editLocationForm) {
            editLocationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (editLocationResult) editLocationResult.textContent = '';
                const formData = new FormData(editLocationForm);
                formData.append('action', 'edit');
                fetch('database_connections/locations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (editLocationResult) editLocationResult.textContent = text;
                    if (text.toLowerCase().includes('success')) {
                        setTimeout(() => {
                            editLocationModal.style.display = 'none';
                            editLocationForm.reset();
                            location.reload();
                        }, 1200);
                    }
                })
                .catch(() => {
                    if (editLocationResult) editLocationResult.textContent = 'Failed to update location.';
                });
            });
        }

        // Delete Location
        document.querySelectorAll('.delete-location-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!confirm('Are you sure you want to delete this location?')) return;
                var row = btn.closest('tr');
                var id = row.getAttribute('data-location-id');
                fetch('database_connections/locations.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=delete&id=' + encodeURIComponent(id)
                })
                .then(res => res.text())
                .then(() => location.reload());
            });
        });

        // Toggle Location Status
        document.querySelectorAll('.toggle-location-status-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr');
                var id = row.getAttribute('data-location-id');
                var currentStatus = row.getAttribute('data-location-status');
                var newStatus = currentStatus === 'open' ? 'closed' : 'open';
                fetch('database_connections/locations.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=toggle_status&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(newStatus)
                })
                .then(res => res.text())
                .then(() => location.reload());
            });
        });
    });

    // Add Admin Modal logic (AJAX, no page reload)
    document.addEventListener("DOMContentLoaded", function() {
        var addAdminForm = document.getElementById('addAdminForm');
        var addAdminResult = document.getElementById('addAdminResult');
        if (addAdminForm) {
            addAdminForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (addAdminResult) addAdminResult.textContent = '';
                const formData = new FormData(addAdminForm);
                fetch('adding/add_admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (addAdminResult) addAdminResult.textContent = text;
                    if (text.toLowerCase().includes('success')) {
                        addAdminForm.reset();
                    }
                })
                .catch(() => {
                    if (addAdminResult) addAdminResult.textContent = 'Failed to add admin.';
                });
            });
        }
    });
    </script>
    <script src="js/main.js"></script>
</body>
</html>
 

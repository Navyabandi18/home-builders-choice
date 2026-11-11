
<?php
session_start();
require_once "../config/db.php";

// Check whether admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? "Admin";

// Get total number of products
$result = $conn->query("SELECT COUNT(*) AS total FROM products");
$product_count = (int)$result->fetch_assoc()['total'];

// Get total number of orders
$result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$order_count = (int)$result->fetch_assoc()['total'];

// Get pending orders
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'Pending'"
);
$pending_count = (int)$result->fetch_assoc()['total'];

// Get recent orders
$recent_orders = $conn->query(
    "SELECT o.order_id, o.order_date, o.order_status,
            o.payment_status, o.subtotal, c.full_name
     FROM orders o
     JOIN customers c ON o.customer_id = c.customer_id
     ORDER BY o.order_id DESC
     LIMIT 5"
);

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .admin-dashboard {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 25px 0;
        }

        .dashboard-card {
            flex: 1;
            min-width: 180px;
            padding: 20px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .dashboard-card h3 {
            margin-top: 0;
        }

        .dashboard-card p {
            font-size: 26px;
            font-weight: bold;
        }

        .admin-options {
            margin: 25px 0;
        }

        .admin-options a {
            display: inline-block;
            margin: 8px;
            padding: 10px 16px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .recent-orders {
            overflow-x: auto;
            margin-top: 25px;
        }

        .recent-orders table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .recent-orders th,
        .recent-orders td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .recent-orders th {
            background: #eee;
        }

        .no-orders {
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<div class="admin-dashboard">

    <h2>Admin Dashboard</h2>

    <h3>
        Welcome, <?php echo e($admin_username); ?>!
    </h3>

    <p>You have successfully logged in as administrator.</p>

    <hr>

    <h3>Dashboard Overview</h3>

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Total Products</h3>
            <p><?php echo $product_count; ?></p>
        </div>

        <div class="dashboard-card">
            <h3>Total Orders</h3>
            <p><?php echo $order_count; ?></p>
        </div>

        <div class="dashboard-card">
            <h3>Pending Orders</h3>
            <p><?php echo $pending_count; ?></p>
        </div>

    </div>

    <hr>

    <h3>Admin Options</h3>

    <div class="admin-options">

        <a href="manage_products.php">Manage Products</a>

        <a href="manage_orders.php">Manage Orders</a>

        <a href="../index.php">Visit Website</a>

        <a href="manage_messages.php">Manage Contact Messages</a>

        <a href="logout.php">Logout</a>



    </div>

    <hr>

    <h3>Recent Customer Orders</h3>

    <?php if ($recent_orders && $recent_orders->num_rows > 0) { ?>

        <div class="recent-orders">

            <table>

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($order = $recent_orders->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                #<?php echo (int)$order['order_id']; ?>
                            </td>

                            <td>
                                <?php echo e($order['full_name']); ?>
                            </td>

                            <td>
                                <?php echo e($order['order_date']); ?>
                            </td>

                            <td>
                                <?php echo e($order['order_status']); ?>
                            </td>

                            <td>
                                <?php echo e($order['payment_status']); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$order['subtotal'], 2); ?>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    <?php } else { ?>

        <div class="no-orders">
            <p>No customer orders have been placed yet.</p>
        </div>

    <?php } ?>

</div>

</body>
</html>

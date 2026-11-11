
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all customer orders
$sql = "SELECT
            orders.order_id,
            orders.order_date,
            orders.subtotal,
            orders.advance_amount,
            orders.balance_amount,
            orders.payment_status,
            orders.order_status,
            customers.full_name,
            customers.email,
            customers.phone
        FROM orders
        INNER JOIN customers
        ON orders.customer_id = customers.customer_id
        ORDER BY orders.order_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error fetching orders: " . $conn->error);
}

// Safe output function
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #f4f6f9;
        }

        .orders-container {
            width: 94%;
            max-width: 1450px;
            margin: 35px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .orders-header h2 {
            margin: 0;
            color: #222;
        }

        .orders-description {
            color: #666;
            margin-bottom: 20px;
        }

        .orders-count {
            background: #eaf1ff;
            color: #1d4ed8;
            padding: 9px 15px;
            border-radius: 6px;
            font-weight: bold;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .orders-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
            background: white;
        }

        .orders-table th {
            background: #1f2937;
            color: white;
            padding: 14px 12px;
            text-align: left;
            white-space: nowrap;
            font-size: 14px;
        }

        .orders-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            white-space: nowrap;
        }

        .orders-table tbody tr:hover {
            background: #f8fafc;
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-other {
            background: #e5e7eb;
            color: #374151;
        }

        .view-button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 8px 13px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .view-button:hover {
            background: #1d4ed8;
        }

        .back-button {
            display: inline-block;
            background: #374151;
            color: white;
            padding: 11px 18px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }

        .back-button:hover {
            background: #1f2937;
        }

        .empty-message {
            padding: 30px;
            text-align: center;
            color: #666;
            background: #f9fafb;
            border-radius: 8px;
            margin-top: 20px;
        }

        @media (max-width: 600px) {
            .orders-container {
                width: 96%;
                padding: 15px;
                margin: 15px auto;
            }

            .orders-header {
                align-items: flex-start;
            }

            .orders-header h2 {
                font-size: 21px;
            }
        }
    </style>

</head>

<body>

<div class="orders-container">

    <div class="orders-header">

        <h2>Manage Customer Orders</h2>

        <span class="orders-count">
            Total Orders: <?php echo (int)$result->num_rows; ?>
        </span>

    </div>

    <p class="orders-description">
        View customer bookings, contact details, rental amounts and payment status.
    </p>

    <hr>

    <?php if ($result->num_rows > 0) { ?>

        <div class="table-wrapper">

            <table class="orders-table">

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Total Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($order = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <strong>
                                    #<?php echo (int)$order['order_id']; ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo e($order['order_date']); ?>
                            </td>

                            <td>
                                <?php echo e($order['full_name']); ?>
                            </td>

                            <td>
                                <?php echo e($order['email']); ?>
                            </td>

                            <td>
                                <?php echo e($order['phone']); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$order['subtotal'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$order['advance_amount'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float)$order['balance_amount'], 2); ?>
                            </td>

                            <td>
                                <?php
                                $order_status = strtolower($order['order_status']);
                                $order_class = $order_status === 'pending'
                                    ? 'status-pending'
                                    : 'status-other';
                                ?>

                                <span class="status <?php echo $order_class; ?>">
                                    <?php echo e($order['order_status']); ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                $payment_status = strtolower($order['payment_status']);
                                $payment_class = $payment_status === 'paid'
                                    ? 'status-paid'
                                    : ($payment_status === 'pending'
                                        ? 'status-pending'
                                        : 'status-other');
                                ?>

                                <span class="status <?php echo $payment_class; ?>">
                                    <?php echo e($order['payment_status']); ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="view_order.php?order_id=<?php echo (int)$order['order_id']; ?>"
                                    class="view-button"
                                >
                                    View Details
                                </a>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    <?php } else { ?>

        <div class="empty-message">
            <h3>No Orders Found</h3>
            <p>No customer orders have been placed yet.</p>
        </div>

    <?php } ?>

    <a href="dashboard.php" class="back-button">
        Back to Dashboard
    </a>

</div>

</body>
</html>

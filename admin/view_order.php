
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Validate order ID
if (!isset($_GET['order_id']) || !ctype_digit($_GET['order_id'])) {
    die("Invalid order ID.");
}

$order_id = (int) $_GET['order_id'];

// Available order statuses
$allowed_statuses = [
    'Pending',
    'Confirmed',
    'Ready for Pickup',
    'Out for Rental',
    'Returned',
    'Cancelled'
];

$success_message = "";
$error_message = "";

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_status = $_POST['order_status'] ?? '';

    if (in_array($new_status, $allowed_statuses, true)) {

        $update_sql = "UPDATE orders
                       SET order_status = ?
                       WHERE order_id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $order_id);

        if ($update_stmt->execute()) {
            $success_message = "Order status updated successfully!";
        } else {
            $error_message = "Unable to update order status.";
        }

        $update_stmt->close();

    } else {
        $error_message = "Invalid order status selected.";
    }
}

// Fetch order and customer details
$sql = "SELECT
            orders.*,
            customers.full_name,
            customers.email,
            customers.phone
        FROM orders
        INNER JOIN customers
        ON orders.customer_id = customers.customer_id
        WHERE orders.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch rented products
$sql = "SELECT
            product_name,
            rental_type,
            duration,
            rental_start_date,
            rental_end_date,
            unit_price,
            quantity,
            line_total
        FROM order_items
        WHERE order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            background: #f4f6f9;
        }

        .order-details-container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .page-heading {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .section {
            width: 100%;
            margin: 25px 0;
            padding: 20px;
            background: #fafafa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
        }

        .section h3 {
            margin-top: 0;
            margin-bottom: 18px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .detail-item {
            padding: 12px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 6px;
            overflow-wrap: anywhere;
        }

        .detail-item strong {
            display: block;
            margin-bottom: 6px;
            color: #555;
        }

        .status-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .status-form label {
            font-weight: bold;
        }

        .status-form select {
            min-width: 220px;
            max-width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .update-btn {
            padding: 11px 20px;
            background: #2878c7;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .update-btn:hover {
            background: #1d5f9e;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .products-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            background: white;
        }

        .products-table th,
        .products-table td {
            padding: 13px 12px;
            border: 1px solid #ddd;
            text-align: center;
            white-space: nowrap;
        }

        .products-table th {
            background: #2878c7;
            color: white;
        }

        .products-table tr:nth-child(even) {
            background: #f7f7f7;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .payment-card {
            padding: 18px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 7px;
            text-align: center;
        }

        .payment-card strong {
            display: block;
            margin-bottom: 10px;
            color: #555;
        }

        .payment-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2878c7;
        }

        .message-success {
            padding: 12px;
            margin-bottom: 15px;
            background: #e5f7e9;
            color: #176b2c;
            border-radius: 5px;
        }

        .message-error {
            padding: 12px;
            margin-bottom: 15px;
            background: #fde8e8;
            color: #b42318;
            border-radius: 5px;
        }

        .back-link {
            display: inline-block;
            margin-top: 10px;
            padding: 11px 18px;
            background: #444;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-link:hover {
            background: #222;
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
            }

            .order-details-container {
                padding: 15px;
                margin: 10px auto;
            }

            .section {
                padding: 14px;
            }

            .details-grid,
            .payment-grid {
                grid-template-columns: 1fr;
            }

            .status-form {
                align-items: stretch;
                flex-direction: column;
            }

            .status-form select,
            .update-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="order-details-container">

    <h2 class="page-heading">Order Details</h2>

    <?php if ($success_message !== "") { ?>
        <div class="message-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php } ?>

    <?php if ($error_message !== "") { ?>
        <div class="message-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php } ?>

    <!-- Booking Information -->
    <div class="section">

        <h3>Booking Information</h3>

        <div class="details-grid">

            <div class="detail-item">
                <strong>Order ID</strong>
                #<?php echo (int) $order['order_id']; ?>
            </div>

            <div class="detail-item">
                <strong>Order Date</strong>
                <?php echo htmlspecialchars($order['order_date']); ?>
            </div>

            <div class="detail-item">
                <strong>Current Order Status</strong>
                <?php echo htmlspecialchars($order['order_status']); ?>
            </div>

            <div class="detail-item">
                <strong>Payment Status</strong>
                <?php echo htmlspecialchars($order['payment_status']); ?>
            </div>

        </div>

    </div>

    <!-- Update Order Status -->
    <div class="section">

        <h3>Update Order Status</h3>

        <form method="POST"
              action="view_order.php?order_id=<?php echo $order_id; ?>"
              class="status-form">

            <label for="order_status">Select New Status:</label>

            <select name="order_status" id="order_status" required>

                <?php foreach ($allowed_statuses as $status) { ?>

                    <option
                        value="<?php echo htmlspecialchars($status); ?>"
                        <?php echo ($order['order_status'] === $status) ? 'selected' : ''; ?>>

                        <?php echo htmlspecialchars($status); ?>

                    </option>

                <?php } ?>

            </select>

            <button type="submit" class="update-btn">
                Update Order Status
            </button>

        </form>

    </div>

    <!-- Customer Details -->
    <div class="section">

        <h3>Customer Details</h3>

        <div class="details-grid">

            <div class="detail-item">
                <strong>Customer Name</strong>
                <?php echo htmlspecialchars($order['full_name']); ?>
            </div>

            <div class="detail-item">
                <strong>Email Address</strong>
                <?php echo htmlspecialchars($order['email']); ?>
            </div>

            <div class="detail-item">
                <strong>Phone Number</strong>
                <?php echo htmlspecialchars($order['phone']); ?>
            </div>

        </div>

    </div>

    <!-- Rented Products -->
    <div class="section">

        <h3>Rented Products</h3>

        <?php if ($items->num_rows > 0) { ?>

            <div class="table-wrapper">

                <table class="products-table">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Rental Type</th>
                            <th>Duration</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($item = $items->fetch_assoc()) { ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </td>

                                <td>
                                    <?php echo (int) $item['quantity']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(ucfirst($item['rental_type'])); ?>
                                </td>

                                <td>
                                    <?php
                                    echo (int) $item['duration'] . " ";
                                    echo htmlspecialchars(ucfirst($item['rental_type']));
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($item['rental_start_date']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($item['rental_end_date']); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float) $item['unit_price'], 2); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float) $item['line_total'], 2); ?>
                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        <?php } else { ?>

            <p>No products found for this order.</p>

        <?php } ?>

    </div>

    <!-- Payment Summary -->
    <div class="section">

        <h3>Payment Summary</h3>

        <div class="payment-grid">

            <div class="payment-card">
                <strong>Total Rental Amount</strong>
                <div class="payment-amount">
                    ₹<?php echo number_format((float) $order['subtotal'], 2); ?>
                </div>
            </div>

            <div class="payment-card">
                <strong>Advance Payment (25%)</strong>
                <div class="payment-amount">
                    ₹<?php echo number_format((float) $order['advance_amount'], 2); ?>
                </div>
            </div>

            <div class="payment-card">
                <strong>Remaining Balance (75%)</strong>
                <div class="payment-amount">
                    ₹<?php echo number_format((float) $order['balance_amount'], 2); ?>
                </div>
            </div>

        </div>

    </div>

    <a href="manage_orders.php" class="back-link">
        ← Back to Manage Orders
    </a>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

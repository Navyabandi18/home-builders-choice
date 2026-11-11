
<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    die("Invalid order ID.");
}

// Fetch order
$stmt = $conn->prepare(
    "SELECT o.*, c.full_name, c.email, c.phone
     FROM orders o
     JOIN customers c ON o.customer_id = c.customer_id
     WHERE o.order_id = ? AND o.customer_id = ?"
);

$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found.");
}

// Fetch products
$stmt = $conn->prepare(
    "SELECT * FROM order_items WHERE order_id = ?"
);

$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();
$items = [];

while ($item = $result->fetch_assoc()) {
    $items[] = $item;
}

$stmt->close();

// Fetch advance payment
$stmt = $conn->prepare(
    "SELECT amount, payment_status, transaction_reference
     FROM payments
     WHERE order_id = ? AND payment_type = 'Advance'
     ORDER BY payment_id DESC
     LIMIT 1"
);

$stmt->bind_param("i", $order_id);
$stmt->execute();

$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$advance_status = strtolower($payment['payment_status'] ?? 'pending');

function formatDuration($duration, $rental_type) {
    $duration = (int)$duration;

    $units = [
        "daily" => $duration == 1 ? "day" : "days",
        "weekly" => $duration == 1 ? "week" : "weeks",
        "monthly" => $duration == 1 ? "month" : "months"
    ];

    $unit = $units[strtolower($rental_type)] ?? $rental_type;

    return $duration . " " . $unit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Confirmation - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .confirmation-section {
            max-width: 900px;
            margin: 30px auto;
            padding: 25px;
        }

        .confirmation-message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .success-message {
            color: green;
        }

        .pending-message {
            color: #b45309;
            font-weight: bold;
        }

        .booking-details,
        .customer-details,
        .payment-details {
            margin-bottom: 25px;
            padding: 18px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .confirmation-product {
            margin-bottom: 20px;
            padding: 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .confirmation-product h3 {
            margin-top: 0;
        }

        .confirmation-links {
            margin-top: 25px;
            text-align: center;
        }

        .confirmation-links a {
            display: inline-block;
            margin: 8px;
        }
    </style>
</head>

<body>

<header>
    <h1>Home Builders Choice</h1>

    <nav>
        <a href="../index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="dashboard.php">Dashboard</a>
    </nav>
</header>

<section class="products-section confirmation-section">

    <h2>Order Confirmation</h2>

    <?php if ($advance_status === 'paid') { ?>

        <p class="confirmation-message success-message">
            Your advance payment was successful! Your booking is confirmed.
        </p>

    <?php } else { ?>

        <p class="confirmation-message pending-message">
            Your order has been recorded, but your advance payment is pending.
        </p>

    <?php } ?>

    <div class="booking-details">

        <h3>Booking Details</h3>

        <p><strong>Order ID:</strong> #<?php echo (int)$order['order_id']; ?></p>

        <p><strong>Order Date:</strong> <?php echo e($order['order_date']); ?></p>

        <p><strong>Order Status:</strong> <?php echo e($order['order_status']); ?></p>

    </div>

    <div class="customer-details">

        <h3>Customer Details</h3>

        <p><strong>Name:</strong> <?php echo e($order['full_name']); ?></p>

        <p><strong>Email:</strong> <?php echo e($order['email']); ?></p>

        <p><strong>Phone:</strong> <?php echo e($order['phone']); ?></p>

    </div>

    <h3>Rented Products</h3>

    <?php if (empty($items)) { ?>

        <p>No products found for this order.</p>

    <?php } else { ?>

        <?php foreach ($items as $item) { ?>

            <div class="confirmation-product">

                <h3><?php echo e($item['product_name']); ?></h3>

                <p><strong>Quantity:</strong>
                    <?php echo (int)$item['quantity']; ?>
                </p>

                <p><strong>Rental Type:</strong>
                    <?php echo e(ucfirst($item['rental_type'])); ?>
                </p>

                <p><strong>Duration:</strong>
                    <?php echo e(formatDuration(
                        $item['duration'],
                        $item['rental_type']
                    )); ?>
                </p>

                <p><strong>Rental Start Date:</strong>
                    <?php echo e($item['rental_start_date']); ?>
                </p>

                <p><strong>Rental End Date:</strong>
                    <?php echo e($item['rental_end_date']); ?>
                </p>

                <p><strong>Unit Price:</strong>
                    ₹<?php echo number_format((float)$item['unit_price'], 2); ?>
                </p>

                <p><strong>Product Total:</strong>
                    ₹<?php echo number_format((float)$item['line_total'], 2); ?>
                </p>

            </div>

        <?php } ?>

    <?php } ?>

    <div class="payment-details">

        <h3>Payment Summary</h3>

        <p><strong>Total Rental Amount:</strong>
            ₹<?php echo number_format((float)$order['subtotal'], 2); ?>
        </p>

        <p><strong>Advance Payment (25%):</strong>
            ₹<?php echo number_format((float)$order['advance_amount'], 2); ?>
        </p>

        <p><strong>Remaining Balance (75%):</strong>
            ₹<?php echo number_format((float)$order['balance_amount'], 2); ?>
        </p>

        <p><strong>Advance Payment Status:</strong>
            <?php echo e($payment['payment_status'] ?? 'Pending'); ?>
        </p>

        <p><strong>Overall Payment Status:</strong>
            <?php echo e($order['payment_status']); ?>
        </p>

        <?php if ($advance_status === 'paid') { ?>

            <p>
                <strong>Razorpay Transaction ID:</strong>
                <?php echo e($payment['transaction_reference']); ?>
            </p>

        <?php } ?>

    </div>

    <h3>Collection Information</h3>

    <p>Please collect your rented construction equipment from the shop.</p>

    <?php if ($advance_status !== 'paid') { ?>

        <p class="pending-message">
            Your advance payment has not been confirmed.
            Please contact the shop for payment instructions.
        </p>

    <?php } else { ?>

        <p class="success-message">
            Your advance payment has been recorded successfully.
        </p>

        <p>
            Please pay the remaining balance of
            <strong>
                ₹<?php echo number_format((float)$order['balance_amount'], 2); ?>
            </strong>
            at the shop.
        </p>

    <?php } ?>

    <div class="confirmation-links">

        <a href="products.php">Continue Shopping</a>

        <a href="dashboard.php">Go to Dashboard</a>

    </div>

</section>

</body>
</html>

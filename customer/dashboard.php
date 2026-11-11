
<?php
session_start();
require_once "../config/db.php";

// Customer must be logged in
if (!isset($_SESSION["customer_id"])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION["customer_id"];

$customer_name = $_SESSION["customer_name"] ?? "Customer";

// Fetch customer's previous orders
$stmt = $conn->prepare(
    "SELECT order_id, order_date, order_status,
            payment_status, subtotal, advance_amount,
            balance_amount
     FROM orders
     WHERE customer_id = ?
     ORDER BY order_id DESC"
);

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

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

    <title>Customer Dashboard - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard-section {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .welcome {
            text-align: center;
            padding: 25px;
        }

        .dashboard-links {
            text-align: center;
            margin: 25px 0;
        }

        .dashboard-links a,
        .view-order {
            display: inline-block;
            padding: 10px 16px;
            margin: 5px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .order-card {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .order-card h3 {
            margin-top: 0;
        }

        .no-orders {
            text-align: center;
            padding: 25px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .status {
            font-weight: bold;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .order-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        @media (max-width: 600px) {
            .order-table td {
                padding: 6px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">Home Builders Choice</div>

    <nav>
        <a href="../index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php">My Cart</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="welcome">

    <h2>
        Welcome, <?php echo e($customer_name); ?>!
    </h2>

    <p>You have successfully logged into your account.</p>

    <p>
        Explore our construction tools and materials available for rent.
    </p>

</section>

<section class="dashboard-section">

    <h2>My Dashboard</h2>

    <div class="dashboard-links">
        <a href="products.php">Browse Products</a>
        <a href="cart.php">My Cart</a>
    </div>

    <h2>My Order History</h2>

    <?php if (empty($orders)) { ?>

        <div class="no-orders">

            <h3>No Orders Yet</h3>

            <p>
                You haven't placed any rental bookings yet.
            </p>

            <a href="products.php" class="view-order">
                Explore Products
            </a>

        </div>

    <?php } else { ?>

        <?php foreach ($orders as $order) { ?>

            <div class="order-card">

                <h3>
                    Order #<?php echo (int)$order["order_id"]; ?>
                </h3>

                <table class="order-table">

                    <tr>
                        <td><strong>Order Date</strong></td>
                        <td><?php echo e($order["order_date"]); ?></td>
                    </tr>

                    <tr>
                        <td><strong>Order Status</strong></td>
                        <td class="status">
                            <?php echo e($order["order_status"]); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Payment Status</strong></td>
                        <td class="status">
                            <?php echo e($order["payment_status"]); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Total Amount</strong></td>
                        <td>
                            ₹<?php echo number_format((float)$order["subtotal"], 2); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Advance Amount</strong></td>
                        <td>
                            ₹<?php echo number_format((float)$order["advance_amount"], 2); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Remaining Balance</strong></td>
                        <td>
                            ₹<?php echo number_format((float)$order["balance_amount"], 2); ?>
                        </td>
                    </tr>

                </table>

                <a
                    href="order_confirmation.php?order_id=<?php echo (int)$order["order_id"]; ?>"
                    class="view-order"
                >
                    View Order Details
                </a>

            </div>

        <?php } ?>

    <?php } ?>

</section>

</body>
</html>

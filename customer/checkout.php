
<?php
session_start();
require_once "../config/db.php";

// Customer must be logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check cart
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Product image mapping
$product_images = [
    "concrete mixer" => "concrete_mixer.jpg",
    "electric concrete mixer" => "concrete_mixer.jpg",
    "drill machine" => "drill_machine.jpg",
    "ladder" => "ladder.jpg",
    "shovel" => "shovel.jpg",
    "wheel barrow" => "wheelbarrow.jpg",
    "wheelbarrow" => "wheelbarrow.jpg"
];

// Safe output function
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Fetch customer details
$stmt = $conn->prepare(
    "SELECT full_name, email, phone
     FROM customers
     WHERE customer_id = ?"
);

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$items = [];
$subtotal = 0;
$error = "";

// Fetch products and validate rental selections
foreach ($_SESSION['cart'] as $product_id => $quantity) {

    $product_id = filter_var($product_id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (!$product_id || $product_id <= 0 || !$quantity || $quantity <= 0) {
        $error = "Invalid product quantity in cart.";
        break;
    }

    $rental = $_SESSION['rental'][$product_id] ?? null;

    if (
        !$rental ||
        !in_array($rental['type'] ?? '', ['daily', 'weekly', 'monthly'], true) ||
        !isset($rental['duration']) ||
        filter_var($rental['duration'], FILTER_VALIDATE_INT) === false ||
        (int)$rental['duration'] < 1 ||
        (int)$rental['duration'] > 365
    ) {
        $error = "Please select a valid rental duration for every product in your cart.";
        break;
    }

    $stmt = $conn->prepare(
        "SELECT product_name, rental_price, weekly_price,
                monthly_price, availability
         FROM products
         WHERE product_id = ?"
    );

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product || $product['availability'] !== 'Available') {
        $error = "One or more products are unavailable. Please review your cart.";
        break;
    }

    $type = $rental['type'];
    $duration = (int)$rental['duration'];

    if ($type === 'daily') {
        $unit_price = (float)$product['rental_price'];
    } elseif ($type === 'weekly') {
        $unit_price = (float)$product['weekly_price'];
    } else {
        $unit_price = (float)$product['monthly_price'];
    }

    if ($unit_price <= 0) {
        $error = "Rental pricing is not available for " .
                 $product['product_name'] . ".";
        break;
    }

    $line_total = $unit_price * $duration * $quantity;
    $subtotal += $line_total;

    $items[] = [
        'product_id' => $product_id,
        'product_name' => $product['product_name'],
        'quantity' => $quantity,
        'rental_type' => $type,
        'duration' => $duration,
        'unit_price' => $unit_price,
        'line_total' => $line_total
    ];
}

$advance_amount = round($subtotal * 0.25, 2);
$balance_amount = round($subtotal - $advance_amount, 2);

// Duration label
function getDurationLabel($type) {
    if ($type === 'daily') {
        return 'Days';
    } elseif ($type === 'weekly') {
        return 'Weeks';
    } else {
        return 'Months';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .checkout-product-image {
            width: 100%;
            max-width: 250px;
            height: 180px;
            object-fit: contain;
            display: block;
            margin: 10px auto 15px;
        }

        .checkout-image-missing {
            height: 180px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f2f2f2;
            color: #777;
            margin-bottom: 15px;
        }

        .payment-summary {
            padding: 20px;
            margin-top: 20px;
        }

    </style>

</head>

<body>

<header>

    <h2>Home Builders Choice</h2>

    <nav>
        <a href="../index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php">Cart</a>
        <a href="dashboard.php">Dashboard</a>
    </nav>

</header>

<section class="form-section">

    <h2>Checkout</h2>

    <?php if ($error !== ""): ?>

        <p style="color:red;">
            <?php echo e($error); ?>
        </p>

        <a href="cart.php">Return to Cart</a>

    <?php else: ?>

        <h3>Customer Details</h3>

        <p>
            <strong>Name:</strong>
            <?php echo e($customer['full_name']); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo e($customer['email']); ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?php echo e($customer['phone']); ?>
        </p>

        <hr>

        <h3>Rental Order Summary</h3>

        <form action="place_order.php" method="POST">

            <!-- CSRF Security Token -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION['csrf_token']); ?>"
            >

            <?php foreach ($items as $item): ?>

                <?php
                $product_name = strtolower(trim($item['product_name']));

                $image_filename = $product_images[$product_name] ?? '';

                $image_path = "../assets/images/" . $image_filename;

                $image_full_path = __DIR__ . "/../assets/images/" . $image_filename;
                ?>

                <div class="product-card">

                    <?php if (
                        !empty($image_filename) &&
                        file_exists($image_full_path)
                    ): ?>

                        <img
                            src="<?php echo e($image_path); ?>"
                            alt="<?php echo e($item['product_name']); ?>"
                            class="checkout-product-image"
                        >

                    <?php else: ?>

                        <div class="checkout-image-missing">
                            Product image not available
                        </div>

                    <?php endif; ?>

                    <h3>
                        <?php echo e($item['product_name']); ?>
                    </h3>

                    <p>
                        <strong>Rental Type:</strong>
                        <?php echo e(ucfirst($item['rental_type'])); ?>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <?php echo (int)$item['duration']; ?>
                        <?php echo e(getDurationLabel($item['rental_type'])); ?>
                    </p>

                    <p>
                        <strong>Quantity:</strong>
                        <?php echo (int)$item['quantity']; ?>
                    </p>

                    <p>
                        <strong>Rental Price:</strong>
                        ₹<?php echo number_format($item['unit_price'], 2); ?>
                    </p>

                    <p>
                        <strong>Rental Total:</strong>
                        ₹<?php echo number_format($item['line_total'], 2); ?>
                    </p>

                    <label>Rental Start Date:</label>

                    <input
                        type="date"
                        name="start_dates[<?php echo (int)$item['product_id']; ?>]"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>

            <?php endforeach; ?>

            <hr>

            <div class="payment-summary">

                <h3>Payment Summary</h3>

                <p>
                    <strong>Subtotal:</strong>
                    ₹<?php echo number_format($subtotal, 2); ?>
                </p>

                <p>
                    <strong>Advance Payment (25%):</strong>
                    ₹<?php echo number_format($advance_amount, 2); ?>
                </p>

                <p>
                    <strong>Remaining Balance (75%):</strong>
                    ₹<?php echo number_format($balance_amount, 2); ?>
                </p>

                <p>
                    <strong>Collection:</strong>
                    Shop Pickup
                </p>

            </div>

            <button
                type="submit"
                <?php echo empty($items) ? 'disabled' : ''; ?>
            >
                Proceed to Secure Payment
            </button>

        </form>

    <?php endif; ?>

</section>

</body>
</html>

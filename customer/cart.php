
<?php
session_start();
require_once "../config/db.php";

// Get cart information
$cart = $_SESSION['cart'] ?? [];

if (!isset($_SESSION['rental'])) {
    $_SESSION['rental'] = [];
}

$message = "";

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

// Process rental selection or remove product
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Remove product from cart
    if (isset($_POST['remove_product'])) {

        $remove_id = filter_var(
            $_POST['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($remove_id && isset($_SESSION['cart'][$remove_id])) {

            unset($_SESSION['cart'][$remove_id]);
            unset($_SESSION['rental'][$remove_id]);

            header("Location: cart.php?removed=1");
            exit();
        }

    } else {

        // Process rental selection
        $product_id = filter_var(
            $_POST['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $rental_type = $_POST['rental_type'] ?? '';

        $duration = filter_var(
            $_POST['duration'] ?? null,
            FILTER_VALIDATE_INT
        );

        $price_columns = [
            'daily' => 'rental_price',
            'weekly' => 'weekly_price',
            'monthly' => 'monthly_price'
        ];

        if (
            $product_id &&
            isset($_SESSION['cart'][$product_id]) &&
            isset($price_columns[$rental_type]) &&
            $duration !== false &&
            $duration >= 1 &&
            $duration <= 365
        ) {

            $price_column = $price_columns[$rental_type];

            $stmt = $conn->prepare(
                "SELECT availability, $price_column AS selected_price
                 FROM products WHERE product_id = ?"
            );

            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (
                $product &&
                $product['availability'] === 'Available' &&
                $product['selected_price'] !== null &&
                (float)$product['selected_price'] > 0
            ) {

                $_SESSION['rental'][$product_id] = [
                    'type' => $rental_type,
                    'duration' => $duration
                ];

                header("Location: cart.php?updated=1");
                exit();

            } else {
                $message = "This product is unavailable or its rental price is not set.";
            }

        } else {
            $message = "Please select a valid rental duration.";
        }
    }
}

// Refresh cart information
$cart = $_SESSION['cart'] ?? [];

// Fetch products in cart
$products = [];

if (!empty($cart)) {

    $ids = array_keys($cart);

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare(
        "SELECT * FROM products WHERE product_id IN ($placeholders)"
    );

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $products[$product['product_id']] = $product;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .cart-product-image {
            width: 100%;
            max-width: 250px;
            height: 180px;
            object-fit: contain;
            display: block;
            margin: 10px auto 15px;
        }

        .cart-image-missing {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            background: #f2f2f2;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<header>
    <h1>Home Builders Choice</h1>

    <nav>
        <a href="../index.php">Home</a>
        <a href="products.php">Products</a>
        <a href="cart.php">My Cart</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<section class="products-section">

    <h2>My Shopping Cart</h2>

    <?php if (isset($_GET['updated'])) { ?>

        <p style="color: green; text-align: center;">
            Rental duration saved successfully!
        </p>

    <?php } ?>

    <?php if (isset($_GET['removed'])) { ?>

        <p style="color: green; text-align: center;">
            Product removed from cart successfully!
        </p>

    <?php } ?>

    <?php if ($message !== "") { ?>

        <p style="color: red; text-align: center;">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php } ?>

    <?php if (empty($products)) { ?>

        <p>Your cart is empty.</p>

        <a href="products.php">Browse Products</a>

    <?php } else { ?>

        <?php $grand_total = 0; ?>

        <div class="product-container">

            <?php foreach ($products as $id => $product) { ?>

                <?php
                $quantity = (int)$cart[$id];

                $rental = $_SESSION['rental'][$id] ?? null;

                $line_total = 0;
                $selected_price = 0;

                // Get product image filename
                $product_name = strtolower(trim($product['product_name']));

                $image_filename = $product_images[$product_name] ?? '';

                // Build image path
                $image_path = "../assets/images/" . $image_filename;

                $image_full_path = __DIR__ . "/../assets/images/" . $image_filename;
                ?>

                <div class="product-card">

                    <!-- Product Image -->
                    <?php if (
                        !empty($image_filename) &&
                        file_exists($image_full_path)
                    ) { ?>

                        <img
                            src="<?php echo htmlspecialchars($image_path); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                            class="cart-product-image"
                        >

                    <?php } else { ?>

                        <div class="cart-image-missing">
                            Product image not available
                        </div>

                    <?php } ?>

                    <h3>
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h3>

                    <p>
                        <strong>Quantity:</strong>
                        <?php echo $quantity; ?>
                    </p>

                    <!-- Remove Product Button -->
                    <form method="POST" action="cart.php">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php echo (int)$id; ?>"
                        >

                        <button
                            type="submit"
                            name="remove_product"
                            value="1"
                            onclick="return confirm('Are you sure you want to remove this product?');"
                        >
                            Remove from Cart
                        </button>

                    </form>

                    <p>
                        Daily: ₹<?php echo number_format((float)$product['rental_price'], 2); ?>
                    </p>

                    <p>
                        Weekly: ₹<?php echo number_format((float)$product['weekly_price'], 2); ?>
                    </p>

                    <p>
                        Monthly: ₹<?php echo number_format((float)$product['monthly_price'], 2); ?>
                    </p>

                    <?php if ($product['availability'] === 'Available') { ?>

                        <form method="POST" action="cart.php">

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php echo (int)$id; ?>"
                            >

                            <label>Choose Rental Type:</label>

                            <select name="rental_type" required>

                                <option value="daily"
                                    <?php if (($rental['type'] ?? '') === 'daily') echo 'selected'; ?>>
                                    Daily
                                </option>

                                <option value="weekly"
                                    <?php if (($rental['type'] ?? '') === 'weekly') echo 'selected'; ?>>
                                    Weekly
                                </option>

                                <option value="monthly"
                                    <?php if (($rental['type'] ?? '') === 'monthly') echo 'selected'; ?>>
                                    Monthly
                                </option>

                            </select>

                            <label>Enter Duration:</label>

                            <input
                                type="number"
                                name="duration"
                                min="1"
                                max="365"
                                value="<?php echo (int)($rental['duration'] ?? 1); ?>"
                                required
                            >

                            <button type="submit">
                                Calculate Rental
                            </button>

                        </form>

                    <?php } else { ?>

                        <p style="color: red;">
                            This product is currently unavailable.
                        </p>

                    <?php } ?>

                    <?php if ($rental !== null) { ?>

                        <?php
                        $price_column = [
                            'daily' => 'rental_price',
                            'weekly' => 'weekly_price',
                            'monthly' => 'monthly_price'
                        ][$rental['type']];

                        $selected_price = (float)$product[$price_column];

                        $line_total = $selected_price
                            * (int)$rental['duration']
                            * $quantity;

                        $grand_total += $line_total;
                        ?>

                        <hr>

                        <p>
                            <strong>Selected Rental:</strong>
                            <?php echo ucfirst($rental['type']); ?>
                        </p>

                        <p>
                            <strong>Duration:</strong>

                            <?php echo (int)$rental['duration']; ?>

                            <?php
                            echo ucfirst(
                                $rental['type'] === 'daily'
                                    ? 'days'
                                    : ($rental['type'] === 'weekly'
                                        ? 'weeks'
                                        : 'months')
                            );
                            ?>

                        </p>

                        <p>
                            <strong>Rental Total:</strong>
                            ₹<?php echo number_format($line_total, 2); ?>
                        </p>

                    <?php } else { ?>

                        <p>Please select your rental type and duration.</p>

                    <?php } ?>

                </div>

            <?php } ?>

        </div>

        <hr>

        <h3>
            Cart Total: ₹<?php echo number_format($grand_total, 2); ?>
        </h3>

        <p>
            <a href="products.php">Continue Shopping</a>
        </p>

        <!-- Proceed to Checkout Button -->
        <?php
        $all_rentals_selected = !empty($products);

        foreach ($products as $id => $product) {
            if (
                !isset($_SESSION['rental'][$id]) ||
                $product['availability'] !== 'Available'
            ) {
                $all_rentals_selected = false;
                break;
            }
        }
        ?>

        <?php if ($grand_total > 0 && $all_rentals_selected) { ?>

            <p>
                <a href="checkout.php" class="btn">
                    Proceed to Checkout
                </a>
            </p>

        <?php } else { ?>

            <p style="color: red;">
                Please select rental details for all available products to continue.
            </p>

        <?php } ?>

    <?php } ?>

</section>

</body>
</html>

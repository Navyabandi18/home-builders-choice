
<?php
session_start();
require_once "../config/db.php";

// Fetch all products
$sql = "SELECT * FROM products ORDER BY product_id DESC";
$result = $conn->query($sql);

// Match database product names with image files
$product_images = [
    "electric concrete mixer" => "concrete_mixer.jpg",
    "brick trowel" => "brick trowel.jpg",
    "drill machine" => "drill_machine.jpg",
    "ladder" => "ladder.jpg",
    "shovel" => "shovel.jpg",
    "wheelbarrow" => "wheelbarrow.jpg"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .product-image-placeholder {
            width: 100%;
            height: 200px;
            background: #eeeeee;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-bottom: 12px;
            color: #555555;
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
        <a href="../index.php#about">About Us</a>
        <a href="../index.php#contact">Contact</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="products-section">

    <h2>Available Rental Products</h2>

    <?php if (isset($_GET['added'])) { ?>
        <p style="color: green; text-align: center;">
            Product added to cart successfully!
        </p>
    <?php } ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'unavailable') { ?>
        <p style="color: red; text-align: center;">
            This product is currently unavailable.
        </p>
    <?php } ?>

    <div class="product-container">

        <?php if ($result && $result->num_rows > 0) { ?>

            <?php while ($product = $result->fetch_assoc()) { ?>

                <?php
                $product_name = strtolower(trim($product['product_name']));

                $image_file = $product_images[$product_name] ?? null;

                $image_path = $image_file
                    ? "../assets/images/" . $image_file
                    : null;

                $image_exists = $image_path &&
                    file_exists(__DIR__ . "/" . $image_path);
                ?>

                <div class="product-card">

                    <?php if ($image_exists) { ?>

                        <img
                            src="<?php echo htmlspecialchars($image_path); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                            class="product-image"
                        >

                    <?php } else { ?>

                        <div class="product-image-placeholder">
                            Product image coming soon
                        </div>

                    <?php } ?>

                    <h3>
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                    </p>

                    <p>
                        <strong>Category:</strong>
                        <?php echo htmlspecialchars($product['category'] ?? ''); ?>
                    </p>

                    <div class="price">

                        <p>
                            Daily: ₹<?php echo number_format((float)$product['rental_price'], 2); ?>
                        </p>

                        <p>
                            Weekly: ₹<?php echo number_format((float)$product['weekly_price'], 2); ?>
                        </p>

                        <p>
                            Monthly: ₹<?php echo number_format((float)$product['monthly_price'], 2); ?>
                        </p>

                    </div>

                    <p class="availability">
                        <?php echo htmlspecialchars($product['availability']); ?>
                    </p>

                    <?php if ($product['availability'] === 'Available') { ?>

                        <form action="add_to_cart.php" method="POST">

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php echo (int)$product['product_id']; ?>"
                            >

                            <button type="submit">
                                Add to Cart
                            </button>

                        </form>

                    <?php } else { ?>

                        <p>Currently unavailable for rental.</p>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>No products found.</p>

        <?php } ?>

    </div>

</section>

<footer>
    <p>
        &copy; <?php echo date("Y"); ?>
        Home Builders Choice. All rights reserved.
    </p>
</footer>

</body>
</html>

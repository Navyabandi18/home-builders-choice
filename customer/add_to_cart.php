
<?php
session_start();
require_once "../config/db.php";

// Check whether a product ID was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['product_id'])) {
    header("Location: products.php");
    exit();
}

$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);

if (!$product_id || $product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Check whether the product exists and is available
$stmt = $conn->prepare(
    "SELECT product_id FROM products
     WHERE product_id = ? AND availability = 'Available'"
);

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: products.php?error=unavailable");
    exit();
}

$stmt->close();

// Create cart if it does not already exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart or increase its quantity
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]++;
} else {
    $_SESSION['cart'][$product_id] = 1;
}

// Return to products page
header("Location: products.php?added=1");
exit();
?>

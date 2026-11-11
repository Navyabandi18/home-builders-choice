
<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($product_id && isset($_SESSION['cart'][$product_id])) {

        unset($_SESSION['cart'][$product_id]);

        // Also remove its rental selection
        unset($_SESSION['rental'][$product_id]);
    }
}

header("Location: cart.php");
exit();
?>

<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Allow deletion only through POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_id = $_POST['product_id'] ?? '';

    if (!filter_var($product_id, FILTER_VALIDATE_INT)) {
        header("Location: manage_products.php");
        exit();
    }

    $product_id = (int) $product_id;

    $stmt = $conn->prepare(
        "DELETE FROM products WHERE product_id = ?"
    );

    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage_products.php?message=deleted");
        exit();
    }

    $stmt->close();
}

header("Location: manage_products.php");
exit();
?>
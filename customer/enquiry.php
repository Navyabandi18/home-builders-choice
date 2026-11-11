<?php
session_start();
require_once "../config/db.php";

// Check customer login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// Check product ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: products.php");
    exit();
}

$product_id = (int) $_GET['id'];
$message = "";

// Fetch selected product
$stmt = $conn->prepare(
    "SELECT * FROM products WHERE product_id = ? AND availability = 'Available'"
);

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found or currently unavailable.");
}

// Submit rental enquiry
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $enquiry_message = trim($_POST['message']);
    $customer_id = $_SESSION['customer_id'];

    if ($enquiry_message == "") {

        $message = "Please enter your enquiry message.";

    } else {

        $insert = $conn->prepare(
            "INSERT INTO enquiries
            (customer_id, product_id, message)
            VALUES (?, ?, ?)"
        );

        $insert->bind_param(
            "iis",
            $customer_id,
            $product_id,
            $enquiry_message
        );

        if ($insert->execute()) {
            $message = "Your rental enquiry has been submitted successfully!";
        } else {
            $message = "Unable to submit your enquiry. Please try again.";
        }

        $insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rental Enquiry - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="form-container">

        <h2>Rental Enquiry</h2>

        <h3>
            Product: <?php echo htmlspecialchars($product['product_name']); ?>
        </h3>

        <p>
            Rental Price: ₹<?php echo number_format($product['rental_price'], 2); ?>
        </p>

        <?php if ($message != "") { ?>
            <p style="color: green; text-align: center;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php } ?>

        <form method="POST">

            <label>Your Enquiry</label>

            <textarea
                name="message"
                placeholder="Enter your rental requirements..."
                required
            ></textarea>

            <button type="submit">Submit Enquiry</button>

        </form>

        <p style="text-align: center;">
            <a href="products.php">Back to Products</a>
        </p>

    </div>

</body>

</html>
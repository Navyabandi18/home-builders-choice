
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

$product_name = "";
$description = "";
$category = "";
$daily_price = "";
$weekly_price = "";
$monthly_price = "";
$availability = "Available";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $daily_price = $_POST['rental_price'] ?? '';
    $weekly_price = $_POST['weekly_price'] ?? '';
    $monthly_price = $_POST['monthly_price'] ?? '';

    $availability = $_POST['availability'] ?? '';

    if (
        $product_name == "" ||
        $category == "" ||
        !is_numeric($daily_price) ||
        !is_numeric($weekly_price) ||
        !is_numeric($monthly_price) ||
        $daily_price <= 0 ||
        $weekly_price <= 0 ||
        $monthly_price <= 0 ||
        !in_array($availability, ['Available', 'Unavailable'], true)
    ) {

        $message = "Please enter valid product details.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO products
            (product_name, description, category,
             rental_price, weekly_price, monthly_price, availability)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssddds",
            $product_name,
            $description,
            $category,
            $daily_price,
            $weekly_price,
            $monthly_price,
            $availability
        );

        if ($stmt->execute()) {

            $message = "Product added successfully!";
            $message_type = "success";

            // Clear form after successful insertion
            $product_name = "";
            $description = "";
            $category = "";
            $daily_price = "";
            $weekly_price = "";
            $monthly_price = "";
            $availability = "Available";

        } else {

            $message = "Unable to add product.";
            $message_type = "error";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product - Home Builders Choice</title>

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

        .add-product-container {
            width: 100%;
            max-width: 750px;
            margin: 20px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.10);
        }

        .page-heading {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2878c7;
            box-shadow: 0 0 4px rgba(40, 120, 199, 0.25);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .pricing-heading {
            margin-top: 25px;
            margin-bottom: 18px;
            padding-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            margin-top: 10px;
            background: #2878c7;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1d5f9e;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 5px;
        }

        .success-message {
            background: #e5f7e9;
            color: #176b2c;
        }

        .error-message {
            background: #fde8e8;
            color: #b42318;
        }

        .back-link {
            display: block;
            margin-top: 22px;
            text-align: center;
        }

        .back-link a {
            color: #2878c7;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 650px) {

            body {
                padding: 10px;
            }

            .add-product-container {
                padding: 20px 15px;
                margin: 10px auto;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>

</head>

<body>

<div class="add-product-container">

    <h2 class="page-heading">Add New Product</h2>

    <?php if ($message != "") { ?>

        <div class="message <?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>

    <form method="POST">

        <div class="form-group">

            <label for="product_name">Product Name</label>

            <input
                type="text"
                id="product_name"
                name="product_name"
                value="<?php echo htmlspecialchars($product_name); ?>"
                placeholder="Enter product name"
                required>

        </div>

        <div class="form-group">

            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                placeholder="Enter product description"
                required><?php echo htmlspecialchars($description); ?></textarea>

        </div>

        <div class="form-group">

            <label for="category">Category</label>

            <input
                type="text"
                id="category"
                name="category"
                value="<?php echo htmlspecialchars($category); ?>"
                placeholder="Enter product category"
                required>

        </div>

        <h3 class="pricing-heading">Rental Pricing</h3>

        <div class="pricing-grid">

            <div class="form-group">

                <label for="rental_price">Daily Price (₹)</label>

                <input
                    type="number"
                    id="rental_price"
                    name="rental_price"
                    min="0.01"
                    step="0.01"
                    value="<?php echo htmlspecialchars((string)$daily_price); ?>"
                    placeholder="Daily price"
                    required>

            </div>

            <div class="form-group">

                <label for="weekly_price">Weekly Price (₹)</label>

                <input
                    type="number"
                    id="weekly_price"
                    name="weekly_price"
                    min="0.01"
                    step="0.01"
                    value="<?php echo htmlspecialchars((string)$weekly_price); ?>"
                    placeholder="Weekly price"
                    required>

            </div>

            <div class="form-group">

                <label for="monthly_price">Monthly Price (₹)</label>

                <input
                    type="number"
                    id="monthly_price"
                    name="monthly_price"
                    min="0.01"
                    step="0.01"
                    value="<?php echo htmlspecialchars((string)$monthly_price); ?>"
                    placeholder="Monthly price"
                    required>

            </div>

        </div>

        <div class="form-group">

            <label for="availability">Availability</label>

            <select name="availability" id="availability" required>

                <option value="Available"
                    <?php echo $availability === 'Available' ? 'selected' : ''; ?>>
                    Available
                </option>

                <option value="Unavailable"
                    <?php echo $availability === 'Unavailable' ? 'selected' : ''; ?>>
                    Unavailable
                </option>

            </select>

        </div>

        <button type="submit" class="submit-btn">
            + Add Product
        </button>

    </form>

    <div class="back-link">
        <a href="manage_products.php">← Back to Manage Products</a>
    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>

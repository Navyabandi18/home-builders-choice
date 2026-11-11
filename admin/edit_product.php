
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Validate product ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: manage_products.php");
    exit();
}

$product_id = (int) $_GET['id'];

$message = "";
$message_type = "";

// Fetch existing product
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}

// Update product
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    $daily_price = $_POST['rental_price'] ?? '';
    $weekly_price = $_POST['weekly_price'] ?? '';
    $monthly_price = $_POST['monthly_price'] ?? '';

    $availability = $_POST['availability'] ?? '';

    // Validate product details
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

        $update = $conn->prepare(
            "UPDATE products
             SET product_name = ?,
                 description = ?,
                 category = ?,
                 rental_price = ?,
                 weekly_price = ?,
                 monthly_price = ?,
                 availability = ?
             WHERE product_id = ?"
        );

        $update->bind_param(
            "sssdddsi",
            $product_name,
            $description,
            $category,
            $daily_price,
            $weekly_price,
            $monthly_price,
            $availability,
            $product_id
        );

        if ($update->execute()) {

            $message = "Product updated successfully!";
            $message_type = "success";

            // Update displayed product details
            $product['product_name'] = $product_name;
            $product['description'] = $description;
            $product['category'] = $category;
            $product['rental_price'] = $daily_price;
            $product['weekly_price'] = $weekly_price;
            $product['monthly_price'] = $monthly_price;
            $product['availability'] = $availability;

        } else {

            $message = "Unable to update product.";
            $message_type = "error";
        }

        $update->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product - Home Builders Choice</title>

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

        .edit-product-container {
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
            margin-bottom: 10px;
        }

        .product-id {
            text-align: center;
            color: #777;
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

        .update-btn {
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

        .update-btn:hover {
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

            .edit-product-container {
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

<div class="edit-product-container">

    <h2 class="page-heading">Edit Product</h2>

    <p class="product-id">
        Product ID: #<?php echo (int) $product_id; ?>
    </p>

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
                value="<?php echo htmlspecialchars($product['product_name']); ?>"
                required>

        </div>

        <div class="form-group">

            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                required><?php echo htmlspecialchars($product['description']); ?></textarea>

        </div>

        <div class="form-group">

            <label for="category">Category</label>

            <input
                type="text"
                id="category"
                name="category"
                value="<?php echo htmlspecialchars($product['category']); ?>"
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
                    value="<?php echo htmlspecialchars((string)$product['rental_price']); ?>"
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
                    value="<?php echo htmlspecialchars((string)$product['weekly_price']); ?>"
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
                    value="<?php echo htmlspecialchars((string)$product['monthly_price']); ?>"
                    required>

            </div>

        </div>

        <div class="form-group">

            <label for="availability">Availability</label>

            <select name="availability" id="availability" required>

                <option value="Available"
                    <?php echo $product['availability'] === 'Available' ? 'selected' : ''; ?>>
                    Available
                </option>

                <option value="Unavailable"
                    <?php echo $product['availability'] === 'Unavailable' ? 'selected' : ''; ?>>
                    Unavailable
                </option>

            </select>

        </div>

        <button type="submit" class="update-btn">
            Update Product
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

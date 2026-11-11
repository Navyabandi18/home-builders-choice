
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch products
$sql = "SELECT * FROM products ORDER BY product_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Products - Home Builders Choice</title>

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

        .products-container {
            width: 100%;
            max-width: 1450px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.10);
            overflow: hidden;
        }

        .page-heading {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        .top-actions a {
            display: inline-block;
            padding: 11px 18px;
            text-decoration: none;
            color: white;
            background: #2878c7;
            border-radius: 5px;
            font-size: 14px;
        }

        .top-actions a:hover {
            background: #1d5f9e;
        }

        .top-actions .logout-link {
            background: #d9534f;
        }

        .top-actions .logout-link:hover {
            background: #b52b27;
        }

        .products-count {
            color: #555;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 7px;
        }

        .products-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            background: white;
        }

        .products-table th,
        .products-table td {
            padding: 13px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .products-table th {
            background: #2878c7;
            color: white;
            white-space: nowrap;
        }

        .products-table tbody tr:nth-child(even) {
            background: #f7f7f7;
        }

        .products-table tbody tr:hover {
            background: #eef5fc;
        }

        .product-description {
            min-width: 220px;
            max-width: 300px;
            text-align: left !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .product-name {
            min-width: 150px;
            font-weight: bold;
        }

        .availability {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #e5f7e9;
            color: #176b2c;
            font-size: 13px;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .edit-btn,
        .delete-btn {
            display: inline-block;
            padding: 8px 13px;
            border: none;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
        }

        .edit-btn {
            background: #2878c7;
        }

        .edit-btn:hover {
            background: #1d5f9e;
        }

        .delete-btn {
            background: #d9534f;
        }

        .delete-btn:hover {
            background: #b52b27;
        }

        .delete-form {
            margin: 0;
        }

        .empty-message {
            padding: 25px !important;
            text-align: center !important;
            color: #666;
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
            }

            .products-container {
                padding: 15px;
                margin: 10px auto;
            }

            .top-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .top-actions a {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="products-container">

    <h2 class="page-heading">Manage Products</h2>

    <!-- Navigation -->
    <div class="top-actions">

        <a href="dashboard.php">← Dashboard</a>

        <a href="add_product.php">+ Add New Product</a>

        <a href="logout.php" class="logout-link">Logout</a>

    </div>

    <?php if ($result) { ?>

        <p class="products-count">
            Total Products: <?php echo $result->num_rows; ?>
        </p>

    <?php } ?>

    <!-- Products Table -->
    <div class="table-wrapper">

        <table class="products-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Daily Price</th>
                    <th>Weekly Price</th>
                    <th>Monthly Price</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($result && $result->num_rows > 0) { ?>

                    <?php while ($product = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $product['product_id']; ?>
                            </td>

                            <td class="product-name">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </td>

                            <td class="product-description">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product['category']); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float) $product['rental_price'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float) $product['weekly_price'], 2); ?>
                            </td>

                            <td>
                                ₹<?php echo number_format((float) $product['monthly_price'], 2); ?>
                            </td>

                            <td>
                                <span class="availability">
                                    <?php echo htmlspecialchars($product['availability']); ?>
                                </span>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="edit_product.php?id=<?php echo (int) $product['product_id']; ?>"
                                        class="edit-btn">
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="delete_product.php"
                                        class="delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this product?');">

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php echo (int) $product['product_id']; ?>">

                                        <button type="submit" class="delete-btn">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="9" class="empty-message">
                            No products found.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>

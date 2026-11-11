
<?php
session_start();
require_once "../config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT customer_id, full_name, password
         FROM customers WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $customer = $result->fetch_assoc();

        if (password_verify($password, $customer["password"])) {

            session_regenerate_id(true);

            $_SESSION["customer_id"] = $customer["customer_id"];
            $_SESSION["customer_name"] = $customer["full_name"];

            header("Location: dashboard.php");
            exit;

        } else {
            $message = "Incorrect email or password.";
        }
    } else {
        $message = "Incorrect email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header>
    <div class="logo">Home Builders Choice</div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<div class="form-container">

    <h2>Customer Login</h2>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>

    </form>

    <p>Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>


<?php
require_once "../config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if ($name === "" || $email === "" || $phone === "" || $password === "") {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
    } else {

        $check = $conn->prepare(
            "SELECT customer_id FROM customers WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "This email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO customers (full_name, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
            } else {
                $message = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header>
    <div class="logo">Home Builders Choice</div>
    <nav>
        <a href="../index.php">Home</a>
    </nav>
</header>

<div class="form-container">
    <h2>Create Your Account</h2>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Phone Number</label>
        <input type="tel" name="phone" required>

        <label>Password</label>
        <input type="password" name="password" minlength="6" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>

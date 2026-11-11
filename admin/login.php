<?php
session_start();
require_once "../config/db.php";

$error = "";

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT admin_id, username, password FROM admin WHERE username = ?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $admin = $result->fetch_assoc();

        if (password_verify($password, $admin['password'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid username or password.";
        }

    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Home Builders Choice</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <div class="form-container">

        <h2>Admin Login</h2>

        <?php if (!empty($error)) { ?>
            <p style="color: red; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php } ?>

        <form method="POST" action="">

            <label>Username</label>
            <input
                type="text"
                name="username"
                placeholder="Enter admin username"
                required
            >

            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Enter admin password"
                required
            >

            <button type="submit">Login</button>

        </form>

        <p style="text-align: center;">
            <a href="../index.php">Back to Home</a>
        </p>

    </div>

</body>
</html>
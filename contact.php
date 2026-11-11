
<?php
require_once "config/db.php";

$message = "";
$message_type = "";

$name = "";
$email = "";
$phone = "";
$subject = "";
$enquiry = "";

// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $enquiry = trim($_POST['message'] ?? '');

    // Validate form
    if (
        $name === "" ||
        $email === "" ||
        $subject === "" ||
        $enquiry === ""
    ) {
        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO contact_messages
            (name, email, phone, subject, message)
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $message = "Something went wrong. Please try again.";
            $message_type = "error";

        } else {

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $phone,
                $subject,
                $enquiry
            );

            if ($stmt->execute()) {

                $message = "Thank you for contacting us! Your message has been submitted successfully.";
                $message_type = "success";

                // Clear form after successful submission
                $name = "";
                $email = "";
                $phone = "";
                $subject = "";
                $enquiry = "";

            } else {

                $message = "Unable to submit your message. Please try again.";
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - Home Builders Choice</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
        }

        .contact-banner {
            background: #263746;
            color: white;
            padding: 55px 20px;
            text-align: center;
        }

        .contact-banner h1 {
            margin-bottom: 12px;
            font-size: 34px;
        }

        .contact-banner p {
            font-size: 16px;
        }

        .contact-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }

        .contact-info,
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .contact-info h2,
        .contact-form h2 {
            color: #263746;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 25px;
        }

        .info-item h3 {
            margin-bottom: 7px;
            color: #333;
            font-size: 17px;
        }

        .info-item p {
            margin: 0;
            color: #666;
            line-height: 1.6;
            overflow-wrap: anywhere;
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
        }

        .form-group textarea {
            min-height: 130px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2878c7;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
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

        .form-message {
            padding: 13px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #e5f7e9;
            color: #176b2c;
        }

        .error {
            background: #fde8e8;
            color: #b42318;
        }

        .required {
            color: red;
        }

        footer {
            text-align: center;
            padding: 20px;
            background: #263746;
            color: white;
            margin-top: 40px;
        }

        @media (max-width: 750px) {

            .contact-container {
                grid-template-columns: 1fr;
                margin: 25px auto;
            }

            .contact-banner h1 {
                font-size: 27px;
            }

            .contact-info,
            .contact-form {
                padding: 22px;
            }
        }

    </style>

</head>

<body>

<header>

    <div class="logo">Home Builders Choice</div>

    <nav>
        <a href="index.php">Home</a>
        <a href="customer/products.php">Products</a>
        <a href="index.php#about">About Us</a>
        <a href="contact.php">Contact</a>
        <a href="customer/login.php">Login</a>
        <a href="customer/register.php">Register</a>
    </nav>

</header>

<section class="contact-banner">

    <h1>Contact Us</h1>

    <p>Have questions about our construction equipment rentals? We are here to help!</p>

</section>

<div class="contact-container">

    <!-- Contact Information -->

    <div class="contact-info">

        <h2>Get in Touch</h2>

        <p>
            Feel free to contact Home Builders Choice for product enquiries,
            rental information and customer support.
        </p>

        <div class="info-item">
            <h3>Phone Number</h3>
            <p>Please contact our shop for assistance.</p>
        </div>

        <div class="info-item">
            <h3>Email Address</h3>
            <p>Contact us through the enquiry form.</p>
        </div>

        <div class="info-item">
            <h3>Business Hours</h3>
            <p>Monday – Saturday</p>
            <p>9:00 AM – 6:00 PM</p>
        </div>

    </div>

    <!-- Contact Form -->

    <div class="contact-form">

        <h2>Send Us a Message</h2>

        <?php if ($message !== "") { ?>

            <div class="form-message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>

        <form method="POST" action="contact.php">

            <div class="form-group">

                <label for="name">
                    Full Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    maxlength="100"
                    required>

            </div>

            <div class="form-group">

                <label for="email">
                    Email Address <span class="required">*</span>
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    maxlength="150"
                    required>

            </div>

            <div class="form-group">

                <label for="phone">Phone Number</label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($phone); ?>"
                    maxlength="20">

            </div>

            <div class="form-group">

                <label for="subject">
                    Subject <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    value="<?php echo htmlspecialchars($subject); ?>"
                    maxlength="200"
                    required>

            </div>

            <div class="form-group">

                <label for="message">
                    Your Message <span class="required">*</span>
                </label>

                <textarea
                    id="message"
                    name="message"
                    maxlength="5000"
                    required><?php echo htmlspecialchars($enquiry); ?></textarea>

            </div>

            <button type="submit" class="submit-btn">
                Send Message
            </button>

        </form>

    </div>

</div>

<footer>

    <p>&copy; 2026 Home Builders Choice. All Rights Reserved.</p>

</footer>

</body>
</html>

<?php
$conn->close();
?>

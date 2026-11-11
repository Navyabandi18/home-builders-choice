
<?php
session_start();

require_once "../config/db.php";
require_once "../config/razorpay.php";

// Prevent cached payment pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Customer login check
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    die("Invalid order.");
}

// Verify order belongs to logged-in customer
$stmt = $conn->prepare(
    "SELECT order_id, subtotal, advance_amount,
            balance_amount, payment_status
     FROM orders
     WHERE order_id = ? AND customer_id = ?"
);

$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found.");
}

// Verify payment session
if (
    empty($_SESSION['pending_order_id']) ||
    (int)$_SESSION['pending_order_id'] !== $order_id
) {
    die("Invalid payment session. Please return to checkout.");
}

// Check order payment status
if ($order['payment_status'] !== 'Pending') {
    die("This order is not awaiting advance payment.");
}

// Fetch the pending Advance payment
$stmt = $conn->prepare(
    "SELECT payment_id, amount, razorpay_order_id, payment_status
     FROM payments
     WHERE order_id = ?
       AND payment_type = 'Advance'
       AND payment_method = 'Online'
     ORDER BY payment_id DESC
     LIMIT 1"
);

$stmt->bind_param("i", $order_id);
$stmt->execute();

$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$payment ||
    empty($payment['razorpay_order_id']) ||
    $payment['payment_status'] !== 'Pending'
) {
    die("No pending Razorpay payment found.");
}

// Confirm the payment amount matches the order's advance amount
$payment_amount = (float)$payment['amount'];
$order_advance = (float)$order['advance_amount'];

if (
    $payment_amount <= 0 ||
    abs($payment_amount - $order_advance) > 0.01
) {
    die("Payment amount mismatch. Please contact support.");
}

// Convert amount into paise
$amount_in_paise = (int)round($payment_amount * 100);

if ($amount_in_paise <= 0) {
    die("Invalid payment amount.");
}

// Fetch customer details
$stmt = $conn->prepare(
    "SELECT full_name, email, phone
     FROM customers
     WHERE customer_id = ?"
);

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Customer details not found.");
}

// Safe values for JavaScript
$razorpay_key_id = json_encode(
    RAZORPAY_KEY_ID,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$razorpay_order_id = json_encode(
    $payment['razorpay_order_id'],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$customer_name = json_encode(
    $customer['full_name'] ?? '',
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$customer_email = json_encode(
    $customer['email'] ?? '',
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

$customer_phone = json_encode(
    $customer['phone'] ?? '',
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Razorpay Payment - Home Builders Choice</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        .payment-box {
            max-width: 550px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
            text-align: center;
        }

        .payment-box p {
            margin: 15px 0;
        }

        .pay-button {
            background: #198754;
            color: white;
            padding: 13px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .pay-button:hover {
            background: #146c43;
        }

        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #paymentMessage {
            color: #555;
            margin-top: 15px;
        }
    </style>

</head>

<body>

<header>
    <h2>Home Builders Choice</h2>
</header>

<div class="payment-box">

    <h2>Complete Your Advance Payment</h2>

    <p>
        <strong>Order ID:</strong>
        <?php echo (int)$order['order_id']; ?>
    </p>

    <p>
        <strong>Total Rental Amount:</strong>
        ₹<?php echo number_format((float)$order['subtotal'], 2); ?>
    </p>

    <p>
        <strong>Advance Payment (25%):</strong>
        ₹<?php echo number_format($payment_amount, 2); ?>
    </p>

    <p>
        <strong>Remaining Balance (75%):</strong>
        ₹<?php echo number_format((float)$order['balance_amount'], 2); ?>
    </p>

    <hr>

    <button
        type="button"
        id="payButton"
        class="pay-button"
    >
        Pay Advance Now
    </button>

    <p id="paymentMessage"></p>

</div>

<script>

const payButton = document.getElementById("payButton");
const paymentMessage = document.getElementById("paymentMessage");

const options = {

    key: <?php echo $razorpay_key_id; ?>,

    amount: <?php echo $amount_in_paise; ?>,

    currency: "INR",

    name: "Home Builders Choice",

    description: "Rental Advance Payment",

    order_id: <?php echo $razorpay_order_id; ?>,

    handler: function (response) {

        paymentMessage.innerText = "Verifying your payment...";
        payButton.disabled = true;

        const form = document.createElement("form");

        form.method = "POST";
        form.action = "verify_payment.php";

        const fields = {
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature,
            order_id: <?php echo (int)$order_id; ?>
        };

        for (const key in fields) {

            const input = document.createElement("input");

            input.type = "hidden";
            input.name = key;
            input.value = fields[key];

            form.appendChild(input);
        }

        document.body.appendChild(form);

        form.submit();
    },

    prefill: {
        name: <?php echo $customer_name; ?>,
        email: <?php echo $customer_email; ?>,
        contact: <?php echo $customer_phone; ?>
    },

    theme: {
        color: "#198754"
    },

    modal: {
        ondismiss: function () {

            paymentMessage.innerText =
                "Payment window closed. Your order is still pending. You can try again.";

            payButton.disabled = false;
        }
    }

};

if (typeof Razorpay === "undefined") {

    paymentMessage.innerText =
        "Unable to load the payment service. Please refresh the page.";

    payButton.disabled = true;

} else {

    const razorpay = new Razorpay(options);

    payButton.addEventListener("click", function () {

        payButton.disabled = true;
        paymentMessage.innerText = "Opening secure payment window...";

        try {
            razorpay.open();
        } catch (error) {

            paymentMessage.innerText =
                "Unable to open payment window. Please try again.";

            payButton.disabled = false;
        }

    });

}

</script>

</body>
</html>

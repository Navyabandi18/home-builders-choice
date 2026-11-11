
<?php
session_start();

require_once "../config/db.php";
require_once "../config/razorpay.php";

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

// Check customer login
if (!isset($_SESSION['customer_id'])) {
    die("Please login first.");
}

$customer_id = (int)$_SESSION['customer_id'];

// Get Razorpay response values
$razorpay_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$razorpay_order_id = trim($_POST['razorpay_order_id'] ?? '');
$razorpay_signature = trim($_POST['razorpay_signature'] ?? '');
$order_id = filter_var(
    $_POST['order_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Validate received values and session
if (
    !$order_id ||
    $order_id <= 0 ||
    $razorpay_payment_id === '' ||
    $razorpay_order_id === '' ||
    $razorpay_signature === '' ||
    empty($_SESSION['pending_order_id']) ||
    (int)$_SESSION['pending_order_id'] !== $order_id
) {
    die("Invalid payment details. Please contact support.");
}

// Verify that the order belongs to the logged-in customer
$stmt = $conn->prepare(
    "SELECT order_id, advance_amount, payment_status
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

// Fetch the corresponding online advance payment
$stmt = $conn->prepare(
    "SELECT payment_id, amount, razorpay_order_id,
            payment_status, transaction_reference
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
    !hash_equals(
        (string)$payment['razorpay_order_id'],
        $razorpay_order_id
    )
) {
    die("Payment record not found.");
}

// Confirm that the local payment amount matches the order
$expected_amount = (int)round(
    (float)$order['advance_amount'] * 100
);

$recorded_amount = (int)round(
    (float)$payment['amount'] * 100
);

if (
    $expected_amount <= 0 ||
    $recorded_amount !== $expected_amount
) {
    die("Payment amount mismatch. Please contact support.");
}

// Verify Razorpay signature
$expected_signature = hash_hmac(
    'sha256',
    $razorpay_order_id . '|' . $razorpay_payment_id,
    RAZORPAY_KEY_SECRET
);

if (!hash_equals($expected_signature, $razorpay_signature)) {
    die("Payment signature verification failed.");
}

// Retrieve payment details directly from Razorpay
$ch = curl_init(
    RAZORPAY_API_URL . "payments/" . rawurlencode($razorpay_payment_id)
);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

if ($response === false || $http_code !== 200) {
    error_log("Razorpay payment lookup failed: " . $curl_error);
    die("Unable to verify payment with Razorpay. Please try again.");
}

$payment_details = json_decode($response, true);

// Confirm the actual payment details
if (
    !is_array($payment_details) ||
    ($payment_details['order_id'] ?? '') !== $razorpay_order_id ||
    (int)($payment_details['amount'] ?? 0) !== $expected_amount ||
    ($payment_details['currency'] ?? '') !== 'INR' ||
    ($payment_details['status'] ?? '') !== 'captured'
) {
    die(
        "Payment is not captured yet. Please contact support or retry."
    );
}

// Update database only after successful Razorpay verification
$transaction_started = false;

try {

    $conn->begin_transaction();
    $transaction_started = true;

    // Lock the order record
    $stmt = $conn->prepare(
        "SELECT order_id, payment_status
         FROM orders
         WHERE order_id = ? AND customer_id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();

    $locked_order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$locked_order) {
        throw new Exception("Order not found during payment update.");
    }

    // Lock the payment record
    $stmt = $conn->prepare(
        "SELECT payment_id, payment_status,
                razorpay_order_id, transaction_reference
         FROM payments
         WHERE payment_id = ? AND order_id = ?
         FOR UPDATE"
    );

    $stmt->bind_param("ii", $payment['payment_id'], $order_id);
    $stmt->execute();

    $locked_payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$locked_payment) {
        throw new Exception("Payment record not found.");
    }

    if (
        $locked_payment['razorpay_order_id'] !== $razorpay_order_id
    ) {
        throw new Exception("Razorpay order ID mismatch.");
    }

    // Handle duplicate callback safely
    if ($locked_payment['payment_status'] === 'Paid') {

        if (
            $locked_payment['transaction_reference'] !==
            $razorpay_payment_id
        ) {
            throw new Exception(
                "Payment was already completed with a different transaction."
            );
        }

        if ($locked_order['payment_status'] !== 'Advance Paid') {
            throw new Exception(
                "Order payment status does not match the completed payment."
            );
        }

        // The same payment was already successfully processed
        $conn->commit();
        $transaction_started = false;

    } else {

        if ($locked_payment['payment_status'] !== 'Pending') {
            throw new Exception("Payment is not pending.");
        }

        if ($locked_order['payment_status'] !== 'Pending') {
            throw new Exception("Order is not awaiting advance payment.");
        }

        // Mark advance payment as paid
        $stmt = $conn->prepare(
            "UPDATE payments
             SET payment_status = 'Paid',
                 transaction_reference = ?
             WHERE payment_id = ?
               AND payment_status = 'Pending'"
        );

        $stmt->bind_param(
            "si",
            $razorpay_payment_id,
            $payment['payment_id']
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception("Payment update failed.");
        }

        $stmt->close();

        // Update order status
        $stmt = $conn->prepare(
            "UPDATE orders
             SET payment_status = 'Advance Paid',
                 order_status = 'Confirmed'
             WHERE order_id = ?
               AND customer_id = ?
               AND payment_status = 'Pending'"
        );

        $stmt->bind_param("ii", $order_id, $customer_id);
        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception("Order status update failed.");
        }

        $stmt->close();

        $conn->commit();
        $transaction_started = false;
    }

    // Clear cart only after successful payment verification
    unset($_SESSION['cart']);
    unset($_SESSION['rental']);
    unset($_SESSION['pending_order_id']);

    header(
        "Location: order_confirmation.php?order_id=" . $order_id
    );
    exit();

} catch (Throwable $e) {

    if ($transaction_started) {
        $conn->rollback();
    }

    error_log(
        "Payment verification error: " . $e->getMessage()
    );

    die(
        "Payment verification failed. Please contact support."
    );
}
?>


<?php
session_start();

require_once "../config/db.php";
require_once "../config/razorpay.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit();
}

// Customer must be logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

// Verify CSRF token
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submitted_token) ||
    !is_string($session_token) ||
    $session_token === '' ||
    !hash_equals($session_token, $submitted_token)
) {
    die("Invalid or expired request. Please return to checkout and try again.");
}

// Check cart
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$cart = $_SESSION['cart'];
$rentals = $_SESSION['rental'] ?? [];
$start_dates = $_POST['start_dates'] ?? [];

if (!is_array($rentals) || !is_array($start_dates)) {
    die("Invalid rental information.");
}

$items = [];
$subtotal = 0;

// Calculate rental end date
function calculateEndDate($start_date, $duration, $rental_type) {

    $start = new DateTimeImmutable($start_date);

    if ($rental_type === 'daily') {
        $end = $start->modify("+$duration days");

    } elseif ($rental_type === 'weekly') {
        $days = $duration * 7;
        $end = $start->modify("+$days days");

    } else {
        $day = (int)$start->format('d');

        $end = $start
            ->modify('first day of this month')
            ->modify("+$duration months");

        $last_day = (int)$end->format('t');

        $end = $end->setDate(
            (int)$end->format('Y'),
            (int)$end->format('m'),
            min($day, $last_day)
        );
    }

    return $end->format('Y-m-d');
}

// Validate cart products
foreach ($cart as $product_id => $quantity) {

    $product_id = filter_var($product_id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (
        $product_id === false ||
        $quantity === false ||
        $product_id <= 0 ||
        $quantity <= 0
    ) {
        die("Invalid product or quantity.");
    }

    $rental = $rentals[$product_id] ?? null;
    $start_date = $start_dates[$product_id] ?? '';

    if (
        !is_array($rental) ||
        !in_array($rental['type'] ?? '', ['daily', 'weekly', 'monthly'], true) ||
        !isset($rental['duration']) ||
        filter_var($rental['duration'], FILTER_VALIDATE_INT) === false ||
        (int)$rental['duration'] < 1 ||
        (int)$rental['duration'] > 365
    ) {
        die("Invalid rental details.");
    }

    // Validate start date
    $date_object = is_string($start_date)
        ? DateTime::createFromFormat('!Y-m-d', $start_date)
        : false;

    if (
        !$date_object ||
        $date_object->format('Y-m-d') !== $start_date ||
        $start_date < date('Y-m-d')
    ) {
        die("Please select a valid rental start date.");
    }

    // Fetch current product details
    $stmt = $conn->prepare(
        "SELECT product_name, rental_price, weekly_price,
                monthly_price, availability
         FROM products
         WHERE product_id = ?"
    );

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product || $product['availability'] !== 'Available') {
        die("Product unavailable.");
    }

    $rental_type = $rental['type'];
    $duration = (int)$rental['duration'];

    if ($rental_type === 'daily') {
        $unit_price = (float)$product['rental_price'];
    } elseif ($rental_type === 'weekly') {
        $unit_price = (float)$product['weekly_price'];
    } else {
        $unit_price = (float)$product['monthly_price'];
    }

    if (!is_finite($unit_price) || $unit_price <= 0) {
        die("Invalid rental price.");
    }

    $line_total = round($unit_price * $duration * $quantity, 2);

    if (!is_finite($line_total) || $line_total <= 0) {
        die("Invalid rental total.");
    }

    $end_date = calculateEndDate(
        $start_date,
        $duration,
        $rental_type
    );

    $subtotal += $line_total;

    $items[] = [
        'product_id' => $product_id,
        'product_name' => $product['product_name'],
        'rental_type' => $rental_type,
        'duration' => $duration,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'unit_price' => $unit_price,
        'quantity' => $quantity,
        'line_total' => $line_total
    ];
}

if (empty($items)) {
    die("No valid rental products found.");
}

// Calculate advance and balance
$subtotal = round($subtotal, 2);
$advance_amount = round($subtotal * 0.25, 2);
$balance_amount = round($subtotal - $advance_amount, 2);

// Razorpay amount is in paise
$amount_paise = (int)round($advance_amount * 100);

if ($amount_paise < 100) {
    die("Advance payment must be at least ₹1.");
}

$transaction_started = false;

try {

    // Validate Razorpay configuration
    if (
        !defined('RAZORPAY_KEY_ID') ||
        !defined('RAZORPAY_KEY_SECRET') ||
        !defined('RAZORPAY_API_URL') ||
        RAZORPAY_KEY_ID === '' ||
        RAZORPAY_KEY_SECRET === ''
    ) {
        throw new Exception("Razorpay configuration is missing.");
    }

    // Start database transaction
    $conn->begin_transaction();
    $transaction_started = true;

    // Create database order
    $stmt = $conn->prepare(
        "INSERT INTO orders
        (customer_id, subtotal, advance_amount, balance_amount,
         payment_status, order_status)
         VALUES (?, ?, ?, ?, 'Pending', 'Pending')"
    );

    $stmt->bind_param(
        "iddd",
        $customer_id,
        $subtotal,
        $advance_amount,
        $balance_amount
    );

    $stmt->execute();

    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert rental items
    $stmt = $conn->prepare(
        "INSERT INTO order_items
        (order_id, product_id, product_name, rental_type,
         duration, rental_start_date, rental_end_date,
         unit_price, quantity, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($items as $item) {

        $stmt->bind_param(
            "iississdid",
            $order_id,
            $item['product_id'],
            $item['product_name'],
            $item['rental_type'],
            $item['duration'],
            $item['start_date'],
            $item['end_date'],
            $item['unit_price'],
            $item['quantity'],
            $item['line_total']
        );

        $stmt->execute();
    }

    $stmt->close();

    // Prepare Razorpay order data
    $razorpay_data = [
        'amount' => $amount_paise,
        'currency' => 'INR',
        'receipt' => 'HBC_' . $order_id,
        'notes' => [
            'website_order_id' => (string)$order_id,
            'customer_id' => (string)$customer_id
        ]
    ];

    $json_data = json_encode($razorpay_data);

    if ($json_data === false) {
        throw new Exception("Unable to encode Razorpay order data.");
    }

    // Connect to Razorpay
    $ch = curl_init(RAZORPAY_API_URL . 'orders');

    if ($ch === false) {
        throw new Exception("Unable to initialize cURL.");
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_data,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);

    curl_close($ch);

    // Check Razorpay response
    if (
        $response === false ||
        $http_code < 200 ||
        $http_code >= 300
    ) {

        error_log(
            "Razorpay API diagnostic: HTTP " . $http_code .
            " | cURL error number: " . $curl_errno .
            " | cURL error: " . $curl_error .
            " | API response: " .
            (is_string($response) ? $response : 'No response')
        );

        throw new Exception(
            "Razorpay API request failed. HTTP status: " . $http_code
        );
    }

    // Decode Razorpay response
    $razorpay_order = json_decode($response, true);

    if (
        !is_array($razorpay_order) ||
        empty($razorpay_order['id']) ||
        ($razorpay_order['currency'] ?? '') !== 'INR' ||
        (int)($razorpay_order['amount'] ?? 0) !== $amount_paise
    ) {

        error_log("Invalid Razorpay API response: " . $response);

        throw new Exception("Invalid Razorpay response.");
    }

    $razorpay_order_id = $razorpay_order['id'];

    // Save pending advance payment
    $payment_type = "Advance";

    $stmt = $conn->prepare(
        "INSERT INTO payments
        (order_id, payment_type, amount, payment_method,
         payment_status, razorpay_order_id)
         VALUES (?, ?, ?, 'Online', 'Pending', ?)"
    );

    $stmt->bind_param(
        "isds",
        $order_id,
        $payment_type,
        $advance_amount,
        $razorpay_order_id
    );

    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();
    $transaction_started = false;

    // Keep cart until payment is successfully verified
    $_SESSION['pending_order_id'] = $order_id;

    // Redirect to Razorpay checkout page
    header("Location: payment.php?order_id=" . $order_id);
    exit();

} catch (Throwable $e) {

    if ($transaction_started) {
        $conn->rollback();
    }

    error_log("Razorpay order error: " . $e->getMessage());

    die(
        "Unable to initiate payment. Please try again later." .
        '<br><a href="cart.php">Return to Cart</a>'
    );
}
?>

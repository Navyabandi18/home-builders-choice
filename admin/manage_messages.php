
<?php
session_start();
require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Delete message
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_message'])) {

    $message_id = (int) $_POST['message_id'];

    if ($message_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM contact_messages WHERE message_id = ?"
        );

        $stmt->bind_param("i", $message_id);

        if ($stmt->execute()) {
            $_SESSION['message_notice'] = "Enquiry deleted successfully.";
        } else {
            $_SESSION['message_notice'] = "Unable to delete the enquiry.";
        }

        $stmt->close();
    }

    header("Location: manage_messages.php");
    exit();
}

// Get notification
$notice = "";

if (isset($_SESSION['message_notice'])) {
    $notice = $_SESSION['message_notice'];
    unset($_SESSION['message_notice']);
}

// Fetch all contact enquiries
$sql = "SELECT * FROM contact_messages ORDER BY submitted_at DESC";
$result = $conn->query($sql);

function clean($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Messages | Admin</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 1250px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0;
            color: #222;
        }

        .back-link {
            background: #333;
            color: white;
            padding: 10px 16px;
            border-radius: 5px;
            text-decoration: none;
        }

        .back-link:hover {
            background: #555;
        }

        .notice {
            padding: 12px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border-radius: 5px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            background: #222;
            color: white;
        }

        tr:hover {
            background: #f8f8f8;
        }

        .message-cell {
            max-width: 280px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 13px;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #b02a37;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .table-container {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Manage Contact Messages</h1>

        <a href="dashboard.php" class="back-link">
            Back to Dashboard
        </a>
    </div>

    <?php if ($notice !== ""): ?>
        <div class="notice">
            <?php echo clean($notice); ?>
        </div>
    <?php endif; ?>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Submitted At</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($row = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo (int) $row['message_id']; ?></td>

                        <td><?php echo clean($row['name']); ?></td>

                        <td><?php echo clean($row['email']); ?></td>

                        <td><?php echo clean($row['phone']); ?></td>

                        <td><?php echo clean($row['subject']); ?></td>

                        <td class="message-cell">
                            <?php echo clean($row['message']); ?>
                        </td>

                        <td>
                            <?php echo clean($row['submitted_at']); ?>
                        </td>

                        <td>
                            <form method="POST"
                                  action="manage_messages.php"
                                  onsubmit="return confirm('Are you sure you want to delete this enquiry?');">

                                <input type="hidden"
                                       name="message_id"
                                       value="<?php echo (int) $row['message_id']; ?>">

                                <button type="submit"
                                        name="delete_message"
                                        class="delete-btn">
                                    Delete
                                </button>

                            </form>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8" class="empty">
                        No contact enquiries found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>

</div>

</body>
</html>

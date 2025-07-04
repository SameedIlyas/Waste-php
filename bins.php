<?php
require 'db_connection.php';
require 'send_email.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$threshold = 80; // Define the fill level threshold

// Fetch all bins
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = "SELECT id, bin_name, waste_type, fill_level, last_emptied, status FROM bins";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $bins = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($bins);
    } else {
        echo json_encode(['success' => false, 'message' => 'No bins found.']);
    }
    exit();
}

// Update bin fill level
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['bin_name']) && isset($data['fill_level'])) {
        $bin_name = $conn->real_escape_string($data['bin_name']);
        $fill_level = (int)$data['fill_level'];

        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, fill_level FROM bins WHERE bin_name = ?");
        $stmt->bind_param("s", $bin_name);
        $stmt->execute();
        $bin_result = $stmt->get_result();
        $bin = ($bin_result->num_rows > 0) ? $bin_result->fetch_assoc() : null;
        $bin_id = $bin['id'] ?? null;
        $prev_fill_level = $bin['fill_level'] ?? null;

        if ($bin_id) {
            $stmt = $conn->prepare("UPDATE bins SET fill_level = ? WHERE id = ?");
            $stmt->bind_param("ii", $fill_level, $bin_id);

            if ($stmt->execute()) {
                $message = "";
                $send_notification = false;

                if ($fill_level >= $threshold && ($prev_fill_level === null || $prev_fill_level < $threshold)) {
                    $message = "Alert: Bin '$bin_name' is now at $fill_level% full.";
                    $send_notification = true;
                }

                if ($fill_level == 0) {
                    $timestamp = date('Y-m-d H:i:s');
                    $stmt = $conn->prepare("UPDATE bins SET last_emptied = ? WHERE id = ?");
                    $stmt->bind_param("si", $timestamp, $bin_id);
                    $stmt->execute();

                    $message = "Bin '$bin_name' has been emptied.";
                    $send_notification = true;
                }

                if ($send_notification) {
                    // Store the notification
                    $stmt = $conn->prepare("INSERT INTO notifications (bin_id, message, email_sent, created_at) VALUES (?, ?, 0, NOW())");
                    $stmt->bind_param("is", $bin_id, $message);
                    $stmt->execute();
                
                    // Fetch all user emails
                    $user_result = $conn->query("SELECT email FROM users");
                    if ($user_result->num_rows > 0) {
                        while ($user = $user_result->fetch_assoc()) {
                            send_email($user['email'], 'Smart Waste Notification', $message);
                        }
                
                        // Update email_sent flag to 1
                        $stmt = $conn->prepare("UPDATE notifications SET email_sent = 1 WHERE bin_id = ? AND message = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->bind_param("is", $bin_id, $message);
                        $stmt->execute();
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Bin updated successfully' . ($send_notification ? ', notification stored.' : '.')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update bin fill level.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bin not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Bin name and fill level are required.']);
    }
    exit();
}
?>

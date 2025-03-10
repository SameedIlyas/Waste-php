<?php
require 'db_connection.php'; // Include database connection
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Fetch all bins
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = "SELECT * FROM bins"; // Query to get all bins from the database
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $bins = [];
        
        // Loop through the result set and fetch bin details
        while ($row = $result->fetch_assoc()) {
            $bins[] = [
                'id' => $row['id'],
                'bin_name' => $row['bin_name'],
                'waste_type' => $row['waste_type'],
                'fill_level' => $row['fill_level'],
                'last_emptied' => $row['last_emptied'],
                'status' => $row['status']
            ];
        }

        // Return the data as JSON
        echo json_encode($bins);
    } else {
        echo json_encode(['success' => false, 'message' => 'No bins found.']);
    }
}

$threshold = 80; // Define the fill level threshold

// Function to send email alerts
function send_email($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with your email
        $mail->Password = 'your-email-password'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Smart Waste System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Update bin fill level and send notifications
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['bin_name']) && isset($data['fill_level'])) {
        $bin_name = $conn->real_escape_string($data['bin_name']);
        $fill_level = (int)$data['fill_level'];
        
        // Get bin details
        $bin_query = "SELECT id, fill_level FROM bins WHERE bin_name = '$bin_name'";
        $bin_result = $conn->query($bin_query);
        $bin = ($bin_result->num_rows > 0) ? $bin_result->fetch_assoc() : null;
        $bin_id = $bin['id'] ?? null;
        $prev_fill_level = $bin['fill_level'] ?? null;
        
        if ($bin_id) {
            $query = "UPDATE bins SET fill_level = $fill_level WHERE id = $bin_id";
            
            if ($conn->query($query) === TRUE) {
                $message = "";
                $send_notification = false;
                
                if ($fill_level >= $threshold && ($prev_fill_level === null || $prev_fill_level < $threshold)) {
                    $message = "Alert: Bin '$bin_name' fill level has exceeded $threshold% and is now at $fill_level%.";
                    $send_notification = true;
                }
                
                if ($fill_level == 0) {
                    $timestamp = date('Y-m-d H:i:s');
                    $query = "UPDATE bins SET last_emptied = '$timestamp' WHERE id = $bin_id";
                    $conn->query($query);
                    $message = "Bin '$bin_name' has been emptied.";
                    $send_notification = true;
                }
                
                if ($send_notification) {
                    // Store notification in database
                    $stmt = $conn->prepare("INSERT INTO notifications (bin_id, message, email_sent, created_at) VALUES (?, ?, 0, NOW())");
                    $stmt->bind_param("is", $bin_id, $message);
                    $stmt->execute();
                    
                    // Fetch admin emails from the users table
                    $admin_query = "SELECT email FROM users WHERE email LIKE '%@%'"; // Fetch all users (Assumption: All users receive alerts)
                    $admin_result = $conn->query($admin_query);
                    
                    if ($admin_result->num_rows > 0) {
                        while ($admin = $admin_result->fetch_assoc()) {
                            send_email($admin['email'], "Bin Alert", $message);
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Bin fill level updated' . ($send_notification ? ' and email notification sent.' : '.')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update bin fill level.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bin not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Bin name and fill level are required.']);
    }
}
?>

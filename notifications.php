<?php
/*require 'db_connection.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bin_id = $_POST['bin_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO notifications (bin_id, message, email_sent) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $bin_id, $message);
    $stmt->execute();

    // Email logic
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';
    $mail->Password = 'your-email-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('your-email@gmail.com', 'Smart Waste System');
    $mail->addAddress('recipient@example.com');

    $mail->isHTML(true);
    $mail->Subject = 'Bin Alert';
    $mail->Body = $message;

    if ($mail->send()) {
        echo json_encode(['success' => true, 'message' => 'Notification sent and email delivered']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
}
?>*/
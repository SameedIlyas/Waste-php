<?php
require 'db_connection.php';
require 'send_email.php'; // Import the email function

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = filter_var(trim($data['name'] ?? ''), FILTER_SANITIZE_STRING);
    $username = filter_var(trim($data['username'] ?? ''), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $passwordRaw = $data['password'] ?? '';
    $password = $passwordRaw ? password_hash($passwordRaw, PASSWORD_DEFAULT) : null;

    if (!$name || !$username || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $checkUser = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $checkUser->bind_param("ss", $email, $username);
        $checkUser->execute();
        $result = $checkUser->get_result();

        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email or username already exists.']);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $username, $email, $password);

            if ($stmt->execute()) {
                // Send welcome email
                $subject = "Welcome to Smart Waste Management";
                $message = "Hello $name,<br><br>Thank you for registering with our Smart Waste System.<br>We're glad to have you on board!";

                send_email($email, $subject, $message); // optional: handle false return if needed

                echo json_encode(['success' => true, 'message' => 'User registered successfully. Welcome email sent.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Registration failed.']);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}
?>

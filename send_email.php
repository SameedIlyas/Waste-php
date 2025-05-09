<?php
require 'vendor/autoload.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;

function send_email($to, $subject, $htmlBody, $textBody = 'This is a fallback text version') {
    $apiKey = getenv('MAILERSEND_API_KEY'); // Set in your .env or server config
    if (!$apiKey) {
        error_log("MailerSend API key not found.");
        return false;
    }

    $mailersend = new MailerSend(['api_key' => $apiKey]);

    $recipients = [ new Recipient($to, 'User') ];

    $emailParams = (new EmailParams())
        ->setFrom(getenv('EMAIL_FROM'))
        ->setFromName(getenv('EMAIL_FROM_NAME'))
        ->setRecipients($recipients)
        ->setSubject($subject)
        ->setHtml($htmlBody)
        ->setText($textBody);

    try {
        $mailersend->email->send($emailParams);
        return true;
    } catch (Exception $e) {
        error_log("MailerSend Error: " . $e->getMessage());
        return false;
    }
}

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

function sendParcelNotification($pdo, $recipient_email, $recipient_name, $tracking_number) {
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('email_subject', 'email_body')");
    $email_templates = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $subject_template = $email_templates['email_subject'] ?? 'Your Parcel Has Arrived!';
    $body_template = $email_templates['email_body'] ?? 'Dear {recipient_name}, a parcel with tracking number {tracking_number} has arrived.';

    $placeholders = ['{recipient_name}', '{tracking_number}'];
    $replacements = [htmlspecialchars($recipient_name), htmlspecialchars($tracking_number)];
    
    $final_subject = str_replace($placeholders, $replacements, $subject_template);
    $final_body = str_replace($placeholders, $replacements, $body_template);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = '';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@website.com', 'KoPickUp Notifications');
        $mail->addAddress($recipient_email, $recipient_name);

        $mail->isHTML(true);
        $mail->Subject = $final_subject;
        $mail->Body    = $final_body;
        $mail->AltBody = strip_tags(str_replace(['</p>', '<br>'], "\n", $final_body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
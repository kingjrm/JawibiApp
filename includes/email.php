<?php
// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');

// Email configuration from environment
define('SMTP_HOST', $env['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $env['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $env['SMTP_USERNAME'] ?? 'maildiaryapp@gmail.com');
define('SMTP_PASSWORD', $env['SMTP_PASSWORD'] ?? 'pqbw zeag upef fntd');
define('FROM_EMAIL', $env['FROM_EMAIL'] ?? 'maildiaryapp@gmail.com');
define('FROM_NAME', $env['FROM_NAME'] ?? 'Jollibee');

// Function to send OTP email
function sendOTPEmail($to, $otp, $type = 'registration') {
    $subject = $type === 'registration' ? 'Jollibee - Verify Your Account' : 'Jollibee - Password Reset';

    $message = "
    <html>
    <head>
        <title>$subject</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #e53e3e; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .otp-code { font-size: 24px; font-weight: bold; color: #e53e3e; text-align: center; margin: 20px 0; padding: 15px; background: white; border: 2px dashed #e53e3e; border-radius: 5px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Jollibee</h1>
                <p>Bringing Joy to Every Meal!</p>
            </div>
            <div class='content'>
                <h2>$subject</h2>
                " . ($type === 'registration' ?
                "<p>Thank you for registering with Jollibee! To complete your account setup, please verify your email address using the OTP code below:</p>" :
                "<p>We received a request to reset your password. Use the OTP code below to proceed:</p>") . "

                <div class='otp-code'>$otp</div>

                <p><strong>Important:</strong> This OTP will expire in 10 minutes for security reasons.</p>
                <p>If you didn't request this, please ignore this email.</p>

                <p>Best regards,<br>Jollibee Team</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Jollibee Corporation. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "Return-Path: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Configure SMTP settings for Gmail
    ini_set('SMTP', SMTP_HOST);
    ini_set('smtp_port', SMTP_PORT);
    ini_set('sendmail_from', FROM_EMAIL);

    // Try to send email
    $result = mail($to, $subject, $message, $headers);

    // Log the result for debugging
    error_log("Email sending result for $to: " . ($result ? 'SUCCESS' : 'FAILED'));

    return $result;
}

// Function to generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to verify OTP
function verifyOTP($input_otp, $stored_otp, $timestamp) {
    // OTP expires in 10 minutes
    if (time() - $timestamp > 600) {
        return false;
    }
    return $input_otp === $stored_otp;
}
?>
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

    // Try direct SMTP connection first (more reliable for Gmail)
    $smtpResult = sendSMTPEmail($to, $subject, $message);

    if ($smtpResult) {
        error_log("Email sent successfully via SMTP to $to");
        return true;
    }

    // Fallback to PHP mail() function
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "Return-Path: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    ini_set('SMTP', SMTP_HOST);
    ini_set('smtp_port', SMTP_PORT);
    ini_set('sendmail_from', FROM_EMAIL);

    $mailResult = mail($to, $subject, $message, $headers);
    error_log("Email sending result for $to via mail(): " . ($mailResult ? 'SUCCESS' : 'FAILED'));

    return $mailResult;
}

// Direct SMTP email sending function with STARTTLS support
function sendSMTPEmail($to, $subject, $message) {
    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT;
    $username = SMTP_USERNAME;
    $password = SMTP_PASSWORD;
    $from_email = FROM_EMAIL;
    $from_name = FROM_NAME;

    try {
        // Create SSL context to handle certificate issues
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Create socket connection with SSL context
        $socket = stream_socket_client(
            "tcp://$smtp_host:$smtp_port",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        // Set timeout
        stream_set_timeout($socket, 30);

        // Read server greeting
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '220')) {
            fclose($socket);
            return false;
        }

        // Send EHLO
        fputs($socket, "EHLO $smtp_host\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break; // End of response
        }

        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '220')) {
            fclose($socket);
            return false;
        }

        // Enable TLS
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("Failed to enable TLS encryption");
            fclose($socket);
            return false;
        }

        // Send EHLO again after TLS
        fputs($socket, "EHLO $smtp_host\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }

        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '334')) {
            fclose($socket);
            return false;
        }

        // Send username (base64 encoded)
        fputs($socket, base64_encode($username) . "\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '334')) {
            fclose($socket);
            return false;
        }

        // Send password (base64 encoded)
        fputs($socket, base64_encode($password) . "\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '235')) {
            fclose($socket);
            return false;
        }

        // Send MAIL FROM
        fputs($socket, "MAIL FROM: <$from_email>\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '250')) {
            fclose($socket);
            return false;
        }

        // Send RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '250')) {
            fclose($socket);
            return false;
        }

        // Send DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '354')) {
            fclose($socket);
            return false;
        }

        // Send email content
        $email_content = "Subject: $subject\r\n";
        $email_content .= "To: $to\r\n";
        $email_content .= "From: $from_name <$from_email>\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_content .= "\r\n";
        $email_content .= $message;
        $email_content .= "\r\n.\r\n";

        fputs($socket, $email_content);

        // Check response
        $response = fgets($socket, 515);
        if (!checkSMTPResponse($response, '250')) {
            fclose($socket);
            return false;
        }

        // Send QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;

    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

// Helper function to check SMTP responses
function checkSMTPResponse($response, $expected_code) {
    return strpos($response, $expected_code) === 0;
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
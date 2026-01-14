<?php
$title = 'Forgot Password - Jollibee';
include 'includes/header.php';
include 'includes/email_new.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_reset_otp']) || isset($_POST['resend_reset_otp'])) {
        $email = $_POST['email'];

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate and send OTP
            $otp = generateOTP();
            if (sendOTPEmail($email, $otp, 'reset')) {
                // Store reset data in session
                $_SESSION['reset_data'] = [
                    'email' => $email,
                    'user_id' => $user['id'],
                    'otp' => $otp,
                    'timestamp' => time()
                ];
            } else {
                $error = 'Failed to send OTP. Please try again.';
            }
        } else {
            $error = 'No account found with this email address.';
        }
    } elseif (isset($_POST['verify_reset_otp'])) {
        if (isset($_SESSION['reset_data'])) {
            $input_otp = $_POST['otp'];
            $new_password = $_POST['new_password'];

            if (verifyOTP($input_otp, $_SESSION['reset_data']['otp'], $_SESSION['reset_data']['timestamp'])) {
                // OTP verified, update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['reset_data']['user_id']])) {
                    unset($_SESSION['reset_data']);
                    header('Location: login.php?message=Password reset successful! Please login with your new password.');
                    exit;
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Invalid or expired OTP. Please try again.';
            }
        } else {
            $error = 'Session expired. Please start password reset again.';
        }
    }
}

// Check if we should show reset form
$show_reset_form = isset($_SESSION['reset_data']);
?>

<div class="min-h-screen bg-gradient-to-br from-red-50 to-yellow-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <img src="https://1000logos.net/wp-content/uploads/2021/05/Jollibee-logo.png" alt="Jollibee Logo" class="mx-auto h-16 w-auto mb-4">
            <h2 class="text-3xl font-extrabold text-gray-900">Reset Password</h2>
            <p class="mt-2 text-sm text-gray-600">Enter your email to receive a password reset code</p>
        </div>

        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-gray-100">
            <?php if ($show_reset_form): ?>
                <!-- OTP Verification Form -->
                <form method="POST" class="space-y-6">
                    <div class="text-center mb-6">
                        <i class="fas fa-key text-4xl text-red-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Enter Reset Code</h3>
                        <p class="text-sm text-gray-600">We've sent a 6-digit code to <?php echo htmlspecialchars($_SESSION['reset_data']['email']); ?></p>
                    </div>

                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                            Reset Code
                        </label>
                        <input type="text" name="otp" id="otp" maxlength="6"
                               class="appearance-none relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg text-center text-2xl font-bold tracking-widest focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="000000" required>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-red-500"></i>New Password
                        </label>
                        <input type="password" name="new_password" id="new_password" required
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="Enter new password">
                    </div>

                    <div>
                        <button type="submit" name="verify_reset_otp"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 transform hover:scale-105">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-key text-red-500 group-hover:text-red-400"></i>
                            </span>
                            Reset Password
                        </button>
                    </div>

                    <div class="text-center">
                        <button type="button" onclick="resendResetOTP(event)"
                                class="text-red-600 hover:text-red-500 text-sm font-medium">
                            <i class="fas fa-refresh mr-1"></i>Didn't receive the code? Resend
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Email Form -->
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-red-500"></i>Email Address
                        </label>
                        <input type="email" name="email" id="email" required
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="Enter your registered email">
                    </div>

                    <div>
                        <button type="submit" name="send_reset_otp"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 transform hover:scale-105">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-paper-plane text-red-500 group-hover:text-red-400"></i>
                            </span>
                            Send Reset Code
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-red-600 hover:text-red-500 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function resendResetOTP() {
    console.log('Resend button clicked');

    // Show loading state
    const button = event.target.closest('button');
    if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Resending...';
        button.disabled = true;
    }

    // Create a form to resend reset OTP
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'resend_reset_otp';
    input.value = '1';
    form.appendChild(input);

    // Use email from session data (stored when OTP was first sent)
    <?php if (isset($_SESSION['reset_data']['email'])): ?>
    const emailInput = document.createElement('input');
    emailInput.type = 'hidden';
    emailInput.name = 'email';
    emailInput.value = '<?php echo htmlspecialchars($_SESSION['reset_data']['email']); ?>';
    form.appendChild(emailInput);
    <?php endif; ?>

    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include 'includes/footer.php'; ?>
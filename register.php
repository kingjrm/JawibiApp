<?php
$title = 'Register - Jollibee';
include 'includes/header.php';
include 'includes/email_new.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered. Please use a different email.';
        } else {
            // Generate and send OTP
            $otp = generateOTP();
            // Always store registration data in session for testing
            $_SESSION['reg_data'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'otp' => $otp,
                'timestamp' => time()
            ];

            if (sendOTPEmail($email, $otp, 'registration')) {
                $message = 'OTP sent to your email. Please check your inbox and enter the code below.';
            } else {
                $message = 'OTP generated (check testing mode below). Please enter the code.';
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        if (isset($_SESSION['reg_data'])) {
            // Generate new OTP and update session
            $otp = generateOTP();
            if (sendOTPEmail($_SESSION['reg_data']['email'], $otp, 'registration')) {
                $_SESSION['reg_data']['otp'] = $otp;
                $_SESSION['reg_data']['timestamp'] = time();
                $message = 'OTP resent! Please check your inbox.';
            } else {
                $error = 'Failed to resend OTP. Please try again.';
            }
        } else {
            $error = 'Session expired. Please start registration again.';
        }
    } elseif (isset($_POST['verify_otp'])) {
        if (isset($_SESSION['reg_data'])) {
            $input_otp = $_POST['otp'];

            if (verifyOTP($input_otp, $_SESSION['reg_data']['otp'], $_SESSION['reg_data']['timestamp'])) {
                // OTP verified, create account
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, username, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
                if ($stmt->execute([
                    $_SESSION['reg_data']['first_name'],
                    $_SESSION['reg_data']['last_name'],
                    $_SESSION['reg_data']['email'],
                    $_SESSION['reg_data']['phone'],
                    $_SESSION['reg_data']['password'],
                    $_SESSION['reg_data']['email'] // Use email as username
                ])) {
                    unset($_SESSION['reg_data']);
                    header('Location: login.php?message=Registration successful! Please login.');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } else {
                $error = 'Invalid or expired OTP. Please try again.';
            }
        } else {
            $error = 'Session expired. Please start registration again.';
        }
    }
}

// Check if we should show OTP form
$show_otp_form = isset($_SESSION['reg_data']);
?>

<div class="min-h-screen bg-gradient-to-br from-red-50 to-yellow-50 flex flex-col">
    <div class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <img src="https://1000logos.net/wp-content/uploads/2021/05/Jollibee-logo.png" alt="Jollibee Logo" class="mx-auto h-16 w-auto mb-4">
            <h2 class="text-3xl font-extrabold text-gray-900">Join Jollibee!</h2>
            <p class="mt-2 text-sm text-gray-600">Create your account and start ordering delicious food</p>
        </div>

        <?php if (isset($message) && !empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error) && !empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-gray-100">
            <?php if ($show_otp_form): ?>
                <!-- OTP Verification Form -->
                <form method="POST" class="space-y-6">
                    <div class="text-center mb-6">
                        <i class="fas fa-envelope-open-text text-4xl text-red-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Verify Your Email</h3>
                        <p class="text-sm text-gray-600">We've sent a 6-digit code to <?php echo isset($_SESSION['reg_data']['email']) ? htmlspecialchars($_SESSION['reg_data']['email']) : ''; ?></p>
                    </div>

                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                            Enter OTP Code
                        </label>
                        <input type="text" name="otp" id="otp" maxlength="6"
                               class="appearance-none relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg text-center text-2xl font-bold tracking-widest focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="000000" required>
                    </div>

                    <div>
                        <button type="submit" name="verify_otp"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 transform hover:scale-105">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-check text-red-500 group-hover:text-red-400"></i>
                            </span>
                            Verify & Create Account
                        </button>
                    </div>

                    <div class="text-center">
                        <button type="button" onclick="resendOTP()"
                                class="text-red-600 hover:text-red-500 text-sm font-medium">
                            Didn't receive the code? Resend OTP
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Registration Form -->
                <form method="POST" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-red-500"></i>First Name
                        </label>
                        <input type="text" name="first_name" id="first_name" required
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="First name">
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-red-500"></i>Last Name
                        </label>
                        <input type="text" name="last_name" id="last_name" required
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                               placeholder="Last name">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-red-500"></i>Email Address
                    </label>
                    <input type="email" name="email" id="email" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                           placeholder="Enter your email">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone mr-2 text-red-500"></i>Phone Number
                    </label>
                    <input type="tel" name="phone" id="phone"
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                           placeholder="Enter your phone number">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-red-500"></i>Password
                    </label>
                    <input type="password" name="password" id="password" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm transition duration-200"
                           placeholder="Create a password">
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        I agree to the
                        <a href="#" onclick="openModal('terms-modal')" class="text-red-600 hover:text-red-500">Terms of Service</a>
                        and
                        <a href="#" onclick="openModal('privacy-modal')" class="text-red-600 hover:text-red-500">Privacy Policy</a>
                    </label>
                </div>

                <div>
                    <button type="submit" name="send_otp"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200 transform hover:scale-105">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus text-red-500 group-hover:text-red-400"></i>
                        </span>
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Already have an account?</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="login.php"
                       class="w-full flex justify-center py-3 px-4 border border-red-300 rounded-lg shadow-sm bg-white text-sm font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In Instead
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <p class="text-xs text-gray-500">
                Join thousands of happy customers enjoying Jollibee's delicious food!
            </p>
        </div>
    </div>  <!-- close flex-grow -->
</div>  <!-- close min-h-screen -->

<?php include 'includes/footer.php'; ?>

<!-- Terms of Service Modal -->
<div id="terms-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900">Terms of Service</h2>
                <button onclick="closeModal('terms-modal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="text-sm text-gray-700 space-y-3">
                <h3 class="font-semibold text-base">1. Acceptance of Terms</h3>
                <p>By accessing and using Jollibee's online ordering system, you accept and agree to be bound by the terms and provision of this agreement.</p>

                <h3 class="font-semibold text-base">2. Use License</h3>
                <p>Permission is granted to temporarily access the materials on Jollibee's website for personal, non-commercial transitory viewing only.</p>

                <h3 class="font-semibold text-base">3. Order Terms</h3>
                <p>All orders placed through our system are subject to availability and acceptance. We reserve the right to refuse or cancel any order.</p>

                <h3 class="font-semibold text-base">4. Payment Terms</h3>
                <p>Payment must be made at the time of ordering. All prices are subject to change without notice.</p>

                <h3 class="font-semibold text-base">5. Delivery Terms</h3>
                <p>Delivery times are estimates only. We are not responsible for delays caused by circumstances beyond our control.</p>

                <h3 class="font-semibold text-base">6. Refund Policy</h3>
                <p>Refunds will be processed within 5-7 business days for eligible orders. Refunds are not available for consumed items.</p>

                <h3 class="font-semibold text-base">7. User Account</h3>
                <p>You are responsible for maintaining the confidentiality of your account and password.</p>

                <h3 class="font-semibold text-base">8. Contact</h3>
                <p>For questions: legal@jollibee.com | 1-800-JOLLIBEE</p>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div id="privacy-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900">Privacy Policy</h2>
                <button onclick="closeModal('privacy-modal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="text-sm text-gray-700 space-y-3">
                <h3 class="font-semibold text-base">1. Information We Collect</h3>
                <p>We collect information you provide directly to us, such as when you create an account or place an order.</p>

                <h3 class="font-semibold text-base">2. Types of Information</h3>
                <p><strong>Personal:</strong> Name, email, phone, address, payment info<br>
                <strong>Automatic:</strong> IP address, browser info, usage data</p>

                <h3 class="font-semibold text-base">3. How We Use Information</h3>
                <p>To process orders, provide service, send updates, improve services, and maintain security.</p>

                <h3 class="font-semibold text-base">4. Information Sharing</h3>
                <p>We do not sell your information. We may share with service providers, payment processors, and delivery partners.</p>

                <h3 class="font-semibold text-base">5. Data Security</h3>
                <p>We implement security measures to protect your information against unauthorized access.</p>

                <h3 class="font-semibold text-base">6. Your Rights</h3>
                <p>You have the right to access, correct, or delete your personal information.</p>

                <h3 class="font-semibold text-base">7. Contact</h3>
                <p>For privacy questions: privacy@jollibee.com | 1-800-JOLLIBEE</p>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('bg-opacity-50')) {
        event.target.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
});

function resendOTP() {
    // Create a form to resend OTP
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'resend_otp';
    input.value = '1';
    form.appendChild(input);

    // Use session data for resend (since form fields may not be available)
    document.body.appendChild(form);
    form.submit();
}
</script>
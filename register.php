<?php
$title = 'Register - Jollibee';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$first_name, $last_name, $email, $phone, $password])) {
        header('Location: login.php');
        exit;
    } else {
        $error = 'Registration failed. Email might already be in use.';
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-red-50 to-yellow-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <img src="https://1000logos.net/wp-content/uploads/2021/05/Jollibee-logo.png" alt="Jollibee Logo" class="mx-auto h-16 w-auto mb-4">
            <h2 class="text-3xl font-extrabold text-gray-900">Join Jollibee!</h2>
            <p class="mt-2 text-sm text-gray-600">Create your account and start ordering delicious food</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-gray-100">
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
                        <a href="#" class="text-red-600 hover:text-red-500">Terms of Service</a>
                        and
                        <a href="#" class="text-red-600 hover:text-red-500">Privacy Policy</a>
                    </label>
                </div>

                <div>
                    <button type="submit"
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

        <div class="text-center">
            <p class="text-xs text-gray-500">
                Join thousands of happy customers enjoying Jollibee's delicious food!
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
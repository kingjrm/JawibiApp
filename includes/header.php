<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Jollibee Ordering System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; font-size: 14px; }
        .hero-bg { background-image: url('assets/chickenjoy.jpg'); }
        .fade-in { animation: fadeIn 0.8s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .tilt:hover { transform: rotateY(5deg) rotateX(5deg); transition: transform 0.3s; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <header class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <img src="https://1000logos.net/wp-content/uploads/2021/05/Jollibee-logo.png" alt="Jollibee Logo" class="h-10">
                    <span class="text-xl font-bold text-red-600">Jollibee</span>
                </div>
                <nav class="space-x-6">
                    <a href="index.php" class="text-red-600 font-semibold hover:text-red-800 transition">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        // Check if user is admin
                        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user_role = $stmt->fetch()['role'] ?? '';
                        ?>
                        <?php if ($user_role === 'admin'): ?>
                            <a href="admin.php" class="text-red-600 font-semibold hover:text-red-800 transition">Admin Panel</a>
                        <?php else: ?>
                            <a href="menu.php" class="text-red-600 font-semibold hover:text-red-800 transition">Menu</a>
                            <a href="cart.php" class="text-red-600 font-semibold hover:text-red-800 transition">Cart</a>
                            <a href="profile.php" class="text-red-600 font-semibold hover:text-red-800 transition">Profile</a>
                        <?php endif; ?>
                        <a href="logout.php" class="text-red-600 font-semibold hover:text-red-800 transition">Logout</a>
                    <?php else: ?>
                        <a href="menu.php" class="text-red-600 font-semibold hover:text-red-800 transition">Menu</a>
                        <a href="login.php" class="text-red-600 font-semibold hover:text-red-800 transition">Login</a>
                        <a href="register.php" class="text-red-600 font-semibold hover:text-red-800 transition">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
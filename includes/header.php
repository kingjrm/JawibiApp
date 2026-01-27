<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'includes/security.php';
// Suppress warnings for production
error_reporting(E_ERROR | E_PARSE);

// Auto logout after 5 minutes of inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://use.fontawesome.com https://pro.fontawesome.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://use.fontawesome.com https://pro.fontawesome.com;");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Jollibee Ordering System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Local FontAwesome as primary fallback -->
    <link rel="stylesheet" href="assets/fontawesome-local.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Fallback FontAwesome if CDN fails -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css" crossorigin="anonymous">
    <!-- Additional fallback -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v6.4.0/css/all.css" crossorigin="anonymous">
    <script>
        // Simple FontAwesome fallback
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                // Check if any FontAwesome icon is working
                var testIcon = document.querySelector('.fas.fa-envelope') || document.querySelector('.fas');
                if (testIcon) {
                    var computedStyle = window.getComputedStyle(testIcon, ':before');
                    var content = computedStyle.getPropertyValue('content');

                    if (!content || content === 'none' || content === 'normal') {
                        // FontAwesome failed, apply emoji fallbacks
                        var style = document.createElement('style');
                        style.textContent = `
                            .fa-envelope:before { content: "📧"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-lock:before { content: "🔒"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-sign-in-alt:before { content: "→"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-user-plus:before { content: "👤+"; font-family: Arial, sans-serif !important; font-size: 14px; }
                            .fa-exclamation-triangle:before { content: "⚠️"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-times:before { content: "✕"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-star:before { content: "⭐"; font-family: Arial, sans-serif !important; font-size: 16px; }
                            .fa-heart:before { content: "❤️"; font-family: Arial, sans-serif !important; font-size: 16px; }
                        `;
                        document.head.appendChild(style);
                    }
                }
            }, 2000); // Wait 2 seconds for fonts to load
        });
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; font-size: 14px; }
        .hero-bg { background-image: url('assets/chickenjoy.jpg'); }
        .fade-in { animation: fadeIn 0.8s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .tilt:hover { transform: rotateY(5deg) rotateX(5deg); transition: transform 0.3s; }

        /* Enhanced Icon Styling */
        .fas, .far, .fab, .fa {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
            font-weight: 900;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
            font-feature-settings: "liga";
        }

        /* Ensure FontAwesome icons are visible */
        .fas:before, .far:before, .fab:before {
            display: inline-block;
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands";
        }

        /* Ultimate fallback - show text if FontAwesome completely fails */
        .fas:before, .far:before, .fab:before {
            /* If content is empty or none, browsers will fall back to these */
        }

        /* Specific text fallbacks for critical icons */
        @supports not (font-family: "Font Awesome 6 Free") {
            .fa-envelope:before { content: "[Email]"; font-family: Arial, sans-serif; font-weight: normal; }
            .fa-lock:before { content: "[Lock]"; font-family: Arial, sans-serif; font-weight: normal; }
            .fa-sign-in-alt:before { content: "[Sign In]"; font-family: Arial, sans-serif; font-weight: normal; }
            .fa-user-plus:before { content: "[Register]"; font-family: Arial, sans-serif; font-weight: normal; }
            .fa-exclamation-triangle:before { content: "[!]"; font-family: Arial, sans-serif; font-weight: normal; }
            .fa-times:before { content: "[X]"; font-family: Arial, sans-serif; font-weight: normal; }
        }

        /* Icon hover effects */
        .icon-hover:hover .fas,
        .icon-hover:hover .far,
        .icon-hover:hover .fab {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        /* Star rating icons */
        .star-rating {
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .star-rating:hover,
        .star-rating.active {
            color: #fbbf24 !important;
        }

        /* Button icons */
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            min-height: 40px;
        }

        /* Form input icons */
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc2626;
            z-index: 10;
        }

        /* Navigation icons */
        .nav-icon {
            transition: all 0.3s ease;
        }

        .nav-icon:hover {
            transform: translateY(-2px);
        }

        /* Modal close icons */
        .modal-close {
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
            color: #dc2626;
        }

        /* Loading spinner */
        .fa-spinner {
            animation: fa-spin 1s infinite linear;
        }

        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
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
                        <a href="login.php" class="text-red-600 font-semibold hover:text-red-800 transition">Sign In</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
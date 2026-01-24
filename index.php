<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jollibee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; font-size: 14px; }
        .hero-bg { background-image: url('assets/chickenjoy.jpg'); }
        .fade-in { animation: fadeIn 0.8s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .tilt:hover { transform: rotateY(5deg) rotateX(5deg); transition: transform 0.3s; }
        .splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .splash img {
            animation: intro 1s ease-in-out forwards;
        }
        @keyframes intro {
            0% { opacity: 0; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.1); }
            100% { opacity: 0; transform: scale(1); visibility: hidden; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Logo Introduction Splash -->
    <div class="splash">
        <img src="https://1000logos.net/wp-content/uploads/2021/05/Jollibee-logo.png" alt="Jollibee Logo" class="h-32">
    </div>
    <div class="main-content" style="opacity: 0; transition: opacity 0.5s ease-in;">
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
                    <a href="menu.php" class="text-red-600 font-semibold hover:text-red-800 transition">Menu</a>
                    <a href="#deals" class="text-red-600 font-semibold hover:text-red-800 transition">Deals</a>
                    <a href="#stores" class="text-red-600 font-semibold hover:text-red-800 transition">Stores</a>
                    <a href="login.php" class="text-red-600 font-semibold hover:text-red-800 transition">Login</a>
                    <a href="register.php" class="text-red-600 font-semibold hover:text-red-800 transition">Register</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Announcement Banner -->
    <div class="bg-red-600 text-white text-center py-2">
        <p class="text-lg font-semibold">🎉 New UI Updates! Enhanced Experience Awaits 🎉</p>
    </div>

    <!-- Hero Section -->
    <section class="hero-bg bg-cover bg-center h-screen flex items-center justify-center text-center text-white relative">
        <div class="absolute inset-0 bg-black bg-opacity-40"></div>
        <div class="relative z-10 fade-in">
            <h1 class="text-5xl md:text-7xl font-bold mb-4">Love at First Bite</h1>
            <p id="typing-text" class="text-xl md:text-2xl mb-8">Indulge in Jollibee favorites — Chickenjoy, Jolly Spaghetti, and more!</p>
            <a href="#menu" class="bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">Explore Menu</a>
        </div>
    </section>

    <!-- Featured Deals -->
    <section class="py-16 bg-white" id="deals">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Featured Deals</h2>
            <div class="flex flex-wrap justify-center gap-8">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden tilt max-w-sm">
                    <img src="assets/deal1.jpg" alt="Family Bucket Meal" class="w-full h-48 object-cover">
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-semibold mb-2">Family Bucket Meal</h3>
                        <p class="text-gray-600">₱599 — 6 pcs Chickenjoy + Spaghetti</p>
                    </div>
                </div>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden tilt max-w-sm">
                    <img src="assets/deal2.jpg" alt="Spicy Chickenjoy" class="w-full h-48 object-cover">
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-semibold mb-2">Spicy Chickenjoy</h3>
                        <p class="text-gray-600">Extra crunch, extra spice!</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Info CTA -->
    <section class="py-16 bg-gradient-to-b from-red-50 to-white" id="menu">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Discover Jollibee</h2>
            <div class="flex flex-wrap justify-center gap-8">
                <div class="bg-white shadow-lg rounded-xl p-8 text-center max-w-sm transform hover:scale-105 hover:shadow-2xl transition duration-300 border border-red-100">
                    <div class="w-24 h-24 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
                        <img src="assets/chickenjoy.jpg" alt="Chickenjoy" class="w-20 h-20 object-cover rounded-full">
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-red-600">Explore Our Menu</h3>
                    <p class="text-gray-600 mb-6">From Chickenjoy to Jolly Spaghetti—see what's in store.</p>
                    <a href="menu.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 inline-block">See Menu</a>
                </div>
                <div class="bg-white shadow-lg rounded-xl p-8 text-center max-w-sm transform hover:scale-105 hover:shadow-2xl transition duration-300 border border-yellow-100" id="stores">
                    <div class="w-24 h-24 mx-auto mb-6 bg-yellow-100 rounded-full flex items-center justify-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png" alt="Store" class="w-12 h-12">
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-yellow-600">Find a Jollibee Near You</h3>
                    <p class="text-gray-600 mb-6">Quickly locate a Jollibee anywhere in the Philippines.</p>
                    <a href="stores.php" class="bg-yellow-400 hover:bg-yellow-500 text-gray-800 font-bold py-3 px-6 rounded-lg transition duration-200 inline-block">Locate Store</a>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Simple JS for animations or interactions if needed
        // For now, just the CSS animations

        // Hide splash after logo animation ends
        document.querySelector('.splash img').addEventListener('animationend', function() {
            document.querySelector('.splash').style.display = 'none';
            document.querySelector('.main-content').style.opacity = '1';
        });

        // Typing effect for the hero text
        const text = "Indulge in Jollibee favorites — Chickenjoy, Jolly Spaghetti, and more!";
        const typingText = document.getElementById('typing-text');
        let index = 0;

        function typeWriter() {
            if (index < text.length) {
                typingText.innerHTML += text.charAt(index);
                index++;
                setTimeout(typeWriter, 50); // Adjust speed here
            }
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Start typing when page loads
        window.onload = function() {
            typingText.innerHTML = ""; // Clear initial text
            typeWriter();
        };
    </script>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    </div>

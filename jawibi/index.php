<?php
$title = 'Jollibee - Love at First Bite';
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero-bg bg-cover bg-center h-screen flex items-center justify-center text-center text-white relative">
        <div class="absolute inset-0 bg-black bg-opacity-40"></div>
        <div class="relative z-10 fade-in">
            <h1 class="text-5xl md:text-7xl font-bold mb-4">Love at First Bite</h1>
            <p class="text-xl md:text-2xl mb-8">Indulge in Jollibee favorites — Chickenjoy, Jolly Spaghetti, and more!</p>
            <a href="menu.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">Order Now</a>
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
    <section class="py-16 bg-red-50" id="menu">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-8">
                <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-sm">
                    <img src="https://cdn-icons-png.flaticon.com/512/877/877951.png" alt="Menu" class="w-12 mx-auto mb-4">
                    <h2 class="text-2xl font-bold mb-4">Explore Our Menu</h2>
                    <p class="text-gray-600 mb-6">From Chickenjoy to Jolly Spaghetti—see what's in store.</p>
                    <a href="menu.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">See Menu</a>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-8 text-center max-w-sm" id="stores">
                    <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png" alt="Store" class="w-12 mx-auto mb-4">
                    <h2 class="text-2xl font-bold mb-4">Find a Jollibee Near You</h2>
                    <p class="text-gray-600 mb-6">Quickly locate a Jollibee anywhere in the Philippines.</p>
                    <a href="#stores" class="bg-yellow-400 hover:bg-yellow-500 text-gray-800 font-bold py-2 px-4 rounded transition">Locate Store</a>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
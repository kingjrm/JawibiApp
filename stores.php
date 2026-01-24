<?php
$title = 'Find a Jollibee Near You';
include 'includes/header.php';

// Mock store data - in a real app, this would come from a database
$stores = [
    ['name' => 'Jollibee BGC', 'lat' => 14.5547, 'lng' => 121.0244, 'address' => 'Bonifacio Global City, Taguig'],
    ['name' => 'Jollibee Makati', 'lat' => 14.5547, 'lng' => 121.0244, 'address' => 'Ayala Avenue, Makati'],
    ['name' => 'Jollibee SM Mall of Asia', 'lat' => 14.5310, 'lng' => 120.9796, 'address' => 'SM Mall of Asia, Pasay'],
    ['name' => 'Jollibee Robinsons Galleria', 'lat' => 14.5648, 'lng' => 121.0369, 'address' => 'Robinsons Galleria, Ortigas'],
    ['name' => 'Jollibee Alabang', 'lat' => 14.4220, 'lng' => 121.0329, 'address' => 'Alabang Town Center, Muntinlupa'],
    ['name' => 'Jollibee Trinoma', 'lat' => 14.6538, 'lng' => 121.0337, 'address' => 'Trinoma Mall, Quezon City'],
    ['name' => 'Jollibee Greenhills', 'lat' => 14.6042, 'lng' => 121.0521, 'address' => 'Greenhills Shopping Center, San Juan'],
    ['name' => 'Jollibee Podium', 'lat' => 14.5833, 'lng' => 121.0597, 'address' => 'The Podium, Ortigas'],
];

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // km

    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);

    $a = sin($latDelta/2) * sin($latDelta/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDelta/2) * sin($lngDelta/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}

$userLat = $_GET['lat'] ?? null;
$userLng = $_GET['lng'] ?? null;

$nearbyStores = [];
if ($userLat && $userLng) {
    foreach ($stores as $store) {
        $distance = calculateDistance($userLat, $userLng, $store['lat'], $store['lng']);
        $store['distance'] = round($distance, 1);
        $nearbyStores[] = $store;
    }
    // Sort by distance
    usort($nearbyStores, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8 text-center">Find a Jollibee Near You</h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <p class="text-gray-600 mb-4">Click the button below to find Jollibee stores near your location.</p>
        <button id="locateBtn" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
            <i class="fas fa-map-marker-alt mr-2"></i>Use My Location
        </button>
        <p id="status" class="mt-2 text-sm text-gray-500"></p>
    </div>

    <?php if (!empty($nearbyStores)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach (array_slice($nearbyStores, 0, 6) as $store): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($store['name']); ?></h3>
                    <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($store['address']); ?></p>
                    <p class="text-red-600 font-semibold"><?php echo $store['distance']; ?> km away</p>
                    <a href="#" class="mt-4 inline-block bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
                        Get Directions
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('locateBtn').addEventListener('click', function() {
    const status = document.getElementById('status');
    status.textContent = 'Getting your location...';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            status.textContent = 'Location found! Loading nearby stores...';

            // Redirect with coordinates
            window.location.href = 'stores.php?lat=' + lat + '&lng=' + lng;
        }, function(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    status.textContent = 'Location access denied. Please enable location services.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    status.textContent = 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    status.textContent = 'Location request timed out.';
                    break;
                default:
                    status.textContent = 'An unknown error occurred.';
                    break;
            }
        });
    } else {
        status.textContent = 'Geolocation is not supported by this browser.';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
<?php
$title = 'Search - Jollibee';
include 'includes/header.php';

$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($query)) {
    $where_clauses[] = "menu_items.name LIKE ?";
    $params[] = "%$query%";
}

if (!empty($category)) {
    $where_clauses[] = "menu_items.category_id = ?";
    $params[] = $category;
}

if (!empty($min_price)) {
    $where_clauses[] = "menu_items.price >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $where_clauses[] = "menu_items.price <= ?";
    $params[] = $max_price;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$stmt = $pdo->prepare("SELECT menu_items.*, categories.name as category_name FROM menu_items LEFT JOIN categories ON menu_items.category_id = categories.id $where_sql ORDER BY menu_items.name");
$stmt->execute($params);
$menu_items = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">Search Results</h1>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">Filters</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Min Price</label>
                <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Max Price</label>
                <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Search</button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if (empty($menu_items)): ?>
        <p class="text-center text-gray-500">No items found matching your criteria.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($menu_items as $item): ?>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition">
                    <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-semibold"><?php echo $item['name']; ?></h3>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm"><?php echo $item['category_name']; ?></span>
                        </div>
                        <p class="text-gray-600 mb-4"><?php echo $item['description']; ?></p>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-lg font-bold text-red-600">₱<?php echo $item['price']; ?></span>
                            <?php
                            $avg_rating = 0;
                            $review_count = 0;
                            $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE item_id = ?");
                            $stmt->execute([$item['id']]);
                            $rating_data = $stmt->fetch();
                            if ($rating_data['count'] > 0) {
                                $avg_rating = round($rating_data['avg_rating'], 1);
                                $review_count = $rating_data['count'];
                            }
                            ?>
                            <div class="flex items-center">
                                <div class="flex text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $avg_rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="ml-2 text-sm text-gray-600">(<?php echo $review_count; ?>)</span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST" action="cart.php" class="flex-1">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border rounded mr-2">
                                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Add to Cart</button>
                            </form>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST" action="favorites.php">
                                    <input type="hidden" name="toggle_favorite" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
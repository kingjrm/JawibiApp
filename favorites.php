<?php
$title = 'Favorites - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle toggle favorite
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_favorite'])) {
    $item_id = $_POST['item_id'];
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
        $stmt->execute([$existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, item_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $item_id]);
    }
    header('Location: favorites.php');
    exit;
}

// Get favorites
$stmt = $pdo->prepare("SELECT menu_items.*, categories.name as category_name FROM favorites JOIN menu_items ON favorites.item_id = menu_items.id LEFT JOIN categories ON menu_items.category_id = categories.id WHERE favorites.user_id = ?");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">My Favorites</h1>

    <?php if (empty($favorites)): ?>
        <p class="text-center text-gray-500">You haven't added any favorites yet. <a href="menu.php" class="text-red-500">Browse menu</a></p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($favorites as $item): ?>
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
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST" action="cart.php" class="flex-1">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border rounded mr-2">
                                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Add to Cart</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="toggle_favorite" value="1">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
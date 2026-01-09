<?php
$title = 'Menu - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get menu items
$stmt = $pdo->query("SELECT * FROM menu_items");
$menu_items = $stmt->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = $quantity;
    }

    $success = 'Item added to cart!';
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8 text-center">Our Menu</h1>
    <?php if (isset($success)): ?>
        <p class="text-green-500 mb-4 text-center"><?php echo $success; ?></p>
    <?php endif; ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($menu_items as $item): ?>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2"><?php echo $item['name']; ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo $item['description']; ?></p>
                    <p class="text-lg font-bold text-red-600 mb-4">₱<?php echo $item['price']; ?></p>
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <div class="flex items-center mb-4">
                            <label class="mr-2">Quantity:</label>
                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border rounded">
                        </div>
                        <button type="submit" name="add_to_cart" class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-700">Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
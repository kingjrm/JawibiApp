<?php
$title = 'Cart - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $quantity = $cart[$item['id']];
        $subtotal = $item['price'] * $quantity;
        $total += $subtotal;
        $cart_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'image' => $item['image']
        ];
    }
}

// Handle update cart
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $id => $qty) {
            if ($qty > 0) {
                $_SESSION['cart'][$id] = $qty;
            } else {
                unset($_SESSION['cart'][$id]);
            }
        }
        header('Location: cart.php');
        exit;
    } elseif (isset($_POST['checkout'])) {
        // Process order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $pdo->lastInsertId();

        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        unset($_SESSION['cart']);
        header('Location: profile.php?order=success');
        exit;
    }
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">Your Cart</h1>
    <?php if (empty($cart_items)): ?>
        <p class="text-center">Your cart is empty. <a href="menu.php" class="text-red-500">Go to Menu</a></p>
    <?php else: ?>
        <form method="POST">
            <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Item</th>
                            <th class="px-6 py-3 text-left">Price</th>
                            <th class="px-6 py-3 text-left">Quantity</th>
                            <th class="px-6 py-3 text-left">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr class="border-t">
                                <td class="px-6 py-4 flex items-center">
                                    <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-16 h-16 object-cover mr-4">
                                    <?php echo $item['name']; ?>
                                </td>
                                <td class="px-6 py-4">₱<?php echo $item['price']; ?></td>
                                <td class="px-6 py-4">
                                    <input type="number" name="quantities[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0" class="w-16 px-2 py-1 border rounded">
                                </td>
                                <td class="px-6 py-4">₱<?php echo $item['subtotal']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-between items-center">
                <button type="submit" name="update_cart" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700">Update Cart</button>
                <div class="text-right">
                    <p class="text-xl font-bold">Total: ₱<?php echo $total; ?></p>
                    <button type="submit" name="checkout" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-700 mt-4">Checkout</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
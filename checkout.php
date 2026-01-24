<?php
$title = 'Checkout - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout'])) {
    header('Location: cart.php');
    exit;
}

$checkout = $_SESSION['checkout'];
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

// No address needed for POS

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_order'])) {
    // Process order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, discount_amount, final_amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $_SESSION['user_id'],
        $total,
        $checkout['discount'],
        $checkout['final_amount']
    ]);
    $order_id = $pdo->lastInsertId();

    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price'], $item['price'] * $item['quantity']]);
    }

    // Award loyalty points
    $points_earned = floor($checkout['final_amount'] / 10); // 1 point per ₱10
    $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points_earned, $_SESSION['user_id']]);

    // Create notification
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], 'Order Placed', 'Your order #' . $order_id . ' has been placed successfully!']);

    unset($_SESSION['cart']);
    unset($_SESSION['checkout']);
    header('Location: profile.php?order=success&points=' . $points_earned);
    exit;
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">Order Confirmation</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <h2 class="text-xl font-semibold mb-4">Order Items</h2>
            <div class="bg-white shadow-lg rounded-lg p-6">
                <?php foreach ($cart_items as $item): ?>
                    <div class="flex justify-between items-center mb-4 pb-4 border-b">
                        <div class="flex items-center">
                            <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-12 h-12 object-cover mr-4">
                            <div>
                                <h3 class="font-semibold"><?php echo $item['name']; ?></h3>
                                <p class="text-sm text-gray-600">Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                        </div>
                        <span class="font-semibold">₱<?php echo $item['subtotal']; ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="flex justify-between items-center text-lg font-bold">
                    <span>Total:</span>
                    <span>₱<?php echo $checkout['final_amount']; ?></span>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-4">Order Details</h2>
            <div class="bg-white shadow-lg rounded-lg p-6 space-y-4">
                <div>
                    <span class="font-semibold">Payment Method:</span>
                    <span>Cash</span>
                </div>
                <?php if (!empty($checkout['promo_code'])): ?>
                    <div>
                        <span class="font-semibold">Promo Code:</span>
                        <span><?php echo htmlspecialchars($checkout['promo_code']); ?> (₱<?php echo $checkout['discount']; ?> off)</span>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="mt-6">
                <button type="submit" name="confirm_order" class="w-full bg-red-500 text-white py-3 rounded-lg hover:bg-red-700 font-semibold text-lg">Confirm Order</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
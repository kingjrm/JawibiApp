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
$discount = 0;
$promo_code = $_GET['promo'] ?? '';

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

// Apply promotion
if (!empty($promo_code)) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND valid_until >= CURDATE()");
    $stmt->execute([$promo_code]);
    $promo = $stmt->fetch();

    if ($promo && $total >= $promo['min_order']) {
        if ($promo['discount_type'] == 'percentage') {
            $discount = $total * ($promo['discount_value'] / 100);
        } else {
            $discount = min($promo['discount_value'], $total);
        }
        $total -= $discount;
    } else {
        $promo_error = 'Invalid or expired promo code';
    }
}

// Handle update cart
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $promo_error = 'Invalid request';
    } elseif (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $id => $qty) {
            $id = validateInt($id);
            $qty = validateInt($qty);
            if ($id && $qty >= 0) {
                if ($qty > 0) {
                    $_SESSION['cart'][$id] = $qty;
                } else {
                    unset($_SESSION['cart'][$id]);
                }
            }
        }
        header('Location: cart.php');
        exit;
    } elseif (isset($_POST['apply_promo'])) {
        $promo_code = $_POST['promo_code'];
        header('Location: cart.php?promo=' . urlencode($promo_code));
        exit;
    } elseif (isset($_POST['checkout'])) {
        // Store checkout data in session
        $_SESSION['checkout'] = [
            'payment_method' => 'cash',
            'promo_code' => $promo_code,
            'discount' => $discount,
            'final_amount' => $total
        ];
        header('Location: checkout.php');
        exit;
    }
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">Your Cart</h1>
    <?php if (empty($cart_items)): ?>
        <p class="text-center">Your cart is empty. <a href="menu.php" class="text-red-500">Go to Menu</a></p>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
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
                                        <input type="number" name="quantities[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0" class="w-16 px-2 py-1 border rounded" form="cart-form">
                                    </td>
                                    <td class="px-6 py-4">₱<?php echo $item['subtotal']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="update_cart" form="cart-form" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700">Update Cart</button>
            </div>

            <div>
                <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Promo Code</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="text" name="promo_code" placeholder="Enter promo code" class="w-full px-3 py-2 border rounded mb-2">
                        <button type="submit" name="apply_promo" class="w-full bg-yellow-500 text-white py-2 rounded hover:bg-yellow-700">Apply</button>
                    </form>
                    <?php if (!empty($promo_code) && empty($promo_error)): ?>
                        <p class="text-green-600 mt-2">Promo applied: <?php echo htmlspecialchars($promo_code); ?></p>
                    <?php elseif (isset($promo_error)): ?>
                        <p class="text-red-600 mt-2"><?php echo $promo_error; ?></p>
                    <?php endif; ?>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>₱<?php echo array_sum(array_column($cart_items, 'subtotal')); ?></span>
                        </div>
                        <?php if ($discount > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount:</span>
                                <span>-₱<?php echo $discount; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total:</span>
                            <span>₱<?php echo $total; ?></span>
                        </div>
                    </div>

                    <form method="POST" id="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="checkout" class="w-full bg-red-500 text-white py-3 rounded-lg hover:bg-red-700 font-semibold text-lg">Checkout</button>
                    </form>
                </div>
            </div>
        </div>

        <form id="cart-form" method="POST" class="hidden">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        </form>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const addressSection = document.getElementById('address-section');
        if (this.value === 'pickup') {
            addressSection.style.display = 'none';
        } else {
            addressSection.style.display = 'block';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
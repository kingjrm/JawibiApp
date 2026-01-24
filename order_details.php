<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: profile.php');
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, mi.name, mi.image
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Status timeline
$status_steps = [
    'pending' => ['label' => 'Order Placed', 'icon' => 'fas fa-shopping-cart', 'color' => 'text-yellow-500'],
    'confirmed' => ['label' => 'Order Confirmed', 'icon' => 'fas fa-check-circle', 'color' => 'text-blue-500'],
    'preparing' => ['label' => 'Preparing', 'icon' => 'fas fa-utensils', 'color' => 'text-orange-500'],
    'ready' => ['label' => 'Ready for Pickup/Delivery', 'icon' => 'fas fa-box-open', 'color' => 'text-purple-500'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'fas fa-truck', 'color' => 'text-green-500']
];

$current_status_index = array_search($order['status'], array_keys($status_steps));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Jawibi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Order #<?php echo $order['id']; ?></h1>
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    <?php
                    switch($order['status']) {
                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                        case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                        case 'preparing': echo 'bg-orange-100 text-orange-800'; break;
                        case 'ready': echo 'bg-purple-100 text-purple-800'; break;
                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                    }
                    ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <!-- Order Status Timeline -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Order Status</h2>
                <div class="flex items-center justify-between">
                    <?php foreach ($status_steps as $key => $step): ?>
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full border-2
                                <?php echo ($key === $order['status'] || array_search($key, array_keys($status_steps)) <= $current_status_index)
                                    ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'; ?>">
                                <i class="<?php echo $step['icon']; ?> <?php echo $step['color']; ?>"></i>
                            </div>
                            <span class="text-xs mt-2 text-center"><?php echo $step['label']; ?></span>
                        </div>
                        <?php if ($key !== 'delivered'): ?>
                            <div class="flex-1 h-0.5 mx-4
                                <?php echo array_search($key, array_keys($status_steps)) < $current_status_index
                                    ? 'bg-blue-500' : 'bg-gray-300'; ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Order Items -->
                <div>
                    <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                    <div class="space-y-4">
                        <?php foreach ($order_items as $item): ?>
                            <div class="flex items-center space-x-4 p-4 border rounded-lg">
                                <?php if ($item['image']): ?>
                                    <img src="public/assets/<?php echo htmlspecialchars($item['image']); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="w-16 h-16 object-cover rounded-lg">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-utensils text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?></p>
                                    <?php if ($item['special_instructions']): ?>
                                        <p class="text-sm text-gray-600">Note: <?php echo htmlspecialchars($item['special_instructions']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">$<?php echo number_format($item['total_price'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <?php if ($order['delivery_fee'] > 0): ?>
                            <div class="flex justify-between">
                                <span>Delivery Fee:</span>
                                <span>$<?php echo number_format($order['delivery_fee'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['tax_amount'] > 0): ?>
                            <div class="flex justify-between">
                                <span>Tax:</span>
                                <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount:</span>
                                <span>-$<?php echo number_format($order['discount_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <hr class="my-2">
                        <div class="flex justify-between font-semibold text-lg">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['final_amount'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="mt-6">
                        <h3 class="font-semibold mb-2">Delivery Information</h3>
                        <div class="text-sm text-gray-600">
                            <p><strong>Type:</strong> <?php echo ucfirst($order['delivery_type']); ?></p>
                            <?php if ($order['delivery_type'] === 'delivery' && $order['address_line1']): ?>
                                <p><strong>Address:</strong></p>
                                <p><?php echo htmlspecialchars($order['address_line1']); ?></p>
                                <?php if ($order['address_line2']): ?>
                                    <p><?php echo htmlspecialchars($order['address_line2']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip_code']); ?></p>
                            <?php endif; ?>
                            <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method'] ?? 'Cash'); ?></p>
                            <?php if ($order['order_notes']): ?>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['order_notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Date -->
                    <div class="mt-4 text-sm text-gray-500">
                        <p>Ordered on: <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        <?php if ($order['updated_at'] !== $order['created_at']): ?>
                            <p>Last updated: <?php echo date('M j, Y \a\t g:i A', strtotime($order['updated_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex space-x-4">
                <a href="profile.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
                <?php if ($order['status'] === 'delivered'): ?>
                    <button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                        <i class="fas fa-redo mr-2"></i>Reorder
                    </button>
                <?php elseif (in_array($order['status'], ['pending', 'confirmed', 'preparing'])): ?>
                    <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition"
                            onclick="cancelOrder(<?php echo $order['id']; ?>)">
                        <i class="fas fa-times mr-2"></i>Cancel Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // Implement order cancellation logic
                alert('Order cancellation feature coming soon!');
            }
        }
    </script>
</body>
</html>
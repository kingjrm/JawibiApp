<?php
$title = 'Profile - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-8">Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
    <?php if (isset($_GET['order']) && $_GET['order'] == 'success'): ?>
        <p class="text-green-500 mb-4">Order placed successfully!</p>
    <?php endif; ?>

    <h2 class="text-2xl font-bold mb-4">Your Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No orders yet. <a href="menu.php" class="text-red-500">Start ordering</a></p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Order #<?php echo $order['id']; ?></h3>
                        <span class="px-2 py-1 rounded <?php echo $order['status'] == 'pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-green-200 text-green-800'; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <p class="text-gray-600">Total: ₱<?php echo $order['total']; ?></p>
                    <p class="text-gray-600">Date: <?php echo $order['created_at']; ?></p>
                    <!-- Could add order items here -->
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
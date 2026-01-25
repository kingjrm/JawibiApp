<?php
$title = 'Profile - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'orders';

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (empty($name) || !validateEmail($email) || !preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
            $error = 'Invalid input data';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user_id]);
            $success = 'Profile updated successfully!';
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        if (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters';
        } elseif (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect!';
        }
    } elseif (isset($_POST['add_address'])) {
        $address_type = $_POST['address_type'];
        $address = $_POST['address'];
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, address_type, address, is_default) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $address_type, $address, $is_default]);
        $success = 'Address added successfully!';
    }
}

// Get data based on tab
if ($tab == 'orders') {
    $stmt = $pdo->prepare("SELECT orders.*, COUNT(order_items.id) as item_count FROM orders LEFT JOIN order_items ON orders.id = order_items.order_id WHERE orders.user_id = ? GROUP BY orders.id ORDER BY orders.created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} elseif ($tab == 'addresses') {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll();
} elseif ($tab == 'reviews') {
    $stmt = $pdo->prepare("SELECT reviews.*, menu_items.name as item_name, menu_items.image FROM reviews JOIN menu_items ON reviews.item_id = menu_items.id WHERE reviews.user_id = ? ORDER BY reviews.created_at DESC");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll();
} elseif ($tab == 'notifications') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
}
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar -->
        <div class="md:w-1/4">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user text-white text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold"><?php echo h($user['name']); ?></h2>
                    <p class="text-gray-600"><?php echo h($user['email']); ?></p>
                    <div class="mt-4 p-3 bg-yellow-100 rounded-lg">
                        <p class="text-sm text-yellow-800">Loyalty Points</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $user['loyalty_points']; ?></p>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="?tab=orders" class="block px-4 py-2 rounded <?php echo $tab == 'orders' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-shopping-bag mr-2"></i>My Orders
                    </a>
                    <a href="?tab=notifications" class="block px-4 py-2 rounded <?php echo $tab == 'notifications' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </a>
                    <a href="?tab=addresses" class="block px-4 py-2 rounded <?php echo $tab == 'addresses' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-map-marker-alt mr-2"></i>Addresses
                    </a>
                    <a href="?tab=reviews" class="block px-4 py-2 rounded <?php echo $tab == 'reviews' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-star mr-2"></i>My Reviews
                    </a>
                    <a href="?tab=settings" class="block px-4 py-2 rounded <?php echo $tab == 'settings' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-cog mr-2"></i>Settings
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="md:w-3/4">
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($tab == 'orders'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">My Orders</h1>
                    </div>
                    <div class="p-6">
                        <?php if (isset($_GET['order']) && $_GET['order'] == 'success'): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                                Order placed successfully!
                                <?php if (isset($_GET['points'])): ?>
                                    You earned <?php echo h($_GET['points']); ?> loyalty points!
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($orders)): ?>
                            <p class="text-center text-gray-500">No orders yet. <a href="menu.php" class="text-red-500">Start ordering</a></p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($orders as $order): ?>
                                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <h3 class="text-lg font-semibold">Order #<?php echo $order['id']; ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo $order['item_count']; ?> items</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="px-2 py-1 rounded text-sm <?php
                                                    $status_colors = [
                                                        'pending' => 'bg-yellow-200 text-yellow-800',
                                                        'confirmed' => 'bg-blue-200 text-blue-800',
                                                        'preparing' => 'bg-orange-200 text-orange-800',
                                                        'ready' => 'bg-purple-200 text-purple-800',
                                                        'delivered' => 'bg-green-200 text-green-800',
                                                        'cancelled' => 'bg-red-200 text-red-800'
                                                    ];
                                                    echo $status_colors[$order['status']] ?? 'bg-gray-200 text-gray-800';
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                                <p class="text-lg font-bold mt-2">₱<?php echo $order['final_total']; ?></p>
                                            </div>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600"><?php echo ucfirst($order['delivery_type']); ?> • <?php echo ucfirst($order['payment_method']); ?></span>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-red-500 hover:text-red-700">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab == 'notifications'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">Notifications</h1>
                    </div>
                    <div class="p-6">
                        <?php if (empty($notifications)): ?>
                            <p class="text-center text-gray-500">No notifications yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="border rounded-lg p-4 <?php echo !$notification['is_read'] ? 'bg-blue-50 border-blue-200' : 'bg-gray-50'; ?>">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h3 class="font-semibold <?php echo !$notification['is_read'] ? 'text-blue-800' : 'text-gray-800'; ?>">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="ml-2 w-2 h-2 bg-blue-500 rounded-full inline-block"></span>
                                                    <?php endif; ?>
                                                </h3>
                                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <p class="text-sm text-gray-400 mt-2"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                                            </div>
                                            <?php if (!$notification['is_read']): ?>
                                                <button onclick="markAsRead(<?php echo $notification['id']; ?>)" class="text-blue-500 hover:text-blue-700 text-sm">
                                                    Mark as read
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab == 'addresses'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b flex justify-between items-center">
                        <h1 class="text-2xl font-bold">My Addresses</h1>
                        <button onclick="document.getElementById('add-address-modal').classList.remove('hidden')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">
                            <i class="fas fa-plus mr-2"></i>Add Address
                        </button>
                    </div>
                    <div class="p-6">
                        <?php if (empty($addresses)): ?>
                            <p class="text-center text-gray-500">No addresses added yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="border rounded-lg p-4 <?php echo $addr['is_default'] ? 'border-red-500' : ''; ?>">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-semibold capitalize"><?php echo $addr['address_type']; ?> <?php echo $addr['is_default'] ? '(Default)' : ''; ?></h3>
                                                <p class="text-gray-600"><?php echo $addr['address']; ?></p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab == 'reviews'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">My Reviews</h1>
                    </div>
                    <div class="p-6">
                        <?php if (empty($reviews)): ?>
                            <p class="text-center text-gray-500">No reviews yet. <a href="menu.php" class="text-red-500">Rate items</a></p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="border rounded-lg p-4">
                                        <div class="flex items-start space-x-4">
                                            <img src="assets/<?php echo $review['image']; ?>" alt="<?php echo $review['item_name']; ?>" class="w-16 h-16 object-cover rounded">
                                            <div class="flex-1">
                                                <h3 class="font-semibold"><?php echo $review['item_name']; ?></h3>
                                                <div class="flex items-center mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="ml-2 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                                </div>
                                                <?php if (!empty($review['comment'])): ?>
                                                    <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab == 'settings'): ?>
                <div class="space-y-6">
                    <!-- Profile Settings -->
                    <div class="bg-white shadow-lg rounded-lg">
                        <div class="px-6 py-4 border-b">
                            <h1 class="text-2xl font-bold">Profile Settings</h1>
                        </div>
                        <div class="p-6">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Update Profile</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-white shadow-lg rounded-lg">
                        <div class="px-6 py-4 border-b">
                            <h1 class="text-2xl font-bold">Change Password</h1>
                        </div>
                        <div class="p-6">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" name="current_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" name="new_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                </div>
                                <button type="submit" name="change_password" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div id="add-address-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Add Address</h2>
            <button onclick="document.getElementById('add-address-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <label class="block text-sm font-medium text-gray-700">Address Type</label>
                <select name="address_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="home">Home</option>
                    <option value="work">Work</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Enter full address" required></textarea>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_default" class="mr-2">
                    Set as default address
                </label>
            </div>
            <button type="submit" name="add_address" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-700">Add Address</button>
        </form>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: notificationId })
    }).then(() => {
        location.reload(); // Refresh to update the UI
    });
}
</script>

<?php include 'includes/footer.php'; ?>
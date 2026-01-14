<?php
$title = 'Admin Panel - Jollibee';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if user is admin
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$tab = $_GET['tab'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];

        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $upload_dir = 'assets/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('item_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = $new_filename;
                } else {
                    $error = 'Failed to upload image.';
                }
            } else {
                $error = 'Invalid image file. Please upload a JPG, PNG, or GIF file under 5MB.';
            }
        } else {
            $error = 'Please select an image to upload.';
        }

        if (empty($error) && !empty($image_path)) {
            $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category_id, $image_path]);
            $success = 'Item added successfully!';
        }
    } elseif (isset($_POST['edit_item'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];

        // Handle image upload (optional for editing)
        $image_path = $_POST['current_image']; // Keep existing image by default

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                $upload_dir = 'assets/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('item_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = $new_filename;
                    // Optionally delete old image file
                    if (!empty($_POST['current_image']) && file_exists($upload_dir . $_POST['current_image'])) {
                        unlink($upload_dir . $_POST['current_image']);
                    }
                } else {
                    $error = 'Failed to upload image.';
                }
            } else {
                $error = 'Invalid image file. Please upload a JPG, PNG, or GIF file under 5MB.';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $category_id, $image_path, $id]);
            $success = 'Item updated successfully!';
        }
    } elseif (isset($_POST['delete_item'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Item deleted successfully!';
    } elseif (isset($_POST['update_order_status'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);

        // Get user_id for the order
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        // Create notification for status update
        $status_messages = [
            'confirmed' => 'Your order has been confirmed!',
            'preparing' => 'Your order is being prepared!',
            'ready' => 'Your order is ready for pickup/delivery!',
            'delivered' => 'Your order has been delivered!',
            'cancelled' => 'Your order has been cancelled.'
        ];

        if (isset($status_messages[$status])) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'order')");
            $stmt->execute([$order['user_id'], 'Order Update', 'Order #' . $order_id . ': ' . $status_messages[$status]]);
        }

        $success = 'Order status updated!';
    } elseif (isset($_POST['add_promo'])) {
        $code = $_POST['code'];
        $description = $_POST['description'];
        $discount_type = $_POST['discount_type'];
        $discount_value = $_POST['discount_value'];
        $min_order = $_POST['min_order'];
        $valid_until = $_POST['valid_until'];

        $stmt = $pdo->prepare("INSERT INTO promotions (code, description, discount_type, discount_value, min_order, valid_until) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $description, $discount_type, $discount_value, $min_order, $valid_until]);
        $success = 'Promotion added successfully!';
    } elseif (isset($_POST['create_pos_order'])) {
        $customer_name = $_POST['customer_name'];
        $payment_method = $_POST['payment_method'];
        $order_items = json_decode($_POST['order_items'], true);

        if (!empty($order_items)) {
            $total = 0;
            foreach ($order_items as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Create order for admin user (id=1)
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, final_amount, status, payment_method) VALUES (1, ?, ?, 'confirmed', ?)"); 
            $stmt->execute([$total, $total, $payment_method]);
            $order_id = $pdo->lastInsertId();

            // Add order items
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price'], $item['price'] * $item['quantity']]);
            }

            $success = 'POS order created successfully!';
        }
    }
}

// Get data based on tab
if ($tab == 'dashboard') {
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_revenue = $pdo->query("SELECT SUM(final_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $top_items = $pdo->query("SELECT mi.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id GROUP BY oi.menu_item_id ORDER BY total_sold DESC LIMIT 5")->fetchAll();
    $recent_orders = $pdo->query("SELECT orders.id, orders.final_amount, orders.status, orders.created_at, CONCAT(users.first_name, ' ', users.last_name) as user_name FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC LIMIT 10")->fetchAll();
} elseif ($tab == 'orders') {
    $stmt = $pdo->query("SELECT orders.id, orders.user_id, orders.total_amount, orders.final_amount, orders.status, orders.payment_method, orders.created_at, CONCAT(users.first_name, ' ', users.last_name) as user_name FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC");
    $orders = $stmt->fetchAll();
} elseif ($tab == 'menu') {
    $stmt = $pdo->query("SELECT menu_items.*, categories.name as category_name FROM menu_items LEFT JOIN categories ON menu_items.category_id = categories.id ORDER BY menu_items.name");
    $menu_items = $stmt->fetchAll();
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
} elseif ($tab == 'users') {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} elseif ($tab == 'promotions') {
    $promotions = $pdo->query("SELECT * FROM promotions ORDER BY created_at DESC")->fetchAll();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar -->
        <div class="md:w-1/4">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
                <nav class="space-y-2">
                    <a href="?tab=dashboard" class="block px-4 py-2 rounded <?php echo $tab == 'dashboard' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="?tab=orders" class="block px-4 py-2 rounded <?php echo $tab == 'orders' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-shopping-bag mr-2"></i>Orders
                    </a>
                    <a href="?tab=menu" class="block px-4 py-2 rounded <?php echo $tab == 'menu' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-utensils mr-2"></i>Menu Items
                    </a>
                    <a href="?tab=users" class="block px-4 py-2 rounded <?php echo $tab == 'users' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-users mr-2"></i>Users
                    </a>
                    <a href="?tab=promotions" class="block px-4 py-2 rounded <?php echo $tab == 'promotions' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-tags mr-2"></i>Promotions
                    </a>
                    <a href="?tab=pos" class="block px-4 py-2 rounded <?php echo $tab == 'pos' ? 'bg-red-500 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-cash-register mr-2"></i>POS
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

            <?php if ($tab == 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-shopping-bag text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Orders</p>
                                <p class="text-2xl font-bold"><?php echo $total_orders; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-users text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Users</p>
                                <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Revenue</p>
                                <p class="text-2xl font-bold">₱<?php echo number_format($total_revenue, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-lg rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="fas fa-clock text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Pending Orders</p>
                                <p class="text-2xl font-bold"><?php echo $pending_orders; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Recent Orders</h2>
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="flex justify-between items-center border-b pb-2">
                                <div>
                                    <p class="font-semibold">Order #<?php echo $order['id']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $order['user_name']; ?> • <?php echo date('M d, H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold">₱<?php echo $order['final_amount']; ?></p>
                                    <span class="px-2 py-1 rounded text-xs <?php
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
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Top Selling Items</h2>
                    <div class="space-y-4">
                        <?php foreach ($top_items as $item): ?>
                            <div class="flex justify-between items-center">
                                <span><?php echo $item['name']; ?></span>
                                <span class="font-bold"><?php echo $item['total_sold']; ?> sold</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($tab == 'orders'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">Order Management</h1>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Order ID</th>
                                        <th class="px-4 py-2 text-left">Customer</th>
                                        <th class="px-4 py-2 text-left">Total</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="border-t">
                                            <td class="px-4 py-2">#<?php echo $order['id']; ?></td>
                                            <td class="px-4 py-2"><?php echo $order['user_name']; ?></td>
                                            <td class="px-4 py-2">₱<?php echo $order['final_amount']; ?></td>
                                            <td class="px-4 py-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1">
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                        <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                    <input type="hidden" name="update_order_status" value="1">
                                                </form>
                                            </td>
                                            <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="px-4 py-2">
                                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-blue-500 hover:text-blue-700">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab == 'menu'): ?>
                <div class="space-y-6">
                    <div class="bg-white shadow-lg rounded-lg">
                        <div class="px-6 py-4 border-b flex justify-between items-center">
                            <h1 class="text-2xl font-bold">Menu Items</h1>
                            <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">
                                <i class="fas fa-plus mr-2"></i>Add Item
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($menu_items as $item): ?>
                                    <div class="border rounded-lg p-4">
                                        <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-32 object-cover rounded mb-4">
                                        <h3 class="font-semibold"><?php echo $item['name']; ?></h3>
                                        <p class="text-sm text-gray-600 mb-2"><?php echo $item['category_name']; ?></p>
                                        <p class="text-lg font-bold text-red-600">₱<?php echo $item['price']; ?></p>
                                        <div class="mt-2 flex justify-between items-center">
                                            <span class="text-sm <?php echo $item['available'] ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                            <div class="flex space-x-2">
                                                <button onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', '<?php echo addslashes($item['description']); ?>', <?php echo $item['price']; ?>, <?php echo $item['category_id']; ?>, '<?php echo $item['image']; ?>')" class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this item?')">
                                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="delete_item" value="1">
                                                    <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab == 'users'): ?>
                <div class="bg-white shadow-lg rounded-lg">
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">User Management</h1>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">ID</th>
                                        <th class="px-4 py-2 text-left">Name</th>
                                        <th class="px-4 py-2 text-left">Email</th>
                                        <th class="px-4 py-2 text-left">Loyalty Points</th>
                                        <th class="px-4 py-2 text-left">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="border-t">
                                            <td class="px-4 py-2"><?php echo $user['id']; ?></td>
                                            <td class="px-4 py-2"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                            <td class="px-4 py-2"><?php echo $user['email']; ?></td>
                                            <td class="px-4 py-2"><?php echo $user['loyalty_points']; ?></td>
                                            <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab == 'promotions'): ?>
                <div class="space-y-6">
                    <div class="bg-white shadow-lg rounded-lg">
                        <div class="px-6 py-4 border-b flex justify-between items-center">
                            <h1 class="text-2xl font-bold">Promotions</h1>
                            <button onclick="document.getElementById('add-promo-modal').classList.remove('hidden')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">
                                <i class="fas fa-plus mr-2"></i>Add Promotion
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($promotions as $promo): ?>
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-semibold"><?php echo $promo['code']; ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo $promo['description']; ?></p>
                                                <p class="text-sm">
                                                    <?php echo $promo['discount_type'] == 'percentage' ? $promo['discount_value'] . '% off' : '₱' . $promo['discount_value'] . ' off'; ?>
                                                    (Min order: ₱<?php echo $promo['min_order']; ?>)
                                                </p>
                                                <p class="text-sm text-gray-500">Valid until: <?php echo date('M d, Y', strtotime($promo['valid_until'])); ?></p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <span class="px-2 py-1 rounded text-sm <?php echo $promo['active'] ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'; ?>">
                                                    <?php echo $promo['active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                                <button class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                                                <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="add-item-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Add Menu Item</h2>
            <button onclick="document.getElementById('add-item-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" name="price" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Image</label>
                <input type="file" name="image" accept="image/*" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <p class="text-xs text-gray-500 mt-1">Upload a JPG, PNG, or GIF image</p>
            </div>
            <button type="submit" name="add_item" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-700">Add Item</button>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="edit-item-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Edit Menu Item</h2>
            <button onclick="document.getElementById('edit-item-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="edit-item-form">
            <input type="hidden" name="id" id="edit-id">
            <input type="hidden" name="current_image" id="edit-current-image">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit-name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="edit-description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" name="price" id="edit-price" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category_id" id="edit-category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Image</label>
                <div class="mt-1">
                    <img id="edit-image-preview" src="" alt="Current image" class="w-20 h-20 object-cover rounded border mb-2" style="display: none;">
                    <input type="file" name="image" accept="image/*" class="block w-full border-gray-300 rounded-md shadow-sm">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image, or upload a new JPG, PNG, or GIF image</p>
                </div>
            </div>
            <button type="submit" name="edit_item" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-700">Update Item</button>
        </form>
    </div>
</div>

<!-- Add Promo Modal -->
<div id="add-promo-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Add Promotion</h2>
            <button onclick="document.getElementById('add-promo-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Code</label>
                <input type="text" name="code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Discount Type</label>
                <select name="discount_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Discount Value</label>
                <input type="number" name="discount_value" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Minimum Order</label>
                <input type="number" name="min_order" step="0.01" value="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Valid Until</label>
                <input type="date" name="valid_until" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <button type="submit" name="add_promo" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-700">Add Promotion</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, description, price, category_id, image) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-category').value = category_id;
    document.getElementById('edit-current-image').value = image;

    // Show current image preview
    const preview = document.getElementById('edit-image-preview');
    if (image) {
        preview.src = 'assets/' + image;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }

    document.getElementById('edit-item-modal').classList.remove('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
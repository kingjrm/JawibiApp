<?php
$title = 'Menu - Jollibee';
include 'includes/header.php';

// Handle add to cart (only for logged-in users)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif (isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
        $item_id = validateInt($_POST['item_id']);
        $quantity = validateInt($_POST['quantity']);

        if (!$item_id || !$quantity || $quantity < 1) {
            $error = 'Invalid item or quantity';
        } else {
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
    } elseif (isset($_POST['toggle_favorite']) && isset($_SESSION['user_id'])) {
        $item_id = validateInt($_POST['item_id']);

        if (!$item_id) {
            $error = 'Invalid item';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND menu_item_id = ?");
            $stmt->execute([$_SESSION['user_id'], $item_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
                $stmt->execute([$existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO favorites (user_id, menu_item_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $item_id]);
            }
            header('Location: menu.php');
            exit;
        }
    } elseif (isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
        $item_id = validateInt($_POST['item_id']);
        $rating = validateInt($_POST['rating']);
        $comment = trim($_POST['comment']);

        if (!$item_id || !$rating || $rating < 1 || $rating > 5) {
            $error = 'Invalid review data';
        } elseif (strlen($comment) > 500) {
            $error = 'Comment too long';
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, menu_item_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $item_id, $rating, $comment]);
            $success = 'Review submitted successfully!';
        }
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Filter parameters
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["menu_items.available = 1"];
$params = [];

if (!empty($category_filter)) {
    $where_clauses[] = "menu_items.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_clauses[] = "menu_items.name LIKE ?";
    $params[] = "%$search%";
}

$where_sql = implode(" AND ", $where_clauses);

$stmt = $pdo->prepare("SELECT menu_items.*, categories.name as category_name FROM menu_items LEFT JOIN categories ON menu_items.category_id = categories.id WHERE $where_sql ORDER BY menu_items.name");
$stmt->execute($params);
$menu_items = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white shadow-lg rounded-lg p-6 sticky top-24">
                <h2 class="text-xl font-bold mb-4">Filters</h2>

                <!-- Search -->
                <div class="mb-6">
                    <form method="GET">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search menu..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="w-full mt-2 bg-red-500 text-white py-2 rounded hover:bg-red-700">Search</button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Categories</h3>
                    <div class="space-y-2">
                        <a href="menu.php" class="block px-3 py-2 rounded <?php echo empty($category_filter) ? 'bg-red-100 text-red-800' : 'text-gray-700 hover:bg-gray-100'; ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="?category=<?php echo $cat['id']; ?>&search=<?php echo urlencode($search); ?>" class="block px-3 py-2 rounded <?php echo $category_filter == $cat['id'] ? 'bg-red-100 text-red-800' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Clear Filters -->
                <?php if (!empty($category_filter) || !empty($search)): ?>
                    <a href="menu.php" class="block w-full text-center bg-gray-500 text-white py-2 rounded hover:bg-gray-700">Clear Filters</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu Items -->
        <div class="lg:w-3/4">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Our Menu</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($menu_items)): ?>
                <p class="text-center text-gray-500">No items found matching your criteria.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition">
                            <img src="assets/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-48 object-cover">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-xl font-semibold"><?php echo h($item['name']); ?></h3>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm"><?php echo h($item['category_name']); ?></span>
                                </div>
                                <p class="text-gray-600 mb-4"><?php echo h($item['description']); ?></p>

                                <!-- Rating -->
                                <?php
                                $avg_rating = 0;
                                $review_count = 0;
                                $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE menu_item_id = ?");
                                $stmt->execute([$item['id']]);
                                $rating_data = $stmt->fetch();
                                if ($rating_data['count'] > 0) {
                                    $avg_rating = round($rating_data['avg_rating'], 1);
                                    $review_count = $rating_data['count'];
                                }
                                ?>
                                <div class="flex items-center mb-4">
                                    <div class="flex text-yellow-400 mr-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $avg_rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-600">(<?php echo $review_count; ?> reviews)</span>
                                </div>

                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-lg font-bold text-red-600">₱<?php echo $item['price']; ?></span>
                                </div>

                                <div class="flex space-x-2 mb-4">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border rounded mr-2">
                                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Add to Cart</button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="toggle_favorite" value="1">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 p-2">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-gray-500 flex-1">Login to add items to cart and favorites</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Reviews Section -->
                                <div class="border-t pt-4">
                                    <h4 class="font-semibold mb-2">Recent Reviews</h4>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT reviews.*, CONCAT(users.first_name, ' ', users.last_name) as user_name FROM reviews JOIN users ON reviews.user_id = users.id WHERE reviews.menu_item_id = ? ORDER BY reviews.created_at DESC LIMIT 2");
                                    $stmt->execute([$item['id']]);
                                    $recent_reviews = $stmt->fetchAll();
                                    ?>
                                    <?php if (!empty($recent_reviews)): ?>
                                        <div class="space-y-2 mb-3">
                                            <?php foreach ($recent_reviews as $review): ?>
                                                <div class="bg-gray-50 p-2 rounded">
                                                    <div class="flex items-center mb-1">
                                                        <span class="font-medium text-sm"><?php echo h($review['user_name']); ?></span>
                                                        <div class="flex text-yellow-400 ml-2">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star text-xs <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($review['comment'])): ?>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Add Review -->
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button onclick="openReviewModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" class="text-blue-500 hover:text-blue-700 text-sm">
                                            <i class="fas fa-plus mr-1"></i>Write a Review
                                        </button>
                                    <?php else: ?>
                                        <p class="text-gray-500 text-sm">Login to write reviews</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="review-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Write a Review</h2>
            <button onclick="closeReviewModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="review-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="item_id" id="review-item-id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex space-x-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" class="hidden">
                        <label for="star<?php echo $i; ?>" class="cursor-pointer">
                            <i class="fas fa-star text-2xl text-gray-300 hover:text-yellow-400 star-rating"></i>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                <textarea name="comment" rows="3" class="w-full px-3 py-2 border rounded" placeholder="Share your thoughts..."></textarea>
            </div>
            <button type="submit" name="submit_review" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-700">Submit Review</button>
        </form>
    </div>
</div>

<script>
function openReviewModal(itemId, itemName) {
    document.getElementById('review-modal').classList.remove('hidden');
    document.getElementById('review-item-id').value = itemId;
    document.querySelector('#review-modal h2').textContent = 'Review ' + itemName;
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.add('hidden');
    document.getElementById('review-form').reset();
    document.querySelectorAll('.star-rating').forEach(star => star.classList.remove('text-yellow-400'));
}

// Star rating functionality
document.querySelectorAll('.star-rating').forEach((star, index) => {
    star.addEventListener('click', function() {
        const rating = index + 1;
        document.querySelectorAll('.star-rating').forEach((s, i) => {
            if (i < rating) {
                s.classList.add('text-yellow-400');
                s.classList.remove('text-gray-300');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-300');
            }
        });
        document.querySelector(`#star${rating}`).checked = true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
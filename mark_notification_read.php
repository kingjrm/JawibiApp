<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $user_id]);

echo json_encode(['success' => true]);
?>
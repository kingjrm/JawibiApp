<?php
// Security functions

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize output for HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate integer
function validateInt($int) {
    return filter_var($int, FILTER_VALIDATE_INT);
}

// Validate float
function validateFloat($float) {
    return filter_var($float, FILTER_VALIDATE_FLOAT);
}

// Check if user is admin
function isAdmin() {
    if (!isset($_SESSION['user_id'])) return false;
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    return $role === 'admin';
}

// Secure file upload
function secureFileUpload($file, $upload_dir = 'assets/', $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large'];
    }

    // Verify it's actually an image
    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        return ['success' => false, 'error' => 'Invalid image file'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('upload_') . '.' . $extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'error' => 'Failed to move file'];
    }
}
?>
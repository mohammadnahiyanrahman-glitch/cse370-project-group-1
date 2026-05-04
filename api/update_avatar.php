<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['avatar'];
$max_size = 2 * 1024 * 1024; // 2MB

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File exceeds 2MB limit.']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed_mimes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$user_id = $_SESSION['user_id'];
$new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;

$upload_dir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
    // Update DB
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    if ($stmt->execute([$new_name, $user_id])) {
        // Update session
        $_SESSION['profile_picture'] = $new_name;
        $url = '/polititrack/uploads/avatars/' . $new_name;
        echo json_encode(['success' => true, 'url' => $url]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
}

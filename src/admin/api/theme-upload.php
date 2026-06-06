<?php
require_once __DIR__ . '/../../_auth.php';
requireAdmin();

header('Content-Type: application/json');

$allowed = ['acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
$theme = $_POST['theme'] ?? '';

if (!in_array($theme, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown theme']);
    exit;
}

$uploadDir = __DIR__ . '/../../img/themes/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$action = $_POST['action'] ?? 'upload';

if ($action === 'delete') {
    $target = $uploadDir . 'bg-' . $theme . '.jpg';
    if (file_exists($target)) {
        unlink($target);
    }
    echo json_encode(['success' => true]);
    exit;
}

if (empty($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload error: ' . $file['error']]);
    exit;
}

// Validate image type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed_mimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only JPEG, PNG and WebP images are allowed']);
    exit;
}

// Max 8 MB
if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'File too large (max 8 MB)']);
    exit;
}

$target = $uploadDir . 'bg-' . $theme . '.jpg';

// Convert to JPEG for uniform handling
$img = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
    default      => null,
};

if (!$img) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not process image']);
    exit;
}

// Scale down to max 1920 px width if needed
$w = imagesx($img);
$h = imagesy($img);
if ($w > 1920) {
    $newW = 1920;
    $newH = (int) round($h * 1920 / $w);
    $resized = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($img);
    $img = $resized;
}

imagejpeg($img, $target, 88);
imagedestroy($img);

echo json_encode([
    'success' => true,
    'url'     => 'img/themes/bg-' . $theme . '.jpg?v=' . time(),
]);

<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

header('Content-Type: application/json');

// Catch fatal errors (e.g. missing GD extension) and return JSON instead of a blank 500
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        // Only output if nothing was sent yet (output_buffering may be off)
        echo json_encode(['error' => 'PHP fatal: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']]);
    }
});
ob_start();

$allowed = ['dark', 'light', 'acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
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

// Check GD is available
if (!function_exists('imagecreatefromjpeg')) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'GD extension is not enabled on this server']);
    exit;
}

// Check upload dir is writable
if (!is_writable($uploadDir)) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'Upload directory is not writable: ' . $uploadDir]);
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
    ob_end_clean();
    echo json_encode(['error' => 'Could not process image (corrupt or unsupported file)']);
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

$json = json_encode([
    'success' => true,
    'url'     => 'img/themes/bg-' . $theme . '.jpg?v=' . time(),
]);
ob_end_clean();
echo $json;

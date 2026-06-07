<?php
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Config;

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
        echo json_encode(['error' => 'PHP fatal: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']]);
    }
});

ob_start();

if (!CsrfUtils::checkToken()) {
    http_response_code(403);
    echo json_encode(['error' => 'Security error: CSRF token mismatch']);
    exit;
}

$uploadDir = __DIR__ . '/../../img/icons/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$action = $_POST['action'] ?? 'upload';
$type   = $_POST['type']   ?? 'favicon'; // 'favicon' or 'siteicon'

if ($action === 'delete') {
    $targets = ($type === 'favicon') 
        ? ['favicon-16.png', 'favicon-32.png']
        : ['site-icon.png'];

    foreach ($targets as $t) {
        if (file_exists($uploadDir . $t)) unlink($uploadDir . $t);
    }
    
    // Update config
    Config::i()->setValue("website_" . $type . "_custom", false);
    
    // Clear template and data cache
    foreach (glob(__CACHE_DIR__ . '/*.cache.php') as $f) @unlink($f);
    foreach (glob(__CACHE_DIR__ . '/templates/*.php') as $f) @unlink($f);

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

$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
if (!in_array($mime, $allowed_mimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only JPEG, PNG, WebP and ICO images are allowed (MIME: ' . $mime . ')']);
    exit;
}

if (!function_exists('imagecreatefromjpeg')) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'GD extension is not enabled on this server']);
    exit;
}

$img = match($mime) {
    'image/jpeg'   => imagecreatefromjpeg($file['tmp_name']),
    'image/png'    => imagecreatefrompng($file['tmp_name']),
    'image/webp'   => imagecreatefromwebp($file['tmp_name']),
    'image/x-icon', 'image/vnd.microsoft.icon' => @imagecreatefrompng($file['tmp_name']) ?: @imagecreatefromjpeg($file['tmp_name']), 
    default        => null,
};

if (!$img) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'Could not process image (GD might not support this format)']);
    exit;
}

// Preserve transparency for PNG
imagealphablending($img, true);
imagesavealpha($img, true);

if ($type === 'favicon') {
    // Generate 16x16 and 32x32
    foreach ([16, 32] as $size) {
        $resized = imagecreatetruecolor($size, $size);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $size, $size, imagesx($img), imagesy($img));
        imagepng($resized, $uploadDir . 'favicon-' . $size . '.png');
        imagedestroy($resized);
    }
} else {
    // Site icon (logo) - limit to max 512px height/width while keeping aspect ratio
    $max = 512;
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > $max || $h > $max) {
        if ($w > $h) {
            $newW = $max;
            $newH = (int) round($h * $max / $w);
        } else {
            $newH = $max;
            $newW = (int) round($w * $max / $h);
        }
        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagepng($resized, $uploadDir . 'site-icon.png');
        imagedestroy($resized);
    } else {
        imagepng($img, $uploadDir . 'site-icon.png');
    }
}

imagedestroy($img);

// Update config
Config::i()->setValue("website_" . $type . "_custom", true);

// Clear template and data cache
foreach (glob(__CACHE_DIR__ . '/*.cache.php') as $f) @unlink($f);
foreach (glob(__CACHE_DIR__ . '/templates/*.php') as $f) @unlink($f);

$json = json_encode([
    'success' => true,
    'url'     => 'img/icons/' . ($type === 'favicon' ? 'favicon-32.png' : 'site-icon.png') . '?v=' . time(),
]);
ob_end_clean();
echo $json;

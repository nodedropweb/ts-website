<?php
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Config;

require_once __DIR__ . '/../_auth.php';
requireAdmin();

header('Content-Type: application/json');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) http_response_code(500);
        echo json_encode(['error' => 'PHP fatal: ' . $err['message']]);
    }
});

ob_start();

$uploadDir = __DIR__ . '/../../img/';
$action = $_POST['action'] ?? 'upload';

if ($action === 'delete') {
    if (file_exists($uploadDir . 'og.png')) unlink($uploadDir . 'og.png');
    if (file_exists($uploadDir . 'og-image.jpg')) unlink($uploadDir . 'og-image.jpg');
    
    Config::i()->setValue("seo_og_image_custom", false);
    
    foreach (glob(__CACHE_DIR . '/*.cache.php') as $f) @unlink($f);
    foreach (glob(__CACHE_DIR . '/templates/*.php') as $f) @unlink($f);

    echo json_encode(['success' => true]);
    exit;
}

if (empty($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed_mimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only JPEG, PNG and WebP are allowed']);
    exit;
}

if (!function_exists('imagecreatefromjpeg')) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'GD extension missing']);
    exit;
}

$img = match($mime) {
    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
    'image/png'  => imagecreatefrompng($file['tmp_name']),
    'image/webp' => imagecreatefromwebp($file['tmp_name']),
    default      => null,
};

if (!$img) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['error' => 'Image processing failed']);
    exit;
}

// Resize to standard OG size: 1200x630 (Cover style)
$targetW = 1200;
$targetH = 630;
$origW = imagesx($img);
$origH = imagesy($img);

$ratio = max($targetW / $origW, $targetH / $origH);
$newW = (int) ($origW * $ratio);
$newH = (int) ($origH * $ratio);

$tmp = imagecreatetruecolor($targetW, $targetH);
imagealphablending($tmp, false);
imagesavealpha($tmp, true);

// Center the image
$offsetX = (int) (($newW - $targetW) / 2);
$offsetY = (int) (($newH - $targetH) / 2);

imagecopyresampled($tmp, $img, -$offsetX, -$offsetY, 0, 0, $newW, $newH, $origW, $origH);

$target = $uploadDir . 'og.png';
imagepng($tmp, $target, 8); // PNG for better quality, medium compression

imagedestroy($img);
imagedestroy($tmp);

// Clean up old JPG if exists
if (file_exists($uploadDir . 'og-image.jpg')) unlink($uploadDir . 'og-image.jpg');

Config::i()->setValue("seo_og_image_custom", true);

foreach (glob(__CACHE_DIR . '/*.cache.php') as $f) @unlink($f);
foreach (glob(__CACHE_DIR . '/templates/*.php') as $f) @unlink($f);

$json = json_encode([
    'success' => true,
    'url'     => 'img/og.png?v=' . time(),
]);
ob_end_clean();
echo $json;

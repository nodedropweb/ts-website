<?php
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if an image was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error or no file provided']);
    exit;
}

$file = $_FILES['image'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$fileMimeType = mime_content_type($file['tmp_name']);

if (!in_array($fileMimeType, $allowedMimeTypes)) {
    echo json_encode(['success' => false, 'error' => 'Ungültiges Dateiformat. Erlaubt sind JPG, PNG, GIF und WEBP.']);
    exit;
}

// Define the uploads directory (relative to the DocumentRoot, e.g., /src)
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate a safe unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . strtolower($extension);
$destination = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Determine the URL path. Since we are in /src/admin/, the uploads folder is /src/uploads/
    // On the actual site, DocumentRoot is /src, so the URL is /uploads/$filename
    $url = '/uploads/' . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $url
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern der Datei.']);
}

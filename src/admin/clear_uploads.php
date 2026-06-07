<?php
require_once __DIR__ . '/_auth.php';

requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';

if (!is_dir($uploadDir)) {
    echo json_encode(['success' => true, 'message' => 'Uploads folder does not exist.']);
    exit;
}

$files = array_diff(scandir($uploadDir), array('.', '..'));
$deletedCount = 0;

foreach ($files as $file) {
    $filePath = $uploadDir . '/' . $file;
    if (is_file($filePath)) {
        unlink($filePath);
        $deletedCount++;
    }
}

echo json_encode([
    'success' => true,
    'message' => "Erfolgreich $deletedCount Bilder gelöscht."
]);

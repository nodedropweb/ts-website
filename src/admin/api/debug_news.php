<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();
header('Content-Type: application/json');
echo json_encode([
    'POST_keys' => array_keys($_POST),
    'action'       => $_POST['action'] ?? 'MISSING',
    'title'        => $_POST['title'] ?? 'MISSING',
    'title_len'    => strlen($_POST['title'] ?? ''),
    'content_len'  => strlen($_POST['content'] ?? ''),
    'content_preview' => substr($_POST['content'] ?? '', 0, 300),
]);

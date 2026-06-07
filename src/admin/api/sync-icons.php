<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\ServerIconCache;
use Wruczek\TSWebsite\Utils\TeamSpeakUtils;

header('Content-Type: application/json');

if (!TeamSpeakUtils::i()->checkTSConnection()) {
    echo json_encode(['success' => false, 'message' => 'No TeamSpeak connection.']);
    exit;
}

try {
    ServerIconCache::syncIcons();
    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

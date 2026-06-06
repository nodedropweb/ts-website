<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\Config;

$db = \Wruczek\TSWebsite\Utils\DatabaseUtils::i()->getDb();

$content = trim($_POST['imprint_content'] ?? '');
$enabled = isset($_POST['imprint_enabled']) ? 'true' : 'false';
$url     = trim($_POST['imprint_url'] ?? 'imprint.php');

$upsert = function (string $identifier, string $type, string $value) use ($db): void {
    if ($db->has('config', ['identifier' => $identifier])) {
        $db->update('config', ['value' => $value], ['identifier' => $identifier]);
    } else {
        $db->insert('config', ['identifier' => $identifier, 'type' => $type, 'value' => $value, 'user_editable' => 0]);
    }
};

$upsert('imprint_content', 'STRING', $content);
$upsert('imprint_enabled', 'BOOL',   $enabled);
$upsert('imprint_url',     'STRING', $url);

// Clear config cache
\Wruczek\TSWebsite\Config::i()->clearConfigCache();

header('Location: ../imprint-edit.php?flash=' . urlencode('Impressum gespeichert.') . '&type=success');
exit;

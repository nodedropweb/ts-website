<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\Config;

$db = \Wruczek\TSWebsite\Utils\DatabaseUtils::i()->getDb();

$content = trim($_POST['imprint_content'] ?? '');
$enabled = isset($_POST['imprint_enabled']);
$url     = trim($_POST['imprint_url'] ?? 'imprint.php');

$config = Config::i();
$config->setValue('imprint_content', $content);
$config->setValue('imprint_enabled', $enabled);
$config->setValue('imprint_url',     $url);

header('Location: ../imprint-edit.php?flash=' . urlencode('Impressum gespeichert.') . '&type=success');
exit;

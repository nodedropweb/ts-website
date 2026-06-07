<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\Config;

$content = trim($_POST['content'] ?? '');

Config::i()->setValue('rules_content', $content);

header('Location: ../rules-edit.php?flash=' . urlencode('Serverregeln gespeichert.') . '&type=success');
exit;

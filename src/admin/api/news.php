<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\News\DefaultNewsStore;

$store  = new DefaultNewsStore();
$action = $_POST['action'] ?? '';

function redirect(string $msg, string $type = 'success'): void {
    header('Location: ../news.php?flash=' . urlencode($msg) . '&type=' . $type);
    exit;
}

switch ($action) {

    case 'create':
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$title || !$content) {
            redirect('Titel und Inhalt dürfen nicht leer sein.', 'danger');
        }
        $store->addNews($title, $content);
        redirect('News erfolgreich erstellt.');
        break;

    case 'edit':
        $id      = (int)($_POST['id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$id || !$title || !$content) {
            redirect('Ungültige Eingabe.', 'danger');
        }
        $store->editNews($id, $title, $content, null, time());
        redirect('News erfolgreich gespeichert.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect('Ungültige ID.', 'danger');
        // Medoo delete
        \Wruczek\TSWebsite\Utils\DatabaseUtils::i()->getDb()->delete('news', ['newsid' => $id]);
        redirect('News gelöscht.');
        break;

    default:
        redirect('Unbekannte Aktion.', 'danger');
}

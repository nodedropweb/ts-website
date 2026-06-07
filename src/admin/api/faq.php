<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\Utils\DatabaseUtils;

$db     = DatabaseUtils::i()->getDb();
$action = $_POST['action'] ?? '';

function redirect(string $msg, string $type = 'success'): void {
    header('Location: ../faq.php?flash=' . urlencode($msg) . '&type=' . $type);
    exit;
}

switch ($action) {

    case 'create':
        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer']   ?? '');
        if (!$question || !$answer) {
            redirect('Frage und Antwort dürfen nicht leer sein.', 'danger');
        }
        $db->insert('faq', [
            'langid'     => 1,
            'question'   => $question,
            'answer'     => $answer,
            'lastmodify' => date('Y-m-d H:i:s'),
        ]);
        redirect('FAQ-Eintrag erstellt.');
        break;

    case 'edit':
        $id       = (int)($_POST['id']       ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer']   ?? '');
        if (!$id || !$question || !$answer) {
            redirect('Ungültige Eingabe.', 'danger');
        }
        $db->update('faq', [
            'question'   => $question,
            'answer'     => $answer,
            'lastmodify' => date('Y-m-d H:i:s'),
        ], ['faqid' => $id]);
        redirect('FAQ-Eintrag gespeichert.');
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) redirect('Ungültige ID.', 'danger');
        $db->delete('faq', ['faqid' => $id]);
        redirect('FAQ-Eintrag gelöscht.');
        break;

    default:
        redirect('Unbekannte Aktion.', 'danger');
}

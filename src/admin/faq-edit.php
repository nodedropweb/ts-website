<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_editor.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$db  = DatabaseUtils::i()->getDb();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$faq = $id ? $db->get('faq', '*', ['faqid' => $id]) : null;

$question = $faq['question'] ?? '';
$answer   = $faq['answer']   ?? '';

$pageTitle = $id ? 'FAQ bearbeiten' : 'Neuer FAQ-Eintrag';
adminHeader($pageTitle, 'faq');
?>

<div class="mb-3">
    <a href="faq.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Zurück
    </a>
</div>

<form method="post" action="api/faq.php">
    <input type="hidden" name="action" value="<?= $id ? 'edit' : 'create' ?>">
    <?php if ($id): ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group">
                <label>Frage <span class="text-danger">*</span></label>
                <?php renderEditor('question', $question, 'faq-question'); ?>
            </div>

            <div class="form-group mt-4">
                <label>Antwort <span class="text-danger">*</span></label>
                <?php renderEditor('answer', $answer, 'faq-answer'); ?>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="faq.php" class="btn btn-secondary mr-2">Abbrechen</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

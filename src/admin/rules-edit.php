<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_editor.php';

requireAdmin();

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\CsrfUtils;

$content = Config::get('rules_content', '');

$flash     = $_GET['flash']  ?? null;
$flashType = $_GET['type']   ?? 'success';

adminHeader(__a('ADMIN_RULES_TITLE'), 'rules');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<form method="post" action="api/rules.php">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group mb-0">
                <label><?= htmlspecialchars(__a('ADMIN_RULES_FORM_CONTENT')) ?></label>
                <?php renderEditor('content', $content, 'rules'); ?>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

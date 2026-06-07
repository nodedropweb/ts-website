<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_editor.php';

requireAdmin();

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$db = DatabaseUtils::i()->getDb();

$content  = Config::get('imprint_content', '');
$enabled  = Config::get('imprint_enabled', false);
$url      = Config::get('imprint_url', 'imprint.php');

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

adminHeader(__a('ADMIN_IMPRINT_TITLE'), 'config');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="config.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> <?= htmlspecialchars(__a('ADMIN_BTN_BACK_TO_CONFIG')) ?>
    </a>
</div>

<form method="post" action="api/imprint.php">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-cog"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_SECTION_GENERAL')) ?></div>
        <div class="card-body">
            <div class="form-group row align-items-center">
                <label class="col-sm-4 col-form-label">Imprint enabled</label>
                <div class="col-sm-8">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="imprint_enabled"
                               name="imprint_enabled" <?= $enabled ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="imprint_enabled"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                    <small class="form-text text-muted">Shows the imprint link in the navigation.</small>
                </div>
            </div>
            <div class="form-group row align-items-center mb-0">
                <label class="col-sm-4 col-form-label" for="imprint_url">URL</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control form-control-sm" id="imprint_url"
                           name="imprint_url" value="<?= htmlspecialchars($url) ?>">
                    <small class="form-text text-muted">
                        <code>imprint.php</code> for the built-in page, or an external URL.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group mb-0">
                <label><?= htmlspecialchars(__a('ADMIN_RULES_FORM_CONTENT')) ?></label>
                <?php renderEditor('imprint_content', $content, 'imprint'); ?>
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

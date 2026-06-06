<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

$ts    = (int)($db->get('config', 'value', ['identifier' => 'onlinerecord_date'])  ?? 0);
$value = (int)($db->get('config', 'value', ['identifier' => 'onlinerecord_value']) ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newValue = max(0, (int)($_POST['value'] ?? 0));
    $newTs    = $_POST['date'] ? (int)(new DateTimeImmutable($_POST['date']))->getTimestamp() : $ts;

    $db->update('config', ['value' => (string)$newValue], ['identifier' => 'onlinerecord_value']);
    $db->update('config', ['value' => (string)$newTs],    ['identifier' => 'onlinerecord_date']);

    header('Location: onlinerecord.php?flash=' . urlencode(__a('ADMIN_ONLINERECORD_SAVED')) . '&type=success');
    exit;
}

// Convert timestamp to datetime-local format (YYYY-MM-DDTHH:MM)
$dateForInput = $ts > 0 ? date('Y-m-d\TH:i', $ts) : '';

adminHeader(__a('ADMIN_ONLINERECORD_TITLE'), 'config');
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

<form method="post">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-trophy"></i> <?= htmlspecialchars(__a('ADMIN_ONLINERECORD_TITLE')) ?></div>
        <div class="card-body">

            <div class="form-group row align-items-center">
                <label class="col-sm-4 col-form-label" for="value"><?= htmlspecialchars(__a('ADMIN_ONLINERECORD_VALUE_LABEL')) ?></label>
                <div class="col-sm-4">
                    <input type="number" class="form-control" id="value"
                           name="value" value="<?= $value ?>" min="0">
                </div>
            </div>

            <div class="form-group row align-items-center mb-0">
                <label class="col-sm-4 col-form-label" for="date"><?= htmlspecialchars(__a('ADMIN_ONLINERECORD_DATE_LABEL')) ?></label>
                <div class="col-sm-5">
                    <input type="datetime-local" class="form-control" id="date"
                           name="date" value="<?= htmlspecialchars($dateForInput) ?>">
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_ONLINERECORD_DATE_HINT')) ?></small>
                </div>
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

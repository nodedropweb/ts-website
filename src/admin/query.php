<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';
$confirmed = isset($_GET['confirmed']);

$db = DatabaseUtils::i()->getDb();

$fields = [
    'query_hostname'  => ['label' => __a('ADMIN_QUERY_HOSTNAME_LABEL'), 'hint' => __a('ADMIN_QUERY_HOSTNAME_HINT')],
    'query_port'      => ['label' => __a('ADMIN_QUERY_PORT_LABEL'),     'hint' => __a('ADMIN_QUERY_PORT_HINT')],
    'query_username'  => ['label' => __a('ADMIN_QUERY_USERNAME_LABEL'), 'hint' => __a('ADMIN_QUERY_USERNAME_HINT')],
    'query_password'  => ['label' => __a('ADMIN_QUERY_PASSWORD_LABEL'), 'hint' => __a('ADMIN_QUERY_PASSWORD_HINT'), 'password' => true],
    'query_nickname'  => ['label' => __a('ADMIN_QUERY_NICKNAME_LABEL'), 'hint' => __a('ADMIN_QUERY_NICKNAME_HINT')],
    'query_displayip' => ['label' => __a('ADMIN_QUERY_DISPLAYIP_LABEL'),'hint' => __a('ADMIN_QUERY_DISPLAYIP_HINT')],
    'tsserver_port'   => ['label' => __a('ADMIN_QUERY_VOICEPORT_LABEL'),'hint' => __a('ADMIN_QUERY_VOICEPORT_HINT')],
];

$values = [];
foreach (array_keys($fields) as $key) {
    $values[$key] = (string)($db->get('config', 'value', ['identifier' => $key]) ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($fields) as $key) {
        $val = trim($_POST[$key] ?? '');
        $db->update('config', ['value' => $val], ['identifier' => $key]);
    }
    header('Location: query.php?flash=' . urlencode(__a('ADMIN_QUERY_SAVED')) . '&type=success&confirmed=1');
    exit;
}

adminHeader(__a('ADMIN_QUERY_TITLE'), 'config');
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

<?php if (!$confirmed): ?>
<!-- Sicherheitsabfrage -->
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-exclamation-triangle"></i> <strong><?= htmlspecialchars(__a('ADMIN_QUERY_WARNING_TITLE')) ?></strong>
    </div>
    <div class="card-body">
        <p class="mb-3"><?= __a('ADMIN_QUERY_WARNING_TEXT') ?></p>
        <a href="query.php?confirmed=1" class="btn btn-warning mr-2">
            <i class="fas fa-edit"></i> <?= htmlspecialchars(__a('ADMIN_QUERY_WARNING_CONFIRM')) ?>
        </a>
        <a href="config.php" class="btn btn-secondary">
            <?= htmlspecialchars(__a('ADMIN_BTN_CANCEL')) ?>
        </a>
    </div>
</div>

<?php else: ?>
<!-- Formular -->
<form method="post">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plug"></i> <?= htmlspecialchars(__a('ADMIN_QUERY_SECTION')) ?></div>
        <div class="card-body">
            <?php foreach ($fields as $key => $meta): ?>
            <div class="form-group row align-items-center">
                <label class="col-sm-4 col-form-label" for="<?= $key ?>">
                    <?= htmlspecialchars($meta['label']) ?>
                </label>
                <div class="col-sm-8">
                    <input type="<?= !empty($meta['password']) ? 'password' : 'text' ?>"
                           class="form-control form-control-sm" id="<?= $key ?>"
                           name="<?= $key ?>" value="<?= htmlspecialchars($values[$key]) ?>"
                           autocomplete="<?= !empty($meta['password']) ? 'new-password' : 'off' ?>">
                    <small class="form-text text-muted"><?= htmlspecialchars($meta['hint']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
            </button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php adminFooter(); ?>

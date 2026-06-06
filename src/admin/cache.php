<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

$cacheLabels = [
    'cache_serverinfo'    => ['label' => __a('ADMIN_CACHE_SERVERINFO_LABEL'),    'hint' => __a('ADMIN_CACHE_SERVERINFO_HINT')],
    'cache_clientlist'    => ['label' => __a('ADMIN_CACHE_CLIENTLIST_LABEL'),    'hint' => __a('ADMIN_CACHE_CLIENTLIST_HINT')],
    'cache_channelist'    => ['label' => __a('ADMIN_CACHE_CHANNELLIST_LABEL'),   'hint' => __a('ADMIN_CACHE_CHANNELLIST_HINT')],
    'cache_servergroups'  => ['label' => __a('ADMIN_CACHE_SERVERGROUPS_LABEL'),  'hint' => __a('ADMIN_CACHE_SERVERGROUPS_HINT')],
    'cache_channelgroups' => ['label' => __a('ADMIN_CACHE_CHANNELGROUPS_LABEL'), 'hint' => __a('ADMIN_CACHE_CHANNELGROUPS_HINT')],
    'cache_banlist'       => ['label' => __a('ADMIN_CACHE_BANLIST_LABEL'),       'hint' => __a('ADMIN_CACHE_BANLIST_HINT')],
    'cache_adminstatus'   => ['label' => __a('ADMIN_CACHE_ADMINSTATUS_LABEL'),   'hint' => __a('ADMIN_CACHE_ADMINSTATUS_HINT')],
    'cache_servericons'   => ['label' => __a('ADMIN_CACHE_SERVERICONS_LABEL'),   'hint' => __a('ADMIN_CACHE_SERVERICONS_HINT')],
    'cache_languages'     => ['label' => __a('ADMIN_CACHE_LANGUAGES_LABEL'),     'hint' => __a('ADMIN_CACHE_LANGUAGES_HINT')],
    'cache_logincode'     => ['label' => __a('ADMIN_CACHE_LOGINCODE_LABEL'),     'hint' => __a('ADMIN_CACHE_LOGINCODE_HINT')],
];

// Load current values
$values = [];
foreach (array_keys($cacheLabels) as $key) {
    $values[$key] = (int)($db->get('config', 'value', ['identifier' => $key]) ?? 0);
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($cacheLabels) as $key) {
        $val = max(0, (int)($_POST[$key] ?? 0));
        $db->update('config', ['value' => (string)$val], ['identifier' => $key]);
    }
    header('Location: cache.php?flash=' . urlencode(__a('ADMIN_CACHE_SAVED')) . '&type=success');
    exit;
}

adminHeader(__a('ADMIN_CACHE_TITLE'), 'config');
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
        <div class="card-header"><i class="fas fa-clock"></i> <?= htmlspecialchars(__a('ADMIN_CACHE_TITLE')) ?></div>
        <div class="card-body">
            <p class="text-muted small mb-4"><?= htmlspecialchars(__a('ADMIN_CACHE_HINT')) ?></p>
            <?php foreach ($cacheLabels as $key => $meta): ?>
            <div class="form-group row align-items-center">
                <label class="col-sm-4 col-form-label" for="<?= $key ?>">
                    <?= htmlspecialchars($meta['label']) ?>
                </label>
                <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" id="<?= $key ?>"
                               name="<?= $key ?>" value="<?= $values[$key] ?>" min="0" step="1">
                        <div class="input-group-append">
                            <span class="input-group-text">s</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-5">
                    <small class="text-muted"><?= htmlspecialchars($meta['hint']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

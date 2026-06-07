<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\AdminStatus;
use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

function loadCfg(\Medoo\Medoo $db, string $key): string {
    return (string)($db->get('config', 'value', ['identifier' => $key]) ?? '');
}

$enabled            = loadCfg($db, 'adminstatus_enabled') === 'true';
$hideOffline        = loadCfg($db, 'adminstatus_hideoffline') === 'true';
$offlineHiddenByDef = loadCfg($db, 'adminstatus_offlinehiddenbydefault') === 'true';
$mode               = (int)(loadCfg($db, 'adminstatus_mode') ?: 2);
$selectedGroups     = json_decode(loadCfg($db, 'adminstatus_groups'), true) ?? [];
$selectedGroups     = array_map('intval', $selectedGroups);
$ignoredUsers       = json_decode(loadCfg($db, 'adminstatus_ignoredusers'), true) ?? [];
$ignoredUsers       = array_map('intval', $ignoredUsers);

// --- Save ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEnabled  = isset($_POST['enabled'])            ? 'true' : 'false';
    $newHideOff  = isset($_POST['hideoffline'])        ? 'true' : 'false';
    $newOffByDef = isset($_POST['offlinehiddenbydef']) ? 'true' : 'false';
    $newMode     = max(1, min(4, (int)($_POST['mode'] ?? 2)));
    $newGroups   = array_values(array_map('intval', $_POST['groups']  ?? []));
    $newIgnored  = array_values(array_map('intval', $_POST['ignored'] ?? []));

    $updates = [
        'adminstatus_enabled'               => $newEnabled,
        'adminstatus_hideoffline'           => $newHideOff,
        'adminstatus_offlinehiddenbydefault'=> $newOffByDef,
        'adminstatus_mode'                  => (string)$newMode,
        'adminstatus_groups'                => json_encode($newGroups),
        'adminstatus_ignoredusers'          => json_encode($newIgnored),
    ];
    foreach ($updates as $key => $val) {
        $db->update('config', ['value' => $val], ['identifier' => $key]);
    }

    AdminStatus::i()->clearCache();

    header('Location: adminstatus.php?flash=' . urlencode(__a('ADMIN_ADMINSTATUS_SAVED')) . '&type=success');
    exit;
}

// Server groups (skip default/template groups)
$serverGroups = CacheManager::i()->getServerGroupList() ?? [];
$serverGroups = array_filter($serverGroups, fn($g) => ((int)($g['sgid'] ?? 0)) > 1 && ((int)($g['type'] ?? 0)) !== 0);

// Members of selected groups for the ignored-users picker
$groupMembers = [];
if (!empty($selectedGroups)) {
    $cachedClients = AdminStatus::i()->getCachedAdminClients($selectedGroups);
    if ($cachedClients) {
        foreach ($cachedClients as $members) {
            foreach ($members as $m) {
                $cldbid = (int)$m['cldbid'];
                if (!isset($groupMembers[$cldbid])) {
                    $groupMembers[$cldbid] = (string)($m['client_nickname'] ?? ('DBID ' . $cldbid));
                }
            }
        }
        ksort($groupMembers);
    }
}

$modeLabels = [
    1 => __a('ADMIN_ADMINSTATUS_MODE_1'),
    2 => __a('ADMIN_ADMINSTATUS_MODE_2'),
    3 => __a('ADMIN_ADMINSTATUS_MODE_3'),
    4 => __a('ADMIN_ADMINSTATUS_MODE_4'),
];

adminHeader(__a('ADMIN_ADMINSTATUS_TITLE'), 'config');
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
        <div class="card-header"><i class="fas fa-sliders-h"></i> <?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_SECTION_GENERAL')) ?></div>
        <div class="card-body">

            <div class="form-group row align-items-center">
                <label class="col-sm-5 col-form-label"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_WIDGET_ENABLED_LABEL')) ?></label>
                <div class="col-sm-7">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="enabled"
                               name="enabled" <?= $enabled ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="enabled"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                </div>
            </div>

            <div class="form-group row align-items-center">
                <label class="col-sm-5 col-form-label"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_HIDE_OFFLINE_LABEL')) ?></label>
                <div class="col-sm-7">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="hideoffline"
                               name="hideoffline" <?= $hideOffline ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="hideoffline"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_HIDE_OFFLINE_HINT')) ?></small>
                </div>
            </div>

            <div class="form-group row align-items-center">
                <label class="col-sm-5 col-form-label"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_OFFLINE_COLLAPSED_LABEL')) ?></label>
                <div class="col-sm-7">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="offlinehiddenbydef"
                               name="offlinehiddenbydef" <?= $offlineHiddenByDef ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="offlinehiddenbydef"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_OFFLINE_COLLAPSED_HINT')) ?></small>
                </div>
            </div>

            <div class="form-group row align-items-start mb-0">
                <label class="col-sm-5 col-form-label"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_DISPLAY_MODE_LABEL')) ?></label>
                <div class="col-sm-7">
                    <?php foreach ($modeLabels as $val => $label): ?>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input"
                               id="mode_<?= $val ?>" name="mode" value="<?= $val ?>"
                               <?= $mode === $val ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="mode_<?= $val ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-users"></i> <?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_SECTION_GROUPS')) ?></div>
        <div class="card-body">
            <?php if (empty($serverGroups)): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_NO_GROUPS')) ?></p>
            <?php else: ?>
            <div class="row">
                <?php foreach ($serverGroups as $g): ?>
                <div class="col-md-4 col-sm-6 mb-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input"
                               id="sg_<?= (int)$g['sgid'] ?>" name="groups[]"
                               value="<?= (int)$g['sgid'] ?>"
                               <?= in_array((int)$g['sgid'], $selectedGroups, true) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="sg_<?= (int)$g['sgid'] ?>">
                            <?= htmlspecialchars((string)$g['name']) ?>
                            <span class="text-muted small">(#<?= (int)$g['sgid'] ?>)</span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-slash"></i> <?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_SECTION_IGNORED')) ?></div>
        <div class="card-body">
            <?php if (!empty($groupMembers)): ?>
                <p class="text-muted small mb-3"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_IGNORED_HINT')) ?></p>
                <div class="row">
                    <?php foreach ($groupMembers as $cldbid => $name): ?>
                    <div class="col-md-4 col-sm-6 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input"
                                   id="ign_<?= $cldbid ?>" name="ignored[]"
                                   value="<?= $cldbid ?>"
                                   <?= in_array($cldbid, $ignoredUsers, true) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="ign_<?= $cldbid ?>">
                                <?= htmlspecialchars($name) ?>
                                <span class="text-muted small">(#<?= $cldbid ?>)</span>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (empty($selectedGroups)): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_NO_GROUPS_SELECTED')) ?></p>
            <?php else: ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_NO_CACHE')) ?></p>
            <?php endif; ?>

            <?php if (!empty($ignoredUsers) && empty($groupMembers)): ?>
                <p class="text-muted small mt-3 mb-0">
                    <?= htmlspecialchars(__a('ADMIN_ADMINSTATUS_CURRENTLY_IGNORED')) ?> <code><?= htmlspecialchars(implode(', ', $ignoredUsers)) ?></code>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-right mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
        </button>
    </div>
</form>

<?php adminFooter(); ?>

<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

$raw     = (string)($db->get('config', 'value', ['identifier' => 'viewer_hidden_channel_ids']) ?? '[]');
$hidden  = array_map('intval', json_decode($raw, true) ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newHidden = array_values(array_map('intval', $_POST['hidden'] ?? []));
    $db->update('config', ['value' => json_encode($newHidden)], ['identifier' => 'viewer_hidden_channel_ids']);
    header('Location: viewer.php?flash=' . urlencode(__a('ADMIN_VIEWER_SAVED')) . '&type=success');
    exit;
}

// Channel list from TS cache
$channels = CacheManager::i()->getChannelList() ?? [];

// Build tree: root channels first, then indent sub-channels
function buildChannelTree(array $channels): array {
    $tree = [];
    foreach ($channels as $cid => $info) {
        $pid = (int)(string)($info['pid'] ?? 0);
        $tree[$pid][] = ['cid' => (int)(string)$cid, 'info' => $info];
    }
    $result = [];
    $walk = function (int $pid, int $depth) use (&$walk, &$tree, &$result) {
        foreach ($tree[$pid] ?? [] as $item) {
            $result[] = ['cid' => $item['cid'], 'info' => $item['info'], 'depth' => $depth];
            $walk($item['cid'], $depth + 1);
        }
    };
    $walk(0, 0);
    return $result;
}

$channelTree = buildChannelTree($channels);

adminHeader(__a('ADMIN_VIEWER_TITLE'), 'config');
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

    <div class="card mb-4 card-accent">
        <div class="card-header">
            <i class="fas fa-eye-slash"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_TITLE')) ?>
            <div class="card-header-actions">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
                </button>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_HINT')) ?>
            </p>

            <?php if (empty($channelTree)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_NO_CHANNELS')) ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="toggle-all">
                                    <label class="custom-control-label" for="toggle-all"></label>
                                </div>
                            </th>
                            <th><?= htmlspecialchars(__a('ADMIN_VIEWER_SECTION')) ?></th>
                            <th class="text-center" style="width: 100px;">ID</th>
                            <th class="text-center" style="width: 150px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($channelTree as $item):
                            $cid   = $item['cid'];
                            $name  = (string)($item['info']['channel_name'] ?? ('Kanal #' . $cid));
                            $depth = $item['depth'];
                            $isHidden = in_array($cid, $hidden, true);
                        ?>
                        <tr class="<?= $isHidden ? 'opacity-75' : '' ?>">
                            <td class="text-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input channel-checkbox"
                                           id="ch_<?= $cid ?>" name="hidden[]"
                                           value="<?= $cid ?>" <?= $isHidden ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="ch_<?= $cid ?>"></label>
                                </div>
                            </td>
                            <td>
                                <div style="padding-left: <?= $depth * 1.5 ?>rem;">
                                    <?php if ($depth > 0): ?>
                                        <span class="text-muted mr-1" style="opacity: 0.5;">└</span>
                                    <?php endif; ?>
                                    <i class="fas fa-hashtag text-muted mr-2 small"></i>
                                    <label for="ch_<?= $cid ?>" class="mb-0" style="cursor: pointer;">
                                        <?= htmlspecialchars($name) ?>
                                    </label>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light">#<?= $cid ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($isHidden): ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-eye-slash"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_HIDDEN_BADGE')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-eye"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_VISIBLE_BADGE')) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_HIDDEN_HINT')) ?>
                </span>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleAll = document.getElementById('toggle-all');
    const checkboxes = document.querySelectorAll('.channel-checkbox');

    if (toggleAll) {
        toggleAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = toggleAll.checked;
            });
        });
    }
});
</script>

<?php adminFooter(); ?>

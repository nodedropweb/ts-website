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

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-sitemap"></i> <?= htmlspecialchars(__a('ADMIN_VIEWER_SECTION')) ?></div>
        <div class="card-body">
            <p class="text-muted small mb-3"><?= htmlspecialchars(__a('ADMIN_VIEWER_HINT')) ?></p>

            <?php if (empty($channelTree)): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars(__a('ADMIN_VIEWER_NO_CHANNELS')) ?></p>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($channelTree as $item):
                    $cid   = $item['cid'];
                    $name  = (string)($item['info']['channel_name'] ?? ('Kanal #' . $cid));
                    $depth = $item['depth'];
                    $isHidden = in_array($cid, $hidden, true);
                ?>
                <label class="list-group-item list-group-item-action d-flex align-items-center py-2"
                       style="padding-left: <?= 1 + $depth * 1.5 ?>rem; cursor:pointer;">
                    <div class="custom-control custom-checkbox mr-2">
                        <input type="checkbox" class="custom-control-input"
                               id="ch_<?= $cid ?>" name="hidden[]"
                               value="<?= $cid ?>" <?= $isHidden ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="ch_<?= $cid ?>"></label>
                    </div>
                    <?php if ($depth > 0): ?>
                        <span class="text-muted mr-1" style="font-size:.8em"><?= str_repeat('└ ', 1) ?></span>
                    <?php endif; ?>
                    <i class="fas fa-hashtag text-muted mr-2" style="font-size:.8em"></i>
                    <?= htmlspecialchars($name) ?>
                    <span class="text-muted small ml-2">(#<?= $cid ?>)</span>
                    <?php if ($isHidden): ?>
                        <span class="badge badge-secondary ml-auto"><?= htmlspecialchars(__a('ADMIN_VIEWER_HIDDEN_BADGE')) ?></span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

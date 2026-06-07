<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = DatabaseUtils::i()->getDb();
    $current = Config::get('admin_cldbids', []);

    if ($action === 'add') {
        $cldbid = (int)($_POST['cldbid'] ?? 0);
        if ($cldbid > 0 && !in_array($cldbid, $current, true)) {
            $current[] = $cldbid;
            $db->update('config', ['value' => json_encode(array_values($current))], ['identifier' => 'admin_cldbids']);
        }
        header('Location: admins.php?flash=' . urlencode(__a('ADMIN_ADMINS_ADDED')));
        exit;
    } elseif ($action === 'remove') {
        $cldbid = (int)($_POST['cldbid'] ?? 0);
        if ($cldbid !== Auth::getCldbid()) {
            $current = array_filter($current, fn($c) => $c !== $cldbid);
            $db = DatabaseUtils::i()->getDb();
            $db->update('config', ['value' => json_encode(array_values($current))], ['identifier' => 'admin_cldbids']);
        }
        header('Location: admins.php?flash=' . urlencode(__a('ADMIN_ADMINS_REMOVED')));
        exit;
    }
}

$admins = Config::get('admin_cldbids', []);

$onlineClients = [];
try {
    $onlineClients = array_filter(
        \Wruczek\TSWebsite\CacheManager::i()->getClientList() ?? [],
        fn($c) => ($c['client_type'] ?? 1) === 0 && !in_array((int)$c['client_database_id'], $admins, true)
    );
} catch (\Throwable $e) {}

adminHeader(__a('ADMIN_ADMINS_TITLE'), 'config');
?>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header"><?= htmlspecialchars(__a('ADMIN_ADMINS_CURRENT')) ?></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(__a('ADMIN_ADMINS_TABLE_CLDBID')) ?></th>
                            <th><?= htmlspecialchars(__a('ADMIN_ADMINS_TABLE_ACTION')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $cldbid): ?>
                    <tr>
                        <td><code><?= (int)$cldbid ?></code>
                            <?php if ((int)$cldbid === Auth::getCldbid()): ?>
                                <span class="badge badge-info ml-1"><?= htmlspecialchars(__a('ADMIN_ADMINS_YOU_BADGE')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$cldbid !== Auth::getCldbid()): ?>
                            <form method="post" style="display:inline"
                                  onsubmit="return confirm(<?= json_encode(__a('ADMIN_ADMINS_CONFIRM_REMOVE')) ?>)">
                                <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cldbid" value="<?= (int)$cldbid ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> <?= htmlspecialchars(__a('ADMIN_BTN_REMOVE')) ?>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted small"><?= htmlspecialchars(__a('ADMIN_ADMINS_CANNOT_REMOVE_SELF')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header"><?= htmlspecialchars(__a('ADMIN_ADMINS_ADD_TITLE')) ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label><?= htmlspecialchars(__a('ADMIN_ADMINS_CLDBID_LABEL')) ?></label>
                        <input type="number" class="form-control" name="cldbid" min="1" required>
                    </div>
                    <?php if (!empty($onlineClients)): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars(__a('ADMIN_ADMINS_ONLINE_LABEL')) ?></label>
                        <?php foreach ($onlineClients as $c): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-block text-left mb-1"
                                onclick="document.querySelector('[name=cldbid]').value=<?= (int)$c['client_database_id'] ?>">
                            <?= htmlspecialchars((string)$c['client_nickname']) ?>
                            <span class="float-right text-muted">ID: <?= (int)$c['client_database_id'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus"></i> <?= htmlspecialchars(__a('ADMIN_BTN_ADD')) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>

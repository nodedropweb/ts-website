<?php
/**
 * Admin Bootstrap Setup
 * Open to anyone when no SETUP_LOCK exists (first-time bootstrap).
 * After the first admin is registered, SETUP_LOCK is written and this page
 * becomes inaccessible to everyone. Delete src/private/SETUP_LOCK to re-enable.
 */

define('DISABLE_CSRF_CHECK', true);
require_once __DIR__ . '/../private/php/load.php';

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\I18n;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

if (!function_exists('__a')) {
    function __a(string $msgid, array $args = []): string {
        return I18n::t($msgid, 'admin', $args);
    }
}

define('SETUP_LOCK_FILE', __DIR__ . '/../private/SETUP_LOCK');

if (file_exists(SETUP_LOCK_FILE)) {
    http_response_code(403);
    ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title><?= htmlspecialchars(__a('ADMIN_SETUP_LOCKED')) ?></title>
<link rel="stylesheet" href="../lib/bootstrap/4.6.0/css/bootstrap.min.css">
</head><body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="col-md-6 col-lg-5"><div class="card">
    <div class="card-header"><i class="fas fa-lock"></i> <?= htmlspecialchars(__a('ADMIN_SETUP_LOCKED')) ?></div>
    <div class="card-body">
        <p class="mb-3"><?= __a('ADMIN_SETUP_LOCKED_MSG') ?></p>
        <a href="../" class="btn btn-secondary">&#8592; <?= htmlspecialchars(__a('ADMIN_SETUP_BTN_GOTO')) ?></a>
    </div>
</div></div>
</body></html><?php
    exit;
}

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cldbid = (int)($_POST['cldbid'] ?? 0);
    if ($cldbid <= 0) {
        $error = __a('ADMIN_SETUP_ERR_INVALID');
    } else {
        $db = DatabaseUtils::i()->getDb();
        if ($db->has('config', ['identifier' => 'admin_cldbids'])) {
            $db->update('config', ['value' => json_encode([$cldbid])], ['identifier' => 'admin_cldbids']);
        } else {
            $db->insert('config', ['identifier' => 'admin_cldbids', 'type' => 'JSON', 'value' => json_encode([$cldbid]), 'user_editable' => 0]);
        }
        @file_put_contents(SETUP_LOCK_FILE, 'Locked by setup.php on ' . date('Y-m-d H:i:s'));
        $success = __a('ADMIN_SETUP_SUCCESS', [$cldbid]);
    }
}

$onlineClients = [];
try {
    $onlineClients = array_filter(
        \Wruczek\TSWebsite\CacheManager::i()->getClientList() ?? [],
        fn($c) => ($c['client_type'] ?? 1) === 0
    );
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(__a('ADMIN_SETUP_TITLE')) ?></title>
    <link rel="stylesheet" href="../lib/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../lib/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="col-md-6 col-lg-5">
    <div class="card">
        <div class="card-header"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__a('ADMIN_SETUP_TITLE')) ?></div>
        <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary"><?= htmlspecialchars(__a('ADMIN_SETUP_BTN_GOTO')) ?></a>
        <?php else: ?>
            <p class="text-muted"><?= __a('ADMIN_SETUP_NO_ADMIN') ?></p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
                <div class="form-group">
                    <label><?= htmlspecialchars(__a('ADMIN_SETUP_CLDBID_LABEL')) ?></label>
                    <input type="number" class="form-control" name="cldbid" min="1" required autofocus>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_SETUP_CLDBID_HINT')) ?></small>
                </div>
                <?php if (!empty($onlineClients)): ?>
                <div class="form-group">
                    <label><?= htmlspecialchars(__a('ADMIN_SETUP_ONLINE_LABEL')) ?></label>
                    <div class="list-group">
                    <?php foreach ($onlineClients as $c): ?>
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between"
                                onclick="document.querySelector('[name=cldbid]').value=<?= (int)$c['client_database_id'] ?>">
                            <?= htmlspecialchars((string)$c['client_nickname']) ?>
                            <span class="badge badge-secondary">ID: <?= (int)$c['client_database_id'] ?></span>
                        </button>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars(__a('ADMIN_SETUP_BTN_REGISTER')) ?></button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

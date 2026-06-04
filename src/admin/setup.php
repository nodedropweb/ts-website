<?php
/**
 * Admin Bootstrap Setup
 * Accessible WITHOUT login — only works when admin_cldbids is still empty.
 * After the first admin is set, this page becomes a no-op (redirects to index).
 */

define('DISABLE_CSRF_CHECK', true);
require_once __DIR__ . '/../private/php/load.php';

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$current = Config::get('admin_cldbids', []);
if (!empty($current)) {
    header('Location: index.php');
    exit;
}

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cldbid = (int)($_POST['cldbid'] ?? 0);
    if ($cldbid <= 0) {
        $error = 'Bitte eine gültige cldbid eingeben (positive ganze Zahl).';
    } else {
        $db = DatabaseUtils::i()->getDb();
        $db->update('config', ['value' => json_encode([$cldbid])], ['identifier' => 'admin_cldbids']);
        $success = "cldbid $cldbid wurde als erster Admin eingetragen. Diese Seite ist nun gesperrt.";
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
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Setup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="col-md-6 col-lg-5">
    <div class="card">
        <div class="card-header"><i class="fas fa-shield-alt"></i> Admin-Panel Ersteinrichtung</div>
        <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary">Zum Admin-Panel</a>
        <?php else: ?>
            <p class="text-muted">
                Noch kein Admin eingetragen. Trage deine <strong>cldbid</strong> ein,
                um Zugriff auf das Admin-Panel zu erhalten.
            </p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
                <div class="form-group">
                    <label>Deine cldbid</label>
                    <input type="number" class="form-control" name="cldbid" min="1" required autofocus>
                    <small class="form-text text-muted">Nach TS3-Login auf der Hauptseite sichtbar, oder aus der Liste unten wählen.</small>
                </div>
                <?php if (!empty($onlineClients)): ?>
                <div class="form-group">
                    <label>Aktuell online</label>
                    <div class="list-group">
                    <?php foreach ($onlineClients as $c): ?>
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between"
                                onclick="document.querySelector('[name=cldbid]').value=<?= (int)$c['client_database_id'] ?>">
                            <?= htmlspecialchars((string)$c['client_nickname']) ?>
                            <span class="badge badge-secondary">cldbid: <?= (int)$c['client_database_id'] ?></span>
                        </button>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-block">Als ersten Admin eintragen</button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

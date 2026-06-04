<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$serverGroups = CacheManager::i()->getServerGroupList() ?? [];
$serverGroups = array_filter($serverGroups, fn($g) => ((int)($g['sgid'] ?? 0)) > 1 && ((int)($g['type'] ?? 0)) !== 0);

$db  = DatabaseUtils::i()->getDb();
$raw = $db->get('config', 'value', ['identifier' => 'adminstatus_groups']);
$selected = [];
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $selected = array_map('intval', $decoded);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chosen = array_map('intval', $_POST['groups'] ?? []);
    $db->update('config', ['value' => json_encode(array_values($chosen))], ['identifier' => 'adminstatus_groups']);
    header('Location: adminstatus.php?flash=' . urlencode('Admin-Status-Gruppen gespeichert.') . '&type=success');
    exit;
}

adminHeader('Admin-Status Gruppen', 'config');
?>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<p class="text-muted">
    Wähle die Servergruppen, deren Mitglieder im Admin-Status-Widget auf der Sidebar angezeigt werden.
</p>

<form method="post">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
    <div class="card mb-4">
        <div class="card-body">
            <?php if (empty($serverGroups)): ?>
                <p class="text-muted">Keine Servergruppen gefunden.</p>
            <?php else: ?>
            <div class="row">
                <?php foreach ($serverGroups as $g): ?>
                <div class="col-md-4 col-sm-6 mb-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input"
                               id="sg_<?= (int)$g['sgid'] ?>" name="groups[]"
                               value="<?= (int)$g['sgid'] ?>"
                               <?= in_array((int)$g['sgid'], $selected, true) ? 'checked' : '' ?>>
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
        <div class="card-footer text-right">
            <a href="config.php" class="btn btn-secondary mr-2">Zurück</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

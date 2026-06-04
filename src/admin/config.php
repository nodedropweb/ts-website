<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$db    = DatabaseUtils::i()->getDb();
$flash = $_GET['flash'] ?? null;
$flashType = $_GET['type'] ?? 'success';

// Load all config rows with type info
$rows = $db->select('config', ['identifier', 'type', 'value'], ['ORDER' => ['identifier' => 'ASC']]);

// Group by section (prefix before first underscore, or "general")
$sections = [];
foreach ($rows as $row) {
    $parts   = explode('_', $row['identifier'], 2);
    $section = count($parts) > 1 ? $parts[0] : 'general';
    $sections[$section][] = $row;
}
ksort($sections);

$sectionLabels = [
    'adminstatus'  => 'Admin-Status Widget',
    'assigner'     => 'Gruppen-Zuweisung',
    'cache'        => 'Cache-Zeiten (Sekunden)',
    'general'      => 'Allgemein',
    'imprint'      => 'Impressum',
    'nav'          => 'Navigation',
    'onlinerecord' => 'Online-Rekord',
    'query'        => 'TeamSpeak Query',
    'viewer'       => 'Channel-Viewer',
    'website'      => 'Website',
    'admin'        => 'Admin-Panel',
    'assignerconfig'=> 'Zuweisung-Config',
    'baseurl'      => 'Allgemein',
    'loginpokeclient'=> 'Allgemein',
    'timezone'     => 'Allgemein',
    'tsserver'     => 'TeamSpeak Server',
    'usingcloudflare'=> 'Allgemein',
];

adminHeader('Konfiguration', 'config');

if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<form method="post" action="api/config.php">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <?php foreach ($sections as $section => $items): ?>
    <div class="card mb-4">
        <div class="card-header">
            <?= htmlspecialchars($sectionLabels[$section] ?? ucfirst($section)) ?>
        </div>
        <div class="card-body">
            <?php foreach ($items as $row):
                $key  = $row['identifier'];
                $type = strtolower($row['type']);
                $val  = $row['value'];
                $inputName = 'config[' . htmlspecialchars($key) . ']';
            ?>
            <div class="form-group row align-items-start">
                <label class="col-sm-4 col-form-label">
                    <code class="text-info"><?= htmlspecialchars($key) ?></code>
                    <span class="config-type-badge config-type-<?= $type ?> ml-1"><?= strtoupper($type) ?></span>
                </label>
                <div class="col-sm-8">
                <?php if ($key === 'assignerconfig'): ?>
                    <a href="assigner.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-gamepad"></i> Im Assigner-Editor bearbeiten
                    </a>
                    <small class="form-text text-muted">Kategorien, Gruppen und Icons visuell konfigurieren.</small>
                <?php elseif ($key === 'adminstatus_groups'): ?>
                    <a href="adminstatus.php" class="btn btn-info btn-sm">
                        <i class="fas fa-users"></i> Admin-Status-Gruppen bearbeiten
                    </a>
                    <small class="form-text text-muted">Wähle welche Servergruppen im Admin-Status-Widget erscheinen.</small>
                <?php elseif ($key === 'admin_cldbids'): ?>
                    <a href="admins.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-shield-alt"></i> Admin-Zugänge verwalten
                    </a>
                    <small class="form-text text-muted">Wer hat Zugriff auf dieses Admin-Panel?</small>
                <?php elseif ($type === 'bool'): ?>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input"
                               id="cfg_<?= $key ?>" name="<?= $inputName ?>" value="true"
                               <?= $val === 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cfg_<?= $key ?>">Aktiviert</label>
                    </div>
                <?php elseif ($type === 'int'): ?>
                    <input type="number" class="form-control form-control-sm"
                           name="<?= $inputName ?>" value="<?= htmlspecialchars($val) ?>">
                <?php elseif ($type === 'json'): ?>
                    <textarea class="form-control form-control-sm font-monospace"
                              name="<?= $inputName ?>" rows="3"
                              style="font-family:monospace"><?= htmlspecialchars($val) ?></textarea>
                    <small class="form-text text-muted">JSON-Format, z.B. <code>[1, 42]</code> oder <code>{"key":"val"}</code></small>
                <?php else: /* STRING */ ?>
                    <input type="text" class="form-control form-control-sm"
                           name="<?= $inputName ?>" value="<?= htmlspecialchars($val) ?>">
                <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-right mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Alle Einstellungen speichern
        </button>
    </div>
</form>

<?php adminFooter(); ?>

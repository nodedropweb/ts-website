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
    'adminstatus'    => __a('ADMIN_CONFIG_SECTION_ADMINSTATUS'),
    'assigner'       => __a('ADMIN_CONFIG_SECTION_ASSIGNER'),
    'cache'          => __a('ADMIN_CONFIG_SECTION_CACHE'),
    'general'        => __a('ADMIN_CONFIG_SECTION_GENERAL'),
    'imprint'        => __a('ADMIN_CONFIG_SECTION_IMPRINT'),
    'nav'            => __a('ADMIN_NAV_CONFIG'),
    'onlinerecord'   => __a('ADMIN_CONFIG_SECTION_ONLINERECORD'),
    'query'          => __a('ADMIN_CONFIG_SECTION_QUERY'),
    'viewer'         => __a('ADMIN_CONFIG_SECTION_VIEWER'),
    'seo'            => __a('ADMIN_CONFIG_SECTION_SEO'),
    'website'        => 'Website',
    'admin'          => __a('ADMIN_CONFIG_SECTION_ADMIN'),
    'assignerconfig' => __a('ADMIN_CONFIG_SECTION_ASSIGNER'),
    'baseurl'        => __a('ADMIN_CONFIG_SECTION_GENERAL'),
    'loginpokeclient'=> __a('ADMIN_CONFIG_SECTION_GENERAL'),
    'timezone'       => __a('ADMIN_CONFIG_SECTION_GENERAL'),
    'tsserver'       => 'TeamSpeak Server',
    'usingcloudflare'=> __a('ADMIN_CONFIG_SECTION_GENERAL'),
];

adminHeader(__a('ADMIN_CONFIG_TITLE'), 'config');

if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<form method="post" action="api/config.php">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <?php foreach ($sections as $section => $items): ?>
    <?php if (in_array($section, ['nav', 'rules', 'tsserver', 'website'], true)) continue; ?>
    <div class="card mb-4">
        <div class="card-header">
            <?= htmlspecialchars($sectionLabels[$section] ?? ucfirst($section)) ?>
        </div>
        <div class="card-body">
            <?php if ($section === 'adminstatus'): ?>
            <a href="adminstatus.php" class="btn btn-info">
                <i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_ADMINSTATUS')) ?>
            </a>
            <?php elseif ($section === 'assigner'): ?>
            <a href="assigner.php" class="btn btn-primary">
                <i class="fas fa-gamepad"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_ASSIGNER')) ?>
            </a>
            <?php elseif ($section === 'cache'): ?>
            <a href="cache.php" class="btn btn-secondary">
                <i class="fas fa-clock"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_CACHE')) ?>
            </a>
            <?php elseif ($section === 'general'): ?>
            <a href="general.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_GENERAL')) ?>
            </a>
            <?php elseif ($section === 'imprint'): ?>
            <a href="imprint-edit.php" class="btn btn-secondary">
                <i class="far fa-id-card"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_IMPRINT')) ?>
            </a>
            <?php elseif ($section === 'onlinerecord'): ?>
            <a href="onlinerecord.php" class="btn btn-secondary">
                <i class="fas fa-trophy"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_ONLINERECORD')) ?>
            </a>
            <?php elseif ($section === 'viewer'): ?>
            <a href="viewer.php" class="btn btn-secondary">
                <i class="fas fa-sitemap"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_VIEWER')) ?>
            </a>
            <?php elseif ($section === 'seo'): ?>
            <a href="seo.php" class="btn btn-info">
                <i class="fas fa-search"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_SEO')) ?>
            </a>
            <?php elseif ($section === 'query'): ?>
            <a href="query.php" class="btn btn-warning">
                <i class="fas fa-plug"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_QUERY')) ?>
            </a>
            <?php elseif ($section === 'admin'): ?>
            <a href="admins.php" class="btn btn-warning">
                <i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_ADMINS')) ?>
            </a>
            <?php elseif ($section === 'nav'): ?>
            <?php /* nav_brand wurde in Allgemeine Einstellungen verschoben */ ?>
            <?php else: ?>
            <?php foreach ($items as $row):
                $key  = $row['identifier'];
                if (in_array($key, ['assignerconfig', 'nav_brand'], true)) continue;
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
                <?php if (in_array($key, ['assignerconfig', 'assigner_cooldown_seconds', 'assigner_required_sgids'], true)): ?>
                    <a href="assigner.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-gamepad"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_EDIT_IN_ASSIGNER')) ?>
                    </a>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_CONFIG_MANAGED_IN_ASSIGNER')) ?></small>
                <?php elseif ($key === 'admin_cldbids'): ?>
                    <a href="admins.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__a('ADMIN_CONFIG_BTN_ADMINS')) ?>
                    </a>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_CONFIG_WHO_HAS_ACCESS')) ?></small>
                <?php elseif ($type === 'bool'): ?>
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input"
                               id="cfg_<?= $key ?>" name="<?= $inputName ?>" value="true"
                               <?= $val === 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cfg_<?= $key ?>"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                <?php elseif ($type === 'int'): ?>
                    <input type="number" class="form-control form-control-sm"
                           name="<?= $inputName ?>" value="<?= htmlspecialchars($val) ?>">
                <?php elseif ($type === 'json'): ?>
                    <textarea class="form-control form-control-sm font-monospace"
                              name="<?= $inputName ?>" rows="3"
                              style="font-family:monospace"><?= htmlspecialchars($val) ?></textarea>
                    <small class="form-text text-muted"><?= __a('ADMIN_CONFIG_JSON_HINT') ?></small>
                <?php else: /* STRING */ ?>
                    <input type="text" class="form-control form-control-sm"
                           name="<?= $inputName ?>" value="<?= htmlspecialchars($val) ?>">
                <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-right mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE_ALL')) ?>
        </button>
    </div>
</form>

<?php adminFooter(); ?>

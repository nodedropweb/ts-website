<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

$fields = [
    'website_title'   => ['label' => __a('ADMIN_GENERAL_FIELD_WEBSITE_TITLE_LABEL'), 'type' => 'string', 'hint' => __a('ADMIN_GENERAL_FIELD_WEBSITE_TITLE_HINT')],
    'nav_brand'       => ['label' => __a('ADMIN_GENERAL_FIELD_NAV_BRAND_LABEL'),     'type' => 'string', 'hint' => __a('ADMIN_GENERAL_FIELD_NAV_BRAND_HINT')],
    'baseurl'         => ['label' => __a('ADMIN_GENERAL_FIELD_BASEURL_LABEL'),       'type' => 'string', 'hint' => __a('ADMIN_GENERAL_FIELD_BASEURL_HINT')],
    'loginpokeclient' => ['label' => __a('ADMIN_GENERAL_FIELD_LOGINPOKE_LABEL'),     'type' => 'bool',   'hint' => __a('ADMIN_GENERAL_FIELD_LOGINPOKE_HINT')],
    'timezone'        => ['label' => __a('ADMIN_GENERAL_FIELD_TIMEZONE_LABEL'),      'type' => 'string', 'hint' => __a('ADMIN_GENERAL_FIELD_TIMEZONE_HINT')],
    'usingcloudflare' => ['label' => __a('ADMIN_GENERAL_FIELD_CLOUDFLARE_LABEL'),    'type' => 'bool',   'hint' => __a('ADMIN_GENERAL_FIELD_CLOUDFLARE_HINT')],
];

$values = [];
foreach (array_keys($fields) as $key) {
    $values[$key] = (string)($db->get('config', 'value', ['identifier' => $key]) ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $meta) {
        $val = $meta['type'] === 'bool'
            ? (isset($_POST[$key]) ? 'true' : 'false')
            : trim($_POST[$key] ?? '');
        $db->update('config', ['value' => $val], ['identifier' => $key]);
    }
    header('Location: general.php?flash=' . urlencode(__a('ADMIN_GENERAL_SAVED')) . '&type=success');
    exit;
}

adminHeader(__a('ADMIN_GENERAL_TITLE'), 'config');
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
        <div class="card-header"><i class="fas fa-cog"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_TITLE')) ?></div>
        <div class="card-body">
            <?php foreach ($fields as $key => $meta): ?>
            <div class="form-group row align-items-center">
                <label class="col-sm-4 col-form-label" for="<?= $key ?>">
                    <?= htmlspecialchars($meta['label']) ?>
                </label>
                <div class="col-sm-8">
                    <?php if ($meta['type'] === 'bool'): ?>
                    <div class="custom-control custom-switch mt-1">
                        <input type="checkbox" class="custom-control-input" id="<?= $key ?>"
                               name="<?= $key ?>" <?= $values[$key] === 'true' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="<?= $key ?>"><?= htmlspecialchars(__a('ADMIN_ENABLED')) ?></label>
                    </div>
                    <?php else: ?>
                    <input type="text" class="form-control form-control-sm" id="<?= $key ?>"
                           name="<?= $key ?>" value="<?= htmlspecialchars($values[$key]) ?>">
                    <?php endif; ?>
                    <?php if ($meta['hint']): ?>
                    <small class="form-text text-muted"><?= htmlspecialchars($meta['hint']) ?></small>
                    <?php endif; ?>
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

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-image"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_MANAGEMENT_TITLE')) ?></div>
        <div class="card-body">
            <div class="row">
                <!-- Favicon -->
                <div class="col-md-6 mb-4">
                    <label class="font-weight-bold"><?= htmlspecialchars(__a('ADMIN_GENERAL_FAVICON_LABEL')) ?></label>
                    <p class="text-muted small"><?= htmlspecialchars(__a('ADMIN_GENERAL_FAVICON_HINT')) ?></p>
                    
                    <div class="d-flex align-items-start">
                        <div class="mr-3 border rounded p-2 bg-light d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <?php 
                                $hasCustomFav = Config::get("website_favicon_custom", false);
                                $favUrl  = $hasCustomFav ? '../img/icons/favicon-32.png?v='.time() : '../img/icons/defaulticon-32.png';
                            ?>
                            <img src="<?= $favUrl ?>" id="preview-favicon" style="max-width: 32px; max-height: 32px;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="custom-file mb-2">
                                <input type="file" class="custom-file-input icon-upload" id="file-favicon" data-type="favicon">
                                <label class="custom-file-label" for="file-favicon"><?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_CHOOSE_BTN')) ?></label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon-delete" data-type="favicon" <?= !$hasCustomFav ? 'disabled' : '' ?>>
                                <i class="fas fa-trash"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_DELETE_BTN')) ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Site Icon -->
                <div class="col-md-6 mb-4">
                    <label class="font-weight-bold"><?= htmlspecialchars(__a('ADMIN_GENERAL_SITEICON_LABEL')) ?></label>
                    <p class="text-muted small"><?= htmlspecialchars(__a('ADMIN_GENERAL_SITEICON_HINT')) ?></p>
                    
                    <div class="d-flex align-items-start">
                        <div class="mr-3 border rounded p-2 bg-light d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <?php 
                                $hasCustomSite = Config::get("website_siteicon_custom", false);
                                $siteIconUrl  = $hasCustomSite ? '../img/icons/site-icon.png?v='.time() : '../img/icons/defaulticon-256.png';
                            ?>
                            <img src="<?= $siteIconUrl ?>" id="preview-siteicon" style="max-width: 48px; max-height: 48px;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="custom-file mb-2">
                                <input type="file" class="custom-file-input icon-upload" id="file-siteicon" data-type="siteicon">
                                <label class="custom-file-label" for="file-siteicon"><?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_CHOOSE_BTN')) ?></label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon-delete" data-type="siteicon" <?= !$hasCustomSite ? 'disabled' : '' ?>>
                                <i class="fas fa-trash"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_DELETE_BTN')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-sync"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_SYNC_ICONS_TITLE')) ?></div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-sm-4"><?= htmlspecialchars(__a('ADMIN_GENERAL_SYNC_ICONS_TITLE')) ?></div>
                <div class="col-sm-8">
                    <button type="button" class="btn btn-info btn-sm mb-2" id="btn-sync-icons">
                        <i class="fas fa-sync"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_SYNC_ICONS_BTN')) ?>
                    </button>
                    <div id="sync-icons-status" class="mt-2" style="display: none;"></div>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_GENERAL_SYNC_ICONS_HINT')) ?></small>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Icon Upload ---
        const fileInputs = document.querySelectorAll('.icon-upload');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (!this.files || !this.files[0]) return;
                
                const type = this.dataset.type;
                const formData = new FormData();
                formData.append('image', this.files[0]);
                formData.append('type', type);

                fetch('api/icon-upload.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '<?= CsrfUtils::getToken() ?>' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('preview-' + type).src = '../' + data.url;
                        document.querySelector('.btn-icon-delete[data-type="' + type + '"]').disabled = false;
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => alert('Upload failed: ' + err));
            });
        });

        // --- Icon Delete ---
        const deleteBtns = document.querySelectorAll('.btn-icon-delete');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = btn.dataset.type;
                if (!confirm(<?= json_encode(__a('ADMIN_GENERAL_ICON_DELETE_CONFIRM')) ?>)) return;
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('type', type);

                fetch('api/icon-upload.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '<?= CsrfUtils::getToken() ?>' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const defaultUrl = (type === 'favicon') ? '../img/icons/defaulticon-32.png' : '../img/icons/defaulticon-256.png';
                        document.getElementById('preview-' + type).src = defaultUrl;
                        btn.disabled = true;
                        // Reset file input label
                        const label = document.querySelector('label[for="file-' + type + '"]');
                        if (label) label.textContent = <?= json_encode(__a('ADMIN_GENERAL_ICON_CHOOSE_BTN')) ?>;
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Delete failed: ' + err);
                });
            });
        });

        // --- TeamSpeak Icon Sync ---
        const btnSync = document.getElementById('btn-sync-icons');
        const syncStatus = document.getElementById('sync-icons-status');
        if (!btnSync) return;

        btnSync.addEventListener('click', function() {
            const icon = btnSync.querySelector('i');
            btnSync.disabled = true;
            icon.classList.add('fa-spin');
            syncStatus.style.display = 'none';

            fetch('api/sync-icons.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '<?= CsrfUtils::getToken() ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                syncStatus.style.display = 'block';
                if (data.success) {
                    syncStatus.innerHTML = '<div class="alert alert-success py-1 small mb-0"><i class="fas fa-check-circle"></i> ' + <?= json_encode(__a('ADMIN_GENERAL_SYNC_ICONS_SUCCESS')) ?> + '</div>';
                } else {
                    syncStatus.innerHTML = '<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-exclamation-circle"></i> Error: ' + data.message + '</div>';
                }
            })
            .catch(err => {
                syncStatus.style.display = 'block';
                syncStatus.innerHTML = '<div class="alert alert-danger py-1 small mb-0"><i class="fas fa-exclamation-circle"></i> Network error: ' + err + '</div>';
            })
            .finally(() => {
                btnSync.disabled = false;
                icon.classList.remove('fa-spin');
            });
        });

    });
    </script>

<?php adminFooter(); ?>

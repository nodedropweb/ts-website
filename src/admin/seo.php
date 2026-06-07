<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$flash     = $_GET['flash'] ?? null;
$flashType = $_GET['type']  ?? 'success';

$db = DatabaseUtils::i()->getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['seo_description'] ?? '');
    $ogTitle     = trim($_POST['seo_og_title'] ?? '');

    $db->update('config', ['value' => $description], ['identifier' => 'seo_description']);
    $db->update('config', ['value' => $ogTitle], ['identifier' => 'seo_og_title']);

    header('Location: seo.php?flash=' . urlencode(__a('ADMIN_GENERAL_SAVED')) . '&type=success');
    exit;
}

adminHeader(__a('ADMIN_SEO_TITLE'), 'config');
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

<div class="card mb-4 card-accent">
    <div class="card-header">
        <i class="fas fa-search"></i> <?= htmlspecialchars(__a('ADMIN_SEO_TITLE')) ?>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            <i class="fas fa-info-circle"></i> <?= htmlspecialchars(__a('ADMIN_SEO_HINT')) ?>
        </p>

        <form method="post">
            <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

            <div class="form-group row">
                <label class="col-sm-4 col-form-label font-weight-bold" for="seo_description">
                    <?= htmlspecialchars(__a('ADMIN_SEO_FIELD_DESCRIPTION_LABEL')) ?>
                </label>
                <div class="col-sm-8">
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="3"><?= htmlspecialchars(Config::get('seo_description')) ?></textarea>
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_SEO_FIELD_DESCRIPTION_HINT')) ?></small>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-4 col-form-label font-weight-bold" for="seo_og_title">
                    <?= htmlspecialchars(__a('ADMIN_SEO_FIELD_OG_TITLE_LABEL')) ?>
                </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="seo_og_title" name="seo_og_title" value="<?= htmlspecialchars(Config::get('seo_og_title')) ?>">
                    <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_SEO_FIELD_OG_TITLE_HINT')) ?></small>
                </div>
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
                </button>
            </div>
        </form>

        <hr class="my-4">

        <!-- OG Image Upload -->
        <div class="row align-items-start">
            <label class="col-sm-4 font-weight-bold">
                <?= htmlspecialchars(__a('ADMIN_SEO_FIELD_OG_IMAGE_LABEL')) ?>
            </label>
            <div class="col-sm-8">
                <div class="d-flex align-items-start mb-3">
                    <div class="mr-3 border rounded p-1 bg-light d-flex align-items-center justify-content-center" style="width: 150px; height: 80px; overflow: hidden;">
                        <?php 
                            $hasCustomOg = Config::get("seo_og_image_custom", false);
                            $ogUrl = $hasCustomOg ? '../img/og.png?v='.time() : 'https://via.placeholder.com/1200x630.png?text=Preview+Image';
                        ?>
                        <img src="<?= $ogUrl ?>" id="preview-og" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="custom-file mb-2">
                            <input type="file" class="custom-file-input" id="file-og">
                            <label class="custom-file-label" for="file-og"><?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_CHOOSE_BTN')) ?></label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-og" <?= !$hasCustomOg ? 'disabled' : '' ?>>
                            <i class="fas fa-trash"></i> <?= htmlspecialchars(__a('ADMIN_GENERAL_ICON_DELETE_BTN')) ?>
                        </button>
                    </div>
                </div>
                <small class="form-text text-muted"><?= htmlspecialchars(__a('ADMIN_SEO_FIELD_OG_IMAGE_HINT')) ?></small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file-og');
    const deleteBtn = document.getElementById('btn-delete-og');
    const preview = document.getElementById('preview-og');

    fileInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        
        const formData = new FormData();
        formData.append('image', this.files[0]);

        fetch('api/seo-upload.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '<?= CsrfUtils::getToken() ?>' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preview.src = '../' + data.url;
                deleteBtn.disabled = false;
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => alert('Upload failed: ' + err));
    });

    deleteBtn.addEventListener('click', function() {
        if (!confirm(<?= json_encode(__a('ADMIN_GENERAL_ICON_DELETE_CONFIRM')) ?>)) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');

        fetch('api/seo-upload.php', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '<?= CsrfUtils::getToken() ?>' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preview.src = 'https://via.placeholder.com/1200x630.png?text=Preview+Image';
                deleteBtn.disabled = true;
                document.querySelector('label[for="file-og"]').textContent = <?= json_encode(__a('ADMIN_GENERAL_ICON_CHOOSE_BTN')) ?>;
            }
        })
        .catch(err => alert('Delete failed: ' + err));
    });
});
</script>

<?php adminFooter(); ?>

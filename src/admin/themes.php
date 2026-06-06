<?php
require_once __DIR__ . '/_auth.php';
requireAdmin();
require_once __DIR__ . '/_layout.php';

$themes = [
    'dark'            => ['label' => 'Dark',             'color' => '#1e202f',                                 'fallback' => false],
    'light'           => ['label' => 'Light',            'color' => 'linear-gradient(135deg,#e8edf5,#5c6bc0)', 'fallback' => true],
    'acrylic'         => ['label' => 'Acrylic',          'color' => 'linear-gradient(135deg,#7c83f5,#a78bfa)', 'fallback' => true],
    'acrylic-midnight'=> ['label' => 'Acrylic Midnight', 'color' => 'linear-gradient(135deg,#0d1b3e,#4fc3f7)', 'fallback' => true],
    'acrylic-ember'   => ['label' => 'Acrylic Ember',    'color' => 'linear-gradient(135deg,#3a0800,#ff7043)', 'fallback' => true],
    'acrylic-forest'  => ['label' => 'Acrylic Forest',   'color' => 'linear-gradient(135deg,#051a0e,#4caf78)', 'fallback' => true],
];

$imgDir   = __DIR__ . '/../img/themes/';
$imgBase  = '../img/themes/';

adminHeader('Theme Backgrounds', 'themes');
?>

<div class="row">
<?php foreach ($themes as $key => $meta): ?>
<?php $imgFile = $imgDir . 'bg-' . $key . '.jpg'; $hasImg = file_exists($imgFile); ?>
<div class="col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center">
            <span style="display:inline-block;width:16px;height:16px;border-radius:50%;background:<?= htmlspecialchars($meta['color']) ?>;margin-right:8px;flex-shrink:0"></span>
            <strong><?= htmlspecialchars($meta['label']) ?></strong>
            <?php if ($hasImg): ?>
                <span class="badge badge-success ml-auto">Custom image</span>
            <?php elseif ($meta['fallback']): ?>
                <span class="badge badge-secondary ml-auto">Picsum fallback</span>
            <?php else: ?>
                <span class="badge badge-dark ml-auto">Custom only</span>
            <?php endif; ?>
        </div>

        <?php if ($hasImg): ?>
        <div class="card-img-top" style="height:160px;background:url('<?= htmlspecialchars($imgBase . 'bg-' . $key . '.jpg') ?>?v=<?= filemtime($imgFile) ?>') center/cover no-repeat;border-bottom:1px solid rgba(0,0,0,.1)"></div>
        <?php else: ?>
        <div class="card-img-top d-flex align-items-center justify-content-center" style="height:160px;background:#1a1a2a;color:#666;font-size:.85rem;border-bottom:1px solid rgba(0,0,0,.1)">
            <i class="fas fa-image mr-2"></i> <?= $meta['fallback'] ? 'No custom image &mdash; using Picsum' : 'No custom image &mdash; solid background' ?>
        </div>
        <?php endif; ?>

        <div class="card-body">
            <form class="theme-upload-form" data-theme="<?= htmlspecialchars($key) ?>">
                <div class="custom-file mb-2">
                    <input type="file" class="custom-file-input" id="file-<?= htmlspecialchars($key) ?>" accept="image/jpeg,image/png,image/webp" required>
                    <label class="custom-file-label" for="file-<?= htmlspecialchars($key) ?>">Choose image…</label>
                </div>
                <p class="text-muted small mb-2">JPEG, PNG or WebP · max 8 MB · will be scaled to 1920 px width</p>
                <div class="d-flex">
                    <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-upload"></i> Upload</button>
                    <?php if ($hasImg): ?>
                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-theme="<?= htmlspecialchars($key) ?>"><i class="fas fa-trash"></i> Remove</button>
                    <?php endif; ?>
                </div>
                <div class="upload-feedback mt-2"></div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.custom-file-input').forEach(function (input) {
    input.addEventListener('change', function () {
        var label = input.nextElementSibling;
        label.textContent = input.files[0] ? input.files[0].name : 'Choose image…';
    });
});

document.querySelectorAll('.theme-upload-form').forEach(function (form) {
    var theme    = form.dataset.theme;
    var feedback = form.querySelector('.upload-feedback');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fileInput = form.querySelector('input[type=file]');
        if (!fileInput.files[0]) return;

        var fd = new FormData();
        fd.append('theme', theme);
        fd.append('image', fileInput.files[0]);

        feedback.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Uploading…</span>';

        fetch('api/theme-upload.php', { method: 'POST', body: fd })
            .then(function (r) {
                if (!r.ok) {
                    return r.text().then(function (t) {
                        throw new Error('HTTP ' + r.status + ': ' + t.slice(0, 200));
                    });
                }
                return r.json();
            })
            .then(function (d) {
                if (d.success) {
                    feedback.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Uploaded — reload to see preview</span>';
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> ' + (d.error || 'Unknown error') + '</span>';
                }
            })
            .catch(function (err) {
                feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> ' + (err.message || 'Network error') + '</span>';
            });
    });
});

document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!confirm('Remove custom background for ' + btn.dataset.theme + '?')) return;
        var fd = new FormData();
        fd.append('theme', btn.dataset.theme);
        fd.append('action', 'delete');

        fetch('api/theme-upload.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) location.reload();
            });
    });
});
</script>

<?php adminFooter(); ?>

<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_editor.php';

requireAdmin();

use Wruczek\TSWebsite\News\DefaultNewsStore;
use Wruczek\TSWebsite\Utils\CsrfUtils;

$store = new DefaultNewsStore();
$id    = isset($_GET['id']) ? (int)$_GET['id'] : null;
$news  = $id ? $store->getNews($id) : null;

$title   = $news['title']   ?? '';
$content = $news['content'] ?? '';

$pageTitle = $id ? __a('ADMIN_NEWS_EDIT_TITLE') : __a('ADMIN_NEWS_NEW_TITLE');
adminHeader($pageTitle, 'news');
?>

<div class="mb-3">
    <a href="news.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> <?= htmlspecialchars(__a('ADMIN_BTN_BACK')) ?>
    </a>
</div>

<form method="post" action="api/news.php">
    <input type="hidden" name="action" value="<?= $id ? 'edit' : 'create' ?>">
    <?php if ($id): ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group">
                <label for="news-title"><?= htmlspecialchars(__a('ADMIN_NEWS_FORM_TITLE')) ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="news-title" name="title"
                       value="<?= htmlspecialchars($title) ?>" required>
            </div>

            <div class="form-group">
                <label><?= htmlspecialchars(__a('ADMIN_NEWS_FORM_CONTENT')) ?> <span class="text-danger">*</span></label>
                <?php renderEditor('content', $content, 'news'); ?>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="news.php" class="btn btn-secondary mr-2"><?= htmlspecialchars(__a('ADMIN_BTN_CANCEL')) ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= htmlspecialchars(__a('ADMIN_BTN_SAVE')) ?>
            </button>
        </div>
    </div>
</form>

<?php adminFooter(); ?>

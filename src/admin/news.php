<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\News\DefaultNewsStore;
use Wruczek\TSWebsite\Utils\CsrfUtils;

$store = new DefaultNewsStore();
$flash = $_GET['flash'] ?? null;
$flashType = $_GET['type'] ?? 'success';

adminHeader('News', 'news');

if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="news-edit.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Neue News
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Erstellt</th>
                    <th>Bearbeitet</th>
                    <th class="text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $newsList = $store->getNewsList(100);
            if (empty($newsList)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Keine News vorhanden.</td></tr>
            <?php else:
                foreach ($newsList as $news): ?>
                <tr>
                    <td class="text-muted"><?= $news['newsId'] ?></td>
                    <td><?= htmlspecialchars($news['title']) ?></td>
                    <td><?= date('d.m.Y H:i', $news['added']) ?></td>
                    <td><?= $news['edited'] ? date('d.m.Y H:i', $news['edited']) : '—' ?></td>
                    <td class="text-right">
                        <a href="news-edit.php?id=<?= $news['newsId'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="post" action="api/news.php" style="display:inline"
                              onsubmit="return confirm('News wirklich löschen?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $news['newsId'] ?>">
                            <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter(); ?>

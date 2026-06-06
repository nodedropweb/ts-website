<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

requireAdmin();

use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\DatabaseUtils;

$db    = DatabaseUtils::i()->getDb();
$flash = $_GET['flash'] ?? null;
$flashType = $_GET['type'] ?? 'success';

adminHeader(__a('ADMIN_FAQ_TITLE'), 'faq');

if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="faq-edit.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= htmlspecialchars(__a('ADMIN_FAQ_NEW_BTN')) ?>
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><?= htmlspecialchars(__a('ADMIN_TABLE_ID')) ?></th>
                    <th><?= htmlspecialchars(__a('ADMIN_TABLE_QUESTION')) ?></th>
                    <th><?= htmlspecialchars(__a('ADMIN_TABLE_LAST_MODIFIED')) ?></th>
                    <th class="text-right"><?= htmlspecialchars(__a('ADMIN_TABLE_ACTIONS')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $faqs = $db->select('faq', ['faqid', 'question', 'lastmodify'], ['ORDER' => ['faqid' => 'ASC']]);
            if (empty($faqs)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4"><?= htmlspecialchars(__a('ADMIN_FAQ_EMPTY')) ?></td></tr>
            <?php else:
                foreach ($faqs as $faq): ?>
                <tr>
                    <td class="text-muted"><?= $faq['faqid'] ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth(strip_tags($faq['question']), 0, 80, '…')) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($faq['lastmodify'])) ?></td>
                    <td class="text-right">
                        <a href="faq-edit.php?id=<?= $faq['faqid'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="post" action="api/faq.php" style="display:inline"
                              onsubmit="return confirm(<?= json_encode(__a('ADMIN_FAQ_CONFIRM_DELETE')) ?>)">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $faq['faqid'] ?>">
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

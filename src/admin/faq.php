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

<div class="mb-3 d-flex justify-content-between align-items-end">
    <a href="faq-edit.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= htmlspecialchars(__a('ADMIN_FAQ_NEW_BTN')) ?>
    </a>
</div>

<form method="post" action="api/config.php" class="card mb-4">
    <input type="hidden" name="csrf-token" value="<?= htmlspecialchars(CsrfUtils::getToken()) ?>">
    <input type="hidden" name="return_to" value="../faq.php">
    
    <div class="card-header"><i class="fas fa-cog"></i> FAQ-Einstellungen</div>
    <div class="card-body">
        <div class="form-group row mb-0 align-items-center">
            <label class="col-sm-3 col-form-label">
                <code class="text-info">faq_contact_url</code>
            </label>
            <div class="col-sm-7">
                <?php $faqContactUrl = $db->get('config', 'value', ['identifier' => 'faq_contact_url']) ?? ''; ?>
                <input type="text" class="form-control form-control-sm" name="config[faq_contact_url]" value="<?= htmlspecialchars($faqContactUrl) ?>" placeholder="z. B. https://example.com/kontakt">
            </div>
            <div class="col-sm-2 text-right">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
            </div>
        </div>
    </div>
</form>

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

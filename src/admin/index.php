<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Config;

requireAdmin();

$newsCount = (new \Wruczek\TSWebsite\News\DefaultNewsStore())->getNewsCount();
$siteTitle = Config::get('website_title', 'TS-Website');

adminHeader('Dashboard', '');
?>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-newspaper fa-2x mb-2 text-primary"></i>
                <h5 class="card-title">News</h5>
                <p class="display-4"><?= $newsCount ?></p>
                <a href="news.php" class="btn btn-primary btn-sm">Verwalten</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-question-circle fa-2x mb-2 text-info"></i>
                <h5 class="card-title">FAQ</h5>
                <a href="faq.php" class="btn btn-info btn-sm mt-3">Verwalten</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-cog fa-2x mb-2 text-warning"></i>
                <h5 class="card-title">Konfiguration</h5>
                <p class="text-muted small"><?= htmlspecialchars($siteTitle) ?></p>
                <a href="config.php" class="btn btn-warning btn-sm">Bearbeiten</a>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>

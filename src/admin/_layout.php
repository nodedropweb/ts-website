<?php
/**
 * Shared admin layout helpers.
 * Call adminHeader($title) and adminFooter() in each page.
 */

use Wruczek\TSWebsite\Auth;

function adminHeader(string $title, string $activeNav = ''): void {
    $nav = [
        'news'     => ['href' => 'news.php',     'label' => '<i class="fas fa-newspaper"></i> News'],
        'faq'      => ['href' => 'faq.php',      'label' => '<i class="fas fa-question-circle"></i> FAQ'],
        'assigner' => ['href' => 'assigner.php', 'label' => '<i class="fas fa-gamepad"></i> Assigner'],
        'config'   => ['href' => 'config.php',   'label' => '<i class="fas fa-cog"></i> Konfiguration'],
    ];
    $version = defined('__TSWEBSITE_VERSION') ? __TSWEBSITE_VERSION : '';
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">

<nav class="navbar navbar-expand-md navbar-dark bg-dark admin-navbar">
    <a class="navbar-brand" href="index.php">
        <i class="fas fa-shield-alt"></i> TS-Website Admin
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav mr-auto">
            <?php foreach ($nav as $key => $item): ?>
            <li class="nav-item<?= $activeNav === $key ? ' active' : '' ?>">
                <a class="nav-link" href="<?= $item['href'] ?>"><?= $item['label'] ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Zur Website
                </a>
            </li>
            <li class="nav-item">
                <span class="nav-link text-muted">
                    <i class="fas fa-user"></i> <?= htmlspecialchars(Auth::getNickname() ?? '') ?>
                </span>
            </li>
        </ul>
    </div>
</nav>

<div class="admin-container container-fluid py-4">
    <h4 class="admin-page-title mb-4"><?= htmlspecialchars($title) ?></h4>
    <?php
}

function adminFooter(): void {
    ?>
</div><!-- /.admin-container -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

function flashMessage(string $type, string $msg): void {
    echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($msg)
        . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
}

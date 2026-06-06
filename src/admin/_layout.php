<?php
/**
 * Shared admin layout helpers.
 * Call adminHeader($title) and adminFooter() in each page.
 */

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\I18n;

/**
 * Translate a string in the admin domain and return it.
 */
// Language switcher — same cookie as installer/frontend
if (!empty($_GET['lang'])) {
    $requestedLang = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['lang']);
    if ($requestedLang !== '') {
        setcookie('tswebsite_language', $requestedLang, time() + 60 * 60 * 24 * 90, '/');
        $_COOKIE['tswebsite_language'] = $requestedLang;
        \Wruczek\TSWebsite\I18n::detectLocale();
    }
}

const ADMIN_LANG_NAMES = [
    'en'    => 'English',     'en-us' => 'English (US)',
    'de'    => 'Deutsch',     'pl'    => 'Polski',
    'ru'    => 'Русский',     'fr'    => 'Français',
    'es'    => 'Español',     'it'    => 'Italiano',
    'nl'    => 'Nederlands',  'cs'    => 'Čeština',
    'hu'    => 'Magyar',      'pt-br' => 'Português (Brasil)',
    'pt-pt' => 'Português (Portugal)',
    'tr'    => 'Türkçe',      'sv'    => 'svenska',
    'nb'    => 'norsk (bokmål)', 'da'  => 'Dansk',
    'uk'    => 'Українська',  'be'    => 'Беларуская',
    'bs'    => 'Босански',    'el'    => 'Ελληνικά',
    'bg'    => 'български',   'zh-cn' => '简体中文',
    'ar'    => 'العربية',
];

function __a(string $msgid, array $args = []): string {
    return I18n::t($msgid, 'admin', $args);
}

function adminHeader(string $title, string $activeNav = ''): void {
    $nav = [
        'news'     => ['href' => 'news.php',      'label' => '<i class="fas fa-newspaper"></i> '     . htmlspecialchars(__a('ADMIN_NAV_NEWS'))],
        'faq'      => ['href' => 'faq.php',       'label' => '<i class="fas fa-question-circle"></i> ' . htmlspecialchars(__a('ADMIN_NAV_FAQ'))],
        'rules'    => ['href' => 'rules-edit.php', 'label' => '<i class="fas fa-book"></i> '          . htmlspecialchars(__a('ADMIN_NAV_RULES'))],
        'assigner' => ['href' => 'assigner.php',  'label' => '<i class="fas fa-gamepad"></i> '       . htmlspecialchars(__a('ADMIN_NAV_ASSIGNER'))],
        'config'   => ['href' => 'config.php',    'label' => '<i class="fas fa-cog"></i> '           . htmlspecialchars(__a('ADMIN_NAV_CONFIG'))],
        'themes'   => ['href' => 'themes.php',   'label' => '<i class="fas fa-palette"></i> Themes'],
    ];
    $version = defined('__TSWEBSITE_VERSION') ? __TSWEBSITE_VERSION : '';
    $lang = I18n::getLocale();
    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Admin</title>
    <link rel="stylesheet" href="../lib/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../lib/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css?v=<?= filemtime(__DIR__ . '/css/admin.css') ?>">
</head>
<body class="admin-body">

<nav class="navbar navbar-expand-md navbar-dark bg-dark admin-navbar">
    <a class="navbar-brand" href="index.php">
        <i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__a('ADMIN_NAV_BRAND')) ?>
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
            <?php
                $adminLocales = \Wruczek\TSWebsite\I18n::availableLocales('admin');
                $adminCurrentLocale = \Wruczek\TSWebsite\I18n::getLocale();
                $adminCurrentPage = basename($_SERVER['PHP_SELF']);
                if (count($adminLocales) > 1): ?>
            <li class="nav-item dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle admin-lang-btn"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-language"></i>
                    <?= htmlspecialchars(ADMIN_LANG_NAMES[$adminCurrentLocale] ?? strtoupper($adminCurrentLocale)) ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php foreach ($adminLocales as $locale): ?>
                    <a class="dropdown-item<?= $locale === $adminCurrentLocale ? ' active' : '' ?>"
                       href="<?= htmlspecialchars($adminCurrentPage) ?>?lang=<?= urlencode($locale) ?>">
                        <?= htmlspecialchars(ADMIN_LANG_NAMES[$locale] ?? strtoupper($locale)) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="../" target="_blank">
                    <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars(__a('ADMIN_NAV_GOTO_WEBSITE')) ?>
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

<script src="../lib/jquery/3.6.0/jquery.min.js"></script>
<script src="../lib/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

function flashMessage(string $type, string $msg): void {
    echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($msg)
        . '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
}

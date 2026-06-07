<?php
require_once __DIR__ . "/../private/php/constants.php";
require_once __PRIVATE_DIR . "/php/I18n.php";

use Wruczek\TSWebsite\I18n;

// Allow switching language via ?lang=xx
if (!empty($_GET['lang'])) {
    $requestedLang = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['lang']);
    if ($requestedLang !== '') {
        setcookie('tswebsite_language', $requestedLang, time() + 60 * 60 * 24 * 90, '/');
        $_COOKIE['tswebsite_language'] = $requestedLang;
    }
}

I18n::detectLocale();

const INSTALLER_LANG_NAMES = [
    'en'    => 'English',
    'en-us' => 'English (US)',
    'de'    => 'Deutsch',
    'pl'    => 'Polski',
    'ru'    => 'Русский',
    'fr'    => 'Français',
    'es'    => 'Español',
    'it'    => 'Italiano',
    'nl'    => 'Nederlands',
    'cs'    => 'Čeština',
    'hu'    => 'Magyar',
    'pt-br' => 'Português (Brasil)',
    'pt-pt' => 'Português (Portugal)',
    'tr'    => 'Türkçe',
    'sv'    => 'svenska',
    'nb'    => 'norsk (bokmål)',
    'da'    => 'Dansk',
    'uk'    => 'Українська',
    'be'    => 'Беларуская',
    'bs'    => 'Босански',
    'el'    => 'Ελληνικά',
    'bg'    => 'български',
    'zh-cn' => '简体中文',
    'ar'    => 'العربية',
];

function __t(string $msgid, array $args = []): string {
    return I18n::t($msgid, 'installer', $args);
}

function _e(string $msgid, array $args = []): void {
    echo I18n::t($msgid, 'installer', $args);
}

if(file_exists(__INSTALLER_LOCK_FILE) && filesize(__INSTALLER_LOCK_FILE) > 1) {
    die('Installer is locked. Please remove the file "private/INSTALLER_LOCK" to run the installer again.');
}

if (!file_exists(__PRIVATE_DIR . "/vendor/autoload.php")) {
    die(
        '<h2>Composer Autoload not found.</h2>' .
        '<p>Please run <code>composer install</code> in the directory <code>' . realpath(__BASE_DIR) . '</code>.</p>'
    );
}

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
set_time_limit(0);

$stepNumber = empty($_GET["step"]) || !file_exists(__DIR__ . "/pages/" . (int)$_GET["step"] . ".php") ? 1 : (int) $_GET["step"];

$steps = [
    1 => __t('INSTALLER_STEP_WELCOME'),
    2 => __t('INSTALLER_STEP_REQUIREMENTS'),
    3 => __t('INSTALLER_STEP_DATABASE'),
    4 => __t('INSTALLER_STEP_TEAMSPEAK'),
    5 => __t('INSTALLER_STEP_SECURITY'),
    6 => __t('INSTALLER_STEP_CONFIGURATION'),
    7 => __t('INSTALLER_STEP_DONE'),
];

$availableLocales = I18n::availableLocales('installer');
$currentLocale    = I18n::getLocale();

ob_start();
require __DIR__ . "/pages/$stepNumber.php";
$pageContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Step <?= $stepNumber ?> — TS-Website Installer</title>
    <link rel="stylesheet" href="../lib/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../lib/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="../lib/jquery/3.6.0/jquery.min.js"></script>
    <script src="../lib/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar installer-navbar">
    <span class="navbar-brand">
        <i class="fas fa-shield-alt"></i> <?= htmlspecialchars(__t('INSTALLER_NAV_BRAND')) ?>
    </span>

    <?php if (count($availableLocales) > 1): ?>
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-language"></i>
            <?= htmlspecialchars(INSTALLER_LANG_NAMES[$currentLocale] ?? strtoupper($currentLocale)) ?>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <?php foreach ($availableLocales as $locale): ?>
            <a class="dropdown-item<?= $locale === $currentLocale ? ' active' : '' ?>"
               href="?step=<?= $stepNumber ?>&amp;lang=<?= urlencode($locale) ?>">
                <?= htmlspecialchars(INSTALLER_LANG_NAMES[$locale] ?? strtoupper($locale)) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <span class="text-muted small">Version <?= defined('__TSWEBSITE_VERSION') ? __TSWEBSITE_VERSION : '' ?></span>
</nav>

<div class="installer-steps">
    <?php foreach ($steps as $num => $label):
        $cls = $num === $stepNumber ? 'active' : ($num < $stepNumber ? 'done' : '');
    ?>
    <?php if ($num > 1): ?><div class="installer-step-sep"></div><?php endif; ?>
    <div class="installer-step <?= $cls ?>">
        <div class="step-badge">
            <?php if ($num < $stepNumber): ?>
                <i class="fas fa-check" style="font-size:.65rem"></i>
            <?php else: ?>
                <?= $num ?>
            <?php endif; ?>
        </div>
        <?= htmlspecialchars($label) ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="installer-container">
    <?= $pageContent ?>
</div>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip({"html": true, "placement": "right"})
    })
</script>
</body>
</html>
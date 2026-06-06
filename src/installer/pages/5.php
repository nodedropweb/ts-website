<?php
if (!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

$htaccessFile   = __PRIVATE_DIR . '/.htaccess';
$htaccessNeeded = "deny from all\n";

$actions = [];
$errors  = [];

// 1. .htaccess prüfen / anlegen
if (!file_exists($htaccessFile)) {
    if (@file_put_contents($htaccessFile, $htaccessNeeded) !== false) {
        $actions[] = '.htaccess created in <code>private/</code>';
    } else {
        $errors[] = 'Could not create .htaccess — please create it manually with content: <code>deny from all</code>';
    }
} else {
    $content = file_get_contents($htaccessFile);
    if (stripos($content, 'deny from all') === false && stripos($content, 'Require all denied') === false) {
        file_put_contents($htaccessFile, $htaccessNeeded . "\n" . $content);
        $actions[] = 'Missing deny rule added to existing .htaccess';
    }
}

// 2. HTTP-Test
$testUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
         . rtrim(dirname(dirname($_SERVER['REQUEST_URI'] ?? '')), '/') . '/private/';

$blocked    = false;
$httpStatus = null;
$ctx        = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
@file_get_contents($testUrl, false, $ctx);
if (!empty($http_response_header[0])) {
    preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
    $httpStatus = (int)($m[1] ?? 0);
    $blocked    = in_array($httpStatus, [403, 401, 404]);
}
?>

<div class="card">
    <div class="card-header"><i class="fas fa-lock"></i> <?= htmlspecialchars(__t('INSTALLER_SEC_TITLE')) ?></div>
    <div class="card-body">

        <?php if (!empty($actions)): ?>
        <div class="alert alert-success">
            <?= __t('INSTALLER_SEC_AUTOCONFIGURED') ?>
            <ul class="mb-0 mt-1">
                <?php foreach ($actions as $a): ?><li><?= $a ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?= __t('INSTALLER_SEC_ACTION_REQUIRED') ?>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="card <?= $blocked ? 'border-success' : ($httpStatus ? 'border-danger' : 'border-warning') ?>">
                    <div class="card-body py-3 d-flex align-items-center">
                        <i class="fas <?= $blocked ? 'fa-shield-alt text-success' : ($httpStatus ? 'fa-exclamation-triangle text-danger' : 'fa-question-circle text-warning') ?> fa-2x mr-3"></i>
                        <div>
                            <strong>
                                <?php if ($blocked): ?><?= htmlspecialchars(__t('INSTALLER_SEC_DIR_PROTECTED')) ?>
                                <?php elseif ($httpStatus): ?><?= htmlspecialchars(__t('INSTALLER_SEC_DIR_NOT_PROTECTED')) ?>
                                <?php else: ?><?= htmlspecialchars(__t('INSTALLER_SEC_DIR_TEST_FAILED')) ?>
                                <?php endif; ?>
                            </strong><br>
                            <small class="text-muted">
                                <?= $httpStatus ? "HTTP $httpStatus at " : "No result for " ?>
                                <code><?= htmlspecialchars($testUrl) ?></code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <div class="card">
                    <div class="card-body py-3 d-flex align-items-center">
                        <i class="fas fa-file-code text-<?= file_exists($htaccessFile) ? 'success' : 'warning' ?> fa-2x mr-3"></i>
                        <div>
                            <strong><?= htmlspecialchars(file_exists($htaccessFile) ? __t('INSTALLER_SEC_HTACCESS_PRESENT') : __t('INSTALLER_SEC_HTACCESS_MISSING')) ?></strong><br>
                            <small class="text-muted"><code><?= htmlspecialchars(basename(__PRIVATE_DIR)) ?>/.htaccess</code></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$httpStatus): ?>
        <div class="alert alert-warning">
            <?= __t('INSTALLER_SEC_WARN_NO_HTTP', [htmlspecialchars($testUrl)]) ?>
        </div>
        <?php elseif (!$blocked): ?>
        <div class="alert alert-danger">
            <?= __t('INSTALLER_SEC_ERR_EXPOSED') ?><br>
            <a href="https://github.com/Wruczek/ts-website/wiki/%5BEN%5D-Securing-private-directory" target="_blank" class="btn btn-sm btn-outline-danger mt-2">
                <i class="fas fa-book"></i> <?= htmlspecialchars(__t('INSTALLER_SEC_WIKI_GUIDE')) ?>
            </a>
        </div>
        <?php endif; ?>

        <p class="text-muted small mb-0">
            <i class="fas fa-info-circle"></i>
            <?= __t('INSTALLER_SEC_INFO') ?>
        </p>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="?step=<?= $stepNumber - 1 ?>" class="btn btn-secondary">
            <i class="fas fa-chevron-left"></i> <?= htmlspecialchars(__t('INSTALLER_BTN_BACK')) ?>
        </a>
        <a href="?step=<?= $stepNumber + 1 ?>" class="btn btn-primary">
            <?= htmlspecialchars(__t('INSTALLER_BTN_NEXT')) ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

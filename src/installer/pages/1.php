<?php if (!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed"); ?>

<?php if(file_exists(__CONFIG_FILE)) { ?>
    <div class="alert alert-danger text-center" role="alert">
        <?= __t('INSTALLER_WELCOME_ALREADY_INSTALLED') ?>
    </div>
<?php } ?>

<div class="modal" tabindex="-1" role="dialog" id="dev-release-notice">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Welcome to ts-website 3.0!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>This is the <strong>nodedropweb fork</strong> of ts-website &mdash; extended with a full admin panel and several improvements over the <a href="https://github.com/Wruczek/ts-website" target="_blank" rel="noopener">original</a>.</p>
                <p><strong>New in this fork:</strong></p>
                <ul>
                    <li class="mb-2"><strong>Admin Panel</strong> &mdash; manage News, FAQ, Rules, Imprint, Group Assigner and site configuration through the browser</li>
                    <li class="mb-2"><strong>tsw.phar CLI tool</strong> &mdash; install, update and maintain the site from the command line (English &amp; German)</li>
                    <li class="mb-2"><strong>PHP 8.4 compatible</strong> &mdash; fully modernized codebase</li>
                    <li class="mb-2"><strong>Local asset hosting</strong> &mdash; all CSS &amp; JS libraries served locally, no external CDN (GDPR compliant)</li>
                </ul>
                <p class="text-muted small mb-0">You can select your language using the dropdown in the navigation bar after closing this dialog.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Let's go!</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" role="dialog" id="metrics-info">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars(__t('INSTALLER_METRICS_MODAL_TITLE')) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars(__t('ARIA_CLOSE')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?= __t('INSTALLER_METRICS_MODAL_P1') ?></p>
                <p><?= __t('INSTALLER_METRICS_MODAL_P2') ?></p>
                <p><?= __t('INSTALLER_METRICS_MODAL_P3') ?></p>
                <p><?= __t('INSTALLER_METRICS_MODAL_DATA_TITLE') ?></p>
                <ul>
                    <li class="mb-1"><?= __t('INSTALLER_METRICS_MODAL_DATA_VERSION') ?></li>
                    <li class="mb-1"><?= __t('INSTALLER_METRICS_MODAL_DATA_EXTENSIONS') ?></li>
                    <li class="mb-1"><?= __t('INSTALLER_METRICS_MODAL_DATA_SERVER') ?></li>
                    <li class="mb-1"><?= __t('INSTALLER_METRICS_MODAL_DATA_OS') ?></li>
                    <li class="mb-1"><?= __t('INSTALLER_METRICS_MODAL_DATA_TS') ?></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= htmlspecialchars(__t('INSTALLER_METRICS_MODAL_BTN_CLOSE')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title text-center mb-0"><?= htmlspecialchars(__t('INSTALLER_WELCOME_TITLE')) ?></h4>

        <p class="card-text text-center text-muted font-italic mb-5">
            Version <?= __TSWEBSITE_VERSION ?> (<?= __TSWEBSITE_COMMIT ?>)
        </p>

        <p class="card-text"><?= __t('INSTALLER_WELCOME_DESCRIPTION') ?></p>
        <p class="card-text text-danger" id="hidejs"><?= __t('INSTALLER_WELCOME_NO_JS') ?></p>
        <p class="card-text"><?= __t('INSTALLER_WELCOME_WIKI_HINT') ?></p>
        <p class="card-text"><?= __t('INSTALLER_WELCOME_READY') ?></p>

        <form method="post" action="?step=<?= $stepNumber + 1 ?>">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="allow-metrics-checkbox" name="allow-metrics-checkbox" checked>
                <label class="custom-control-label" for="allow-metrics-checkbox">
                    <?= __t('INSTALLER_WELCOME_METRICS_CHECKBOX') ?>
                    <a href="#" data-toggle="modal" data-target="#metrics-info"><?= htmlspecialchars(__t('INSTALLER_WELCOME_METRICS_LEARN_MORE')) ?></a>
                </label>
            </div>

            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="accept-license-checkbox" name="accept-license-checkbox" required>
                <label class="custom-control-label" for="accept-license-checkbox">
                    <?= __t('INSTALLER_WELCOME_LICENSE_CHECKBOX') ?>
                </label>
            </div>

            <button id="submitform" type="submit" style="display: none"></button>
        </form>
    </div>
    <div class="card-footer">
        <a id="nextbutton" href="#" class="btn btn-primary float-right disabled" style="display: none">
            <?= htmlspecialchars(__t('INSTALLER_BTN_START')) ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

<script>
    $("#dev-release-notice").modal("show")

    $("#hidejs").css("display", "none");
    $("#nextbutton").css("display", "inline-block");

    $("#nextbutton").click(function () {
        $("#submitform").click();
    });

    $("#accept-license-checkbox").change(function () {
        if (this.checked) {
            $("#nextbutton").removeClass("disabled");
        } else {
            $("#nextbutton").addClass("disabled");
        }
    });
</script>

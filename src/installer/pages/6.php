<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use Wruczek\TSWebsite\Config;

require_once __PRIVATE_DIR . "/../private/vendor/autoload.php";

if (!empty($_POST)) {
    $baseUrl = @$_POST["base-url"];
    $websiteName = @$_POST["website-name"];
    $timezone = @$_POST["timezone"];
    $usingCloudflare = isset($_POST["using-cloudflare"]);

    if (!in_array($timezone, timezone_identifiers_list())) {
        $errormessage = "Invalid timezone";
    } else {
        try {
            Config::i()->setValue("baseurl", $baseUrl);
            Config::i()->setValue("website_title", $websiteName);
            Config::i()->setValue("nav_brand", $websiteName);
            Config::i()->setValue("timezone", $timezone);
            Config::i()->setValue("usingcloudflare", $usingCloudflare);

            header("Location: ?step=" . ($stepNumber + 1));
        } catch (\Exception $e) {
            $errormessage = "Error saving config: " . htmlspecialchars($e->getMessage());
        }
    }
}

$defaultTimezone = date_default_timezone_get();

$defaultBase = (@$_SERVER["HTTPS"] === "on" ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$defaultBase = dirname(dirname($defaultBase)); // get the path for the previous directory, not the installer

$displayip = Config::get("query_displayip"); // set initial website name to the TS3 IP
?>

<?php if(!empty($errormessage)) { ?>
<div class="text-center">
    <div class="alert alert-danger" style="display: inline-block">
        <?= $errormessage ?>
    </div>
</div>
<?php } ?>

<div class="card">

    <div class="card-body">
        <h4 class="card-title text-center"><?= htmlspecialchars(__t('INSTALLER_CONFIG_TITLE')) ?></h4>

        <div class="row justify-content-md-center">
            <form id="configureform" class="col-md-5" method="post" action="<?= "?step=$stepNumber" ?>">

                <div class="alert alert-info mb-4">
                    <?= __t('INSTALLER_CONFIG_ALMOST_DONE') ?>
                </div>

                <div class="form-group mb-4">
                    <label for="base-url"><?= htmlspecialchars(__t('INSTALLER_CONFIG_BASE_URL_LABEL')) ?></label>
                    <input class="form-control"
                           id="base-url"
                           name="base-url"
                           placeholder="<?= htmlspecialchars(__t('INSTALLER_CONFIG_BASE_URL_LABEL')) ?>"
                           value="<?= htmlspecialchars($defaultBase) ?>"
                           required autofocus autocomplete="off">
                </div>

                <div class="form-group mb-4">
                    <label for="website-name"><?= htmlspecialchars(__t('INSTALLER_CONFIG_SITE_NAME_LABEL')) ?></label>
                    <input class="form-control"
                           id="website-name"
                           name="website-name"
                           placeholder="<?= htmlspecialchars(__t('INSTALLER_CONFIG_SITE_NAME_LABEL')) ?>"
                           value="<?= htmlspecialchars($displayip) ?>"
                           required autocomplete="off">
                </div>

                <div class="form-group mb-4">
                    <label for="timezone"><?= htmlspecialchars(__t('INSTALLER_CONFIG_TIMEZONE_LABEL')) ?></label>
                    <select class="form-control" name="timezone" id="timezone" required>
                        <option <?= empty($defaultTimezone) ? "selected" : "" ?> disabled value="">
                            <?= htmlspecialchars(__t('INSTALLER_CONFIG_TIMEZONE_PLACEHOLDER')) ?>
                        </option>

                        <?php foreach (timezone_identifiers_list() as $timezone) {
                            $selected = $timezone === $defaultTimezone;
                            $time = (new DateTime("now", new DateTimeZone($timezone)))->format("H:i (P)");
                            ?>
                            <option <?= $selected ? "selected" : "" ?> value="<?= $timezone ?>">
                                <?= "$timezone - $time" ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group mb-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="using-cloudflare" name="using-cloudflare"
                            <?= isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? "checked" : "" ?>>
                        <label class="custom-control-label" for="using-cloudflare">
                            <?= htmlspecialchars(__t('INSTALLER_CONFIG_CLOUDFLARE_LABEL')) ?>
                        </label>
                    </div>
                    <p><small><?= htmlspecialchars(__t('INSTALLER_CONFIG_CLOUDFLARE_HINT')) ?></small></p>
                </div>

                <button id="submitform" type="submit" style="display: none"></button>
            </form>
        </div>
    </div>

    <div class="card-footer text-right">
        <a href="#" id="submitformalt" class="btn btn-primary float-right">
            <?= htmlspecialchars(__t('INSTALLER_BTN_SUBMIT')) ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

<script>
    $("#submitformalt").click(function () {
        $("#submitform").click();
    });
</script>

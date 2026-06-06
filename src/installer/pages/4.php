<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Convert as TeamSpeak3_Helper_Convert;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\ServerIconCache;
use Wruczek\TSWebsite\Utils\ApiUtils;

if (!empty($_POST)) {
    $queryhostname = trim($_POST["queryhostname"]);
    $queryport = trim($_POST["queryport"]);
    $queryserverport = trim($_POST["queryserverport"]);
    $queryusername = trim($_POST["queryusername"]);
    $querypassword = trim($_POST["querypassword"]);
    $querydisplayip = trim($_POST["querydisplayip"]);

    if (!empty($queryhostname) && !empty($queryport)
        && !empty($queryserverport) && !empty($queryusername)
        && !empty($querypassword) && !empty($querydisplayip)
    ) {
        require_once __PRIVATE_DIR . "/vendor/autoload.php";

        try {
            $tsNodeHost = TeamSpeak3::factory("serverquery://$queryhostname:$queryport/");
            $tsNodeHost->login($queryusername, $querypassword);
            $tsServer = $tsNodeHost->serverGetByPort($queryserverport);

            if(is_array($tsServer->getInfo())) {
                $tsVersion = $tsServer->getInfo()["virtualserver_version"];
                $tsBuildNo = $tsVersion->section("[", 1)->filterDigits()->toInt();

                if ($tsBuildNo < 1564054246) {
                    $errormessage =
                        'Your TeamSpeak server version is not supported.<br>' .
                        'Current version: ' . TeamSpeak3_Helper_Convert::versionShort($tsVersion) . ' (build ' . $tsBuildNo . ')' . '<br>' .
                        'Supported versions: 3.10.0 (build 1564054246) and newer';
                } else {
                    $configdata = [
                        "query_hostname" => $queryhostname,
                        "query_port" => $queryport,
                        "tsserver_port" => $queryserverport,
                        "query_username" => $queryusername,
                        "query_password" => $querypassword,
                        "query_displayip" => $querydisplayip,
                    ];

                    foreach ($configdata as $key => $value) {
                        try {
                            Config::i()->setValue($key, $value);
                        } catch (\Exception $e) {
                            die("Error while updating config in database, at " . htmlspecialchars($key) . " => " . htmlspecialchars($value));
                        }
                    }

                    $cacheIcons = true;
                }
            } else {
                $errormessage = 'Cannot retrieve server information';
            }
        } catch (Exception $e) {
            $errormessage = htmlspecialchars("Error " . $e->getCode() . ": " . $e->getMessage());

            if($e->getCode() === 520) {
                $errormessage .= '<br>You have entered wrong username and/or password. Please check it and try again.';
            }

            if($e->getCode() === 2568) {
                $errormessage .= '<br>Query account does not have permissions. ' . 'Click <a href="#" data-toggle="modal" ' .
                    'data-target="#queryperms">here</a> to view required permissions list.';
            }
        }

        // Explicitly destroy TS3 connection objects so the TCP socket
        // is closed here rather than during PHP shutdown — prevents OOM
        // in StringHelper when the destructor tries to drain the socket buffer.
        unset($tsServer, $tsNodeHost);
        @ini_set('display_errors', 0);
        error_reporting(0);
    }
}

if (isset($_GET["syncicons"])) {
    require_once __PRIVATE_DIR . "/vendor/autoload.php";

    set_time_limit(0); // this might take a while

    try {
        ServerIconCache::syncIcons();
        $response = json_encode(["success" => true]);
    } catch (\Exception $e) {
        $response = json_encode(["success" => false, "error" => $e->getMessage()]);
    }

    // Reset TS3 connection before output so the TCP destructor
    // does not crash with OOM during PHP shutdown
    try {
        \Wruczek\TSWebsite\Utils\TeamSpeakUtils::i()->reset();
    } catch (\Throwable $ignored) {}

    // Suppress any further errors (e.g. OOM in TS3 framework shutdown)
    @ini_set('display_errors', 0);
    error_reporting(0);

    // The installer wraps pages in ob_start() — discard the buffer and send JSON directly
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    echo $response;
    exit;
}
?>

<!-- Modal -->
<div class="modal fade" id="queryperms" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars(__t('INSTALLER_TS_QUERYPERMS_TITLE')) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars(__t('ARIA_CLOSE')) ?>"  >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <?php
                $perms = [
                    'b_serverinstance_info_view'              => 'Establish connection to the server',
                    'b_virtualserver_select'                  => 'Select virtual server',
                    'b_virtualserver_info_view'               => 'Read server info (name, slots, …) — Channel Viewer & Status Widget',
                    'b_virtualserver_channel_list'            => 'Fetch channel list — Channel Viewer',
                    'b_virtualserver_client_list'             => 'Fetch online clients — Channel Viewer & Login system',
                    'b_virtualserver_servergroup_list'        => 'List server groups — Admin Status Widget & Group Assigner',
                    'b_virtualserver_channelgroup_list'       => 'List channel groups — Channel Viewer',
                    'b_virtualserver_servergroup_client_list' => 'Fetch server group members — Admin Status Widget',
                    'b_client_info_view'                      => 'Read client details — Channel Viewer popover',
                    'b_virtualserver_ban_list'                => 'Fetch ban list — Admin Panel',
                ];
                ?>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="pl-3" style="width:50%">Permission</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($perms as $perm => $desc): ?>
                        <tr>
                            <td class="pl-3"><code><?= htmlspecialchars($perm) ?></code></td>
                            <td class="text-muted small"><?= htmlspecialchars($desc) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($errormessage)) { ?>
<div class="text-center">
    <div class="alert alert-danger" style="display: inline-block">
        <?= $errormessage ?>
    </div>
</div>
<?php } ?>

<?php if(isset($cacheIcons)) { ?>

    <div class="text-center">
        <div class="alert alert-primary" style="display: inline-block">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm mr-2" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <?= htmlspecialchars(__t('INSTALLER_TS_CACHING_ICONS')) ?>
            </div>
        </div>
    </div>

<?php } else { ?>
    <div class="card">

        <div class="card-body">
            <h4 class="card-title text-center"><?= htmlspecialchars(__t('INSTALLER_TS_TITLE')) ?></h4>

            <div class="row justify-content-md-center">
                <form id="tsform" class="col-md-4" method="post" action="<?= "?step=$stepNumber" ?>">

                    <div class="alert alert-info">
                        <?= __t('INSTALLER_TS_ALLOWLIST_HINT') ?>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-link fa-fw"></i></span>
                        </div>
                        <input class="form-control" name="queryhostname" placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_HOSTNAME')) ?>" required autofocus autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" data-toggle="tooltip" title="<?= htmlspecialchars(__t('INSTALLER_TS_HOSTNAME_TIP')) ?>">
                                <i class="fa fa-question-circle fa-fw"></i>
                            </span>
                        </div>
                    </div>

                    <p class="text-muted text-center" style="font-size: 100%">
                        <?= __t('INSTALLER_TS_LOCALHOST_HINT') ?>
                    </p>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-signal fa-fw"></i></span>
                        </div>
                        <input type="number" class="form-control" name="queryport"
                               placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_QUERYPORT')) ?>" value="10011" required autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" data-toggle="tooltip" title="<?= htmlspecialchars(__t('INSTALLER_TS_QUERYPORT_TIP')) ?>">
                                <i class="fa fa-question-circle fa-fw"></i>
                            </span>
                        </div>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-signal fa-fw"></i></span>
                        </div>
                        <input type="number" class="form-control" name="queryserverport"
                               placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_VOICEPORT')) ?>" value="9987" required autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" data-toggle="tooltip" title="<?= htmlspecialchars(__t('INSTALLER_TS_VOICEPORT_TIP')) ?>">
                                <i class="fa fa-question-circle fa-fw"></i>
                            </span>
                        </div>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-user fa-fw"></i></span>
                        </div>
                        <input class="form-control" name="queryusername" placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_QUERYUSERNAME')) ?>" required autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" data-toggle="tooltip" title="<?= htmlspecialchars(__t('INSTALLER_TS_QUERYUSERNAME_TIP')) ?>">
                                <i class="fa fa-exclamation-triangle color-danger fa-fw"></i>
                            </span>
                        </div>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-lock fa-fw"></i></span>
                        </div>
                        <input type="password" class="form-control" name="querypassword" placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_QUERYPASSWORD')) ?>" required autocomplete="off">
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-font fa-fw"></i></span>
                        </div>
                        <input class="form-control" name="querydisplayip" placeholder="<?= htmlspecialchars(__t('INSTALLER_TS_DISPLAYIP')) ?>" required autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" data-toggle="tooltip"
                                  title="<?= htmlspecialchars(__t('INSTALLER_TS_DISPLAYIP_TIP')) ?>">
                                <i class="fa fa-question-circle fa-fw"></i>
                            </span>
                        </div>
                    </div>

                    <a href="#" data-toggle="modal" data-target="#queryperms" class="text-center">
                        <p><?= htmlspecialchars(__t('INSTALLER_TS_QUERYPERMS_LINK')) ?></p>
                    </a>

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

<?php } ?>

<script>
    $("#submitformalt").click(function () {
        $("#submitform").click();
    });
</script>

<?php if(isset($cacheIcons)) { ?>
    <script>
        $.ajax({
            data: { syncicons: 1 },
            success: function (res) {
                if (!res.success) {
                    alert("An error occurred while trying to sync TS3 icons: " + JSON.stringify(res))
                }

                location = "?step=5"
            },
            error: function () {
                alert("An error occurred while trying to sync TS3 icons")
                location = "?step=5"
            }
        })
    </script>
<?php } ?>

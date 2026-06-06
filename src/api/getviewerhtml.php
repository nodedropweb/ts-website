<?php

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\ViewerRenderer;

require_once __DIR__ . "/../private/php/load.php";

$html = null;
$viewerRenderer = new ViewerRenderer("img/ts-icons", Config::get("viewer_hidden_channel_ids"));

if ($viewerRenderer->checkRequiredData()) {
    $html = $viewerRenderer->renderViewer();
}

echo $html;

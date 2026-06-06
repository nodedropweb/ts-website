<?php

use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

$data = [
    "pagetitle"    => "Impressum",
    "paneltitle"   => '<i class="far fa-id-card"></i>Impressum',
    "panelcontent" => \Wruczek\TSWebsite\Config::get("imprint_content", ""),
];

TemplateUtils::i()->renderTemplate("simple-page", $data);

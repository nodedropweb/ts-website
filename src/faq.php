<?php

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\DatabaseUtils;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

$db = DatabaseUtils::i()->getDb();
$qa = $db->select("faq", "*");

$contactUrl = Config::get('faq_contact_url', '#');
$additionalText = sprintf(
    '<div class="alert alert-info"><i class="fas fa-info-circle"></i>%s <a href="%s">%s</a></div>',
    __get('FAQ_CONTACT_TEXT'),
    htmlspecialchars($contactUrl),
    __get('FAQ_CONTACT_LINK')
);

$data = [
    "additionaltext" => $additionalText,
    "qa" => $qa
];

TemplateUtils::i()->renderTemplate("faq", $data);

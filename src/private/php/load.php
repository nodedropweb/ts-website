<?php

use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\ServerIconCache;
use Wruczek\TSWebsite\Utils\CsrfUtils;
use Wruczek\TSWebsite\Utils\Language\LanguageUtils;
use Wruczek\TSWebsite\Utils\Utils;

session_name("tswebsite_sessionid");

// make session last 90 days
ini_set("session.gc_maxlifetime", 60 * 60 * 24 * 90);
ini_set("session.cookie_lifetime", 60 * 60 * 24 * 90);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define("__RENDER_START", microtime(true));

require_once __DIR__ . "/constants.php";

@header("TSW_DevMode: " . (__DEV_MODE ? "enabled" : "disabled"));

if(__DEV_MODE) {
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL);
}

if(!file_exists(__INSTALLER_LOCK_FILE)) {
    if(file_exists(__BASE_DIR . "/installer")) {
        header("Location: installer/index.php");
    } else {
        echo '&#129300; Something is not right! Looks like the website is not installed ("private/INSTALLER_LOCK" not found or is empty), but ' .
            'installation wizard folder "installer" cannot be found! Please start the installation again and follow installation guide step-by-step.';
    }

    exit;
}

require_once __PRIVATE_DIR . "/vendor/autoload.php";

// Check CSRF token if needed and validate it
if (!defined("DISABLE_CSRF_CHECK") &&
    in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "DELETE", "PATCH"])
) {
    CsrfUtils::validateRequest();
}

// Try to guess user language and store it
// If the current language is not defined, or is invalid then return to default
{
    $lang = LanguageUtils::i()->detectUserLanguage();

    if(!$lang) {
        $lang = LanguageUtils::i()->getDefaultLanguage();
    }

    define("USER_LANGUAGE_ID", $lang->getLanguageId());
}

// Shortcut to language functions
{
    /**
     * Shortcut to translate and output the result
     */
    function __(string $identifier, $args = [], bool $nullOnError = false) {
        echo __get($identifier, $args, $nullOnError);
    }

    /**
     * Shortcut to translate and return the result
     */
    function __get(string $identifier, $args = [], bool $nullOnError = false) {
        try {
            return LanguageUtils::i()->translate($identifier, $args);
        } catch (\Exception $e) {
            if ($nullOnError) {
                return null;
            } else {
                return "(unknown translation for " . Utils::escape($identifier) . ")";
            }
        }
    }
}

// Set timezone
date_default_timezone_set(Config::get("timezone"));

// Init TS3 library
// This makes it possible to cache TS3 library objects
TeamSpeak3::init();

// Close the raw TS3 TCP socket before PHP shutdown to prevent OOM crashes
// in StringHelper when the destructor tries to drain the socket buffer.
// We bypass the TS3 "quit" handshake entirely by closing the stream directly.
register_shutdown_function(function () {
    error_reporting(0);
    try {
        // getExistingTSNodeHost() returns null if no connection was made this request
        // so we never accidentally open a new connection during shutdown
        $tsHost = \Wruczek\TSWebsite\Utils\TeamSpeakUtils::i()->getExistingTSNodeHost();
        if ($tsHost !== null) {
            // Close the raw TCP stream directly — bypasses quit/readLine, no OOM
            $stream = $tsHost->getAdapter()->getTransport()->getStream();
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    } catch (\Throwable $ignored) {}
});

// Sync server icon cache if needed
ServerIconCache::syncIfNeeded();

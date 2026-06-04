<?php
/**
 * Admin authentication guard.
 * Include at the top of every admin page.
 *
 * Checks:
 *  1. User is logged in via TS3 (Auth::isLoggedIn())
 *  2. User's cldbid is in the admin_cldbids config value
 *
 * To add an admin, set config key "admin_cldbids" (JSON, type JSON) to
 * an array of cldbids, e.g. [1, 42, 99]
 */

require_once __DIR__ . "/../private/php/load.php";

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Config;

function isAdmin(): bool {
    if (!Auth::isLoggedIn()) {
        return false;
    }
    $allowed = Config::get('admin_cldbids', []);
    if (!is_array($allowed) || empty($allowed)) {
        return false;
    }
    return in_array(Auth::getCldbid(), $allowed, true);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        $loginUrl = '../?openlogin=1';
        include __DIR__ . '/_403.php';
        exit;
    }
}

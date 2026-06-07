#!/usr/bin/env php
<?php
/**
 * TS-Website Development Reset / Install Tool
 *
 * Usage:
 *   php reset.php                    Interaktives Menü
 *   php reset.php save               Verbindungsdaten als Profil speichern
 *   php reset.php install            Komplettinstallation aus Profil (kein Browser nötig)
 *   php reset.php reset              DB + Lock zurücksetzen, dbconfig.php bleibt
 *   php reset.php reset --clean      Alles zurücksetzen inkl. dbconfig.php
 *   php reset.php status             Aktuellen Zustand anzeigen
 *
 * Profil-Datei: /var/www/ts-website/.reset-profile.json
 */

if (php_sapi_name() !== 'cli') {
    die('Nur als CLI-Tool verwenden.');
}

// Berechtigungs-Hinweis: private/ gehört www-data
if (posix_getuid() !== 0 && file_exists(PRIVATE_DIR) && !is_writable(PRIVATE_DIR)) {
    echo "\033[33m  ⚠  Hinweis: src/private/ gehört www-data.\033[0m\n";
    echo "\033[33m     Für vollständige Schreibrechte bitte als root ausführen:\033[0m\n";
    echo "\033[97m     wsl -d drupaltv -u root -- php /var/www/ts-website/reset.php\033[0m\n\n";
}

// ── Pfade ────────────────────────────────────────────────────────────────────
define('ROOT',        __DIR__);
define('SRC',         ROOT . '/src');
define('PRIVATE_DIR', SRC  . '/private');
define('CONFIG_FILE', PRIVATE_DIR . '/dbconfig.php');
define('LOCK_FILE',   PRIVATE_DIR . '/INSTALLER_LOCK');
define('CACHE_DIR',   PRIVATE_DIR . '/cache');
define('PROFILE',     ROOT . '/.reset-profile.json');
define('SQL_MAIN',    SRC  . '/installer/dbinstall_mysql.sql');
define('SQL_LANG',    SRC  . '/installer/dbinstall_mysql_lang.sql');
define('AUTOLOAD',    PRIVATE_DIR . '/vendor/autoload.php');

// ── ANSI-Ausgabe ─────────────────────────────────────────────────────────────
function ok(string $msg):  void { echo "\033[32m  ✓  $msg\033[0m\n"; }
function err(string $msg): void { echo "\033[31m  ✗  $msg\033[0m\n"; exit(1); }
function act(string $msg): void { echo "\033[33m  →  $msg\033[0m\n"; }
function inf(string $msg): void { echo "\033[90m     $msg\033[0m\n"; }
function hdr(string $msg): void { echo "\n\033[1;36m$msg\033[0m\n"; }
function ask(string $prompt, string $default = ''): string {
    $hint = $default !== '' ? " [$default]" : '';
    echo "\033[97m     $prompt$hint:\033[0m ";
    $in = trim(fgets(STDIN));
    return $in !== '' ? $in : $default;
}

// ── Argumente ────────────────────────────────────────────────────────────────
$cmd   = $argv[1] ?? null;
$clean = in_array('--clean', $argv, true);

hdr('TS-Website CLI-Tool');

if ($cmd === null) {
    echo "  Befehle:\n";
    echo "    \033[97msave\033[0m      Verbindungsdaten als Profil speichern\n";
    echo "    \033[97minstall\033[0m   Komplettinstallation aus Profil (kein Browser nötig)\n";
    echo "    \033[97mreset\033[0m     DB + Lock zurücksetzen (dbconfig.php bleibt)\n";
    echo "    \033[97mreset --clean\033[0m  Alles zurücksetzen inkl. dbconfig.php\n";
    echo "    \033[97mstatus\033[0m    Aktuellen Zustand anzeigen\n\n";
    $cmd = ask('Befehl');
}

switch ($cmd) {
    case 'save':    cmdSave();    break;
    case 'install': cmdInstall(); break;
    case 'reset':   cmdReset();   break;
    case 'status':  cmdStatus();  break;
    default:        err("Unbekannter Befehl: $cmd");
}

// ════════════════════════════════════════════════════════════════════════════
// SAVE — Verbindungsdaten aus laufendem System in Profil schreiben
// ════════════════════════════════════════════════════════════════════════════
function cmdSave(): void {
    hdr('Profil speichern');

    // ── DB-Abschnitt ──────────────────────────────────────────────────────
    if (file_exists(CONFIG_FILE)) {
        $db = require CONFIG_FILE;
        act('dbconfig.php gelesen');
    } else {
        echo "\n  \033[33mdbconfig.php nicht gefunden — bitte Datenbankdaten eingeben:\033[0m\n";
        $db = [
            'type'     => 'mysql',
            'host'     => ask('DB-Host',      '127.0.0.1'),
            'username' => ask('DB-Benutzername'),
            'password' => ask('DB-Passwort'),
            'database' => ask('DB-Name',      'ts_website'),
            'prefix'   => ask('Tabellen-Prefix', 'tsw_'),
            'port'     => ask('DB-Port',      '3306'),
            'charset'  => 'utf8mb4',
        ];
    }

    act('Datenbankverbindung herstellen...');
    $pdo    = connectDb($db);
    $prefix = $db['prefix'] ?? 'tsw_';
    ok('Verbunden');

    // ── Query-Abschnitt ───────────────────────────────────────────────────
    $queryKeys = [
        'query_hostname', 'query_port', 'query_username', 'query_password',
        'query_displayip', 'query_nickname', 'tsserver_port',
    ];
    $query = [];
    try {
        $in   = implode("','", $queryKeys);
        $rows = $pdo->query("SELECT identifier, value FROM {$prefix}config WHERE identifier IN ('$in')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $query[$row['identifier']] = $row['value'];
        }
        act('Query-Einstellungen aus DB gelesen');
    } catch (\Exception $e) {
        inf('Konnte Query-Einstellungen nicht lesen: ' . $e->getMessage());
    }

    // Fehlende Query-Werte abfragen
    $queryDefaults = [
        'query_hostname'  => '127.0.0.1',
        'query_port'      => '10011',
        'query_username'  => '',
        'query_password'  => '',
        'query_displayip' => '',
        'query_nickname'  => 'TS-website',
        'tsserver_port'   => '9987',
    ];
    foreach ($queryDefaults as $key => $default) {
        if (empty($query[$key])) {
            $label = str_replace(['query_', '_'], ['', ' '], $key);
            $query[$key] = ask("TS $label", $default);
        }
    }

    // ── Site-Abschnitt ────────────────────────────────────────────────────
    $siteKeys = ['baseurl', 'website_title', 'nav_brand', 'timezone', 'usingcloudflare'];
    $site = [];
    try {
        $in   = implode("','", $siteKeys);
        $rows = $pdo->query("SELECT identifier, value FROM {$prefix}config WHERE identifier IN ('$in')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $site[$row['identifier']] = $row['value'];
        }
    } catch (\Exception $e) {}

    $siteDefaults = [
        'baseurl'         => 'http://localhost',
        'website_title'   => $query['query_displayip'] ?? 'TS-Website',
        'nav_brand'       => $query['query_displayip'] ?? 'TS-Website',
        'timezone'        => 'Europe/Berlin',
        'usingcloudflare' => 'false',
    ];
    foreach ($siteDefaults as $key => $default) {
        if (!isset($site[$key]) || $site[$key] === '') {
            $site[$key] = ask("Site $key", $default);
        }
    }

    // ── Speichern ─────────────────────────────────────────────────────────
    $profile = ['db' => $db, 'query' => $query, 'site' => $site];
    file_put_contents(PROFILE, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    ok('Profil gespeichert: ' . PROFILE);
    inf('DB:    ' . $db['username'] . '@' . $db['host'] . '/' . $db['database']);
    inf('Query: ' . ($query['query_username'] ?? '?') . '@' . ($query['query_hostname'] ?? '?') . ':' . ($query['query_port'] ?? '?'));
    inf('Site:  ' . ($site['baseurl'] ?? '?') . ' | ' . ($site['website_title'] ?? '?'));
}

// ════════════════════════════════════════════════════════════════════════════
// INSTALL — Komplettinstallation aus Profil ohne Browser
// ════════════════════════════════════════════════════════════════════════════
function cmdInstall(): void {
    hdr('Installation aus Profil');

    $profile = loadProfile();
    $db      = $profile['db'];
    $query   = $profile['query'] ?? [];
    $site    = $profile['site']  ?? [];
    $prefix  = $db['prefix'] ?? 'tsw_';

    // 1. dbconfig.php schreiben
    act('dbconfig.php schreiben...');
    writeDbConfig($db);
    ok('dbconfig.php erstellt');

    // 2. DB verbinden
    act('Datenbankverbindung herstellen...');
    $pdo = connectDb($db);
    ok('Verbunden');

    // 3. Tabellen löschen und neu anlegen
    dropAndRecreate($pdo, $prefix);

    // 4. Query-Einstellungen schreiben
    if (!empty($query)) {
        act('TeamSpeak-Verbindungsdaten schreiben...');
        foreach ($query as $key => $value) {
            $pdo->prepare("UPDATE {$prefix}config SET value = ? WHERE identifier = ?")
                ->execute([$value, $key]);
        }
        ok('Query-Einstellungen geschrieben');
    }

    // 5. Site-Einstellungen schreiben
    if (!empty($site)) {
        act('Website-Einstellungen schreiben...');
        foreach ($site as $key => $value) {
            $pdo->prepare("UPDATE {$prefix}config SET value = ? WHERE identifier = ?")
                ->execute([$value, $key]);
        }
        ok('Website-Einstellungen geschrieben');
    }

    // 6. Icons synchronisieren (optional, überspringen wenn TS offline)
    if (file_exists(AUTOLOAD)) {
        act('Server-Icons synchronisieren (überspringbar)...');
        try {
            require_once AUTOLOAD;
            \Wruczek\TSWebsite\ServerIconCache::syncIcons();
            ok('Icons synchronisiert');
        } catch (\Throwable $e) {
            inf('Icons übersprungen: ' . $e->getMessage());
        }
    }

    // 7. Installer-Lock setzen
    act('Installer-Lock setzen...');
    $msg = 'Installiert via CLI-Tool am ' . date('d.m.Y H:i:s') . '. Löschen um Installer erneut auszuführen.';
    file_put_contents(LOCK_FILE, $msg);
    ok('INSTALLER_LOCK gesetzt');

    // 8. Cache leeren
    clearCache();

    hdr('Installation abgeschlossen ✓');
    inf('→ http://localhost/       Website direkt nutzbar');
    inf('→ http://localhost/admin/ Admin-Panel');
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// RESET — DB + Lock zurücksetzen
// ════════════════════════════════════════════════════════════════════════════
function cmdReset(): void {
    global $clean;
    hdr('Zurücksetzen');

    $profile = loadProfile();
    $db      = $profile['db'];
    $query   = $profile['query'] ?? [];
    $prefix  = $db['prefix'] ?? 'tsw_';

    act('Datenbankverbindung herstellen...');
    $pdo = connectDb($db);
    ok('Verbunden');

    dropAndRecreate($pdo, $prefix);

    // Query-Werte wiederherstellen
    if (!empty($query)) {
        act('Verbindungsdaten in tsw_config schreiben...');
        foreach ($query as $key => $value) {
            $pdo->prepare("UPDATE {$prefix}config SET value = ? WHERE identifier = ?")
                ->execute([$value, $key]);
        }
        ok('Query-Einstellungen wiederhergestellt');
    }

    // Site-Werte wiederherstellen
    $site = $profile['site'] ?? [];
    if (!empty($site)) {
        foreach ($site as $key => $value) {
            $pdo->prepare("UPDATE {$prefix}config SET value = ? WHERE identifier = ?")
                ->execute([$value, $key]);
        }
        ok('Site-Einstellungen wiederhergestellt');
    }

    // Installer-Lock löschen
    act('Installer-Lock löschen...');
    if (file_exists(LOCK_FILE)) { unlink(LOCK_FILE); ok('INSTALLER_LOCK gelöscht'); }
    else { inf('INSTALLER_LOCK war nicht vorhanden'); }

    // dbconfig.php
    if ($clean) {
        act('dbconfig.php löschen (--clean)...');
        if (file_exists(CONFIG_FILE)) { unlink(CONFIG_FILE); ok('dbconfig.php gelöscht'); }
        else { inf('dbconfig.php war nicht vorhanden'); }
    } else {
        act('dbconfig.php aus Profil wiederherstellen...');
        writeDbConfig($db);
        ok('dbconfig.php wiederhergestellt');
    }

    clearCache();

    hdr('Reset abgeschlossen');
    if ($clean) {
        inf('→ http://localhost/installer/   Installer ab Schritt 1');
    } else {
        inf('→ http://localhost/             Website direkt nutzbar');
        inf('→ http://localhost/installer/   Installer ab Schritt 1');
    }
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// STATUS
// ════════════════════════════════════════════════════════════════════════════
function cmdStatus(): void {
    hdr('Aktueller Status');

    $check = function(string $label, bool $state, string $detail = ''): void {
        $icon  = $state ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
        $extra = $detail ? "  \033[90m($detail)\033[0m" : '';
        echo "  $icon  $label$extra\n";
    };

    $check('dbconfig.php vorhanden',   file_exists(CONFIG_FILE));
    $check('INSTALLER_LOCK vorhanden', file_exists(LOCK_FILE));

    $profileOk = file_exists(PROFILE);
    $check('Reset-Profil vorhanden', $profileOk,
           $profileOk ? 'zuletzt: ' . date('d.m.Y H:i', filemtime(PROFILE)) : '');

    if ($profileOk) {
        $p = json_decode(file_get_contents(PROFILE), true);
        $sections = array_keys($p);
        inf('Profil-Abschnitte: ' . implode(', ', $sections));
    }

    if (file_exists(CONFIG_FILE)) {
        try {
            $db     = require CONFIG_FILE;
            $pdo    = connectDb($db);
            $prefix = $db['prefix'] ?? 'tsw_';
            $tables = $pdo->query("SHOW TABLES LIKE '" . str_replace('_', '\\_', $prefix) . "%'")->fetchAll(PDO::FETCH_COLUMN);
            $check('DB-Tabellen (' . count($tables) . ')', count($tables) > 0, implode(', ', $tables));
        } catch (\Exception $e) {
            $check('DB-Verbindung', false, $e->getMessage());
        }
    } else {
        $check('DB-Tabellen prüfen', false, 'dbconfig.php fehlt');
    }

    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// Hilfsfunktionen
// ════════════════════════════════════════════════════════════════════════════
function loadProfile(): array {
    if (!file_exists(PROFILE)) {
        err('Kein Profil gefunden. Bitte zuerst: php reset.php save');
    }
    return json_decode(file_get_contents(PROFILE), true);
}

/** Gibt Verbindungsdaten für mysqli aus dem PDO-Objekt zurück */
function parseDsnFromPdo(PDO $pdo): array {
    // Wir speichern die Credentials beim Connect als statische Variable
    return $GLOBALS['_db_credentials'];
}

function connectDb(array $db): PDO {
    $GLOBALS['_db_credentials'] = [
        'host'   => $db['host'],
        'user'   => $db['username'],
        'pass'   => $db['password'],
        'dbname' => $db['database'],
        'port'   => $db['port'] ?? 3306,
    ];
    $dsn = 'mysql:host=' . $db['host'] . ';port=' . ($db['port'] ?? 3306)
         . ';dbname=' . $db['database'] . ';charset=' . ($db['charset'] ?? 'utf8mb4');
    try {
        return new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\PDOException $e) {
        err('DB-Verbindung fehlgeschlagen: ' . $e->getMessage());
    }
}

function dropAndRecreate(PDO $pdo, string $prefix): void {
    act("Tabellen mit Prefix '$prefix' löschen...");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = $pdo->query("SHOW TABLES LIKE '" . str_replace('_', '\\_', $prefix) . "%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE `$table`");
        inf("Gelöscht: $table");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    ok(count($tables) . ' Tabellen gelöscht');

    foreach ([SQL_MAIN, SQL_LANG] as $sqlFile) {
        act('SQL ausführen: ' . basename($sqlFile) . '...');
        $sql = file_get_contents($sqlFile);
        if ($sql === false) err("Kann nicht lesen: $sqlFile");
        $sql = str_replace('DBPREFIX', $prefix, $sql);
        try {
            // Multi-Statement via mysqli — identisch zum Web-Installer
            $db = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            // PDO unterstützt kein Multi-Statement exec() zuverlässig → mysqli nutzen
            $cfg = parseDsnFromPdo($pdo);
            $mysqli = new \mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['dbname'], (int)$cfg['port']);
            if ($mysqli->connect_error) err('mysqli-Verbindung fehlgeschlagen: ' . $mysqli->connect_error);
            $mysqli->set_charset('utf8mb4');
            if (!$mysqli->multi_query($sql)) err('SQL-Fehler in ' . basename($sqlFile) . ': ' . $mysqli->error);
            // Alle Ergebnisse abarbeiten (nötig damit mysqli nicht blockiert)
            do { if ($res = $mysqli->store_result()) $res->free(); } while ($mysqli->next_result());
            $mysqli->close();
        } catch (\Throwable $e) {
            err('SQL-Fehler in ' . basename($sqlFile) . ': ' . $e->getMessage());
        }
        ok(basename($sqlFile) . ' ausgeführt');
    }
}

function writeDbConfig(array $db): void {
    $lines = '';
    foreach ($db as $k => $v) {
        $lines .= sprintf("    '%s' => '%s',\n", addcslashes($k, "'"), addcslashes($v, "'"));
    }
    $lines = rtrim($lines, ",\n");
    $code  = "<?php\n/*\n * TS-website database config file\n"
           . " * Erstellt durch CLI-Tool am " . date('d-m-Y H:i:s') . "\n */\n\nreturn [\n$lines\n];\n";
    if (@file_put_contents(CONFIG_FILE, $code) === false) {
        err('Konnte dbconfig.php nicht schreiben — als root ausführen: wsl -d drupaltv -u root -- php reset.php');
    }
}

function clearCache(): void {
    act('Cache leeren...');
    $n = 0; $skipped = 0;
    foreach (array_merge(
        glob(CACHE_DIR . '/*.cache.php')     ?: [],
        glob(CACHE_DIR . '/templates/*.php') ?: []
    ) as $f) {
        if (@unlink($f)) { $n++; } else { $skipped++; }
    }
    if ($skipped > 0) {
        inf("$skipped Dateien übersprungen (keine Schreibrechte) — als root ausführen für vollständige Bereinigung");
    }
    ok("$n Cache-Dateien gelöscht");
}

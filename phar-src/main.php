<?php
/**
 * TSW — TS-Website CLI Tool
 * Executed as phar://tsw.phar/main.php
 */

define('TSW_VERSION', '3.0.0');

// ── Paths ─────────────────────────────────────────────────────────────────────
define('SQL_MAIN', 'phar://tsw.phar/sql/dbinstall_mysql.sql');
define('SQL_LANG', 'phar://tsw.phar/sql/dbinstall_mysql_lang.sql');

$GLOBALS['PROJECT_ROOT'] = dirname(Phar::running(false));

function root(): string    { return $GLOBALS['PROJECT_ROOT']; }
function priv(): string    { return root() . '/src/private'; }
function cfg(): string     { return priv() . '/dbconfig.php'; }
function lock(): string    { return priv() . '/INSTALLER_LOCK'; }
function cache(): string   { return priv() . '/cache'; }
function profile(): string { return root() . '/.reset-profile.json'; }
function autoload(): string { return priv() . '/vendor/autoload.php'; }

// ── Language ──────────────────────────────────────────────────────────────────
function detectLang(): string {
    global $argv;
    foreach ($argv as $arg) {
        if ($arg === '--lang=de' || $arg === '--lang=DE') return 'de';
        if ($arg === '--lang=en' || $arg === '--lang=EN') return 'en';
    }
    $env = getenv('TSW_LANG');
    if ($env && strtolower($env) === 'de') return 'de';
    return 'en';
}

$GLOBALS['_lang'] = detectLang();

function t(string $en, string $de): string {
    return $GLOBALS['_lang'] === 'de' ? $de : $en;
}

// ── ANSI output ───────────────────────────────────────────────────────────────
function ok(string $s): void  { echo "\033[32m  ✓  $s\033[0m\n"; }
function err(string $s): void { echo "\033[31m  ✗  $s\033[0m\n"; exit(1); }
function act(string $s): void { echo "\033[33m  →  $s\033[0m\n"; }
function inf(string $s): void { echo "\033[90m     $s\033[0m\n"; }
function hdr(string $s): void { echo "\n\033[1;36m$s\033[0m\n"; }
function ask(string $prompt, string $default = ''): string {
    $hint = $default !== '' ? " \033[90m[$default]\033[0m" : '';
    echo "\033[97m     $prompt$hint: \033[0m";
    $in = trim(fgets(STDIN));
    return $in !== '' ? $in : $default;
}
function bold(string $s): string   { return "\033[1m$s\033[0m"; }
function cyan(string $s): string   { return "\033[36m$s\033[0m"; }
function green(string $s): string  { return "\033[32m$s\033[0m"; }
function gray(string $s): string   { return "\033[90m$s\033[0m"; }
function yellow(string $s): string { return "\033[33m$s\033[0m"; }

// Delete a file that may be owned by www-data (written by Apache/installer).
// Strategy: unlink() → chmod(0666)+unlink() → shell sudo rm → hard error.
function tryDelete(string $path): void {
    if (!file_exists($path)) {
        inf(t('Not present: ' . basename($path), 'Nicht vorhanden: ' . basename($path)));
        return;
    }
    // 1. direct unlink
    if (@unlink($path)) {
        ok(t('Deleted: ' . basename($path), 'Geloescht: ' . basename($path)));
        return;
    }
    // 2. loosen permissions first, then retry
    @chmod($path, 0666);
    if (@unlink($path)) {
        ok(t('Deleted (after chmod): ' . basename($path), 'Geloescht (nach chmod): ' . basename($path)));
        return;
    }
    // 3. try sudo rm -f (works if user has passwordless sudo or is already root)
    $escaped = escapeshellarg($path);
    exec("sudo rm -f $escaped 2>/dev/null", $out, $rc);
    if ($rc === 0 && !file_exists($path)) {
        ok(t('Deleted (via sudo): ' . basename($path), 'Geloescht (via sudo): ' . basename($path)));
        return;
    }
    // 4. give up with a clear, actionable message
    $info = function_exists('posix_getpwuid') && file_exists($path)
        ? (posix_getpwuid((int) fileowner($path))['name'] ?? '?')
        : 'www-data';
    err(t(
        'Cannot delete ' . basename($path) . " (owned by $info). Run as root:  sudo php tsw.phar reset --clean",
        'Kann ' . basename($path) . " nicht loeschen (Eigentuemer: $info). Als root ausfuehren:  sudo php tsw.phar reset --clean"
    ));
}

// ── Help screen ───────────────────────────────────────────────────────────────
function showHelp(bool $isError = false): void {
    echo "\n";
    echo cyan("  ████████╗███████╗██╗    ██╗") . "\n";
    echo cyan("     ██╔══╝██╔════╝██║    ██║") . "   " . bold("TS-Website CLI Tool") . "  " . gray("v" . TSW_VERSION) . "\n";
    echo cyan("     ██║   ███████╗██║ █╗ ██║") . "   php tsw.phar " . yellow(t("[command]","[befehl]")) . " " . gray("[--clean]") . "\n";
    echo cyan("     ██║   ╚════██║██║███╗██║") . "\n";
    echo cyan("     ██║   ███████║╚███╔███╔╝") . "\n";
    echo cyan("     ╚═╝   ╚══════╝ ╚══╝╚══╝") . "\n";
    echo "\n";

    echo bold(t("Description:","Beschreibung:")) . "\n";
    echo "  " . t(
        "Development tool for TS-Website. Stores connection data as a profile\n  and enables quick reinstallation without a browser.",
        "Entwicklungs-Werkzeug fuer TS-Website. Speichert Verbindungsdaten\n  als Profil und ermoeglicht schnelle Neuinstallation ohne Browser."
    ) . "\n\n";

    echo bold(t("Usage:","Nutzung:")) . "\n";
    echo "  " . cyan("php tsw.phar") . " " . yellow(t("<command>","<befehl>")) . " " . gray("[--clean]") . "\n\n";

    echo bold(t("Commands:","Befehle:")) . "\n";
    $cmds = [
        ["save",        t("Save connection data (DB + Query + Site) as a profile",  "Verbindungsdaten (DB + Query + Site) als Profil speichern")],
        ["install",     t("Full installation from profile — no browser needed",      "Komplettinstallation aus Profil — kein Browser noetig")],
        ["reset",       t("Reset DB tables + delete installer lock",                 "DB-Tabellen zuruecksetzen + Installer-Lock loeschen")],
        ["status",      t("Show current state (lock, profile, tables)",              "Aktuellen Zustand anzeigen (Lock, Profil, Tabellen)")],
        ["clear-cache", t("Clear all cached pages and data",                         "Alle zwischengespeicherten Seiten und Daten loeschen")],
        ["help",        t("Show this help",                                          "Diese Hilfe anzeigen")],
    ];
    foreach ($cmds as [$cmd, $desc]) {
        echo "  " . str_pad(green($cmd), 28) . gray($desc) . "\n";
    }
    echo "\n";

    echo gray(t(
        "  Tip: add --lang=de to any command for German output.\n" .
        "  To set German permanently: export TSW_LANG=de  (add to ~/.bashrc)\n",
        "  Tipp: --lang=en an jeden Befehl anhaengen fuer englische Ausgabe.\n" .
        "  Dauerhaft auf Englisch umstellen: export TSW_LANG=en  (in ~/.bashrc eintragen)\n"
    )) . "\n";

    echo bold(t("Options:","Optionen:")) . "\n";
    echo "  " . str_pad(yellow("--clean"), 28)      . gray(t("On reset/install: also delete dbconfig.php",  "Bei reset/install: auch dbconfig.php loeschen")) . "\n";
    echo "  " . str_pad(yellow("--lang=en|de"), 28) . gray(t("Output language (default: en)",               "Ausgabesprache (Standard: en)")) . "\n";
    echo "  " . str_pad(yellow("--version"), 28)    . gray(t("Show version","Version anzeigen")) . "\n";
    echo "\n";

    echo bold(t("Examples:","Beispiele:")) . "\n";
    echo gray("  # " . t("Create profile from running system:","Erstmalig Profil anlegen (aus laufendem System):")) . "\n";
    echo "  php tsw.phar " . green("save") . "\n\n";
    echo gray("  # " . t("Full installation without browser:","Komplettinstallation ohne Browser:")) . "\n";
    echo "  php tsw.phar " . green("install") . "\n\n";
    echo gray("  # " . t("Reset DB + lock only (keep dbconfig.php):","Nur DB + Lock zuruecksetzen (dbconfig.php bleibt):")) . "\n";
    echo "  php tsw.phar " . green("reset") . "\n\n";
    echo gray("  # " . t("Full reset for clean installer test:","Alles zuruecksetzen fuer sauberen Installer-Test:")) . "\n";
    echo "  php tsw.phar " . green("reset") . " " . yellow("--clean") . "\n\n";
    echo gray("  # " . t("German output:","Deutsche Ausgabe:")) . "\n";
    echo "  php tsw.phar " . green("status") . " " . yellow("--lang=de") . "\n\n";

    echo bold(t("Profile file:","Profil-Datei:")) . "\n";
    echo "  " . gray(root() . "/.reset-profile.json") . "\n";
    echo "  " . t(
        "Contains: DB connection, TS Query credentials, website settings",
        "Enthaelt: DB-Verbindung, TS-Query-Zugangsdaten, Website-Einstellungen"
    ) . "\n\n";

    echo bold(t("Note:","Hinweis:")) . "\n";
    echo "  " . yellow("src/private/") . " " . t("is owned by www-data. For full write access:","gehoert www-data. Fuer vollstaendige Schreibrechte:") . "\n";
    echo "  " . gray("wsl -d drupaltv -u root -- php tsw.phar install") . "\n\n";

    if ($isError) exit(1);
}

// ── Entry point ───────────────────────────────────────────────────────────────
$cmd   = null;
$clean = false;
$ver   = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--clean')   { $clean = true; continue; }
    if ($arg === '--version') { $ver   = true; continue; }
    if (str_starts_with($arg, '--lang=')) continue;
    if ($cmd === null) $cmd = $arg;
}

if ($ver) {
    echo "tsw " . TSW_VERSION . "\n";
    exit(0);
}

if ($cmd === null || $cmd === 'help' || $cmd === '--help' || $cmd === '-h') {
    showHelp();
    exit(0);
}

if (function_exists('posix_getuid') && posix_getuid() !== 0
    && file_exists(priv()) && !is_writable(priv())) {
    echo yellow("\n  ⚠  " . t(
        "src/private/ is owned by www-data — run as root for full access:",
        "src/private/ gehoert www-data — fuer vollstaendige Rechte als root ausfuehren:"
    )) . "\n";
    echo gray("     wsl -d drupaltv -u root -- php tsw.phar $cmd\n\n");
}

switch ($cmd) {
    case 'save':        cmdSave();        break;
    case 'install':     cmdInstall();     break;
    case 'reset':       cmdReset();       break;
    case 'status':      cmdStatus();      break;
    case 'clear-cache': cmdClearCache();  break;
    default:
        echo yellow("  " . t("Unknown command: ","Unbekannter Befehl: ")) . bold($cmd) . "\n";
        echo gray("  " . t("Available commands: ","Verfuegbare Befehle: ") . "save, install, reset, status, help\n\n");
        exit(1);
}

// ════════════════════════════════════════════════════════════════════════════
// SAVE
// ════════════════════════════════════════════════════════════════════════════
function cmdSave(): void {
    hdr(t('Save profile', 'Profil speichern'));

    if (file_exists(cfg())) {
        $db = require cfg();
        act(t('dbconfig.php read', 'dbconfig.php gelesen'));
    } else {
        echo "\n  " . yellow(t("dbconfig.php not found — enter database credentials:","dbconfig.php nicht gefunden — Datenbankdaten eingeben:")) . "\n";
        $db = [
            'type'     => 'mysql',
            'host'     => ask(t('DB host',     'DB-Host'),           '127.0.0.1'),
            'username' => ask(t('DB user',     'DB-Benutzername')),
            'password' => ask(t('DB password', 'DB-Passwort')),
            'database' => ask(t('DB name',     'DB-Name'),            'ts_website'),
            'prefix'   => ask(t('Table prefix','Tabellen-Prefix'),    'tsw_'),
            'port'     => ask(t('DB port',     'DB-Port'),            '3306'),
            'charset'  => 'utf8mb4',
        ];
    }

    act(t('Connecting to database...', 'Datenbankverbindung herstellen...'));
    $pdo    = connectDb($db);
    $prefix = $db['prefix'] ?? 'tsw_';
    ok(t('Connected to ', 'Verbunden mit ') . $db['database'] . '@' . $db['host']);

    $queryKeys = ['query_hostname','query_port','query_username','query_password',
                  'query_displayip','query_nickname','tsserver_port'];
    $query = [];
    try {
        $in   = implode("','", $queryKeys);
        $rows = $pdo->query("SELECT identifier, value FROM {$prefix}config WHERE identifier IN ('$in')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $query[$r['identifier']] = $r['value'];
        if (count($query) > 0) {
            act(t('Query settings read from DB', 'Query-Einstellungen aus DB gelesen'));
        }
    } catch (\Exception $e) {
        inf(t('Query settings not readable: ', 'Query-Einstellungen nicht lesbar: ') . $e->getMessage());
    }

    $defaults = ['query_hostname'=>'127.0.0.1','query_port'=>'10011','query_username'=>'',
                 'query_password'=>'','query_displayip'=>'','query_nickname'=>'TS-website','tsserver_port'=>'9987'];
    foreach ($defaults as $k => $d) {
        if (empty($query[$k])) $query[$k] = ask('TS ' . str_replace(['query_','_'], ['',''], $k), $d);
    }

    $siteKeys = ['baseurl','website_title','nav_brand','timezone','usingcloudflare','admin_cldbids'];
    $site = [];
    try {
        $in   = implode("','", $siteKeys);
        $rows = $pdo->query("SELECT identifier, value FROM {$prefix}config WHERE identifier IN ('$in')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $site[$r['identifier']] = $r['value'];
    } catch (\Exception $e) {}

    $siteD = ['baseurl'=>'http://localhost','website_title'=>$query['query_displayip'] ?? 'TS-Website',
              'nav_brand'=>$query['query_displayip'] ?? 'TS-Website','timezone'=>'Europe/Berlin','usingcloudflare'=>'false', 'admin_cldbids' => '[]'];
    foreach ($siteD as $k => $d) {
        if (empty($site[$k])) $site[$k] = ask("Site $k", $d);
    }

    $profile = ['db' => $db, 'query' => $query, 'site' => $site];
    file_put_contents(profile(), json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    ok(t('Profile saved: ', 'Profil gespeichert: ') . profile());
    inf('DB:    ' . $db['username'] . '@' . $db['host'] . '/' . $db['database']);
    inf('Query: ' . ($query['query_username'] ?? '?') . '@' . ($query['query_hostname'] ?? '?') . ':' . ($query['query_port'] ?? '?'));
    inf('Site:  ' . ($site['baseurl'] ?? '?') . ' | ' . ($site['website_title'] ?? '?'));
}

// ════════════════════════════════════════════════════════════════════════════
// INSTALL
// ════════════════════════════════════════════════════════════════════════════
function cmdInstall(): void {
    hdr(t('Installation from profile', 'Installation aus Profil'));

    $profile = loadProfile();
    $db      = $profile['db'];
    $query   = $profile['query'] ?? [];
    $site    = $profile['site']  ?? [];
    $prefix  = $db['prefix'] ?? 'tsw_';

    act(t('Writing dbconfig.php...', 'dbconfig.php schreiben...'));
    writeDbConfig($db);
    ok(t('dbconfig.php created', 'dbconfig.php erstellt'));

    act(t('Connecting to database...', 'Datenbankverbindung herstellen...'));
    $pdo = connectDb($db);
    ok(t('Connected', 'Verbunden'));

    dropAndRecreate($pdo, $prefix);

    if (!empty($query)) {
        act(t('Writing TeamSpeak connection data...', 'TeamSpeak-Verbindungsdaten schreiben...'));
        foreach ($query as $k => $v) $pdo->prepare("UPDATE {$prefix}config SET value=? WHERE identifier=?")->execute([$v,$k]);
        ok(t('Query settings written', 'Query-Einstellungen geschrieben'));
    }
    if (!empty($site)) {
        act(t('Writing website settings...', 'Website-Einstellungen schreiben...'));
        foreach ($site as $k => $v) $pdo->prepare("UPDATE {$prefix}config SET value=? WHERE identifier=?")->execute([$v,$k]);
        ok(t('Website settings written', 'Website-Einstellungen geschrieben'));
    }

    if (file_exists(autoload())) {
        act(t('Syncing server icons...', 'Server-Icons synchronisieren...'));
        try {
            require_once autoload();
            \Wruczek\TSWebsite\ServerIconCache::syncIcons();
            ok(t('Icons synced', 'Icons synchronisiert'));
        } catch (\Throwable $e) {
            inf(t('Skipped: ', 'Uebersprungen: ') . $e->getMessage());
        }
    }

    act(t('Setting installer lock...', 'Installer-Lock setzen...'));
    if (@file_put_contents(lock(), t('Installed via tsw CLI on ','Installiert via tsw CLI am ') . date('d.m.Y H:i:s')) === false)
        err(t('Could not write INSTALLER_LOCK — run as root', 'Konnte INSTALLER_LOCK nicht schreiben — als root ausfuehren'));
    ok(t('INSTALLER_LOCK set', 'INSTALLER_LOCK gesetzt'));

    clearCacheFiles();

    hdr(t('Installation complete ✓', 'Installation abgeschlossen ✓'));
    inf('→ ' . ($site['baseurl'] ?? 'http://localhost') . '/       ' . t('Website ready', 'Website direkt nutzbar'));
    inf('→ ' . ($site['baseurl'] ?? 'http://localhost') . '/admin/ Admin panel');
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// RESET
// ════════════════════════════════════════════════════════════════════════════
function cmdReset(): void {
    global $clean;
    hdr($clean ? t('Full reset (--clean)', 'Vollstaendiger Reset (--clean)') : t('Reset', 'Zuruecksetzen'));

    $profile = loadProfile();
    $db      = $profile['db'];
    $query   = $profile['query'] ?? [];
    $site    = $profile['site']  ?? [];
    $prefix  = $db['prefix'] ?? 'tsw_';

    act(t('Connecting to database...', 'Datenbankverbindung herstellen...'));
    $pdo = connectDb($db);
    ok(t('Connected', 'Verbunden'));

    dropAndRecreate($pdo, $prefix);

    foreach (array_merge($query, $site) as $k => $v)
        $pdo->prepare("UPDATE {$prefix}config SET value=? WHERE identifier=?")->execute([$v,$k]);
    ok(t('Connection and site settings restored', 'Verbindungs- und Site-Einstellungen wiederhergestellt'));

    act(t('Deleting installer lock...', 'Installer-Lock loeschen...'));
    tryDelete(lock());

    if ($clean) {
        act(t('Deleting dbconfig.php...', 'dbconfig.php loeschen...'));
        tryDelete(cfg());
    } else {
        act(t('Restoring dbconfig.php...', 'dbconfig.php wiederherstellen...'));
        writeDbConfig($db);
        ok(t('dbconfig.php restored', 'dbconfig.php wiederhergestellt'));
    }

    clearCacheFiles();

    hdr(t('Reset complete', 'Reset abgeschlossen'));
    if ($clean) {
        inf(t('→ http://localhost/installer/   Installer from step 1',
              '→ http://localhost/installer/   Installer ab Schritt 1'));
    } else {
        inf(t("→ http://localhost/             Website ready",
              "→ http://localhost/             Website direkt nutzbar"));
        inf(t("→ http://localhost/installer/   Installer from step 1",
              "→ http://localhost/installer/   Installer ab Schritt 1"));
    }
    echo "\n";
}

// ── Status ────────────────────────────────────────────────────────────────────
function cmdStatus(): void {
    hdr(t('Current status', 'Aktueller Status'));

    $chk = function(string $label, bool $ok, string $detail = ''): void {
        $icon  = $ok ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
        $extra = $detail ? "  \033[90m($detail)\033[0m" : '';
        echo "  $icon  $label$extra\n";
    };

    $chk(t('dbconfig.php present',   'dbconfig.php vorhanden'),   file_exists(cfg()));
    $chk(t('INSTALLER_LOCK present', 'INSTALLER_LOCK vorhanden'), file_exists(lock()));

    $pOk = file_exists(profile());
    $chk(t('Reset profile present', 'Reset-Profil vorhanden'), $pOk,
         $pOk ? t('last modified: ','zuletzt: ') . date('d.m.Y H:i', filemtime(profile())) : '');
    if ($pOk) {
        $p = json_decode(file_get_contents(profile()), true);
        inf(t('Sections: ', 'Abschnitte: ') . implode(', ', array_keys($p)));
    }

    if (file_exists(cfg())) {
        try {
            $db     = require cfg();
            $pdo    = connectDb($db);
            $prefix = $db['prefix'] ?? 'tsw_';
            $tables = $pdo->query("SHOW TABLES LIKE '" . str_replace('_','\\_',$prefix) . "%'")->fetchAll(PDO::FETCH_COLUMN);
            $chk(t('DB tables','DB-Tabellen') . ' (' . count($tables) . ')', count($tables) > 0, implode(', ', $tables));
        } catch (\Exception $e) {
            $chk(t('DB connection', 'DB-Verbindung'), false, $e->getMessage());
        }
    } else {
        $chk(t('Check DB tables', 'DB-Tabellen pruefen'), false, t('dbconfig.php missing', 'dbconfig.php fehlt'));
    }
    echo "\n";
}

// ── Clear Cache ───────────────────────────────────────────────────────────────
function cmdClearCache(): void {
    hdr(t('Clear cache', 'Cache leeren'));
    clearCacheFiles();
    ok(t('Cache cleared successfully', 'Cache erfolgreich geleert'));
}

// ── Helper functions ──────────────────────────────────────────────────────────
function loadProfile(): array {
    if (!file_exists(profile()))
        err(t('No profile found — run first: php tsw.phar save', 'Kein Profil — bitte zuerst: php tsw.phar save'));
    return json_decode(file_get_contents(profile()), true);
}

function connectDb(array $db): PDO {
    $GLOBALS['_db'] = $db;
    $dsn = 'mysql:host=' . $db['host'] . ';port=' . ($db['port']??3306) . ';dbname=' . $db['database'] . ';charset=' . ($db['charset']??'utf8mb4');
    try {
        return new PDO($dsn, $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (\PDOException $e) {
        err(t('DB connection failed: ', 'DB-Verbindung fehlgeschlagen: ') . $e->getMessage());
    }
}

function dropAndRecreate(PDO $pdo, string $prefix): void {
    act(t("Dropping tables with prefix '$prefix'...", "Tabellen mit Prefix '$prefix' loeschen..."));
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = $pdo->query("SHOW TABLES LIKE '" . str_replace('_','\\_',$prefix) . "%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) { $pdo->exec("DROP TABLE `$tbl`"); inf(t("Dropped: ","Geloescht: ") . $tbl); }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    ok(count($tables) . t(' tables dropped', ' Tabellen geloescht'));

    $db = $GLOBALS['_db'];
    foreach ([SQL_MAIN, SQL_LANG] as $sqlFile) {
        act(t('Running SQL: ', 'SQL ausfuehren: ') . basename($sqlFile) . '...');
        $sql = file_get_contents($sqlFile);
        if ($sql === false) err(t("SQL file not readable: ","SQL-Datei nicht lesbar: ") . $sqlFile);
        $sql = str_replace('DBPREFIX', $prefix, $sql);
        $mysqli = new \mysqli($db['host'], $db['username'], $db['password'], $db['database'], (int)($db['port']??3306));
        if ($mysqli->connect_error) err('mysqli: ' . $mysqli->connect_error);
        $mysqli->set_charset('utf8mb4');
        if (!$mysqli->multi_query($sql)) err(t('SQL error in ','SQL-Fehler in ') . basename($sqlFile) . ': ' . $mysqli->error);
        do { if ($r = $mysqli->store_result()) $r->free(); } while ($mysqli->next_result());
        $mysqli->close();
        ok(basename($sqlFile) . t(' executed', ' ausgefuehrt'));
    }
}

function writeDbConfig(array $db): void {
    $lines = '';
    foreach ($db as $k => $v) $lines .= sprintf("    '%s' => '%s',\n", addcslashes($k,"'"), addcslashes($v,"'"));
    $code = "<?php\n/* TS-website database config — generated by tsw CLI on " . date('d-m-Y H:i:s') . " */\nreturn [\n" . rtrim($lines,",\n") . "\n];\n";
    if (@file_put_contents(cfg(), $code) === false)
        err(t('Could not write dbconfig.php — run as root', 'Konnte dbconfig.php nicht schreiben — als root ausfuehren'));
}

function clearCacheFiles(): void {
    act(t('Clearing cache...', 'Cache leeren...'));
    $n = 0; $skip = 0;
    foreach (array_merge(glob(cache().'/*.cache.php')??[], glob(cache().'/templates/*.php')?:[]) as $f) {
        @unlink($f) ? $n++ : $skip++;
    }
    if ($skip) inf("$skip " . t('files skipped (permissions)', 'Dateien uebersprungen (Rechte)'));
    ok("$n " . t('cache files deleted', 'Cache-Dateien geloescht'));
}

# ts-website — Claude Context

## Projekt

Fork von [nodedropweb/ts-website](https://github.com/nodedropweb/ts-website) (Branch `2.0`),
ursprünglich von Wruczek. Ziel: PHP 8.4/8.5-Kompatibilität.

WSL-Distro: `drupaltv` | Pfad: `/var/www/ts-website/`  
Apache + PHP 8.4 FPM | MariaDB | VHost: `localhost`  
DocumentRoot: `/var/www/ts-website/src`

---

## Setup (Lokal)

```bash
# Apache VHost ist eingerichtet: /etc/apache2/sites-enabled/ts-website.conf
# PHP 8.4 FPM läuft

# Datenbank
# Host: 127.0.0.1 | DB: ts_website | User: tswebsite | Pass: tswebsite

# Permissions nach Code-Änderungen
wsl -d drupaltv -u root -- bash -c "chown -R www-data:www-data /var/www/ts-website/src/private"

# Cache leeren (nach Template/PHP-Änderungen nötig)
wsl -d drupaltv -u root -- bash -c "rm -f /var/www/ts-website/src/private/cache/*.cache.php /var/www/ts-website/src/private/cache/templates/*.php"
```

---

## Abhängigkeiten

| Paket | Version | Hinweis |
|-------|---------|---------|
| `planetteamspeak/ts3-php-framework` | `^1.3.0` | Breaking: Namespace-Änderung von `TeamSpeak3_*` zu `PlanetTeamSpeak\TeamSpeak3Framework\*` |
| `latte/latte` | `^3.0` | Breaking: `{php}` entfernt, `setTranslator()` → `TranslatorExtension` |
| `catfan/medoo` | `^2.4` | Breaking: Config-Keys umbenannt (`database_type`→`type`, `server`→`host`, `database_name`→`database`) |
| `wruczek/php-file-cache` | `^0` | Abandoned, aber funktioniert noch |

---

## Wichtige Namespace-Mappings (TS3 Framework 1.3.0)

```php
// Alte Klasse                          → Neue Klasse (als Alias)
TeamSpeak3                              → PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3
TeamSpeak3_Exception                    → PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception
TeamSpeak3_Adapter_ServerQuery_Exception→ PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException
TeamSpeak3_Node_Host                    → PlanetTeamSpeak\TeamSpeak3Framework\Node\Host
TeamSpeak3_Node_Server                  → PlanetTeamSpeak\TeamSpeak3Framework\Node\Server
TeamSpeak3_Helper_String                → PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper
TeamSpeak3_Helper_Convert               → PlanetTeamSpeak\TeamSpeak3Framework\Helper\Convert
```

---

## Bekannte Bugs & Fixes die wir angewendet haben

### 1. TS3 Framework — OOM in StringHelper (Vendor-Patch, nicht in Git!)
**Problem:** `split()` nutzte `$this->count()` als explode-Limit → bei Binär-Daten explodierende Array-Allokation.  
**Fix:** `/src/private/vendor/planetteamspeak/ts3-php-framework/src/Helper/StringHelper.php`
```php
// Zeile ~222: split() — vorher:
$parts = explode($separator, $this->string, ($limit) ?: $this->count());
// Nachher:
$parts = $limit > 0 ? explode($separator, $this->string, $limit) : explode($separator, $this->string);
```

### 2. TS3 Framework — toUtf8() OOM
**Problem:** `mb_convert_encoding($str, 'UTF-8', mb_list_encodings())` → 100+ Encodings → OOM.  
**Fix:** `Helper/StringHelper.php` → `toUtf8()`
```php
$detected = mb_detect_encoding($this->string, mb_detect_order(), true);
if ($detected !== false && $detected !== 'UTF-8') {
    $this->string = mb_convert_encoding($this->string, 'UTF-8', $detected);
}
```

### 3. TS3 Framework — Implicitly Nullable Deprecations (Vendor-Patch, nicht in Git!)
**Problem:** Dutzende Methoden in `Uri.php`, `Server.php`, `Host.php` etc. haben `Type $param = null` ohne `?`.  
**Fix:** Skript `/tmp/fix_nullable2.php` anwenden:
```php
// Einfache Typen: Type $x = null → ?Type $x = null
// Union Types: Type|Other $x = null → Type|Other|null $x = null
// mixed $x = null bleibt mixed (mixed schließt null ein!)
```
Nach `composer install` gehen diese Patches verloren — dann erneut anwenden.

### 4. PHP Shutdown → 500-Fehler
**Problem:** TS3-Destruktor schickt `QUIT` nach PHP-Shutdown → OOM in StringHelper → Cache-Korruption → alle folgenden Requests benötigen neue TS3-Verbindung → 12s Wartezeit.  
**Fix:** `load.php` — `register_shutdown_function` der `Transport::disconnect()` aufruft (setzt `$stream = null`, Destruktor überspringt dann QUIT):
```php
register_shutdown_function(function () {
    error_reporting(0);
    try {
        $tsHost = \Wruczek\TSWebsite\Utils\TeamSpeakUtils::i()->getExistingTSNodeHost();
        if ($tsHost !== null) {
            $tsHost->getAdapter()->getTransport()->disconnect();
        }
    } catch (\Throwable $ignored) {}
});
```

### 5. Medoo 2.x — isdefault als integer statt string
**Problem:** `$lang["isdefault"] === "1"` schlägt fehl, Medoo 2 gibt `int` zurück.  
**Fix:** `Utils/Language/LanguageUtils.php` → `(int) $lang["isdefault"] === 1`

### 6. Latte 3 — TranslatorExtension
**Problem:** `$latte->setTranslator()` existiert in Latte 3 nicht mehr.  
**Fix:** `Utils/TemplateUtils.php`
```php
use Latte\Essential\TranslatorExtension;
$this->getLatte()->addExtension(new TranslatorExtension(function ($s, ...$args) {
    return __get((string) $s, $args);
}));
```

---

## Vendor-Patches nach composer install neu anwenden

Nach jedem `composer install` oder `composer update` müssen die Vendor-Patches manuell neu angewendet werden:

```bash
# 1. OOM-Fix in StringHelper
wsl -d drupaltv -- bash -c "php /tmp/patch_stringhelper.php"

# 2. Nullable-Fix für alle Framework-Dateien
wsl -d drupaltv -- bash -c "php /tmp/fix_nullable2.php"

# Permissions danach
wsl -d drupaltv -u root -- bash -c "chown -R www-data:www-data /var/www/ts-website/src/private/vendor"
```

> **TODO:** Langfristig eigenen Fork von `planetteamspeak/ts3-php-framework` anlegen und `composer.json` darauf zeigen lassen, oder `cweagans/composer-patches` einrichten.

---

## Datei-Permissions Workflow

Weil Apache als `www-data` läuft und git-Edits als `drupal`:

```bash
# Vor dem Editieren (damit der Edit-Tool schreiben kann)
wsl -d drupaltv -u root -- bash -c "chown drupal:drupal /var/www/ts-website/src/path/to/file.php"

# Nach dem Editieren (damit Apache lesen kann)
wsl -d drupaltv -u root -- bash -c "chown -R www-data:www-data /var/www/ts-website/src/private/php"
# oder für Templates:
wsl -d drupaltv -u root -- bash -c "chown -R www-data:www-data /var/www/ts-website/src/private/templates"
```

---

## Debugging

```bash
# PHP-Fehler temporär anzeigen
wsl -d drupaltv -u root -- bash -c "sed -i 's/display_errors = Off/display_errors = On/' /etc/php/8.4/fpm/php.ini && systemctl reload php8.4-fpm"

# Danach zurücksetzen!
wsl -d drupaltv -u root -- bash -c "sed -i 's/display_errors = On/display_errors = Off/' /etc/php/8.4/fpm/php.ini && systemctl reload php8.4-fpm"

# Apache Error Log
wsl -d drupaltv -u root -- bash -c "tail -30 /var/log/apache2/error.log"

# PHP FPM Log
wsl -d drupaltv -u root -- bash -c "tail -20 /var/log/php8.4-fpm.log"

# API testen
wsl -d drupaltv -- bash -c "curl -s http://localhost/api/getstatus.php"
```

---

## Offene TODOs

- [ ] Eigenen Fork von `planetteamspeak/ts3-php-framework` mit allen Patches anlegen
- [ ] `composer.json` auf eigenen Fork zeigen lassen (dann keine manuellen Vendor-Patches mehr nötig)
- [ ] Installer-Step für SQLite reaktivieren (aktuell auskommentiert)
- [ ] Admin-Panel fehlt komplett (ist upstream auch noch nicht fertig)
- [ ] PHP 8.5 Kompatibilität testen sobald verfügbar

---

## Versionshinweise

- **TeamSpeak Server 3.13.8** ist die aktuellste verfügbare Version — kein Update nötig/möglich.
- Der Installer prüft auf Build `>= 1564054246` (= TS3 3.10.0) — das ist korrekt und muss nicht angepasst werden.

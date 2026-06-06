#!/usr/bin/env php
<?php
/**
 * Baut tsw.phar aus phar-src/
 * Ausführung: php -d phar.readonly=Off build-phar.php
 */

if (ini_get('phar.readonly')) {
    die("Fehler: phar.readonly ist aktiv.\nAusführen mit: php -d phar.readonly=Off build-phar.php\n");
}

$pharFile = __DIR__ . '/tsw.phar';
$srcDir   = __DIR__ . '/phar-src';

if (file_exists($pharFile)) unlink($pharFile);

echo "Baue tsw.phar...\n";

$phar = new Phar($pharFile, 0, 'tsw.phar');
$phar->startBuffering();

// Dateien hinzufügen
$phar->addFile($srcDir . '/main.php',              'main.php');
$phar->addFile($srcDir . '/sql/dbinstall_mysql.sql',      'sql/dbinstall_mysql.sql');
$phar->addFile($srcDir . '/sql/dbinstall_mysql_lang.sql', 'sql/dbinstall_mysql_lang.sql');

// Stub (Entry Point)
$stub = <<<'STUB'
#!/usr/bin/env php
<?php
Phar::mapPhar('tsw.phar');
require 'phar://tsw.phar/main.php';
__HALT_COMPILER();
STUB;

$phar->setStub($stub);
$phar->stopBuffering();

chmod($pharFile, 0755);

$size = round(filesize($pharFile) / 1024);
echo "✓ tsw.phar erstellt ({$size} KB)\n";
echo "  Enthält: main.php, sql/dbinstall_mysql.sql, sql/dbinstall_mysql_lang.sql\n";
echo "\nNutzung:\n";
echo "  php tsw.phar help\n";
echo "  wsl -d drupaltv -u root -- php /var/www/ts-website/tsw.phar save\n";

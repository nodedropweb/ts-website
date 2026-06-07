<?php
require_once __DIR__ . '/../_auth.php';
requireAdmin();

use Wruczek\TSWebsite\Config;

function redirect(string $msg, string $type = 'success'): void {
    header('Location: ../config.php?flash=' . urlencode($msg) . '&type=' . $type);
    exit;
}

$posted = $_POST['config'] ?? [];
if (!is_array($posted)) {
    redirect('Ungültige Daten.', 'danger');
}

// Load all known config types from DB
$db   = \Wruczek\TSWebsite\Utils\DatabaseUtils::i()->getDb();
$rows = $db->select('config', ['identifier', 'type']);
$types = [];
foreach ($rows as $row) {
    $types[$row['identifier']] = strtolower($row['type']);
}

$errors = [];

foreach ($types as $key => $type) {
    if ($type === 'bool') {
        // Checkboxes are only posted when checked
        $value = isset($posted[$key]) ? 'true' : 'false';
    } else {
        $value = $posted[$key] ?? null;
        if ($value === null) continue; // not in form
        $value = trim($value);
    }

    // Validate JSON
    if ($type === 'json' && $value !== '') {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = "Ungültiges JSON für «$key»: " . json_last_error_msg();
            continue;
        }
    }

    // Validate INT
    if ($type === 'int' && !is_numeric($value)) {
        $errors[] = "«$key» muss eine Zahl sein.";
        continue;
    }

    try {
        Config::i()->setValue($key, $value);
    } catch (\Exception $e) {
        $errors[] = "Fehler beim Speichern von «$key»: " . $e->getMessage();
    }
}

if ($errors) {
    redirect(implode(' | ', $errors), 'danger');
}

redirect('Konfiguration gespeichert.');

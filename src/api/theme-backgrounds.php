<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');

$themes = ['dark', 'light', 'acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
$result = [];

// Try to determine the base path of the installation automatically
// This works even if baseurl is set to something else in the DB
$basePath = str_replace('/api/theme-backgrounds.php', '', $_SERVER['SCRIPT_NAME']);
$basePath = rtrim($basePath, '/');

foreach ($themes as $theme) {
    $file = __DIR__ . '/../img/themes/bg-' . $theme . '.jpg';
    if (file_exists($file)) {
        // Return domain-relative path (e.g. /img/themes/bg-dark.jpg)
        $result[$theme] = $basePath . '/img/themes/bg-' . $theme . '.jpg?v=' . filemtime($file);
    }
}

echo json_encode($result);

<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');

$themes = ['dark', 'light', 'acrylic', 'acrylic-midnight', 'acrylic-ember', 'acrylic-forest'];
$result = [];

foreach ($themes as $theme) {
    $file = __DIR__ . '/../img/themes/bg-' . $theme . '.jpg';
    if (file_exists($file)) {
        $result[$theme] = 'img/themes/bg-' . $theme . '.jpg?v=' . filemtime($file);
    }
}

echo json_encode($result);

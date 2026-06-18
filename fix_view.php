<?php
$file = __DIR__ . '/resources/views/surgeries/show.blade.php';
$lines = file($file);
// Keep lines 0-549 (first 550 lines) and then skip lines 550-708 (old duplicate content),
// then continue from line 709 onwards
$new = array_merge(array_slice($lines, 0, 550), array_slice($lines, 709));
file_put_contents($file, implode('', $new));
echo 'Done. New line count: ' . count($new) . PHP_EOL;

<?php
// Fix: register service\ namespace in composer.json autoloader
$base = '/www/wwwroot/www.hbdxm.com';
$file = $base . '/composer.json';
$j = json_decode(file_get_contents($file), true);
$j['autoload']['psr-4']['service\\'] = 'extend/service/';
file_put_contents($file, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "composer.json updated OK\n";

// Run dump-autoload
chdir($base);
passthru('composer dump-autoload -o 2>&1', $rc);
echo "\nExit code: $rc\n";

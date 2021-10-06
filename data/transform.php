<?php

$constellations = include(__DIR__.'/../data/constellations.php');

foreach ($constellations as $constellation_name => $stars) {
    $constellations[$constellation_name] = [
        'path' => [],
        'stars' => $constellations[$constellation_name]
    ];
}

file_put_contents(__DIR__.'/../var/constellations.php', '<?php return ' . var_export($constellations, true));

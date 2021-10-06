<?php

$SOL_MAG = -26;
$STAR_SIZE_MAG_FACTOR = 0.05;
$w = 6*360;
$wh = $w*0.5;
$whh = $w*0.25;

$img = imagecreatetruecolor($w, 2 * $w);
$black = imagecolorallocate($img, 0, 0, 0);
$white = imagecolorallocate($img, 255, 255, 255);
$red = imagecolorallocate($img, 255, 0, 0);
$green = imagecolorallocate($img, 0, 255, 0);
$blue = imagecolorallocate($img, 0, 0, 255);

imagefilledrectangle($img, 0, 0, $w, $w*2, $white);

imagefilledarc($img, $wh, $wh, $w, $w, 0, 360, $black, IMG_ARC_PIE);
imagearc($img, $wh, $wh, $whh, $whh, 0, 360, $white);
imagearc($img, $wh, $wh, $wh, $wh, 0, 360, $blue);
imagearc($img, $wh, $wh, $wh + $whh, $wh + $whh, 0, 360, $white);
imageline($img, 0, $wh, $w, $wh, $red);

imagefilledarc($img, $wh, $w + $wh, $w, $w, 0, 360, $black, IMG_ARC_PIE);

imagearc($img, $wh, $w + $wh, $whh, $whh, 0, 360, $white);
imagearc($img, $wh, $w + $wh, $wh, $wh, 0, 360, $blue);
imagearc($img, $wh, $w + $wh, $wh + $whh, $wh + $whh, 0, 360, $white);

imageline($img, 0, $w + $wh, $w,  $w + $wh, $red);

imageline($img, $wh, 0, $wh, 2*$w, $red);

$constellations = include(__DIR__.'/../data/constellations.php');

foreach ($constellations as $constellation_name => $constellation) {
    $north = false;
    $pos = [];
    $nb_stars = count($constellation['stars']);
    for ($j = 0 ; $j < $nb_stars ; $j++) {
        $is_southern_hemi = $constellation['stars'][$j][3] < 0;
        $hemif = $is_southern_hemi ? -1 : 1;
        $hemifi = $is_southern_hemi ? 1 : -1;
        $hemi = $is_southern_hemi ? $w : 0;
        $h = (90 + $hemifi * $constellation['stars'][$j][3]) / 90;


        $x = $wh + $wh * cos($hemifi * deg2rad($constellation['stars'][$j][2])) * $h;
        $y = $hemi + $wh - $wh * sin($hemifi * deg2rad($constellation['stars'][$j][2])) * $h;
        $pos[] = [$x, $y, $constellation['stars'][$j][4]];

        //imageline($img, $wh, $wh, $x, $y, $white);
        $north = true;
    }
    //if($north) break;

    foreach ($constellation['path'] as $path) {
        for ($k = 1 ; $k < count($path) ; $k++) {
            $pos[$path[$k - 1]][3] = true;
            $pos[$path[$k]][3] = true;

            if (ceil($pos[$path[$k - 1]][1] / $w) != ceil($pos[$path[$k]][1] / $w)) {
                continue;
            }
            imageline($img, $pos[$path[$k - 1]][0], $pos[$path[$k - 1]][1], $pos[$path[$k]][0], $pos[$path[$k]][1], $green);
        }
    }

    for ($j = 0 ; $j < $nb_stars ; $j++) {
        //imagesetpixel($img, $pos[$j][0], $pos[$j][1], isset($pos[$j][3]) ? $red : $white);
        if ($pos[$j][2] < 0) {
            $r = 25;
        } elseif($pos[$j][2] < 1) {
            $r = 22.5;
        } elseif($pos[$j][2] < 2) {
            $r = 20;
        } elseif($pos[$j][2] < 3) {
            $r = 12.5;
        } elseif($pos[$j][2] < 4) {
            $r = 6.25;
        } elseif($pos[$j][2] < 5) {
            $r = 3.125;
        } else {
            $r = 1.5625;
        }

        var_dump($pos[$j][2] . ' => ' . $r);
        imagefilledarc($img, $pos[$j][0], $pos[$j][1], $r, $r, 0, 360, isset($pos[$j][3]) ? $red : $white, IMG_ARC_PIE);
    }
}

imagepng($img, __DIR__ . '/../var/constellations.png');
